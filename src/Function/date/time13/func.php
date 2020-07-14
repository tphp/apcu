<?php

return function($date = null){
	if(empty($date)){
		list($t1, $t2) = explode(' ', microtime());
		return $t2 . str_pad(ceil($t1 * 1000), 3, '0', STR_PAD_LEFT);
	}

	if(is_numeric($date)) return $date;

	return strtotime($date);
};