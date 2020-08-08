<?php

/**
 * 接口配置系统
 * Class ApiConf
 */
class ApiConf {
    function __construct($obj=null, $flag=""){
        $this->obj = $obj;
        $this->type = $obj->tpl_type;
        $this->flag = $flag;
        $this->url_conf = rtrim(trim(getenv("URL_CONF")), "/") . "/";
        $this->user_test = "admin_sys_relation_user_info";
    }

    /**
     * 复制文件夹
     * @param string $dir_src 原路径
     * @param string $dir_new 新路径
     */
    public function copy($field, $dir_src="", $dir_new="", $ini_file=""){
        $dir_src = strtolower(trim(trim($dir_src), "/\\")); //原目录
        if(empty($dir_src)) {
            EXITJSON(0, "原目录不能为空");
        }

        $dir_new = strtolower(trim(trim($dir_new), "/\\")); //新目录
        if(empty($dir_new)) {
            EXITJSON(0, "新目录不能为空");
        }

        if($dir_src == $dir_new){
            EXITJSON(0, "原目录和新目录不能相同");
        }

        $dir_src_len = strlen($dir_src);
        $dir_new_len = strlen($dir_new);
        if($dir_new_len > $dir_src_len){
            $sub_dir_l = substr($dir_new, 0, $dir_src_len);
            $sub_dir_r = substr($dir_new, $dir_src_len);
            if($dir_src == $sub_dir_l && $sub_dir_r[0] == '/'){
                EXITJSON(0, "新目录不能复制到原目录内");
            }
        }

        $data = $this->getHttpData($this->url_conf."{$this->flag}.copy", [
            "dir_src" => $dir_src,
            "dir_new" => $dir_new
        ]);
        $json = json_decode($data, true);
        if(empty($json)){
            EXITJSON(0, $data);
        }else{
            $jdata = $json['data'];
            $sdata = $this->obj->getDataToDir([$jdata], "sql", $ini_file)[1];
            $ret_list = $this->initObjNextList([[$jdata], $sdata], $jdata['__pdir__'], $field);
            $ret_list['md5'] = $this->__getMd5($dir_new);
            EXITJSON($json['code'], $json['msg'], $ret_list);
        }
    }

    private function __setInfo(&$info){
        $gdate = getdate();
        $info['date'] =  [
            'y' => $gdate['year'],
            'm' => $gdate['mon'],
            'd' => $gdate['mday'],
            'h' => $gdate['hours'],
            'i' => $gdate['minutes'],
            's' => $gdate['seconds'],
            'time_stamp' => $gdate['0']
        ];
    }

    // 获取数据库用户信息
    public function getUserInfoUsercode($usercode, $is_save=false, $password='', $is_check=false){
        $data = $this->getHttpData($this->url_conf."user.login", [
            "username" => $usercode,
            "password" => $password,
            "is_save" => $is_save ? 'yes' : 'no',
            "is_check" => $is_check ? 'yes' : 'no', //是否验证
            "crf" => $_POST['crf']
        ]);
        $json = json_decode($data, true);
        if(empty($json)){
            return [
                'code' => 0,
                'msg' => $data
            ];
        }else if($json['code'] == 1 && isset($json['data'])){
            $json = $json['data'];
        }
        return $json;
    }

    // 获取配置信息
    public function getConfig($group='', $keyname=''){
        $post = [];
        $is_array = true;
        if(!empty($group)){
            $group = trim($group);
            if(!empty($group)){
                $post['group'] = $group;
                if(!empty($keyname)) {
                    $keyname = trim($keyname);
                    if(!empty($keyname)) {
                        $post['keyname'] = $keyname;
                        $is_array = false;
                    }
                }
            }
        }
        $data = $this->getHttpData($this->url_conf."config.get", $post);
        $json = json_decode($data, true);
        if(empty($json)){
            return "";
        }else if($json['code'] == 1 && isset($json['data'])){
            $json = $json['data'];
        }else if($is_array){
            $json = [];
        }else{
            $json = '';
        }
        return $json;
    }

    // 设置配置信息
    public function setConfig($group='', $keyname='', $value=''){
        $info = [];
        if(is_array($group)){
            $info = $group;
        } elseif (!empty($group) && (is_numeric($group) || is_string($group))){
            $group = $group."";
            if(is_array($keyname)){
                $info[$group] = $keyname;
            } elseif (!empty($keyname) && (is_numeric($keyname) || is_string($keyname))){
                $keyname = $keyname."";
                $info[$group][$keyname] = $value;
            }
        }
        if(empty($info)){
            return false;
        }
        $post = [];
        foreach ($info as $key=>$val){
            if(!is_array($val)){
                continue;
            }
            foreach ($val as $k=>$v){
                $post[$key][$k] = $v;
            }
        }
        if(empty($post)){
            return false;
        }
        $this->getHttpData($this->url_conf."config.set", [
            'data' => json_encode($post, true)
        ]);
        return true;
    }

    // 获取模拟用户信息
    public function getUserInfo(){
        $cacheid = $this->user_test;
        $info = Cache::get($cacheid);
        if(empty($info)){
            $obj = $this->obj;
            if(!empty($obj)){
                $usercode = trim($this->getConfig('test', 'usercode'));
                if(!empty($usercode)){
                    $info = $this->getUserInfoUsercode($usercode);
                    unset($info['date']);
                    $this->setUserInfo($info);
                }
            }
        }
        $this->__setInfo($info);
        return $info;
    }

    // 设置模拟用户信息
    public function setUserInfo($info){
        Cache::put($this->user_test, $info, 60 * 60);
    }

    // 清空模拟用户信息
    public function clearUserInfo(){
        Cache::forget($this->user_test);
    }

    /**
     * 获取目录列表
     * @param string $dir
     * @return array|mixed
     */
    public function _list($dir = ""){
        $data = $this->getHttpData($this->url_conf."{$this->flag}.list", ['dir' => $dir]);
        $json = json_decode($data, true);
        if(empty($json)) {
            return [
                'code' => 0,
                'msg' => $data
            ];
        }elseif($json['code'] == 0){
            return $json;
        }else{
            $jdata = $json['data'];
            if($this->flag === 'relation'){
                foreach ($jdata as $key=>$val){
                    $d_name = $val['__dir_name__'];
                    $d_show_dir = trim($dir."/".$d_name, "/");
                    $jdata[$key]['__dir_name__'] = "<a href='relation/test?__dir__={$d_show_dir}' target='_blank'>{$d_name}</a>";
                }
            }
            $jkv = [];
            $i = 0;
            $jlist = [];
            foreach ($jdata as $key=>$val){
                $jdata[$key]['__dir__'] = $key;
                $jkv[$key] = $i;
                $jlist[$i] = $jdata[$key];
                $i ++;
            }

            $show_list = $this->obj->getDataToDir($jlist)[1];
            $sdata = [];
            foreach ($jdata as $key => $val) {
                $sdata[$key] = $show_list[$jkv[$key]];
            }
            $json['data'] = [$jdata, $sdata];
            return $json;
        }
    }

    /**
     * 获取目录树
     * @return array|mixed
     */
    public function tree(){
        $data = $this->getHttpData($this->url_conf."{$this->flag}.tree");
        $json = json_decode($data, true);
        if(empty($json)){
            return [
                'code' => 0,
                'msg' => $data
            ];
        }else{
            if(is_array($json['data'])) {
                $jdata = $json['data'];
                $jkv = [];
                $i = 0;
                $jlist = [];
                foreach ($jdata as $key => $val) {
                    foreach ($val as $k => $v) {
                        $jdata[$key][$k]['__dir__'] = $k;
                        $jkv[$key][$k] = $i;
                        $jlist[$i] = $jdata[$key][$k];
                        $i ++;
                    }
                }
                $show_list = $this->obj->getDataToDir($jlist)[1];
                $sdata = [];
                foreach ($jdata as $key => $val) {
                    foreach ($val as $k => $v) {
                        $sdata[$key][$k] = $show_list[$jkv[$key][$k]];
                    }
                }
                $json['data'] = [$jdata, $sdata];
            }
            return $json;
        }
    }

    /**
     * 去除目录键值
     * @param $tree_list
     * @param array $ret_list
     * @return array
     */
    private function getDirTreeDelKey($tree_list, &$ret_list=[]){
        foreach ($tree_list as $key=>$val){
            if(isset($val['children'])){
                $val['children'] = $this->getDirTreeDelKey($val['children']);
            }
            $ret_list[] = $val;
        }
        return $ret_list;
    }

    /**
     * 获取目录扩展信息
     * @param string $flag
     * @param string $field
     * @return array
     */
    public function getDirTree($flag='', $field='', $base_dir=''){
        $first_field = "";
        $is_array = false;
        if(is_string($field)){
            $fieldstr = $field;
            $first_field = $field;
        }elseif(is_array($field)){
            $fieldstr = implode(",", $field);
            if(count($field) > 0){
                $first_field = $field[0];
            }
            $is_array = true;
        }else{
            $fieldstr = "";
        }
        $sends = ['field' => $fieldstr];
        if(!empty($base_dir)){
            $sends['base_dir'] = $base_dir;
        }
        $data = $this->getHttpData($this->url_conf."{$flag}.tree", $sends);
        $json = json_decode($data, true);
        $return = [];
        $jdata = $json['data'];
        if(!is_array($jdata)) {
            return $return;
        }
        ksort($jdata);
        $tree_list = [];
        foreach ($jdata as $key=>$val){
            ksort($val);
            $tmp_list = &$tree_list;
            if(!empty($key)) {
                $dirs = explode("/", $key);
                foreach ($dirs as $dir) {
                    if (!isset($tmp_list[$dir])) {
                        $tmp_list[$dir] = [];
                    }
                    if (!isset($tmp_list[$dir]['children'])) {
                        $tmp_list[$dir]['children'] = [];
                    }
                    $tmp_list = &$tmp_list[$dir]['children'];
                }
            }
            empty($key) ? $prev_key = "" : $prev_key = $key . "/";
            foreach ($val as $k=>$v){
                $title = $v[$first_field];
                if(empty($title)){
                    $title = $k;
                }else{
                    $title = "{$k}&nbsp;&nbsp;<span class=\"is_tree\">{$title}</span>";
                }
                $tmp_list[$k]['id'] = $prev_key . $k;
                $tmp_list[$k]['title'] = $title;
                if($is_array){
                    foreach ($field as $kk=>$vv){
                        $tval = $v[$vv];
                        if(!empty($tval)){
                            $tval = trim($tval);
                            if(!empty($tval) && !in_array($vv, ['id', 'title', 'children', 'checked', 'spread'])){
                                $tmp_list[$k][$vv] = $tval;
                            }
                        }
                    }
                }
            }
        }
        return $this->getDirTreeDelKey($tree_list);
    }

    /**
     * 数据编辑
     * @param array $data
     * @return array|mixed
     */
    public function edit($data = []){
        $vconfig = $this->obj->apifun->vimconfig['handle'];
        foreach ($data as $key=>$val){
            $vk = $vconfig[$key];
            $vk_type = $vk['type'];
            if($vk_type == 'dir'){
                $val = str_replace("\\", "/", $val);
                $reset_valarr = [];
                $valarr = explode("/", $val);
                foreach ($valarr as $va){
                    $va = trim($va);
                    if($va !== ''){
                        $reset_valarr[] = $va;
                    }
                }
                $data[$key] = implode("/", $reset_valarr);
            }elseif($vk_type === 'password'){
                if($vk['md5']){
                    $data[$key] = "";
                }elseif($vk['aes']){
                    // aes解密
                    if(!isset($xcrypto)){
                        $xcrypto = import('XCrypto');
                    }
                    $data[$key] = $xcrypto->aesEncrypt($data[$key]);
                }elseif($vk['des']){
                    // des解密
                    if(!isset($xcrypto)){
                        $xcrypto = import('XCrypto');
                    }
                    $data[$key] = $xcrypto->desEncrypt($data[$key]);
                }
            }
        }
        $src_dir = $this->getDir();
        $dir = $data['__dir__'];
        unset($data['__dir__']);
        if($this->type == 'edit'){
            empty($dir) && $dir = $src_dir;
        }
        $set_data = [];
        $set_data['json'] = json_encode($data, true);
        $set_data['type'] = $this->type;
        $set_data['dir'] = $dir;
        $set_data['src_dir'] = $src_dir;
        $infostr = $this->getHttpData($this->url_conf."{$this->flag}.edit", $set_data);
        $info = json_decode($infostr, true);
        if(empty($info)){
            return [
                'code' => 0,
                'msg' => $infostr
            ];
        }
        $info_data = $info['data'];
        if($this->flag == 'relation'){
            $_dir = $info_data['__dir__'];
            $_dir_name = $info_data['__dir_name__'];
            $info['data']['__dir_name__'] = "<a href='relation/test?__dir__={$_dir}' target='_blank'>{$_dir_name}</a>";
        }
        return $info;
    }

    /**
     * 获取md5字符串
     * @param string $dir
     * @return bool|string
     */
    private function __getMd5($dir = ""){
        return substr(md5($dir), 8, 8);
    }

    /**
     * 获取列表信息
     * @param $data
     * @param $pdir
     * @param $field
     * @return array
     */
    private function initObjNextList($data, $pdir, $field){
        $is_href = false;
        if($this->flag == 'relation'){
            $is_href = true;
        }
        $pklist = [];
        $srclist = [];
        $showlist = [];
        $sorts = [];
        if(empty($pdir)){
            $pdir_ok = "";
        }else{
            $pdir_ok = "{$pdir}/";
        }
        list($d0, $d1) = $data;
        foreach ($d0 as $key=>$val){
            $dir = $pdir_ok.$val['__dir__'];
            $md5 = $this->__getMd5($dir);
            $sorts[] = $md5;
            $pklist[$md5] = $dir;
            $val['__dir__'] = $dir;
            $val['__pdir__'] = $pdir;
            $show_val = $d1[$key];
            $show_val['__dir__'] = $dir;
            $show_val['__pdir__'] = $pdir;
            if($is_href){
                $_dir = $dir;
                $_dir_name = $val['__dir_name__'];
                $show_val['__dir_name__'] = "<a href='relation/test?__dir__={$_dir}' target='_blank'>{$_dir_name}</a>";
            }
            foreach ($val as $k=>$v){
                $vlist = $field[$k]['list'];
                if(is_array($vlist)){
                    isset($vlist[$v]) && $show_val[$k] = $vlist[$v];
                }
            }
            $srclist[$md5] = $val;
            $showlist[$md5] = $show_val;
        }
        return [
            'pks' => $pklist,
            'sort' => $sorts,
            'src' => $srclist,
            'show' => $showlist
        ];
    }

    // 删除HTML代码中的BODY以外的代码
    private function __webExitFormatHtml($html){
        $pos = strpos(strtolower($html), "<body");
        if($pos !== false){
            $html = substr($html, $pos);
            $pos = strpos(strtolower($html), ">");
            if($pos !== false){
                $html = substr($html, $pos + 1);
            }
        }
        $pos = strpos(strtolower($html), "</body>");
        if($pos !== false){
            $html = substr($html, 0, $pos);
        }
        return $html;
    }

    /**
     * 访问接口错误时退出页面
     * @param int $http_code
     * @param string $text
     */
    private function __webExit($http_code = 0, $text = ""){
        $http_codes = [
            404 => '页面找不到',
            501 => '服务器错误',
            502 => '服务器错误',
            503 => '服务器错误'
        ];
        if($http_code !== 200) {
            $msg = "未知错误";
            if (isset($http_codes[$http_code])) {
                $msg = $http_codes[$http_code];
            }
            $text = $this->__webExitFormatHtml($text);
            $ret_data = [
                'code' => $http_code,
                'msg' => $msg,
                'data' => $this->__webExitFormatHtml($text)
            ];
            if(count($_POST) > 0){
                EXITJSON(0, "<div>{$http_code}: {$msg}</div><div>{$text}</div>");
            }else {
                echo view("sys.public.layout.tpl.error.page.html.tpl", $ret_data);
            }
            exit();
        }
    }

    /**
     * 退出页面状态提示
     * @param $data
     * @param int $code
     * @param string $msg
     */
    public function webExit($data, $code = 400, $msg = "请求无效"){
        if(count($_POST) > 0){
            if(is_array($data)){
                $msg = implode('\n', $data);
            }else{
                $msg = $this->__webExitFormatHtml($data);
            }
            EXITJSON(0, $msg);
        } else {
            if(is_string($data)){
                $data = $this->__webExitFormatHtml($data);
            }
            if(is_array($data)){
                if(count($data) == 1){
                    $data = $data[0];
                }
            }
            echo view("sys.public.layout.tpl.error.page.html.tpl", [
                'code' => $code,
                'msg' => $msg,
                'data' => $data
            ]);
            exit();
        }
    }

    /**
     * 获取退出页面状态提示
     * @param $data
     * @param int $code
     * @param string $msg
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getWebExit($data, $code = 400, $msg = "请求无效"){
        if(is_string($data)){
            $data = $this->__webExitFormatHtml($data);
        }
        if(count($data) == 1){
            $data = $data[0];
        }
        return view("sys.public.layout.tpl.error.page.div.tpl", [
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        ]);
    }

    /**
     * _init模块
     * 初始化模块
     */
    public function initObj(){
        $obj = $this->obj;

        $tpl_type = $obj->tpl_type;
        if($tpl_type == 'html'){
            return;
        }

        if($tpl_type == 'list') {
            $tpl_type = "html";
            $obj->tpl_type = $tpl_type;
            $obj->tpl_type = $tpl_type;
            $obj->apifun->type = $tpl_type;
            $obj->apifun->vim->tpl_type = $tpl_type;
            $obj->config['layout'] = 'public/list';
        }else{
            $obj->config['layout'] = false;
        }

        if($obj->isPost()){
            $o_type = $obj->tpl_type;
            $field = $obj->apifun->vimconfig_field;
            if($o_type == 'html') {
                if ($_POST['type'] == 'all') {
                    $tree_info = $this->tree();
                    $data = $tree_info['data'];
                    if (empty($tree_info)) {
                        EXITJSON(0, "配置接口服务出错");
                    }
                    if (!is_array($data)) {
                        EXITJSON($tree_info['code'], $tree_info['msg']);
                    }
                    $d_list = [];
                    list($d0, $d1) = $data;
                    foreach ($d0 as $key => $val) {
                        if (empty($key)) {
                            $kmd5 = "top";
                        } else {
                            $kmd5 = $this->__getMd5($key);
                        }
                        $d_list[$kmd5] = $this->initObjNextList([$val, $d1[$key]], $key, $field);
                    }
                    EXITJSON($tree_info['code'], $tree_info['msg'], $d_list);
                } else {
                    $pdir = $_POST['value'];
                    $info = $this->_list($pdir);
                    $data = $info['data'];
                    if (empty($info)) {
                        EXITJSON(0, "配置接口服务出错");
                    }
                    if (!is_array($data)) {
                        EXITJSON($info['code'], $info['msg']);
                    }
                    EXITJSON($info['code'], $info['msg'], $this->initObjNextList($data, $pdir, $field));
                }
            }elseif($o_type == 'delete') {
                $pdata = $_POST['data'];
                if(is_array($pdata)){
                    $dirs = implode(",", $pdata);
                    $data = $this->getHttpData($this->url_conf."{$this->flag}.delete", ['dirs' => $dirs]);
                    $djson = json_decode($data, true);
                    if(empty($djson)){
                        EXITJSON(0, $data);
                    }
                    EXITJSON($djson['code'], $djson['msg']);
                }
                EXITJSON(1, "删除成功");
            }else{
                $info = $this->edit($_POST);
                if(empty($info)){
                    EXITJSON(0, "配置接口服务出错");
                }
                if($info['code'] == 0){
                    EXITJSON(0, $info['msg']);
                }
                $idata = $info['data'];
                $dir = $idata['__dir__'];
                $md5 = $this->__getMd5($dir);
                $pks = [
                    [
                        'md5' => $md5,
                        'pk' => $dir
                    ]
                ];

                $show_data = $this->obj->getDataToDir([$idata])[1][0];
                foreach ($idata as $k=>$v){
                    $vlist = $field[$k]['list'];
                    if(is_array($vlist)){
                        isset($vlist[$v]) && $show_data[$k] = $vlist[$v];
                    }
                }
                EXITJSON($info['code'], $info['msg'], [
                    'pks' => $pks,
                    'src' => [
                        $md5 => $idata
                    ],
                    'show' => [
                        $md5 => $show_data
                    ]
                ]);
            }
        }

        $tpl_type = $obj->tpl_type;
        if($tpl_type == 'edit') {
            $dir = $this->getDir();
            $info = $infostr = $this->getHttpData($this->url_conf."{$this->flag}.get", ['dir' => $dir]);
            $infoarr = json_decode($info, true);
            if(!empty($infoarr) && $infoarr['code'] == 1){
                $detail = $infoarr['data'];
            }else{
                $detail = [];
            }
            $detail['__dir__'] = $dir;
            $vh = $this->obj->apifun->vimconfig_handle;
            foreach ($detail as $key=>$val){
                $vh_info = $vh[$key];
                if(isset($vh_info)){
                    $vh_type = $vh_info['type'];
                    if(in_array($vh_type, ['selects', 'checkbox'])){
                        $json = json_decode($val, true);
                        if(!empty($json)){
                            $detail[$key] = implode(",", $json);
                        }
                    }
                }
            }
            $obj->setView('field', $detail);
            $obj->apifun->data_ext = [
                "_" => [
                    $detail
                ],
                "src" => [
                    $detail
                ],
            ];
        }else{
            $data = $this->_list();
            if($data['code'] == 1){
                $list = $data['data'][0];
            }else{
                $this->webExit($data['msg']);
            }

            $pklist = [];
            foreach ($list as $key=>$val){
                $pklist[$key] = $val['__dir__'];
            }
            list($srclist, $list) = $data['data'];

            $src_field = $obj->apifun->vimconfig_field;
            $field = [];
            foreach ($src_field as $key=>$val){
                if(is_string($val)){
                    $field[$val] = ['name' => $val];
                }else{
                    $field[$key] = $val;
                }
            }
            $obj->setView('field', $field);
            foreach ($list as $key=>$val){
                if(is_array($val)) {
                    $pklist[$key] = $val['__dir__'];
                    foreach ($val as $k => $v) {
                        $vlist = $field[$k]['list'];
                        if (is_array($vlist)) {
                            isset($vlist[$v]) && $list[$key][$k] = $vlist[$v];
                        }
                    }
                }
            }
            $obj->apifun->data_ext = [
                "_" => [],
                "src" => [],
            ];
        }
        $vim = $obj->apifun->vimconfig;
        $vim['operwidth'] += 50 * 2;
        $new_vim_oper = [];
        $is_insert = false;
        foreach ($vim['oper'] as $key=>$val){
            if($key == 'handle'){
                $new_vim_oper['copy'] = [
                    "name" => "复制",
                    "url" => "/public/copy.add?flag={$this->flag}&tplname={$obj->tplname}",
                    "key" => "dir",
                    "ismax" => false,
                    "width" => 600,
                    "height" => 200
                ];
                $new_vim_oper['add'] = '新增';
                $is_insert = true;
            }
            $new_vim_oper[$key] = $val;
        }
        if(!$is_insert){
            $new_vim_oper['add'] = '新增';
        }
        $vim_batch = [];
        $vim_batch['open_close'] = "全部展开";
        if(is_array($vim['batch'])){
            foreach ($vim['batch'] as $key=>$val){
                $vim_batch[$key] = $val;
            }
        }
        $vim['batch'] = $vim_batch;
        $vim['oper'] = $new_vim_oper;
        $obj->setView('srclist', $srclist);
        $obj->setView('vim', $vim);
        $obj->setView('pklist', $pklist);
        $obj->setView('config', $vim['field']);
        $obj->setView('list', $list);
        $obj->setDataFlag();
    }

    /**
     * 获取目录
     * @return mixed|string
     */
    private function getDir(){
        $pk = $_GET['pk'];
        $dir = "";
        $pkarr = json_decode($pk, true);
        if(is_array($pkarr) && isset($pkarr[0])){
            $dir = $pkarr[0];
        }
        return $dir;
    }

    /**
     * 获取目录列表
     * @param string $dir
     * @return array|mixed
     */
    public function getField($dir = "") {
        $json = $this->getHttpData($this->url_conf . "table.fields", [
            "table_dir" => $dir
        ]);
        $data = json_decode($json, true);
        if(empty($data)){
            return [
                'code' => 0,
                'msg' => $json
            ];
        }
        return $data;
    }

    public function getFieldsInfo($dir = ""){
        $json = $this->getHttpData($this->url_conf . "table.fields_info", [
            "table_dir" => $dir
        ]);
        $data = json_decode($json, true);
        if(empty($data)){
            return [
                'code' => 0,
                'msg' => $json
            ];
        }
        return $data;
    }

    /**
     * 获取表配置信息
     * @param string $dir
     * @return array|mixed
     */
    public function getRelationInfo($dir = ""){
        $json = $this->getHttpData($this->url_conf . "relation.config", [
            "dir" => $dir
        ]);
        $data = json_decode($json, true);
        if(empty($data)){
            return [
                'code' => 0,
                'msg' => $json
            ];
        }
        return $data;
    }

    /**
     * 获取表配置信息
     * @param string $dir
     * @return array|mixed
     */
    public function getRelationInfoWeb($dir = ""){
        $post = [
            "dir" => $dir
        ];
        $path = $_GET['path'];
        if(!empty($path)){
            $path = base64_decode($path);
            if(!empty($path)){
                $post['is_path'] = 'true';
            }
        }
        $json = $this->getHttpData($this->url_conf . "relation.config_web", $post);
        $data = json_decode($json, true);
        if(empty($data)){
            return [
                'code' => 0,
                'msg' => $json,
                'http_code' => 500
            ];
        }
        $data['http_code'] = 200;
        return $data;
    }

    /**
     * 获取表配置信息键值
     * @param $data
     * @return array
     */
    public function getRelationInfoKeyValue($data){
        $ret_data = [];
        if(is_array($data) && is_array($data['table'])){
            $src = $data['table']['src'];
            $list = $data['table']['list'];
            if(!empty($src) && !empty($list)){
                foreach ($src as $key =>$val){
                    $rmk = "";
                    $lv = $list[$key];
                    if(!empty($lv)){
                        if(!empty($lv['f_name'])){
                            $rmk = trim($lv['f_name']);
                        }
                    }
                    if(empty($rmk) && !empty($val['remark'])) {
                        $rmk = trim($val['remark']);
                    }
                    $key_name = $val['name'];
                    $r['id'] = $key_name;
                    $r['title'] = $key_name;
                    if(!empty($rmk)){
                        $r['title'] .= " - ".$rmk;
                    }
                    if(!empty($rmk)){
                        $r['remark'] = $rmk;
                    }
                    $ret_data[] = $r;
                }
            }
        }
        return $ret_data;
    }

    /**
     * 获取表配置信息
     * @param string $dir
     * @param string $sql
     * @return array|mixed
     */
    public function getRelationSqlView($dir='', $sql = ""){
        $json = $this->getHttpData($this->url_conf . "relation.sql_view", [
            "dir" => $dir,
            "sql" => $sql
        ]);
        $data = json_decode($json, true);
        if(empty($data)){
            return [
                'code' => 0,
                'msg' => $json
            ];
        }
        return $data;
    }

    /**
     * 获取其他类型URL信息
     * @param string $dir
     * @param array $post
     * @return array|mixed
     */
    public function getOtherUrl($dir='', $post=[]){
        $json = $this->getHttpData($this->url_conf . $dir, $post);
        $data = json_decode($json, true);
        if(empty($data)){
            return [
                'code' => 0,
                'msg' => $json
            ];
        }
        return $data;
    }

    /**
     * 远程获取数据，GET和POST模式
     * @param $url 指定URL完整路径地址
     * @param $para 请求的数据
     * @param $type GET或POST类型
     * @param null $header 请求头部信息
     * @param bool $output_encoding 输出编码格式，如：utf-8
     * @param bool $iscurl 获取远程信息类型
     * @return bool|mixed|string
     */
    public function getHttpData($url, $para=null, $type=null, $header=NULL, $output_encoding = false, $iscurl = true) {
        $crf = SESSION_ID_MD5;
        if(strpos($url, "?") > 0){
            $url .= "&crf=".$crf;
        }else{
            $url .= "?crf=".$crf;
        }
        if($this->obj->viewdata['is_login']){
            $type = $this->obj->tpl_type;
            $url .= "&menu_dir={$this->obj->tpl_path}&pro={$GLOBALS['DOMAIN_CONFIG']['key']}&is_login=true&type={$type}";
        }
        if ($iscurl) {
            $type = strtolower($type);
            if($type == 'get' || empty($para)){
                if(!empty($para)) {
                    $content = http_build_query($para);
                    if (strpos($url, '?') !== false) {
                        $url .= "&{$content}";
                    } else {
                        $url .= "?{$content}";
                    }
                }
                $curl = curl_init($url);
                if(!empty($header)) curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//获取数据返回
                curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);//在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
            }elseif($type == 'json') {
                $para_string = json_encode($para);
                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($curl, CURLOPT_POSTFIELDS, $para_string);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
                $headernew = [];
                $headernew[] = 'Content-Type: application/json';
                $headernew[] = 'Content-Length: ' . strlen($para_string);
                foreach($header as $val){
                    $headernew[] = $val;
                }
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headernew);
            }else{
                $curl = curl_init();
                curl_setopt($curl, CURLOPT_URL, $url) ;
                if(!empty($header)){
                    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
                }else{
                    curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
                }
                curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
                curl_setopt($curl,CURLOPT_POST, count($para)); // post传输数据
                curl_setopt($curl,CURLOPT_POSTFIELDS, $para);// post传输数据
            }
            curl_setopt($curl, CURLOPT_TIMEOUT, 15);

            $responseText = curl_exec($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
        }else{
            $http_code = 0;
            $responseText = file_get_contents($url);

        }
        //设置编码格式
        if ($output_encoding) {
            $html_encoding = $this->getEncode($responseText);
            $responseText = iconv($html_encoding, $output_encoding, $responseText);
        }

        $this->__webExit($http_code, $responseText);
        return $responseText;
    }

    /**
     * 获取文件编码
     * @param $string
     * @return string
     */
    private static function getEncode($string) {
        return mb_detect_encoding($string, array('ASCII', 'GB2312', 'GBK', 'UTF-8'));
    }
}