<?php

#id = session

class Session
{
	static function Login($data, $args)
	{
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

		include PACKAGES . "admin/views/login.phtml";
	}

	static function Logout($data, $args)
	{

		unset($_SESSION['login']);

		header("Location: /admin");
		exit();

	}

}