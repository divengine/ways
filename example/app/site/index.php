<?php

#id = site
#listen = *
#listen = /...
#listen = /

// register controllers
divWays::register('site/control/Prepare.php');
divWays::register('site/control/Home.php');
divWays::register('site/control/News.php');

// register models
divWays::register('site/model/bootstrap.php');

// register controller with custom properties
divWays::register('site/control/Blog.php', [
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
