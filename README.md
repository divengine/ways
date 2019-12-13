# Div PHP Ways 2.4.0

A "way" is different to a "route". We need a path for found 
a specific resource, but we need a way for do something. 
This library follow this concept when implements the 
routing and control of PHP application.

Ways is a class that adapts the concept of SOA to the architecture 
of a PHP application, and tries to integrate the parts of a hybrid 
system. 

With Ways you should think more about "control points" 
than on controllers of an MVC pattern. Control points are 
activated when they are needed, ie on demand, depending on 
the definition you have made.

```php
<?php

ways::listen('sql://...', function($data, $args){
    
	ways::listen('sql://query', function($data){
	  $pdo = new PDO();
	  $st = $pdo->prepare($data['query']);
	  $st->execute($data['params']);
	  $data['result'] = $st->fetchAll(PDO::FETCH_OBJ);
	  return $data;
	});
	
});

ways::invoke('sql://query', [
    'query' => 'SELECT * FROM cats WHERE name = ?',
    'params' => ['Tom']
]);

```


On other platforms it is common to define all routes to 
the drivers in the same file and once. In Ways this 
is not an obligation. You can have an initial control 
point and depending on the URI invoked go to another 
control point X where routes are defined, so that the 
path is formed on demand, thus improving performance 
of its application. The structure of a URI may suggest 
that Ways allows a hierarchical structure of control points, 
but it does not, it can create a whole graph structure.

A first URI is invoked, from HTTP or CLI. But inside your code
you can invoke any URIs

![div ways MVC](https://github.com/divengine/resources/raw/master/div-ways/cards/div-ways-mvc-sample.png)

In addition to this, a control point may require the 
previous execution of another control point. You can also 
implement events or hooks, so you can execute one control 
point before or after another, without the latter knowing 
the existence of the first. These flexibilities are valid 
for example in a plugins architecture.

The control points can interact, and this means, redirect 
the flow to another, call control points directly, exchange 
data and url arguments, handle the output on screen, etc.

In addition, you can establish rules for the execution of 
control points.

```php
<?php

ways::rule('is-monday', function(){
    return date('D') === 'Mon';
});

ways::listen('*', function (){
    echo 'Today is Monday !!!';
}, [ways::PROPERTY_RULES => ['is-monday']]);

```

Ways is not only intended for the web but also for 
command line applications. Ways is implemented in a 
single class, in a single file. This allows quick start-up
and easy adaptation with other platforms.

## Documentation
https://github.com/divengine/ways/wiki

## Installation

With composer...
```
composer require divengine/ways
```

Without composer, download the class and...

```php
include "path/to/divengine/ways.php";
```

## Basic usage
```php
<?php

// arbitrary location for software's packages
define('PACKAGES', 'path/to/app/');

use divengine\ways;

// ways with closure
ways::listen("get://home", function($data){
	echo "Hello {$data['user']}";
}, "home");

// add a hook
ways::hook(DIV_WAYS_BEFORE_RUN, "home", function($data){
	$data['user'] = "Peter";
});

// listen... 
$data = ways::bootstrap('_url', 'home');
```

## Call a static method

**app/control/Home.php**
```php
<?php

#id = home
#listen = /home

class Home {
	
	static function Run()
	{
	    echo "Hello world";
	}
		
	static function About()
	{
		echo "About us";
	}
	
	#listen@Contact = get://about
	static function Contact()
	{
		echo "Contact us";
	}
}
```

**index.php**
```php
<?php

// register a controller with the default static method ::Run()
ways::register("app/control/Home.php");

// route to another static method ([controllerID]@[method])
ways::listen("/about", "home@About");

// route to closure
ways::listen("/sayMeHello/{name}", function($data, $args) {
	echo "Hello {$args['name']}";	
});

// hook on the fly
ways::hook(DIV_WAYS_BEFORE_RUN, 
	ways::listen("/tests/...", function(){
		
		ways::listen("/tests/1", function(){
			echo "This is the test 1";
		}); 	
		
		ways::listen("/tests/2", function(){
			echo "This is the test 2";
		});
		
		if (ways::match("/tests/3")) {
			echo "This is the test 3";
		}
		
		ways::bootstrap();
	}), 
	function(){
		if (!isset($_SESSION['user']))
		{
			echo "You are not a tester";
			return false;
		}
		return true;
	});

// route to a static method
ways::bootstrap("_url", "home");
```

**.htaccess**
```apacheconfig
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^((?s).*)$ index.php?_url=/$1 [QSA,L]
```
# CLI app

```php
<?php

// say me hello
// $ php one_script.php hello Peter
ways::listen("/hello/{name}", function ($data = [], $args = []) {
	echo "Hello {$args['name']}\n";
});
```

# Get controller properties

```php
<?php

$property = "This is a property value";

ways::listen("/", function ($data = [], $args = [], $properties = []) {
	echo "Controller ID = " . $properties['id'] . "\n";
	echo "A controller property = " . $properties['myProperty'];

}, [
	'myProperty' => $property,
]);

```

Enjoy!

-- 

@rafageist

Eng. Rafa Rodriguez

https://rafageist.github.io