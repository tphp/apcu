<?php
/**
 * $str : 字符串
 * $search : 搜索字符串
 * $replace ： 替换字符串
 * $isregular ： 是否使用正则替换
 */
return function($str, $search = "", $replace = "", $isregular = false) {
	if($search == "") return $str;
	if($isregular){
		return preg_replace($search, $replace, $str);
	}else {
		return str_replace($search, $replace, $str);
	}
};