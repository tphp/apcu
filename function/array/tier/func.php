<?php

/**
 * $issys 是否是系统array_unique函数
 */
return function($data, $_this = "", $_next = "", $topvalue = ""){
	if(empty($data)) return [];

	if(empty($_this) || empty($_next)) return $data;

	$funname = "func_array_tier";
	if(!function_exists($funname)) {
		/**
		 *data:数据组
		 *_this:子节点
		 *_next:父节点
		 *topvalue:顶部值
		 */
		function func_array_tier($data, $_this, $_next, $topvalue, $data2 = array(), $tmparr = array()) {
			if(!isset($topvalue) || trim($topvalue) == ''){
				if(count($data) > 0){
					$topvalue = $data[0][$_next];
					foreach($data as $key=>$val){
						if($val[$_next] < $topvalue){
							$topvalue = $val[$_next];
						}
					}
				}
			}

			if(empty($data2)){
				$parents = array($topvalue);
				$tmparr = &$data2;
				foreach($data as $key=>$val){
					if(in_array($val[$_next], $parents)){
						unset($val[$_this]);
						unset($val[$_next]);
						$tmparr[$key] = $val;
						unset($data[$key]);
					}
				}
			}

			$newtmparr = array();
			$parents = array();
			foreach($tmparr as $key=>$val){
				$parents[] = $key;
				foreach($data as $k=>$v){
					if($key == $v[$_next]){
						unset($v[$_this]);
						unset($v[$_next]);
						$tmparr[$key]['_next_'][$k] = $v;
						$newtmparr[$k] = &$tmparr[$key]['_next_'][$k];
						unset($data[$k]);
					}
				}
			}
			if(empty($parents) || empty($data) || empty($newtmparr)){
				return $data2;
			}else{
				return func_array_tier($data, $_this, $_next, $parents, $data2, $newtmparr);
			}
		}
	}

	$newdata = [];
	foreach ($data as $key=>$val){
		$newdata[$val[$_this]] = $val;
	}
	return $funname($newdata, $_this, $_next, $topvalue);
};