<?php

#id = prepare
#type = background

class Prepare {

	static function Run()
	{
		return ['today' => date("Y-m-d h:i:s")];
	}
}