<?php

include __DIR__ . "/../../divWays.php";

// print the date
divWays::listen("/date", function ($data = [], $args = []) {
	echo date("Y-m-d");
});

// say me hello
divWays::listen("/hello/{name}", function ($data = [], $args = []) {
	echo "Hello {$args['name']}\n";
});

divWays::bootstrap();