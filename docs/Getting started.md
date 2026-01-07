## Install the library:

With composer...

```
composer require divengine/ways
```

Without composer, download the class and...

```
include "path/to/divengine/ways.php";
```

## Basic usage:

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