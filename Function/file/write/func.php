<?php
return function($file_url, $str = "", $addBool = false){
	if(empty($file_url)) return $file_url;

	$funname = "func_file_write";
	if(!function_exists($funname)) {
		function func_file_write($path)
		{
            if (is_readable($path)) return;
            $plen = strlen($path);
            if($plen <= 0) return;
            $t_i = 0;
            if($path[0] == '/'){
                $t_i = 1;
            }else{
                if($plen > 2 && $path[1] == ':') $t_i = 3;
            }
            $bUrl = substr($path, 0, $t_i);
            for ($i = $t_i; $i < $plen; $i++) {
                if (substr($path, $i, 1) == '\\' || substr($path, $i, 1) == '/') {
                    $bUrl = $bUrl . substr($path, $t_i, $i - $t_i);
                    if (!is_readable($bUrl)) mkdir($bUrl);
                    for ($j = $i + 1; $j < strlen($path) - 1; $j++) {
                        if (substr($path, $j, 1) == '\\' || substr($path, $j, 1) == '/') {
                            $i++;
                        } else {
                            break;
                        }
                    }
                    $t_i = $i;
                }
            }
		}
	}

	$funname($file_url);
	if($addBool){
		file_put_contents($file_url, $str, FILE_APPEND);
	}else{
		file_put_contents($file_url, $str);
	}
	return $file_url;
};