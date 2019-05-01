<?php

include __DIR__ . "/../../divWays.php";

$property = "This is a property value";

divWays::listen("/", function ($data = [], $args = [], $properties = []) {
	echo "Controller ID = " . $properties['id'] . "\n";
	echo "A controller property = " . $properties['myProperty'];

}, [
	'myProperty' => $property,
]);

divWays::bootstrap();