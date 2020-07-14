<?php
/**
 * $str : 去除字符串两边空格
 * $trim :去除字符串，默认为空格
 */
return function($str, $trim="") {
    if(empty($trim)){
        return trim($str);
    }
    return trim($str, $trim);
};