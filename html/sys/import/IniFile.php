<?php

class IniFile {

    function __construct($path = "", $group_name = "")
    {
        $this->xfile = import("XFile");
        $this->setPath($path);
        $this->setGroupName($group_name);
        $this->chgarr = ["\n", "\t", "\e", "\f", "\r", "\v"];
    }

    public function xFile(){
        return $this->xfile;
    }

    /**
     * 设置路径
     * @param string $path
     * @return $this
     */
    public function setPath($path = ""){
        $path = trim($path);
        $this->path = $path;
        return $this;
    }

    /**
     * 设置空文件
     */
    private function setFileEmpty(){
        if(!empty($this->path) && !is_file($this->path)){
            $this->xfile->write($this->path, "");
        }
    }

    /**
     * 设置下标
     * @param string $keyname
     * @return $this
     */
    public function setGroupName($group_name = ""){
        $group_name = strtolower(trim($group_name));
        empty($group_name) && $group_name = 'default';
        $this->group_name = $group_name;
        return $this;
    }

    /**
     * 获取下标
     */
    public function getGroupName(){
        return $this->group_name;
    }

    /**
     * 写入到文件当前分组
     * @param $data
     * @param bool $is_add
     */
    public function write($data, $is_add = true) {
        if(!is_array($data)) return $this;
        $new_data = [];
        foreach ($data as $key=>$val){
            if(is_string($val) || is_numeric($val)){
                $new_data[$key] = $val;
            }elseif(is_array($val) || is_object($val)){
                $new_data[$key] = json_encode($val, JSON_UNESCAPED_UNICODE);
            }elseif(is_bool($val)){
                if($val){
                    $new_data[$key] = 'true';
                }else{
                    $new_data[$key] = 'false';
                }
            }
        }
        if(empty($new_data)) return $this;
        $this->writeAll([
            $this->group_name => $new_data
        ], $is_add);
        return $this;
    }

    /**
     * 写入到文件
     * @param $data
     * @param bool $is_add 是否仅添加
     * @return string
     */
    public function writeAll($data, $is_add = true) {
        if(!is_array($data)) return $this;
        $string = '';
        $ini_data = [];

        if($is_add){
            $ini_data = $this->readAll();
        }

        foreach ($data as $key => $val){
            $key = strtolower(trim($key));
            if(is_array($val) && is_string($key)){
                foreach ($val as $k => $v){
                    $k = strtolower(trim($k));
                    $ini_data[$key][$k] = $v;
                }
            }
        }
        ksort($ini_data);
        $chgarr = $this->chgarr;
        $chgkey = $chgarr;
        $chgkey[] = " ";
        if(isset($ini_data['default'])){
            $def = ['default' => $ini_data['default']];
            unset($ini_data['default']);
            $ini_data = array_merge($def, $ini_data);
        }
        foreach(array_keys($ini_data) as $key) {
            if($this->inArray($key, $chgkey)) continue;
            $string .= '['.$key."]\n";
            $string .= $this->writeGetString($ini_data[$key], '')."\n";
        }
        $this->setFileEmpty();
        file_put_contents($this->path, $string);
        return $this;
    }

    private function inArray($key, $arr){
        $bool = false;
        foreach ($arr as $chr) {
            if(strpos($key, $chr) !== false){
                $bool = true;
                break;
            }
        }
        return $bool;
    }
    /**
     *  write get string
     */
    private function writeGetString(& $ini, $prefix) {
        if(!is_array($ini)) return "";
        $string = '';
        ksort($ini);
        $chgarr = $this->chgarr;
        $chgkey = $chgarr;
        $chgkey[] = " ";
        foreach($ini as $key => $val) {
            $key = strtolower(trim($key));
            if($this->inArray($key, $chgkey)) continue;
            if (is_array($val)) {
                $string .= $this->writeGetString($ini[$key], $prefix.$key.'.');
            } else {
                $val = str_replace("\r", "", $val);
                $val = str_replace("\n", "[\\n]", $val);
                $tv = $this->setValue($val);
                $string .= $prefix.$key.' = '.$tv."\n";
            }
        }
        return $string;
    }

    /**
     *  manage keys
     */
    private function setValue($val) {
        if ($val === true) { return 'true'; }
        else if ($val === false) { return 'false'; }
        return $val;
    }

    public function read($keyname = "") {
        return $this->readAll($this->group_name, $keyname);
    }
    /**
     * 读取所有信息
     * @param string $group_name
     * @param string $key_name
     * @return array|mixed|string
     */
    public function readAll($group_name = "", $key_name = "") {
        $ini = array();
        if(empty($this->path) || !is_file($this->path)){
            $lines = [];
        }else{
            $lines = file($this->path);
        }
        $section = 'default';
        $multi = '';
        foreach($lines as $line) {
            if (substr($line, 0, 1) !== ';') {
//                $line = str_replace("\r", "", str_replace("\n", "", $line));
                $key = "";
                if (preg_match('/^\[(.*)\]/', $line, $m)) {
                    $section = $m[1];
                } else if ($multi === '' && preg_match('/^([a-z0-9_.\[\]-]+)\s*=\s*(.*)$/i', $line, $m)) {
                    $key = $m[1];
                    $val = $m[2];
                    $val = str_replace("[\\n]", "\n", $val);
                    if (substr($val, -1) !== "\\") {
                        $val = trim($val);
                        $this->manageKeys($ini[$section], $key, $val);
                        $multi = '';
                    } else {
                        $multi = substr($val, 0, -1)."\n";
                    }
                } else if ($multi !== '') {
                    if (substr($line, -1) === "\\") {
                        $multi .= substr($line, 0, -1)."\n";
                    } else {
                        $this->manageKeys($ini[$section], $key, $multi.$line);
                        $multi = '';
                    }
                }
            }
        }

        $buf = get_defined_constants(true);
        $consts = array();
        foreach($buf['user'] as $key => $val) {
            $consts['{'.$key.'}'] = $val;
        }
        array_walk_recursive($ini, array('IniFile', 'replaceConsts'), $consts);
        $group_name = strtolower(trim($group_name));
        $key_name = strtolower(trim($key_name));
        if(!empty($group_name)){
            if(!isset($ini[$group_name])){
                return [];
            }
            $inig = $ini[$group_name];
            if(!empty($key_name)){
                if(!is_array($inig) || !isset($inig[$key_name])){
                    return "";
                }
                return $inig[$key_name];
            }
            return $inig;
        }
        return $ini;
    }
    /**
     *  manage keys
     */
    private function getValue($val) {
        if (preg_match('/^-?[0-9]$/i', $val)) { return intval($val); }
        else if (strtolower($val) === 'true') { return true; }
        else if (strtolower($val) === 'false') { return false; }
        else if (preg_match('/^"(.*)"$/i', $val, $m)) { return $m[1]; }
        else if (preg_match('/^\'(.*)\'$/i', $val, $m)) { return $m[1]; }
        return $val;
    }
    /**
     *  manage keys
     */
    private function getKey($val) {
        if (preg_match('/^[0-9]$/i', $val)) { return intval($val); }
        return $val;
    }
    /**
     *  manage keys
     */
    private function manageKeys(& $ini, $key, $val) {
        if (preg_match('/^([a-z0-9_-]+)\.(.*)$/i', $key, $m)) {
            $this->manageKeys($ini[$m[1]], $m[2], $val);
        } else if (preg_match('/^([a-z0-9_-]+)\[(.*)\]$/i', $key, $m)) {
            if ($m[2] !== '') {
                $ini[$m[1]][$this->getKey($m[2])] = $this->getValue($val);
            } else {
                $ini[$m[1]][] = $this->getValue($val);
            }
        } else {
            $ini[$this->getKey($key)] = $this->getValue($val);
        }
    }
    /**
     *  replace utility
     */
    private function replaceConsts(& $item, $key, $consts) {
        if (is_string($item)) {
            $item = strtr($item, $consts);
        }
    }

    /**
     * 获取路径目录
     * @param $top_path
     * @return array
     */
    public function getTree($top_path)
    {
        $xfile = $this->xfile;
        $topdirs = $xfile->getDirs($top_path);
        $dirs = [];
        foreach ($topdirs as $dir) {
            if (!in_array($dir, ['.git', '.idea'])) {
                $cdirs = $xfile->getAllDirs($top_path . DIRECTORY_SEPARATOR . $dir);
                $dirs[] = $dir;
                foreach ($cdirs as $cdir) {
                    $dirs[] = $dir . DIRECTORY_SEPARATOR . $cdir;
                }
            }
        }

        $infos = [];
        foreach ($dirs as $dir) {
            $filename = $top_path . $dir . "/_.ini";
            if (is_file($filename)) {
                $ini = $this->setPath($filename)->readAll();
                $info = [];
                $px = $ini['push_xxyj'];
                if ($ini['info']['status'] && is_array($ini['push_xxyj'])) {
                    $month = trim($px['month']);
                    $day = trim($px['day']);
                    $week = trim($px['week']);
                    $hour = trim($px['hour']);
                    $minute = trim($px['minute']);
                    $second = trim($px['second']);
                    $dvarr = [];
                    !empty($month) && $dvarr[] = $month;
                    !empty($day) && $dvarr[] = $day;
                    !empty($week) && $dvarr[] = $week;
                    !empty($hour) && $dvarr[] = $hour;
                    !empty($minute) && $dvarr[] = $minute;
                    !empty($second) && $dvarr[] = $second;
                    if(!empty($dvarr)){
                        if ($ini['push_email']['status']) {
                            $info[] = 'email';
                        }
                        if ($ini['push_qywx']['status']) {
                            $info[] = 'qywx';
                        }
                        empty($info) && $px['status'] && $info[] = 'run';
                        $dev = $ini['info']['dev'];
                        //当开发模式设置不为空时返回
                        if (!empty($info) && in_array($dev, ['sql', 'api', 'code', 'python', 'java'])) {
                            $is_ok = true;
                            if(!in_array($dev, ['code']) && empty($ini[$dev])) {
                                $is_ok = false;
                            }
                            if($is_ok){
                                $start_date = trim($px['start_date']);
                                $end_date = trim($px['end_date']);
                                $run_date = trim($px['run_date']);
                                $send_second = trim($px['send_second']);
                                $arr = [
                                    $month,
                                    $day,
                                    $week,
                                    $hour,
                                    $minute,
                                    $second,
                                    $start_date,
                                    $end_date,
                                    $run_date,
                                    $send_second
                                ];
                                foreach ($arr as $key => $val) {
                                    $arr[$key] = str_replace("@", "_", $val);
                                }
                                $dtstr = $ini['info']['id'] . "@" . $dev . "@";
                                $strlink = substr(md5($dtstr . md5(implode("@", $arr))), 8, 16);
                                $infos[implode("|", $info)][] = $dir . "@" . $dtstr . $strlink . "@" . date("Y-m-d H:i:s", filemtime($filename));
                            }
                        }
                    }
                }
            }
        }
        return $infos;
    }
}