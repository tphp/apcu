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

    /**
     * 替换HTML标签中的字符
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return mixed
     */
    public function replaceStrToHtml($search='', $replace='', $subject=''){
        if(!is_string($subject)){
            $subject = "{$subject}";
        }
        $s_len = strlen($subject);
        $is_tag = false;
        $tmp_str = "";
        $ret_str = "";
        for($i = 0; $i < $s_len; $i ++){
            $si = $subject[$i];
            if($si == '<'){
                $is_tag = true;
                if(!empty($tmp_str)){
                    $tmp_str = str_replace($search, $replace, $tmp_str);
                    $ret_str .= $tmp_str;
                    $tmp_str = "";
                }
                $ret_str .= $si;
                continue;
            }elseif($si == '>'){
                $is_tag = false;
                $ret_str .= $si;
                continue;
            }
            if($is_tag){
                $ret_str .= $si;
            }else{
                $tmp_str .= $si;
            }
        }
        if(!empty($tmp_str)){
            $ret_str .= str_replace($search, $replace, $tmp_str);
        }
        return $ret_str;
    }
}