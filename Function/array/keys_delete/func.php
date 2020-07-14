<?php

/**
 * '删除键值，已 "," 隔开'
 */
return function($data, $key = ""){
    if(!is_array($data)){
        return "";
    }
    $keys = explode(",", $key);
    foreach ($keys as $k){
        if(isset($data[$k])){
            unset($data[$k]);
        }
    }
	return $data;
};