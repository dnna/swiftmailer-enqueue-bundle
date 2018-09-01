# swiftmailer-enqueue-bundle
This is a symfony bundle that allows using an [Enqueue](https://github.com/php-enqueue/enqueue-bundle) message queue to spool and consume messages.

Basically an implementation of https://blog.forma-pro.com/spool-swiftmailer-emails-to-real-message-queue-9ecb8b53b5de with extra features like graceful shutdowns and other customization options.

Installation
============

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require dnna/swiftmailer-enqueue-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require dnna/swiftmailer-enqueue-bundle
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Enqueue\Bundle\EnqueueBundle(),
            new Dnna\SwiftmailerEnqueueBundle\SwiftmailerEnqueueBundle(),
        );

        // ...
    }

    // ...
}
```

## Usage ##

Below is the configuration reference for this bundle:

``` yaml
dnna_swiftmailer_enqueue:
    queue:
        service_id: enqueue.transport.context
        key: swiftmailer_spool
    consumption:
        receive_timeout: 1000
    extensions:
        signal_extension: true
```

All parameters are optional and if not set will use the default values.

**Warning**: Installing this bundle changes `swiftmailer:spool:send` into a blocking command.
This means it will not exit until the time or message limit specified has been reached.
If no limits are specified the command will never exit.
