<?php

//create argument checker
function is_news_category($value)
{
	return $value == 'national' || $value == 'international';
}

// custom argument checker for [a-z] values
function is_abc($value)
{
	$l = strlen($value);
	for($i = 0; $i < $l; $i ++) if(strpos('abcdefghijklmnopqrstuvwxyz', $value[ $i ]) === false) return false;
	return true;
}

divWays::listen("/tests", function($data, $args)
{
	// testing match ways with patterns

	$pairs = [
		"elemental" => ["/", "/"],
		"elemental suffix/prefix" => ["/...", "/"],
		"equal" => ["/home", "/home"],
		"different" => ["/home", "/about"],
		"right to left" => [".../{n-1}/{n}", "a/b/c/d/e"],
		"left to right" => ["{1}/{2}/...", "a/b/c/d/e"],
		"between" => [".../b/c/...", "a/b/c/d/e"],
		"suffix" => [".../b/c", "a/b/c"],
		"prefix" => ["a/b/...", "a/b/c"],
		"complex between" => [".../{1}/c/{2}/...", "a/b/c/d/e/f"],
		"wrong pattern" => [".../{a}/.../{b}/...", "a/b/c/d/e/f"],
		"check argument" => ["blog/{id|is_int}", "blog/1"],
		"check argument 2" => ["news/{category|is_news_category}", "news/national"],
		"no match" => ["/admin/dashboard", "/blog"],
		"argument" => ["/blog/{id}", "blog/1"],
		"complexity" => [
			'.../{section}/*-{chapter}/{name}.{type|is_abc}',
			'site/all/documentation/chapter-5/intro.html'
		]
	];

	include "../app/site/views/test-match.phtml";

}, ['id' => "tests"]);
