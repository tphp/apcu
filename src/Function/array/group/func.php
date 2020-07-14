<?php

/**
 * $keyname 键名
 */
return function($data, $keyname = ""){
	if(empty($data)) return [];
	$keyname = trim($keyname);
	if(empty($keyname)) return [];

	$newdata = [];
	foreach ($data as $key=>$val){
		$kname = trim($val[$keyname]);
		unset($val[$keyname]);
		$newdata[$kname][] = $val;
	}
	return $newdata;
};