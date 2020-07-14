<?php
/**
 * $str : 字符串
 * $ismb ： 是否中文类型
 */
return function($str, $ismb = false) {
	if($ismb){
		return mb_strlen($str);
	}else{
		return strlen($str);
	}
};