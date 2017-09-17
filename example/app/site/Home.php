<?php

/* Div PHP Control example */

#id = home
#require = prepare
#require = decorator
#listen = home

class Home {


	static function Run($data)
	{
		include PACKAGES . "site/views/index.html";

		return $data;
	}


	static function About($data)
	{
		echo "About us";
		include PACKAGES . "site/views/index.html";

		return $data;
	}
}