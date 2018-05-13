<?php

#id = prepare
#type = background

class Prepare {

	static function Run($data, $args)
	{
		return ['today' => date("Y-m-d h:i:s")];
	}
}