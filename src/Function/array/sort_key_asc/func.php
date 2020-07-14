<?php

return function($arr, $isall = false){
	$funname = "func_array_sort_key_asc";
	if(!function_exists($funname)) {
		function func_array_sort_key_asc($arr, $isall){
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

				ksort($nums);
				ksort($strs);

				foreach ($strs as $key=>$val){
					$nums[$key] = $val;
				}

				if($isall) {
					foreach ($nums as $key => $val) {
						if (is_array($val)) {
							$nums[$key] = func_array_sort_key_asc($val, $isall);
						}
					}
				}
				return $nums;
			}else{
				return $arr;
			}
		}
	}

	return $funname($arr, $isall);
};