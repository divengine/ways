# Div PHP Ways 1.2
A "way" is different to "path". We need a path for found 
a specific resource, but we need a way for do something. 
This library follow this concept when implements the 
routing and control of PHP application.

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
	
	static function Run($data)
	{
	    echo "Hello world";
	}
	
	static function About($data)
	{
		echo "About us";
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
See the example.

