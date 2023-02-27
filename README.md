# AMI Listener

## Requires 
PHP 5.4

## Installation

```
composer require rayansys/AMIListener
```

## Basic Usage

```php
<?php
use AMIListener\AMIListener;

require_once __DIR__ . '/vendor/autoload.php';

$ami = new AMIListener("username","secret","127.0.0.1",5038);
$ami->addListener(function($parameter){
    print_r($parameter);
});
$ami->start();
```

## Available commands
```php start.php start  ```  
```php start.php start -d  ```  
```php start.php status  ```  
```php start.php status -d  ```  
```php start.php connections```  
```php start.php stop  ```  
```php start.php stop -g  ```  
```php start.php restart  ```  
```php start.php reload  ```  
```php start.php reload -g  ```