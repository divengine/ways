---
icon: FabReadme
---
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

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^((?s).*)$ index.php?_url=/$1 [QSA,L]
```