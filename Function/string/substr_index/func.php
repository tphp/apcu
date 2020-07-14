<?php

/**
 *str:字符串
 *start:开始字符串
 *end:结束字符串
 *ismb ： 是否中文类型
 */

return function($str, $start = 0, $end = 0, $ismb = false) {
	if($ismb) {
		return mb_substr($str, $start, $end);
	}else{
		return substr($str, $start, $end);
	}
};