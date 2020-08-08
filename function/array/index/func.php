<?php

return function($arr){
	$args = func_get_args();
	unset($args[0]);
	if(!isset($args[1]) || empty($args[1])) return $arr;

	$ret = $arr;
	foreach ($args as $val){
		$val = strtolower(trim($val));
		if(empty($val)){
			$ret = null;
			break;
		}else{
			$tmp = [];
			foreach ($ret as $k=>$v){
				$tmp[strtolower(trim($k))] = $v;
			}

			if(empty($tmp[$val])){
				$ret = null;
				break;
			}

			$ret = $tmp[$val];
		}
	}
	return $ret;
};