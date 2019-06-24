<?php

//echo "aaa";

class Texts
{

  #listen@randomWord = model://texts/randomWord
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

	#listen@randomSentence = model://texts/randomSentence
	static function randomSentence()
	{
		$sentence = ucfirst(self::randomWord());
		for($j = 0; $j < rand(8, 12); $j ++) $sentence .= self::randomWord() . " ";
		$sentence .= ". ";

		return $sentence;
	}

  #listen@randomParagraph = model://texts/random/paragraph/{max_sentences}
	static function randomParagraph($data, $args)
	{
		$p = '';
		for($j = 0; $j < rand(3, $args['max_sentences']); $j ++) $p .= self::randomSentence();

		return ["paragraph" => $p];
	}
}