<?php

/**
 * 数组设置
 * 当key为空时返回空
 * 当value为空时销毁对应端key
 */
return function($data, $key = null, $value = null){
	if(empty($key)) return null;

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

	$funnamesetarray($data, $key, $value);

	return $data;
};