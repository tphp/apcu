<?php

return function($time = null, $format = 'Y-m-d H:i:s', $is_empty=false){
    if($is_empty){
        if(empty($time) || $time == 0){
            return '';
        }
    }
    if($time === null || $time === "") $time = time();
	$timestr = $time."";
	if(strlen($timestr) > 10){
		$time = substr($timestr, 0, 10);
	}
    return date($format, $time);
};