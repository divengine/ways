```php
<?php

// say me hello
// $ php one_script.php hello Peter
ways::listen("/hello/{name}", function ($data = [], $args = []) {
	echo "Hello {$args['name']}\n";
});
```