notorm-tracy-panel
==================

NotORM Panel for Tracy for debugging

Instalation
-----------

## Fastest use with all comforts

```php
NotOrmTracyPanel::simpleInit($notorm, $pdo);
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
