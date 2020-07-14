<?php

/**
 * 保留键值，已 "," 隔开
 */
return function($data, $key = ""){
    if(!is_array($data)){
        return "";
    }
    $keys = explode(",", $key);
    $keybs = [];
    foreach ($keys as $k){
        $keybs[$k] = true;
    }
    $ret = [];
    foreach ($data as $k=>$v){
        $keybs[$k] && $ret[$k] = $v;
    }
	return $ret;
};