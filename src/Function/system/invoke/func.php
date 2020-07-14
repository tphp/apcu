<?php

return function($data, $invokename = ""){
	if(empty($invokename)) return $data;
	if(!function_exists($invokename)) return null;
	$args = func_get_args();
	unset($args[0]);
	unset($args[1]);
	if(empty($args)) return $invokename($data);
	$argstr = "";
	foreach ($args as $key=>$val){
		$argstr .= ", \$args['{$key}']";
	}
	$ret = "";
	eval("\$ret = {$invokename}(\$data{$argstr});");
	return $ret;
};