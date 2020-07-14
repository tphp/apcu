<?php
/**
 * $str : 查找字符串是否存在
 * $findstr : 要查询的字符串
 * $trueset : 存在并$trueset不为空时设置
 * $falseset : 不存在并$falseset不为空时设置
 */
return function($str, $findstr="", $trueset = "", $falseset = "") {
    if(empty($str) && empty($findstr)) return "";
    $bool = false;
    strpos($str, $findstr) !== false && $bool = true;
    if(empty($trueset)) return $str;
    if($bool) return $trueset;
    if(empty($falseset)) return $str;
    return $falseset;
};