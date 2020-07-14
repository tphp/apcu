<?php

return function($arr, $str = ""){
	if(empty($str) || empty($arr)) return false;

	return in_array($str, $arr);
};