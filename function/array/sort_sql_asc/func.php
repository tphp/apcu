<?php

return function($arr, $fieldname = ""){

	if(empty($fieldname)) return $arr;

	$fieldnums = [];
	$fieldstrs = [];
	$fieldarrs = [];
	$fieldnulls = [];
	foreach ($arr as $key=>$val){
		if(isset($val[$fieldname])){
			$tmp = $val[$fieldname];
			if(is_string($tmp)){
				$fieldstrs[$key] = $tmp;
			}elseif(is_numeric($tmp)){
				$fieldnums[$key] = $tmp;
			}else{
				$fieldarrs[$key] = $tmp;
			}
		}else{
			$fieldnulls[$key] = $val;
		}
	}

	asort($fieldnums);
	asort($fieldstrs);

	$ret = [];
	foreach ($fieldnums as $key=>$val) $ret[$key] = $arr[$key];
	foreach ($fieldstrs as $key=>$val) $ret[$key] = $arr[$key];
	foreach ($fieldarrs as $key=>$val) $ret[$key] = $arr[$key];
	foreach ($fieldnulls as $key=>$val) $ret[$key] = $arr[$key];
	return $ret;
};