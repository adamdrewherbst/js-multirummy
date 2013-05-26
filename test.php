<?php

$str = '{"a":"hello","b":{}}';
echo '$str: '.$str.PHP_EOL;
$arr = json_decode($str, true);
var_dump($arr);

?>
