# swiftmailer-enqueue-bundle
Symfony bundle that allows using an [Enqueue](https://github.com/php-enqueue/enqueue-bundle) message queue to spool and consume messages.

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