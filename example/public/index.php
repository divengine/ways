<?php

/* Div PHP Control example */

// needed for this example
session_start();
session_name("div-ways-example");

// arbitrary location for software's packages
define('PACKAGES', '../app/');

// include the library
include "../../divWays.php";

// include separated bootstrap definition
include "../app/site/control/Tests.php";

// register simple scripts as controllers
divWays::register('scripts/log.php');
divWays::register('scripts/decorator.php');

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

// another bootstrap for public site
divWays::register('site/index.php');

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

// listen... (see the "_url" in the .htaccess file)

$data = divWays::bootstrap('_url', 'home');

$total_executions = divWays::getTotalExecutions();
if($total_executions == 0) die("404 page not found");

// --------------------------- Debug footer ---------------------------- //
echo "<div style=\"color: white; font-family: Arial; background: black;padding:10px;>\"";
echo "<h2>Arguments by controller</h2><br/>";

$list = divWays::getArgsByController();

foreach($list as $controller => $context) foreach($context as $pattern => $args)
{
	echo "<b>$controller </b>($pattern): ";
	foreach($args as $arg => $value) echo "$arg = <u>$value</u> &nbsp;";
	echo "<br/>";
}
echo "Current way: " . divWays::getCurrentWay() . "<br/>";
echo "</div>";