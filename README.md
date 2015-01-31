notorm-tracy-panel
==================

NotORM Panel for Tracy for debugging

Instalation
-----------

## Fastest use with all comforts

```php
NotOrmTracyPanel::simpleInit($notorm, $pdo);
```

or just

```php
NotOrmTracyPanel::simpleInit($notorm);
```

## Basic usage

```php
$panel = NotOrmTracyPanel::getInstance(); // or new NotOrmTracyPanel()
\Tracy\Debugger::getBar()->addPanel($panel);

$notorm->debug = function($query, $parameters) {
	NotOrmTracyPanel::getInstance()->logQuery($query, $parameters);
};
```
	
## Using with time measurement

```php
$panel = NotOrmTracyPanel::getInstance(); // or new NotOrmTracyPanel()
\Tracy\Debugger::getBar()->addPanel($panel);

$notorm->debug = function($query, $parameters) {
	$instance = NotOrmTracyPanel::getInstance();
	$instance->logQuery($query, $parameters);
    $instance->startQueryTimer($instance->getIndex());
};

$notorm->debugTimer = function () {
	$instance = NotOrmTracyPanel::getInstance();
    $instance->stopQueryTimer($instance->getIndex());
};
```
	
## You can set driver info

```php
$panel->setPlatform($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
```
	
## You can use SQL Explain utility, if you set NotORM or PDO connection

```php
$panel->setNotOrm($notorm);
```
	
or

```php
$panel->setPdo($pdo);
```

Result?
-------

![Panel](http://zemistr.github.io/notorm-tracy-panel/images/preview.png)


Changelog
---------
v1.0.0 (2015-01-13)
- initial release


-----

(c) Martin Zeman (Zemistr), 2015 (http://zemistr.eu)
