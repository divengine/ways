<?php

/* Div PHP Control example */

#id = admin-login
#listen = admin/login

if (isset($_POST['send']))
{
	$user = $_POST['user'];
	$pass = $_POST['pass'];
	if ($user == "admin" && $pass == "123")
	{
		$_SESSION['login'] = $user;
		header("Location: /admin");
		exit();
	}
}

include PACKAGES . "admin/views/login.html";