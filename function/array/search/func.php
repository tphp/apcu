<?php

return function($arr, $str = "", $iscase = false, $strict = null){
	if($iscase) return array_search($str, $arr, $strict);

	$newarr = [];
	$str = strtolower($str);
	foreach ($arr as $key=>$val){
		if(!is_array($val)) $newarr[$key] = strtolower($val);
	}

	return array_search($str, $newarr, $strict);
};