<?php

class ApiFile {
    function __construct($path = "")
    {
        $this->path = rtrim(trim($path), "/");
        $this->inifile = import("IniFile", "", "info");
        $this->xfile = $this->inifile->xFile();
    }

    public function getId($path=''){
        if(empty($path)){
            $path = $this->path;
        }else{
            $path = rtrim(trim($path), "/");
        }
        if(empty($path) || !is_dir($path)){
            return 1;
        }
        list($maxid, $ids) = $this->getDirs($path);

        for($i = 1; $i <= $maxid; $i ++){
            if(!isset($ids[$i])){
                return $i;
            }
        }

        return $maxid + 1;
    }

    private function getDirs($path){
        //重新设置重复的文件夹ID
        $inifile = $this->inifile;
        $xfile = $this->xfile;
        $dirs = [];
        foreach($xfile->getDirs($path) as $dir){
            if(!in_array($dir, ['.git', '.idea'])){
                $tdir = $path."/".$dir;
                $dirs[] = $dir;
                foreach ($xfile->getAllDirs($tdir) as $d){
                    $dirs[] = $dir."/".$d;
                }
            }
        }
        $ids = [];
        foreach ($dirs as $dir){
            $tdir = $path."/".$dir."/_.ini";
            if(file_exists($tdir)) {
                $id = $inifile->setPath($tdir)->read("id");
                !is_numeric($id) && $id = 'NULL';
                $time = filectime($tdir);
                $ids[$id][$dir] = $time;
            }
        }

        $redir = [];
        $maxid = 1;
        foreach ($ids as $key=>$val){
            if(count($val) > 1) {
                asort($val);
                array_shift($val);
                foreach ($val as $k=>$v){
                    $redir[] = $k;
                }
            }
            $key > $maxid && $maxid = $key;
        }
        return [$maxid, $ids, $redir, $dirs];
    }

    public function update(){
        $path = $this->path;
        if(empty($path) || !is_dir($path)){
            return;
        }

        $inifile = $this->inifile;
        list($maxid, $ids, $redir, $dirs) = $this->getDirs($path);

        $maxid_pushs = [];
        for($i = 1; $i <= $maxid; $i ++){
            !isset($ids[$i]) && $maxid_pushs[] = $i;
        }

        if(count($dirs) > 0){
            foreach ($dirs as $dir){
                $tdir = $path."/".$dir;
                if(is_dir($tdir)) {
                    $tini = $tdir . "/_.ini";
                    $initext = trim($this->xfile->read($tini));
                    if(empty($initext)){
                        $this->xfile->deleteDir($tdir);
                    }
                }
            }
        }

        if(count($redir) > 0){
            foreach ($redir as $dir){
                if(count($maxid_pushs) > 0){
                    $mid = array_shift($maxid_pushs);
                }else{
                    $maxid ++;
                    $mid = $maxid;
                }
                $tdir = $path."/".$dir."/_.ini";
                $inifile->setPath($tdir)->write([
                    'id' => $mid
                ]);
            }
        }
    }

    /**
     * 获取API链接
     * @return string
     */
    public function getDomain(){
        list($hh, $port) = explode(":", $_SERVER['HTTP_HOST']);
        $hharr = explode(".", $hh);
        $hhlen = count($hharr);
        if($hhlen > 0){
            unset($hharr[0]);
        }
        $domain = implode(".", $hharr);
        !empty($port) && $domain .= ":".$port;
        return $domain;
    }

    /**
     * 获取参数信息
     * @param $param
     * @return string
     */
    private function getParam($param){
        $retparam = "";
        if(!empty($param && (is_string($param) || is_numeric($param)))){
            $param = trim($param, "?");
            if(!empty($param)){
                if($param[0] == '#'){
                    $retparam = $param;
                }else{
                    $retparam = "?".$param;
                }
            }
        }
        return $retparam;
    }

    /**
     * 获取API链接
     * @return string
     */
    public function getUrl($param = "", $user_path=""){
        $domain = $this->getDomain();
        $user_path = trim($user_path, "/");
        $user_path = trim($user_path);
        $upath = (empty($user_path) ? '' : $user_path."/").trim($this->path, "/");
        $returl = "http://{$domain}/".$upath.$this->getParam($param);
        return $returl;
    }

    /**
     * 数组键值小写
     * @param $data
     * @return array
     */
    private function getKeyToLower($data){
        $new_data = [];
        if(!is_array($data)) return $new_data;
        foreach ($data as $key=>$val){
            $key = strtolower(trim($key));
            $new_data[$key] = $val;
        }
        return $new_data;
    }

    /**
     * 获取API页面数据
     * @return string
     */
    public function getHtml($param = ""){
        $pstr = $this->getParam($param);
        $tmpgets = $_GET;
        $gets = [];
        if(!empty($pstr) && $pstr[0] == '?'){
            $pstr = ltrim($pstr, "?");
            $parr = explode("&", $pstr);
            foreach ($parr as $pa){
                $arr = explode("=", $pa);
                if(count($arr) > 1){
                    $gets[$arr[0]] = $arr[1];
                }
            }
        }
        $_GET = $this->getKeyToLower($gets);
        $html = tpl("/api/".$this->path.".html");
        $_GET = $tmpgets;
        return $html;
    }
}