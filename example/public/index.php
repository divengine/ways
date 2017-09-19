<?php

/* Div PHP Control example */

// needed for this example
session_start();
session_name("div-control-example");

// arbitrary location for software's packages
define('PACKAGES', '../app/');

// include the library
include "../../divWays.php";

// register a controller with custom ID (different from controller definition/properties)
divWays::register('site/Prepare.php', [
	'id' => 'prepareData'
]);

// register a controller and redefine the require property
divWays::register('site/Home.php', [
	'require' => ['prepareData']
]);

// register controller with custom properties
divWays::register('site/Blog.php', [
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

// register simple scripts as controllers
divWays::register('site/decorator.php');
divWays::register('site/log.php');

// custom ways
divWays::listen("/about", "home@About");

// ways with closure
divWays::listen("GET-PUT://contact", function($data, $args)
{
	echo "Hello {$data['username']}";

	return $data;
}, ['id' => 'contact']);

// a hook before run the contact closure
divWays::hook(DIV_WAYS_BEFORE_RUN, "contact", function($data)
{
	$data['username'] = "Me";

	return $data;
});

// another bootstrap for admin
divWays::register('admin/index.php');

// another bootstrap for api
divWays::register('api/bootstrap.php');

// a hook before run the home
divWays::hook(DIV_WAYS_BEFORE_RUN, "home", function($data, $args)
{
	$data['username'] = "Visitor";

	return $data;
});

//create argument checker
function is_news_category($value)
{
	return $value == 'national' || $value == 'international';
}

divWays::listen("/tests", function($data, $args)
{
	// testing match ways with patterns

	$pairs = [
		"elemental" => ["/", "/"],
		"equal" => ["/home", "/home"],
		"different" => ["/home", "/about"],
		"right to left" => [".../{n-1}/{n}", "a/b/c/d/e"],
		"left to right" => ["{1}/{2}/...", "a/b/c/d/e"],
		"between" => [".../b/c/...", "a/b/c/d/e"],
		"suffix" => [".../b/c", "a/b/c"],
		"prefix" => ["a/b/...", "a/b/c"],
		"complex between" => [".../{1}/c/{2}/...", "a/b/c/d/e/f"],
		"wrong pattern" => [".../{a}/.../{b}/...", "a/b/c/d/e/f"],
		"check argument" => ["blog/{id|is_int}", "blog/1"],
		"check argument 2" => ["news/{category|is_news_category}", "news/national"]
	];

	include "../app/site/views/test-match.phtml";

});

// listen... (see the "_url" in the .htaccess file)
$data = divWays::bootstrap('_url', 'home');

if(divWays::getTotalExecutions() == 0) die("404 page not found");