# Div PHP Ways 1.0
A "way" is different to "path". We need a path for found 
a specific resource, but we need a way for do something. 
This library follow this concept when implements the 
routing and control of PHP application.

## Basic usage
```php
<?php

// arbitrary location for software's packages
define('PACKAGES', '../app/');

// include the library
include "../../divWays.php";

// ways with closure
divWays::listen("get://home", function($data){
	echo "Hello world";
});

// listen... 
$data = divWays::bootstrap('_url', 'home');
```
See the example.

