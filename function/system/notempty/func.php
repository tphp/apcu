<?php

return function($data, $dtinfo = ""){
    if(empty($dtinfo) && $dtinfo !== 0 && $dtinfo !== '0') return $data;
    $funlen = func_num_args();
    $args = func_get_args();
    if($funlen <= 2) return $data;
    if($funlen <= 3) return apcu($args[2], $data);
    $key = $args[2];
    $newargs = [];
    for ($i = 3; $i < $funlen; $i ++){
        $newargs[] = $args[$i];
    }
	return apcu_ret([$key, $newargs], $data);
};