# Div PHP Ways 1.4

A "way" is different to a "route". We need a path for found 
a specific resource, but we need a way for do something. 
This library follow this concept when implements the 
routing and control of PHP application.

With Div Ways you should think more about "control points" 
than on controllers of an MVC pattern. Control points are 
activated when they are needed, ie on demand, depending on 
the definition you have made.

On other platforms it is common to define all routes to 
the drivers in the same file and once. In Div Ways this 
is not an obligation. As you can see in the examples, 
you can have an initial control point and depending on 
the URL go to another control point X where routes are 
defined, so that the path is formed on demand, thus 
improving performance of its application. The structure 
of a URL may suggest that Div Ways allows a hierarchical 
structure of contorl points, but it does not, it can 
create a whole graph structure.

In addition to this, a control point may require the 
previous execution of another control point. You can also 
implement events or hooks, so you can execute one control 
point before or after another, without the latter knowing 
the existence of the first. These flexibilities are valid 
for example in a plugins architecture.

The control points can interact, and this means, redirect 
the flow to another, call control points directly, exchange 
data and url arguments, handle the output on screen, etc.

Div Ways is not only intended for the web but also for 
command line applications. Div Ways is implemented in a 
single class, in a single file. This allows quick start-up
and easy adaptation with other platforms.
## Installation

With composer...
```
composer require divengine/div-ways@dev
```

Without composer, donwload the class and...
```
include "divWays.php";
```

## Basic usage
```php
<?php

// arbitrary location for software's packages
define('PACKAGES', 'path/to/app/');

// include the library
include "path/to/divWays.php";

// ways with closure
divWays::listen("get://home", function($data){
	echo "Hello {$data['user']}";
}, "home");

// add a hook
divWays::hook(DIV_WAYS_BEFORE_RUN, "home", function($data){
	$data['user'] = "Peter";
});

// listen... 
$data = divWays::bootstrap('_url', 'home');
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

include "divWays.php"; 

// register a controller with the default static method ::Run()
divWays::register("app/control/Home.php");

// route to another static method ([controllerID]@[method])
divWays::listen("/about", "home@About");

// route to closure
divWays::listen("/sayMeHello/{name}", function($data, $args) {
	echo "Hello {$args['name']}";	
});

// hook on the fly
divWays::hook(DIV_WAYS_BEFORE_RUN, 
	divWays::listen("/tests/...", function(){
		
		divWays::listen("/tests/1", function(){
			echo "This is the test 1";
		}); 	
		
		divWays::listen("/tests/2", function(){
			echo "This is the test 2";
		});
		
		if (divWays::match("/tests/3")) {
			echo "This is the test 3";
		}
		
		divWays::bootstrap();
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
divWays::bootstrap("_url", "home");
```

**.htaccess**
```apacheconfig
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^((?s).*)$ index.php?_url=/$1 [QSA,L]
```

Note: See the example.

# CLI app

```php
<?php

// say me hello
// $ php one_script.php hello Peter
divWays::listen("/hello/{name}", function ($data = [], $args = []) {
	echo "Hello {$args['name']}\n";
});
```

# Get controller properties

```php
<?php

$property = "This is a property value";

divWays::listen("/", function ($data = [], $args = [], $properties = []) {
	echo "Controller ID = " . $properties['id'] . "\n";
	echo "A controller property = " . $properties['myProperty'];

}, [
	'myProperty' => $property,
]);

```
