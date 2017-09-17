<?php

#id = log
#type = background
#listen = *

$f = fopen("../logs/access.log",  "a");
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "[unknown client ip]";
fputs($f,date("Y-m-d h:i:s - "). " - [$ip] - " . divWays::getCurrentWay() . "\n");
fclose($f);
