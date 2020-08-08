<?php
/**
 * $str : 字符串
 */
return function($str) {
	$str = str_replace("\t", " ", $str);
	$str = str_replace("　", " ", $str);
	$str = preg_replace('/\s(?=\s)/', "\\1", $str);
	$str = trim($str);
	return $str;
};