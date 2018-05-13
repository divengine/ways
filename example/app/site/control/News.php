<?php

require_once "../app/site/model/Texts.php";

#id = news
#listen = /news
#listen = /news/{category}
#listen = /news/{category}/{limit:is_int}
class News
{

	static function Run($data, $args)
	{
		include "../app/site/views/header.html";

		// default args
		$default_args = [
			"category" => "All",
			"limit" => 10
		];

		$args = divWays::cop($default_args, $args);


		echo "<h1>News of {$args['category']}</h1>";
		echo "Last {$args['limit']} news<br/>";

		for($i = 0; $i < $args['limit']; $i ++)
		{
			echo "<p>#$i ";
			echo Texts::randomParagraph();
			echo "...</p>";
		}

		return $data;
	}

}