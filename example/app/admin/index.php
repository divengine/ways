<?php

/* Div PHP Control example */

#id = admin-home
#listen = admin
#listen = admin/...

divWays::register('admin/Dashboard.php');
divWays::register('admin/login.php');
divWays::register('admin/logout.php');

$data = divWays::bootstrap("_url", "/admin", $executed);