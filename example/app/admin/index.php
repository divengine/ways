<?php

/* Div PHP Control example */

#id = admin-home
#listen = admin
#listen = admin/...

divWays::register('admin/control/Dashboard.php');
divWays::register('admin/control/Session.php');

divWays::listen('admin/login', 'session@Login');
divWays::listen('admin/logout', 'session@Logout');

$data = divWays::bootstrap();