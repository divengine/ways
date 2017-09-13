<?php

/* Div PHP Control example */

// needed for this example
session_start();
session_name("div-control-example");
// arbitrary location for software's packages
define('PACKAGES', '../app/');

// include the library
include "../../divControl.php";

// variant 1:
/*
divControl::register('site/Home.php');
divControl::register('site/Prepare.php');
divControl::register('admin/index.php');
divControl::register('admin/login.php');
divControl::register('admin/logout.php');
*/

// variant 2:
divControl::bulkRegister("../app.ini");

// listen... (see the "_url" in the .htaccess file)
divControl::bootstrap('_url', 'home');
