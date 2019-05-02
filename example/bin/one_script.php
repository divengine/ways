<?php

include __DIR__ . "/../../divWays.php";

// print the date
// $ php one_script.php date
divWays::listen("/date", function ($data = [], $args = []) {
	echo date("Y-m-d");
});

// say me hello
// $ php one_script.php hello Peter
divWays::listen("/hello/{name}", function ($data = [], $args = []) {
	echo "Hello {$args['name']}\n";
});

// unordered arguments
// $ php one_script.php backup --output_file backup.sql
divWays::listen("/backup/...", function ($data = [], $args = []) {
	if (isset($args['--output_file']))
		echo "Backup data in {$args['--output_file']}\n";
});

divWays::bootstrap();