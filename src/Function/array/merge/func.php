<?php

/**
 * $issys 是否是系统array_merge函数
 */
return function($data, $arrs = null, $issys = false){
	if(empty($arrs)) return $data;
	if(empty($data)) return $arrs;

	if($issys) return array_merge($data, $arrs);

	$funnameloop = "func_array_merge_loop";
	if(!function_exists($funnameloop)) {
		function func_array_merge_loop($data = null, $retkey = [], &$retval = []){
			foreach ($data as $key=>$val){
				$newkey = $retkey;
				$newkey[] = $key;
				if(!is_array($val)){
					if(empty($val) && $val !== 0 && $val !== '') {
						$retval[] = [$newkey];
					}else{
						$retval[] = [$newkey, $val];
					}
				}else{
					func_array_merge_loop($val, $newkey, $retval);
				}
			}
			return $retval;
		}
	}

	$funnamesetarray = "func_array_merge_setArray";
	if(!function_exists($funnamesetarray)) {
		function func_array_merge_setArray(&$arrdata, $key = null, $value = null)
		{
			if (is_array($key)) { //如果$key是数组
				$keystr = "";
				$keyarr = [];
				foreach ($key as $v) {
					$keystr .= "['{$v}']";
					$keyarr[] = $keystr;
					eval("if(!is_array(\$arrdata{$keystr})) { unset(\$arrdata{$keystr});}");
				}

				if (empty($value) && $value !== 0 && $value !== '') {
					eval("unset(\$arrdata{$keystr});");
					foreach ($keyarr as $v) {
						$vbool = false;
						eval("if(empty(\$arrdata{$v})) { unset(\$arrdata{$v}); \$vbool = true;}");
						if ($vbool) break;
					}
				} else {
					eval("\$arrdata{$keystr} = \$value;");
				}
			} else { //如果$key是字符串
				if (empty($value) && $value !== 0 && $value !== '') {
					unset($arrdata[$key]);
				} else {
					eval("if(!is_array(\$arrdata['{$key}'])) { unset(\$arrdata['{$key}']);}");
					$arrdata[$key] = $value;
				}
			}
		}
	}

	$newdata = $funnameloop($arrs);
	foreach ($newdata as $val){
		if(count($val[0]) == 1){
			$funnamesetarray($data, $val[0][0], $val[1]);
		}else{
			$funnamesetarray($data, $val[0], $val[1]);
		}
	}

	return $data;
};