<?php

return function($arr){
    $args = func_get_args();
    $argslen = count($args);
    $isempty = empty($arr);
    if($argslen <= 1){
        return "";
    }else if($isempty){
        return $args[1];
    }else if($argslen >= 3){
        return $args[2];
    }
	return $arr;
};