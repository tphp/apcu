<?php

class XFile{
	public function mkDir($urlRoot){
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
				$bUrl = $bUrl.substr($urlRoot, $t_i, $i - $t_i);
				if(empty($bUrl)){
				    continue;
                }
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

	public function isFileExists($file){  //判断文件是否存在
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

	public function write($urlRoot, $str, $addBool = false){
		$this->mkDir($urlRoot);
		if($addBool){
			file_put_contents($urlRoot, $str, FILE_APPEND);
		}else{
			file_put_contents($urlRoot, $str);
		}
	}

	public function read($file_url){
		$str = "";
		if($this->isFileExists($file_url)){
			$file_handle = fopen($file_url, "r");
			while(!feof($file_handle)) {
				$line = fgets($file_handle);
				$str = $str.$line;
			}
			fclose($file_handle);
		}
		return $str;
	}

	public function delete($file_url){
		if(file_exists($file_url)){
			unlink($file_url);
		}
	}

    /**
     * 清空文件夹函数和清空文件夹后删除空文件夹函数的处理
     * @param $path
     */
    public function deleteDir($path){
        $path = rtrim($path, "/");
        //如果是目录则继续
        if(is_dir($path)){
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($path);
            foreach ($p as $val) {
                //排除目录中的.和..
                if ($val != "." && $val != "..") {
                    //如果是目录则递归子目录，继续操作
                    $pdir = $path . "/" . $val;
                    if (is_dir($pdir)) {
                        //子目录中操作删除文件夹和文件
                        $this->deleteDir($pdir . '/');
                        //目录清空后删除空文件夹
                        @rmdir($pdir);
                    } else {
                        //如果是文件直接删除
                        unlink($pdir);
                    }
                }
            }
            @rmdir($path);
        }
    }

    /**
     * 获取文件夹及文件
     * @param string $dirRoot
     * @param string $child
     * @param array $no_dirs
     * @param array $dirs
     * @return array
     */
    private function __getAllDirs($dirRoot = "", $child = "", $no_dirs=[], &$dirs = []){
        $dir = $dirRoot;
        !empty($child) && $dir .= DIRECTORY_SEPARATOR.$child;
        $files = scandir($dir);
        foreach($files as $v) {
            $newPath = $dir .DIRECTORY_SEPARATOR . $v;
            if(is_dir($newPath) && $v != '.' && $v != '..') {
                if(!empty($no_dirs) && is_array($no_dirs) && in_array($v, $no_dirs)){
                    continue;
                }
                $d = $v;
                !empty($child) && $d = $child.DIRECTORY_SEPARATOR.$d;
                $dirs[] = $d;
                $this->__getAllDirs($dirRoot, $d, $no_dirs, $dirs);
            }
        }
        return $dirs;
    }

    public function getAllDirs($dirRoot = "", $no_dirs=[]){
        $dirRoot = rtrim(trim($dirRoot), "/");
        if(empty($dirRoot) || !is_dir($dirRoot)) return [];
        $all_dirs = $this->__getAllDirs($dirRoot, "", $no_dirs);
        foreach($all_dirs as $key=>$val){
            $all_dirs[$key] = str_replace("\\", "/", $val);
        }
        return $all_dirs;
    }
    /**
     * 获取文件夹及文件
     * @param string $urlRoot
     * @return array
     */
	public function getDirsFiles($urlRoot = ""){
        $urlRoot = rtrim(trim($urlRoot), "/");
	    if(empty($urlRoot) || !is_dir($urlRoot)) return [];
        $files = scandir($urlRoot);
        $fileItem = [];
        foreach($files as $v) {
            $newPath = $urlRoot .DIRECTORY_SEPARATOR . $v;
            if(is_dir($newPath) && $v != '.' && $v != '..') {
                $fileItem['dirs'][] = $v;
            }else if(is_file($newPath)){
                $fileItem['files'][] = $v;
            }
        }
        return $fileItem;
    }

    /**
     * 用法：
     * xCopy("feiy","feiy2",1):拷贝feiy下的文件到 feiy2,包括子目录
     * xCopy("feiy","feiy2",0):拷贝feiy下的文件到 feiy2,不包括子目录
     * 参数说明：
     * $source:源目录名
     * $destination:目的目录名
     * $child:复制时，是不是包含的子目录
     */
    function copy($source, $destination, $child = true){
        if(!is_dir($source)){
            echo("Error:the $source is not a direction!");
            return 0;
        }


        if(!is_dir($destination)){
            mkdir($destination,0777);
        }

        $handle=dir($source);
        while($entry=$handle->read()) {
            if(($entry!=".")&&($entry!="..")){
                if(is_dir($source."/".$entry)){
                    if($child)
                        $this->copy($source."/".$entry,$destination."/".$entry,$child);
                }
                else{
                    copy($source."/".$entry,$destination."/".$entry);
                }
            }
        }
        return 1;
    }
    
    /**
     * 获取文件夹
     * @param string $urlRoot
     * @return array|mixed
     */
    public function getDirs($urlRoot = ""){
        $df = $this->getDirsFiles($urlRoot);
        if(empty($df) || empty($df['dirs'])) return [];
	    return $df['dirs'];
    }

    /**
     * 获取文件
     * @param string $urlRoot
     * @return array|mixed
     */
    public function getFiles($urlRoot = ""){
        $df = $this->getDirsFiles($urlRoot);
        if(empty($df) || empty($df['files'])) return [];
        return $df['files'];
    }

	public function getGzData($gzFile) {
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

    /**
     * 显示图片
     * @param $image_file
     * @return bool|string
     */
	public function showImage($image_file){
        if(!file_exists($image_file)){
            return "Image Error !";
        }
        $info = getimagesize($image_file);
        $imgform = $info['mime'];
        $imgdata = fread(fopen($image_file, 'rb'), filesize($image_file));
        header("content-type:{$imgform}");
        return $imgdata;
    }

    /**
     * 获取图片base64编码字符串
     * @param $image_file
     * @return string
     */
    public function getImageBase64($image_file){
        $image_info = getimagesize($image_file);
        $image_data = fread(fopen($image_file, 'r'), filesize($image_file));
        $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
        return $base64_image;
    }
}