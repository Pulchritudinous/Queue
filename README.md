Pulchritudinous Queue - Queue Labour Manager
========================================

For asynchronously job scheduling and management.

Requirements
------------

PHP 5.4.0 or above.

License
------------

Pulchritudinous Queue is licensed under the MIT License - see the [LICENSE](LICENSE) file for details

Server Configuration
------------
The queue.php shell file is running non-daemon mode and is ment to be running as a service with help of a processes controller like [Supervisor](http://supervisord.org/).

#### Example Supervisord Configuration
```shell
[program:queue]
command = /usr/bin/php /var/www/shell/queue.php
autostart=true
autorestart=true
user = www-data
priority=20
```

