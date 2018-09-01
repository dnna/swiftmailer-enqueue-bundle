<?php

namespace Dnna\SwiftmailerEnqueueBundle\SwiftMailer;

use Interop\Queue\ExceptionInterface as PsrException;
use Interop\Queue\PsrContext;
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
     * @param PsrContext $context
     * @param PsrQueue|string $queue
     * @param $receiveTimeout
     * @param $extensions
     * @param $logger
     */
    public function __construct(PsrContext $context, $queue, $receiveTimeout, $extensions, $logger)
    {
        $this->context = $context;
        if (false == $queue instanceof PsrQueue) {
            $queue = $this->context->createQueue($queue);
        }
        $this->queue = $queue;
        $this->receiveTimeout = $receiveTimeout;
        $this->extensions = $extensions;
        $this->logger = $logger;
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
     * {@inheritdoc}
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
                if (false == $isTransportStarted) {
                    $transport->start();
                    $isTransportStarted = true;
                }
                $message = unserialize($psrMessage->getBody());
                $count += $transport->send($message, $failedRecipients);
                $consumer->acknowledge($psrMessage);
            }
            if ($this->getMessageLimit() && $count >= $this->getMessageLimit()) {
                $this->logger->debug('Exiting because we reached the message limit');
                break;
            }
            if ($this->getTimeLimit() && (time() - $time) >= $this->getTimeLimit()) {
                $this->logger->debug('Exiting because we reached the time limit');
                break;
            }
            if ($consumptionContext->isExecutionInterrupted()) {
                $this->logger->debug('Exiting because we received an interrupt');
                break;
            }
        }

        return $count;
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