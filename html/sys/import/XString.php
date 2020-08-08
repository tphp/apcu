<?php

class XString{
    /**
     *str:字符串
     *start:开始字符串
     *end:结束字符串
     */
    public function getSubStr($str, $start = "", $end = "") {
        if(!empty($start)){
            $pos = strpos($str, $start);
            if($pos === 0 || $pos > 0){
                $pos = $pos + strlen($start);
                $str = substr($str, $pos);
            }else{
                return "";
            }
        }
        if(!empty($end)){
            $pos = strpos($str, $end);
            if($pos > 0) {
                $str = substr($str, 0, $pos);
            }else{
                return "";
            }
        }
        return $str;
    }
}