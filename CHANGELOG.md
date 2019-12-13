Dec 12, 2019
-------------------
- `new`: passing controller's properties to rules

```php
<?php
ways::rule('is-admin-section', function($data, $args, $props){
	var_dump($props);
	return $args['section'] === 'admin';
});
```
- `release`: version 2.4.1

Dec 12, 2019
-------------------
- `new`: passing data and args to rules

```php
<?php

ways::rule('is-admin-section', function($data, $args){
    return $args['section'] === 'admin';
});

ways::listen("/{section}/login", function(){
    echo "admin login";
}, ['rules' => ['is-admin-section']]);

```
- `release`: version 2.4.0

Aug 24, 2019
-------------------
- `fix`: rules for default action Run
- `release`: version 2.3.4

Jul 23, 2019
-------------------
- `fix`: fix flow of the data before bootstrap and invoke
- `release`: version 2.3.3

Jul 23, 2019
-------------------
- `fix`: bug fix in rules
- `release`: version 2.3.2

Jul 18, 2019
-------------------
- `fix`. Fix lost data resulting from "file control points"
- `fix`. Other fixes for nested calls of control points
- `fix`. Other minor fixes
- `release`. Urgent release 2.3.1!

Jul 17, 2019
-------------------
- `fix`: fix nested calls of control points
- `fix`: check if exists methods, before execute an action
- `improvement`: The * pattern is now for all protocols.

```php
<?php

ways::listen("*", function($data){
    $data['track'] = 1;
    return $data;
});

ways::listen("app://config", function(){
    $data['config'] = [1,2,3];
    return $data;
});

$config = ways::invoke("app://config");
echo json_encode($config); // {"track":1,"config":[1,2,3]}
```
 
- `improvement`: Independent "ways" for invocations: The execution of the PHP script 
(CLI or HTTP) have a main "request id" or "thread id", that is named **WAY ID**. 
Then each ways::invoke() have their own way id. 

```php
<?php

ways::listen("app://config", function(){
    $data['config'] = [1,2,3];
    return $data;
});

$config = ways::invoke("app://config");
echo json_encode($config); // {"track":1,"config":[1,2,3]}

// the second call did not work in 2.2.0 version 
$config = ways::invoke("app://config"); 
echo json_encode($config); // {"track":1,"config":[1,2,3]}
```

- Release 2.3.0 version

Jul 8, 2019
-------------------
- Support namespaces! Now `divengine\ways` detect namespace instruction.

```php
<?php

namespace MyApp;

#listen = /
class MyController {
	static function Run() {
	    echo "Hello universe";
	}
}
```
Jul 6, 2019
-------------------
- Adding rules!

```php

ways::rule('some-rule', function() {return true or false;});

ways::listen("/secret", function() {...}, [
	ways::PROPERTY_RULES => [
		'some-rule',
		function () {
			return true; // another rule
		}
	] 
]);

```
Jul 3, 2019
-------------------
- Structural changes: `divengine` namespace and `ways` classname

Jun 24, 2019
-------------------
- IMPORTAN bugfix! As in divengine\ways, as you are executing, more control points can be loaded depending
on the flow that you have designed for the user's actions, since the list of control points is possibly
a "list that is dynamically increasing".

Solution: Fix `divengine\ways::callAll()` method adding & before item foreach

```php
foreach (self::$__listen as $pattern => &$methods) { ... }
```

Now the expected behavior must happen

- New method `divengine\ways::invoke($way, $data)`

```php
<?php
$entry = divengine\ways::invoke('model://entries/1', [
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
include "divengine\ways.php"

divengine\ways::listen("/my/way", function($data, $args) {
	echo "Hello world";
});

divengine\ways::bootstrap('_url'); // $_GET['_url'] is missing, then assume $_GET['_url'] = $_SERVER['REQUEST_URI']
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

include "divengine\ways.php";
divengine\ways::register("control/Blog.php");
```

Oct 14, 2017
-------------------
- New method divengine\ways::redirect($way)

Oct 11, 2017
-------------------
- important bugfix when normalize the ways

Sep 20, 2017
-------------------
- fix match method and more complexity

```php
<?php

// documentation/chapter-1
divengine\ways::listen("documentation/chapter-{id|is_int}", function($data, $args){
	echo "Chapter #{$args['id']}";
});

 ```
- better control of the ways (executing single instance of each controller)
- improvement example
- release 1.2

Sep 18, 2017
-------------------
- better match method
- argument checker

```php
<?php
    divengine\ways::listen("blog/{id:is_int}", function($data, $args){
        echo "{$args['id']} is integer";
    });
```

- improvement example
- drop the obsolete bulkRegister

Sep 17, 2017
-------------------
- improvement/fix match() method
- capture URL arguments

```php
<?php

divengine\ways::listen("blog/{id}", function($data, $args){
	echo "Entry #{$args['id']}";
});
```

- release 1.1 version