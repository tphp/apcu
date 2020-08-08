<?php

return function($arr, $isall = false){
	$funname = "func_array_sort_value_asc";
	if(!function_exists($funname)) {
		function func_array_sort_value_asc($arr, $isall){
			if(is_array($arr)){
				$nums = [];
				$strs = [];
				$arrs = [];
				foreach ($arr as $key=>$val){
					if (is_string($val)) {
						$strs[$key] = $val;
					}elseif(is_numeric($val)) {
						$nums[$key] = $val;
					}else{
						$arrs[$key] = $val;
					}
				}

				asort($nums);
				asort($strs);
				asort($arrs);

				if($isall) {
					foreach ($arrs as $key => $val) {
						$arrs[$key] = func_array_sort_value_asc($val, $isall);
					}
				}

				$ret = [];
				foreach ($nums as $key=>$val) $ret[$key] = $val;
				foreach ($strs as $key=>$val) $ret[$key] = $val;
				foreach ($arrs as $key=>$val) $ret[$key] = $val;

				return $ret;
			}else{
				return $arr;
			}
		}
	}

	return $funname($arr, $isall);
};