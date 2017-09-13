<?php

/* Div PHP Control example */

#id = admin-home
#listen = admin

if (!isset($_SESSION['login']))
{
	header("Location: /admin/login");
	exit();
}

include "../app/admin/views/index.html";