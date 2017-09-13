<?php

/* Div PHP Control example */

#id = home
#listen = home
#require = prepare

class Home {

	static function Run($data = [])
	{
		include "../app/site/views/index.html";
	}
}