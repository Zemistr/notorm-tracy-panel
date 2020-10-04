NotORM Tracy panel
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
v2.0.0 (2020-10-04)
- Big thanks to @janbarasek! Amazing job! :hearth:
- Big package upgrade - PHPStan, PHP 7.1+, Nette 3, Tests and so on!

v1.0.1 (2017-01-30)
- Add new versions of packages

v1.0.0 (2015-01-13)
- initial release
-----

(c) Martin Zeman (Zemistr), 2020 (http://zemistr.eu)
