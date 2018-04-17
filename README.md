# Pulchritudinous Queue - Queue Labour Manager

For asynchronously job scheduling and management.

## License

Pulchritudinous Queue is licensed under the MIT License - see the [LICENSE](LICENSE) file for details

## Requirements

* PHP 5.4.0 or above.
* A processes controller (like [Supervisord](http://supervisord.org/))

## Features
* No dependency on ordinary Magento cron job.
* Prevents parallel queue execution.
* Support simultaneous worker execution (default 2).
* Supports multiple methods of execution.
    - Run any worker of the same type simultaneously.
    - Waits for any running worker of the same type to finish.
    - Batches workers of the same type to be run simultaneously.
* Worker error management and rescheduling.
* Recurring worker execution.

## Server Configuration

The queue.php shell file is running non-daemon mode and is supposed to be running as a service with help of a processes controller like [Supervisor](http://supervisord.org/).

## Usage

### Create a new worker

You can read more about different configurations in the `worker.xml.sample` file inside this modules `etc` folder.

Create a `worker.xml` file inside a modules `etc` folder
```xml
<config>
    <pulchqueue>
        <worker>
            <my_unqiue_worker_name>
                <rule>wait</rule>
                <priority>100</priority>
                <class>myspace/worker_export_order</class>
            </my_unqiue_worker_name>
        </worker>
    </pulchqueue>
</config>
```

Create a model for the class you configured above.

```php
<?php
class My_Space_Model_Worker_Export_Order
    extends Pulchritudinous_Queue_Model_Worker_Abstract
{
    public function execute()
    {
        // Something that does something
    }
}
```

### Schedule a worker

To schedule a worker you only need to do this.

```php
<?php
$order = Mage::getModel('sales/order')->load(1);

Mage::getSingleton('pulchqueue/queue')->add(
    'my_unqiue_worker_name',
    $order->getData(),
    ['identity' => $order->getId(), 'deplay' => 100]
)
```

### Example Supervisord Configuration

This is a example of how to configure Supervisord to work with this queue.

```shell
[program:queue]
command = /usr/bin/php /var/www/shell/queue.php
autostart=true
autorestart=true
user = www-data
priority=20
```
