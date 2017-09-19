<?php

#id = site
#listen = *
#listen = /...
#listen = /

// register controllers
divWays::register('site/Control/Prepare.php');
divWays::register('site/Control/Home.php');
divWays::register('site/Control/News.php');

// register controller with custom properties
divWays::register('site/Control/Blog.php', [
	'id' => 'blog',
	'listen' => [
		'/blog',
		'/blog/...'
	],
	'method' => 'Latest'
]);

// route to blog entry
divWays::listen('/blog/{id}', 'blog@Entry');

// route to blog comments
divWays::listen('/blog/{id}/comments', 'blog@Comments');

// custom ways
divWays::listen("/about", "home@About");

// bootstrap
$data = divWays::bootstrap();
