<?php
/**
 * $str : 字符串
 * $search : 搜索字符串
 * $replace ： 替换字符串
 * $isregular ： 是否使用正则替换
 */
return function($data, $list = []) {
	if(is_array($data)) {
		foreach ($data as $key => $val) {
			if (isset($list[$val])) $data[$key] = $list[$val];
		}
	}elseif(is_string($data) || is_numeric($data)){
		if (isset($list[$data])) $data = $list[$data];
	}
	return $data;
};