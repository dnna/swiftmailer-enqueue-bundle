<?php

namespace Dnna\SwiftmailerEnqueueBundle\SwiftMailer;

use Interop\Queue\ExceptionInterface as PsrException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;
use Enqueue\Consumption\Context as ConsumptionContext;

class QueueSpool extends \Swift_ConfigurableSpool
{
    /**
     * @var PsrContext
     */
    private $context;
    /**
     * @var PsrQueue
     */
    private $queue;
    /**
     * @var $receiveTimeout
     */
    private $receiveTimeout;
    /**
     * @var $extensions
     */
    private $extensions;
    /**
     * @var $logger
     */
    private $logger;
    /**
     * @var $requeueOnException
     */
    private $requeueOnException;
    /**
     * @var $maxRequeueAttempts
     */
    private $maxRequeueAttempts;

    /**
     * @param PsrContext $context
     * @param PsrQueue|string $queue
     * @param $receiveTimeout
     * @param $extensions
     * @param $logger
     * @param $requeueOnException
     * @param $maxRequeueAttempts
     */
    public function __construct(
        PsrContext $context,
        $queue,
        $receiveTimeout,
        $extensions,
        $logger,
        $requeueOnException,
        $maxRequeueAttempts
    ) {
        $this->context = $context;
        if (false == $queue instanceof PsrQueue) {
            $queue = $this->context->createQueue($queue);
        }
        $this->queue = $queue;
        $this->receiveTimeout = $receiveTimeout;
        $this->extensions = $extensions;
        $this->logger = $logger;
        $this->requeueOnException = $requeueOnException;
        $this->maxRequeueAttempts = $maxRequeueAttempts;
    }

    /**
     * {@inheritdoc}
     * @throws \Swift_IoException
     */
    public function queueMessage(\Swift_Mime_SimpleMessage $message)
    {
        try {
            $message = $this->context->createMessage(serialize($message));
            $this->context->createProducer()->send($this->queue, $message);
        } catch (PsrException $e) {
            throw new \Swift_IoException(sprintf('Unable to send message to message queue.'), null, $e);
        }
    }

    /**
     * @param \Swift_Transport $transport
     * @param null $failedRecipients
     * @return int
     * @throws \Interop\Queue\Exception
     * @throws \Interop\Queue\InvalidDestinationException
     * @throws \Interop\Queue\InvalidMessageException
     */
    public function flushQueue(\Swift_Transport $transport, &$failedRecipients = null)
    {
        $consumer = $this->context->createConsumer($this->queue);
        $isTransportStarted = false;
        $failedRecipients = (array)$failedRecipients;
        $count = 0;
        $time = time();
        $consumptionContext = new ConsumptionContext($this->context);
        $consumptionContext->setLogger($this->logger);
        $this->triggerExtensionHook($consumptionContext, 'onStart');
        while (true) {
            $this->triggerExtensionHook($consumptionContext, 'onBeforeReceive');
            if ($psrMessage = $consumer->receive($this->receiveTimeout)) {
                try {
                    if (false == $isTransportStarted) {
                        $transport->start();
                        $isTransportStarted = true;
                    }
                    $message = unserialize($psrMessage->getBody());
                    try {
                        $count += $transport->send($message, $failedRecipients);
                    } catch (\Throwable $te) {
                        // Retry once in case we encountered the infamous:
                        // Expected response code 250 but got code "421", with message
                        // "421 Timeout waiting for data from client."
                        $transport->stop();
                        $transport->start();
                        $count += $transport->send($message, $failedRecipients);
                    }
                } catch (\Exception $e) {
                    // Requeue the email
                    $this->handleException($e, $psrMessage);
                    $consumer->reject($psrMessage);

                    return $count;
                }
                $consumer->acknowledge($psrMessage);
            }
            if ($this->reachedExitCondition($count, $time, $consumptionContext)) {
                break;
            }
        }

        return $count;
    }

    private function reachedExitCondition($count, $time, $consumptionContext)
    {
        if ($this->getMessageLimit() && $count >= $this->getMessageLimit()) {
            $this->logger->debug('Exiting because we reached the message limit');

            return true;
        }
        if ($this->getTimeLimit() && (time() - $time) >= $this->getTimeLimit()) {
            $this->logger->debug('Exiting because we reached the time limit');

            return true;
        }
        if ($consumptionContext->isExecutionInterrupted()) {
            $this->logger->debug('Exiting because we received an interrupt');

            return true;
        }

        return false;
    }

    /**
     * @param \Exception $e
     * @param PsrMessage $psrMessage
     * @throws \Interop\Queue\Exception
     * @throws \Interop\Queue\InvalidDestinationException
     * @throws \Interop\Queue\InvalidMessageException
     * @throws \Exception
     */
    private function handleException(\Exception $e, PsrMessage $psrMessage)
    {
        if ($this->requeueOnException) {
            $attempt = $psrMessage->getProperty('requeue_attempt', 1);
            $psrMessage->setRedelivered(true);
            $psrMessage->setProperty('requeue_attempt', ++$attempt);
            if ($attempt < $this->maxRequeueAttempts) {
                $this->context->createProducer()->send($this->queue, $psrMessage);
                $this->logger->error(
                    'Requeued message for attempt #' . $attempt . '. Exception was: ' . $e->getMessage()
                );
            } else {
                $this->logger->error('Will not requeue message because we reached max attempts (#' . $attempt . ')');
                throw $e; // Make sure the current worker dies so it doesn't try to reprocess the same message
            }
        }
    }

    private function triggerExtensionHook(ConsumptionContext $context, $hook)
    {
        foreach ($this->extensions as $curExtension) {
            $curExtension->$hook($context);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return true;
    }
}
