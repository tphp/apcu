<?php

return function($arr, $isall = false){
	$funname = "func_array_sort_key_desc";
	if(!function_exists($funname)) {
		function func_array_sort_key_desc($arr, $isall){
			if(is_array($arr)){
				$nums = [];
				$strs = [];
				foreach ($arr as $key=>$val){
					if (is_string($key)) {
						$strs[$key] = $val;
					} else {
						$nums[$key] = $val;
					}
				}

				krsort($nums);
				krsort($strs);

				foreach ($nums as $key=>$val){
					$strs[$key] = $val;
				}

				if($isall) {
					foreach ($strs as $key => $val) {
						if (is_array($val)) {
							$strs[$key] = func_array_sort_key_desc($val, $isall);
						}
					}
				}
				return $strs;
			}else{
				return $arr;
			}
		}
	}

	return $funname($arr, $isall);
};