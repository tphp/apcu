<?php
return function($file_url){
	$funname = "func_file_read";
	if(!function_exists($funname)) {
		function func_file_read($file) {  //判断文件是否存在
			if (preg_match('/^http:\/\//', $file)) {
				//远程文件
				if (ini_get('allow_url_fopen')) {
					if (@fopen($file, 'r')) return true;
				} else {
					$parseurl = parse_url($file);
					$host = $parseurl['host'];
					$path = $parseurl['path'];
					$fp = fsockopen($host, 80, $errno, $errstr, 10);
					if (!$fp) return false;
					fputs($fp, "GET {$path} HTTP/1.1 \r\nhost:{$host}\r\n\r\n");
					if (preg_match('/HTTP\/1.1 200/', fgets($fp, 1024))) return true;
				}
				return false;
			}
			return file_exists($file);
		}
	}


	$str = "";
	if($funname($file_url)){
		$file_handle = fopen($file_url, "r");
		while(!feof($file_handle)) {
			$line = fgets($file_handle);
			$str = $str.$line;
		}
		fclose($file_handle);
	}
	return $str;
};