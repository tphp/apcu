<?php

class Upload
{
    public function __construct($path = "", $filename = "")
    {
        $this->resize = [];
        if(empty($filename) || !is_string($filename)){
            if(is_numeric($filename)){
                $filename .= "";
            }else{
                $filename = "";
            }
        }
        $this->filename = trim(trim(trim($filename), "/\\"));
        $this->path = trim(trim($path, "\/\\"));
        $this->save_path = storage_path('app/public/');
        $this->xfile = import('XFile');
        $this->files = [];
    }

    /**
     * 增加缩列图
     * @param int $w 宽
     * @param int $h 高
     * @return $this
     */
    public function addResize($w=0, $h=0, $keyname = ""){
        if($w > 0 && $h > 0) {
            $resize = $this->resize;
            $resize[$keyname] = [$w, $h];
            $this->resize = $resize;
        }
        return $this;
    }

    /**
     * 生成缩列图
     * @param $im
     * @param $maxwidth
     * @param $maxheight
     * @param $filename
     */
    private function resizeImage($im, $w, $h, $filename){
        if (file_exists($filename)) {
            unlink($filename);
        }
        $width = imagesx($im);
        $height = imagesy($im);
        if(($w && $width > $w) || ($h && $height > $h)){
            if($w && $width > $w){
                $widthratio = $w/$width;
                $RESIZEWIDTH=true;
            }
            if($h && $height > $h){
                $heightratio = $h/$height;
                $RESIZEHEIGHT=true;
            }
            if($RESIZEWIDTH && $RESIZEHEIGHT){
                if($widthratio < $heightratio){
                    $ratio = $widthratio;
                }else{
                    $ratio = $heightratio;
                }
            }elseif($RESIZEWIDTH){
                $ratio = $widthratio;
            }elseif($RESIZEHEIGHT){
                $ratio = $heightratio;
            }
            $newwidth = $width * $ratio;
            $newheight = $height * $ratio;
            if(function_exists("imagecopyresampled")){
                $newim = imagecreatetruecolor($newwidth, $newheight);
                imagecopyresampled($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            }else{
                $newim = imagecreate($newwidth, $newheight);
                imagecopyresized($newim, $im, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            }
            ImageJpeg ($newim, $filename);
            ImageDestroy ($newim);
        }else{
            ImageJpeg ($im, $filename);
        }
    }

    /**
     * 运行文件
     * @param $is_save_src 是否保存源文件
     */
    public function run($is_save_src = false){
        if(empty($_FILES)) return $this;
        $request = Request();
        $files = [];
        foreach ($_FILES as $key=>$val) {
            $name = $val['name'];
            $pos = strrpos($name, ".");
            if($pos > 0) {
                $file_ext = strtolower(trim(substr($name, $pos)));
            }else{
                $file_ext = "";
            }
            $time = time();
            $filename = $this->filename;
            $path = $this->path;
            if(empty($filename)){
                $path .= "/".date("Ym/d", $time);
                $file_base = $time."_".str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
            }else{
                $file_base = $filename;
            }
            $file = $file_base.$file_ext;
            if($is_save_src || $pos === false || empty($this->resize)) {
                $files[$key]["file"] = $path."/".$file;
                $request->file($key)->storeAs($path, $file, 'public');
            }else{
                $dir = $this->save_path.$path."/";
                !is_readable($dir) && $this->xfile->mkdir($dir);
            }
            if($pos > 0) {
                $type = $val['type'];
                $tmp_name = $val['tmp_name'];
                if ($val['size']) {
                    if ($type == "image/pjpeg" || $type == "image/jpeg") {
                        $im = imagecreatefromjpeg($tmp_name);
                    } elseif ($type == "image/x-png" || $type == "image/png") {
                        $im = imagecreatefrompng($tmp_name);
                    } elseif ($type == "image/gif") {
                        $im = imagecreatefromgif($tmp_name);
                    }
                    if ($im) {
                        foreach ($this->resize as $keyname => $vlist) {
                            list($w, $h) = $vlist;
                            if(is_numeric($keyname)) $keyname = "{$w}_{$h}";
                            $b_file = $path."/".$file_base."_{$w}_{$h}".$file_ext;
                            $thumb_path = $this->save_path.$b_file;
                            $this->resizeImage($im, $w, $h, $thumb_path);
                            $files[$key][$keyname] = $b_file;
                        }
                        ImageDestroy($im);
                    }
                }
            }
        }
        $this->files = $files;
        return $this;
    }

    /**
     * 获取浏览文件路径
     * @param null $keynamme
     * @return array
     */
    public function urls($keynamme = "", $is_real = false){
        $retfiles = [];
        if(empty($this->files)) return $retfiles;
        $files = $this->files;
        $save_path = $this->save_path;
        $surl = rtrim(trim(Storage::url("")), "/") . "/";
        if(empty($keynamme)){
            if($is_real){
                foreach ($files as $key=>$val){
                    foreach ($val as $k=>$v){
                        $v = ltrim(trim($v), "/");
                        $retfiles[$key][$k] = [$surl.$v, $save_path.$v];
                    }
                }
            }else{
                foreach ($files as $key=>$val){
                    foreach ($val as $k=>$v){
                        $v = ltrim(trim($v), "/");
                        $retfiles[$key][$k] = $surl.$v;
                    }
                }
            }
        }elseif(!empty($files[$keynamme])){
            $fileinfo = $files[$keynamme];
            if($is_real){
                foreach ($fileinfo as $key=>$val){
                    $val = ltrim(trim($val), "/");
                    $retfiles[$key] = [$surl.$val, $save_path.$val];
                }
            }else{
                foreach ($fileinfo as $key=>$val){
                    $val = ltrim(trim($val), "/");
                    $retfiles[$key] = $surl.$val;
                }
            }
        }
        return $retfiles;
    }
}
