<?php

namespace Tphp\Apcu\Controllers;

class FileController{

	private function _mkdir($urlRoot){
		$t_i = 0;
		for($i = 1; $i < strlen($urlRoot) - 1; $i ++){
			if(substr($urlRoot, $i - 1, 1) == '.' && substr($urlRoot, $i + 1, 1) != '.'){
				if(substr($urlRoot, $i, 1) == '\\' || substr($urlRoot, $i, 1) == '/'){
					$t_i = $i + 1;
				}
			}
		}
		$bUrl = substr($urlRoot, 0, $t_i);
		for($i = $t_i; $i < strlen($urlRoot); $i ++){
			if(substr($urlRoot, $i, 1) == '\\' || substr($urlRoot, $i, 1) == '/'){
				//echo substr($urlRoot, $t_i, $i - $t_i)."<br>";
				$bUrl = $bUrl.substr($urlRoot, $t_i, $i - $t_i);
				if(!is_readable($bUrl)) mkdir($bUrl);
				for($j = $i + 1; $j < strlen($urlRoot) - 1; $j ++){
					if(substr($urlRoot, $j, 1) == '\\' || substr($urlRoot, $j, 1) == '/'){
						$i ++;
					}else{
						break;
					}
				}
				$t_i = $i;
			}
		}
	}

	public static function isFileExists($file){  //判断文件是否存在
		if(preg_match('/^http:\/\//',$file)){
			//远程文件
			if(ini_get('allow_url_fopen')){
				if(@fopen($file,'r')) return true;
			}else{
				$parseurl=parse_url($file);
				$host=$parseurl['host'];
				$path=$parseurl['path'];
				$fp=fsockopen($host,80, $errno, $errstr, 10);
				if(!$fp)return false;
				fputs($fp,"GET {$path} HTTP/1.1 \r\nhost:{$host}\r\n\r\n");
				if(preg_match('/HTTP\/1.1 200/',fgets($fp,1024))) return true;
			}
			return false;
		}
		return file_exists($file);
	}

	public static function makeFile($urlRoot, $str, $addBool = false){
		self::_mkdir($urlRoot);
		if($addBool){
			file_put_contents($urlRoot, $str, FILE_APPEND);
		}else{
			file_put_contents($urlRoot, $str);
		}
	}
	
	public static function makeDirect($urlRoot){
		self::_mkdir($urlRoot);
	}
	
	public static function readFile($file_url){
		$str = "";
		if(self::isFileExists($file_url)){
			$file_handle = fopen($file_url, "r");
			while(!feof($file_handle)) {
				$line = fgets($file_handle);
				$str = $str.$line;
			}
			fclose($file_handle);
		}
		return $str;
	}
	
	public static function delFile($file_url){
		if(file_exists($file_url)){
			unlink($file_url);
		}
	}

	public static function getGzData($gzFile) {
		$gz   = gzopen($gzFile, 'r');
		$sqlstr = "";
		while(true){
			$sqltmp = gzgets($gz);
			if(preg_match('/.*;$/', trim($sqltmp))){
				$sqlstr .= $sqltmp;
			}elseif(substr(trim($sqltmp), 0, 2) != '--' && !empty($sqltmp)){
				$sqlstr .= $sqltmp;
			}elseif(gzeof($gz)){
				break;
			}
		}
		return $sqlstr;
	}
}