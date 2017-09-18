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
divWays::register('site/Prepare.php',[
	'id' => 'prepareData'
]);

// register a controller and redefine the require property
divWays::register('site/Home.php', [
	'require' => ['prepareData']
]);

// register controller with custom properties
divWays::register('site/Blog.php', [
	'id' => 'blog',
	'listen' => ['/blog', '/blog/...'],
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
divWays::listen("GET-PUT://contact", function($data, $args){
	echo "Hello {$data['username']}";
	return $data;
}, ['id' => 'contact']);

divWays::hook(DIV_WAYS_BEFORE_RUN,  "contact", function($data){
	$data['username'] = "Me";
	return $data;
});

// another bootstrap for admin
divWays::register('admin/index.php');

// another bootstrap for api
divWays::register('api/bootstrap.php');

// variant 2:
// divWays::bulkRegister("../app.ini");

divWays::hook(DIV_WAYS_BEFORE_RUN, "home", function($data, $args){
	$data['username'] = "Visitor";
	return $data;
});

// listen... (see the "_url" in the .htaccess file)
$data = divWays::bootstrap('_url', 'home');

if (divWays::getTotalExecutions() == 0)
	die("404 page not found");