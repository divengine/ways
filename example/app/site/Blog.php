<?php

class Blog
{
	static function Latest($data, $args)
	{
		echo "<h1>Blog</h1>";
	}

	static function Entry($data, $args)
	{
		echo "<h2>Entry #{$args['id']}</h2>";
	}

	static function Comments($data, $args)
	{
		echo "<h2>Comments of entry #{$args['id']}</h2>";
	}
}