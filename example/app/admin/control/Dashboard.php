<?php

#id = dashboard
#listen = admin

if (!isset($_SESSION['login']))
{
	header("Location: /admin/login");
	exit();
}

include PACKAGES . "admin/views/index.html";