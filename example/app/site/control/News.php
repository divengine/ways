<?php

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
			for($j = 0; $j < rand(3, 10); $j ++) echo self::randomSentence();
			echo "...</p>";
		}

		return $data;
	}

	static function randomWord()
	{
		$word     = '';
		$cons     = 'bcdfghjklmnpqrstvwxyz';
		$cons_len = strlen($cons);

		$vocals     = 'aeiou';
		$vocals_len = strlen($vocals);

		$len = rand(1, 4);
		for($i = 0; $i < $len; $i ++) $word .= $cons[ rand(0, $cons_len - 1) ] . $vocals[ rand(0, $vocals_len - 1) ];

		return $word;
	}

	static function randomSentence()
	{
		$sentence = ucfirst(self::randomWord());
		for($j = 0; $j < rand(8, 12); $j ++) $sentence .= self::randomWord() . " ";
		$sentence .= ". ";

		return $sentence;
	}
}