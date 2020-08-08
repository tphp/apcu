<?php

return function($arr){
	$args = func_get_args();
	unset($args[0]);
	if(!isset($args[1]) || empty($args[1])) return $arr;

	$argskey = strtolower(trim($args[1]));
	unset($args[1]);

	//不区分大小写匹配
	$arrlowers = [];
	foreach ($arr as $key=>$val){
		if(is_array($val)){
			$tp = [];
			foreach ($val as $k=>$v){
				$tp[strtolower(trim($k))] = $v;
			}
			$arrlowers[] = $tp;
		}
	}

	if(empty($arrlowers)) return null;

	$argscot = count($args);
	$retarr = [];
	if($argscot < 1){
		foreach ($arrlowers as $key=>$val){
			$retarr[] = $val[$argskey];
		}
	}elseif($argscot < 2){
		$argskey2 = strtolower(trim($args[2]));
		foreach ($arrlowers as $key=>$val){
			$retarr[$val[$argskey]] = $val[$argskey2];
		}
	}else{
		$newargs = [];
		foreach ($args as $v){
			$newargs[] = strtolower(trim($v));
		}

		foreach ($arrlowers as $key=>$val){
			$tmparr = [];
			foreach ($newargs as $v){
				$tmparr[] = $val[$v];
			}
			$retarr[$val[$argskey]] = $tmparr;
		}
	}

	return $retarr;
};