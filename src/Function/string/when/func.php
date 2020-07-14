<?php
/**
 * $str : 字符串
 * $search : 搜索字符串
 * $replace ： 替换字符串
 * $isregular ： 是否使用正则替换
 */
return function($str, $search = "", $replace = "", $elsestr="") {
    $flen = func_num_args();
    if($flen <= 2) return $str;
    if($str == $search){
        return $replace;
    }elseif($flen >= 4){
        return $elsestr;
    }
    return $str;
};