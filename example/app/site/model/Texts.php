<?php

class Texts
{

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

	static function randomParagraph()
	{
		$p = '';
		for($j = 0; $j < rand(3, 10); $j ++) $p .= self::randomSentence();

		return $p;
	}
}