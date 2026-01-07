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
