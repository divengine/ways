Jul 3, 2019
-------------------
- Structural changes: `divengine` namespace and `ways` classname

Jun 24, 2019
-------------------
- IMPORTAN bugfix! As in divWays, as you are executing, more control points can be loaded depending
on the flow that you have designed for the user's actions, since the list of control points is possibly
a "list that is dynamically increasing".

Solution: Fix `divWays::callAll()` method adding & before item foreach

```php
foreach (self::$__listen as $pattern => &$methods) { ... }
```

Now the expected behavior must happen

- New method `divWays::invoke($way, $data)`

```php
<?php
$entry = divWays::invoke('model://entries/1', [
	'filter' => 'monkey island' 
]);
```

Jun 19, 2019
-------------------
- new constant for default GET variable of way information: DIV_WAYS_DEFAULT_WAY_VAR
- more flexibility of ::match ::getCurrentWay
- new methods for ::setDefaultWay and ::setWayVar
- listen ways from CLI !!
- hook on the fly! ::listen() return the ID of closure
- release 1.3 version

May 17, 2019
-------------------
- fix merge of hook data resulting

Oct 18, 2017
-------------------
- Use $_SERVER['REQUEST_URI'] if any way is detected via GET vars. This
improvement allow the use of built-in PHP web server in development environment.

_index.php_
  
```php
<?php
include "divWays.php"

divWays::listen("/my/way", function($data, $args) {
	echo "Hello world";
});

divWays::bootstrap('_url'); // $_GET['_url'] is missing, then assume $_GET['_url'] = $_SERVER['REQUEST_URI']
```

_cli_
```
$ php -S localhost:9090
```

_web-browser_
```
http://localhost:9090/my/way
```

Oct 15, 2017
-------------------
- allow listen by method in controller registration

_Blog.php_
```php
<?php

#id = blog
#listen = /feed
#listen@Entry = /blog/{entry}
#listen@Post = post://blog/post

class Blog {

	static function Run($data = [], $args = [])
	{
		echo "This is the feed";
		return $data;
	}

	static function Entry($data = [], $args = [])
	{
		echo "This is the entry {$args['entry']}";
		return $data;
	}

	static function Post($data = [], $args = [])
	{
		echo "Posting a blog entry ...";
		return $data;
	}
}
```

_bootstrap.php_
```php
<?php

include "divWays.php";
divWays::register("control/Blog.php");
```

Oct 14, 2017
-------------------
- New method divWays::redirect($way)

Oct 11, 2017
-------------------
- important bugfix when normalize the ways

Sep 20, 2017
-------------------
- fix match method and more complexity

```php
<?php

// documentation/chapter-1
divWays::listen("documentation/chapter-{id|is_int}", function($data, $args){
	echo "Chapter #{$args['id']}";
});

 ```
- better control of the ways (executing single instance of each controller)
- improve example
- release 1.2

Sep 18, 2017
-------------------
- better match method
- argument checker

```php
<?php
    divWays::listen("blog/{id:is_int}", function($data, $args){
        echo "{$args['id']} is integer";
    });
```

- improve example
- drop the obsolete bulkRegister

Sep 17, 2017
-------------------
- improve/fix match() method
- capture URL arguments

```php
<?php

divWays::listen("blog/{id}", function($data, $args){
	echo "Entry #{$args['id']}";
});
```

- release 1.1 version