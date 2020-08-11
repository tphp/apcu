<?php

namespace Tphp\Apcu\Controllers;

use \Tphp\Apcu\Controllers\VimController as Vim;

class ApiController
{
    function __construct($tpl, $tplclass) {
        $this->type = $tplclass->tpl_type;
        $this->tpl = $tpl;
        $this->tplclass = $tplclass;
        $this->vim = new Vim();
        $this->base_tpl_path = "";
        if(defined("BASE_TPL_PATH")){
            $this->base_tpl_path = trim(trim(BASE_TPL_PATH, "/"))."/";
        }
        $this->file_formats = [
            'image' => ['gif', 'jpg', 'jpeg', 'png', 'bmp', 'ico'],
            'file' => ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'htm', 'html', 'txt', 'zip', 'rar', 'gz', 'bz2','pdf']
        ];
    }

    private function getPks(){
        $allfield = $this->allfield;
        $pks = [];
        if(!empty($allfield)){
            foreach ($allfield as $key=>$val){
                $val['key'] == 'PRI' && $pks[] = $key;
            }
        }
        if(empty($pks)){
            $vconfig = $this->vconfig;
            if(!empty($vconfig)) {
                foreach ($vconfig as $key => $val) {
                    if (isset($val['title']) && $val['title']) {
                        $pks[] = $key;
                    }
                }
            }
        }
        return $pks;
    }

    /**
     * 获取随机字符串
     * @param int $length
     * @param string $char
     * @return bool|string
     */
    function strRand($length = 5, $char = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
        $string = '';
        for($i = $length; $i > 0; $i--) {
            $string .= $char[mt_rand(0, strlen($char) - 1)];
        }
        return $string;
    }

    /**
     * 错误消息提醒
     * @param $msg
     */
    private function __exitError($msg){
        if($this->tplclass->isPost()){
            EXITJSON(0, $msg);
        }else{
            if(!is_string($msg)){
                $msg = json_encode($msg, true);
            }
            exit($msg);
        }
    }

    private function getPkMd5s($list){
        if(!is_array($list)) return [];
        $pks = $this->pks;
        $retlist = [];
        foreach ($list as $key=>$val) {
            $tpks = [];
            foreach ($pks as $v) {
                $tpks[$v] = $val[$v];
            }
            $tpkstr = json_encode($tpks, true);
            $retlist[$key] = [
                'pk' => $tpkstr,
                'md5' => substr(md5($tpkstr), 8, 8)
            ];
        }
        return $retlist;
    }

    /**
     * 获取页面
     * @return string
     */
    public function html(){
        $type = $this->type;
        $this->pks = $this->getPks();
        if($type == 'listxxx'){
            $tpl = $this->tplclass->tpl_init;
            $viewpath = "{$this->tplclass->tplname}.vim";
            !view()->exists($viewpath) && page404();
            $retstr = view('sys.vim.list', [
                'tpl_path' => $tpl
            ]);
        }elseif($type == 'list'){
            $retstr = $this->_list();
        }else if(method_exists($this, $type)){
            $retstr = $this->$type();
        }else{
            page404();
        }

        return $retstr;
    }

    /**
     * 设置配置项
     * 初始化数据
     */
    public function setConfig(){
        list($this->retconfig, $this->vconfig, $vimconfig, $this->allfield) = $this->vim->getDataConfig($this->tplclass);
        $vconfig = &$this->vconfig;
        $operwidth = 20; //单个操作宽度

        //分页设置
        $config = [];
        $type = $this->type;


        //操作窗口标题
        $oper_title = "";
        if(!empty($vimconfig['field'])) {
            foreach ($vimconfig['field'] as $key => $val) {
                if (is_array($val) && $val['title']) {
                    $oper_title = $key;
                    break;
                }
            }
        }

        //如果标题未找到则使用主键
        if(empty($oper_title) && !empty($this->allfield)){
            foreach ($this->allfield as $key=>$val){
                if($val['key'] == 'PRI'){
                    $oper_title = $key;
                    break;
                }
            }
        }

        $this->oper_title = $oper_title;

        if(empty($vimconfig['field'])){
            $this->vimconfig_field = [];
        }else{
            $this->vimconfig_field = $vimconfig['field'];
        }

        if(empty($vimconfig['handle'])){
            $this->vimconfig_handle = [];
        }else{
            $this->vimconfig_handle = $vimconfig['handle'];
        }

        if(empty($vimconfig['handleinit'])){
            $this->vimconfig_handleinit = [];
        }else{
            $this->vimconfig_handleinit = $vimconfig['handleinit'];
        }

        if(empty($vimconfig['delete'])){
            $this->vimconfig_delete = [];
        }else{
            $this->vimconfig_delete = $vimconfig['delete'];
        }

        if(empty($vimconfig['tree'])){
            $this->vimconfig_tree = [];
        }else{
            $this->vimconfig_tree = $vimconfig['tree'];
        }

        if(empty($type) || $type == 'json'){
            $gets = [];
            foreach($_GET as $key=>$val){
                $gets[strtolower(trim($key))] = $val;
            }
            $config = [];
            if(array_key_exists('p', $gets) && is_numeric($gets['p'])){
                $config['ispage'] = true;
                if(array_key_exists('psize', $gets) && is_numeric($gets['psize'])){
                    $config['pagesize'] = $gets['psize'];
                }
            }
        }elseif($type == 'html' || $type == 'htm'){
            $p = $_GET['p'];
            $config = [];
            if($p > 0){
                $config['ispage'] = true;
                $psize = $_GET['psize'];
                if($psize > 0){
                    $config['pagesize'] = $psize;
                }
            }
            !empty($_GET) && $config['get'] = $_GET;
            !empty($_POST) && $config['post'] = $_POST;
        }elseif($type != 'get'){
            $runstr = $type."Config";
            if(method_exists($this, $runstr)) {
                $config = $this->$runstr($vimconfig, $this->allfield);
                if($type == 'list' && $this->is_tree){
                    unset($vconfig[$oper_title]['edit']);
                }
            }
        }

        //操作设置
        $newhandle = [];
        $batch = [];
        $handles = [];
        $oper = $vimconfig['oper'];
        (empty($oper) || !is_array($oper)) && $oper = [];

        $operunset = [];
        $this->setHandles($oper);
        foreach ($oper as $key=>$val){
            if(!is_array($val) || !isset($val['url']) || !is_string($key)){
                $operunset[] = $key;
                continue;
            }
            !isset($val['name']) && $val['name'] = $key;
            $kname = $val['name'];
            $knamelen = (strlen($kname) + mb_strlen($kname,'UTF8')) / 4;
            $operwidth += 12 * $knamelen + 22;
        }
        foreach ($operunset as $val) unset($oper[$val]);


        $is = $vimconfig['is'];

        if(!empty($vimconfig['tree'])){
            $vtree = $vimconfig['tree'];
            if(isset($vtree['edit'])){
                $tree_edit = $vtree['edit'];
            }else{
                $tree_edit = true;
            }
            $batch['open_close'] = "全部展开";
            $tree_edit && $oper['add'] = "新增";
        }

        $is['add'] && $batch['add'] = '新增';

        if($is['view']){
            $oper['view'] = "查看";
        }

        $handle = $vimconfig['handle'];
        if(!empty($handle) && is_array($handle)){
            foreach ($handle as $key=>$val){
                if(is_numeric($key)){
                    $newhandle[$val] = ['name' => $val];
                }elseif(is_string($val) || is_numeric($val)){
                    $newhandle[$key] = ['name' => $val];
                }elseif(is_array($val)){
                    empty($val['name']) && $val['name'] = $vconfig[$key]['name'];
                    empty($val['name']) && $val['name'] = $key;
                    if(isset($val['batch_only'])){
                        if($val['batch_only'] !== false) {
                            $bo = $val['batch_only'];
                            is_string($bo) && $bo = trim($bo);
                            if ($bo === true || empty($bo) || !is_string($bo)) $bo = "编辑";
                            unset($val['batch']);
                            unset($val['batch_only']);
                            $handles[$bo][$key] = $val;
                        }else{
                            $newhandle[$key] = $val;
                        }
                    }elseif(isset($val['batch'])){
                        if($val['batch'] !== false) {
                            $bt = $val['batch'];
                            is_string($bt) && $bo = trim($bt);
                            if ($bt === true || empty($bt) || !is_string($bt)) $bt = "编辑";
                            unset($val['batch']);
                            $handles[$bt][$key] = $val;
                        }
                        $newhandle[$key] = $val;
                    }else{
                        $newhandle[$key] = $val;
                    }
                }
            }
        }elseif(is_bool($handle) && $handle){
            $newhandle = true;
        }

        if(!empty($handles)){
            $hds = [];
            foreach ($handles as $key=>$val){
                $hds[] = [
                    'key' => $key,
                    'field' => $val
                ];
            }
            $batch['handle'] = '编辑';
            $vimconfig['handles'] = $hds;
        }

        $is['deletes'] && $batch['delete'] = '删除';
        $is['clear'] && $batch['clear'] = '清空数据';

        $is['import'] && $batch['import'] = '导入';
        if(empty($newhandle)){
            unset($vimconfig['handle']);
        }else {
            $vimconfig['handle'] = $newhandle;
            $oper['handle'] = "编辑";
        }
        if(empty($batch)){
            unset($config['batch']);
        }else {
            $this->setHandles($batch);
            if(!empty($batch) && is_array($batch)) {
                if (empty($vimconfig['batch'])) {
                    $vimconfig['batch'] = $batch;
                } else {
                    if(!is_array($vimconfig['batch'])){
                        $vimconfig['batch'] = [];
                    }
                    foreach ($batch as $key=>$val){
                        $vimconfig['batch'][$key] = $val;
                    }
                }
            }
        }

        if($is['delete']){
            $oper['delete'] = "删除";
        }
        $this->setHandles($oper);
        foreach ($oper as $key=>$val){
            if(!is_string($val)) continue;
            $knamelen = (strlen($val) + mb_strlen($val,'UTF8')) / 4;
            $operwidth += 12 * $knamelen + 22;
        }

        if($operwidth > 0) {
            $owidth = $vimconfig['operwidth']; //单个操作宽度
            empty($owidth) && $owidth = 0;
            !is_numeric($owidth) && $owidth = 0;
            $owidth < 0 && $owidth = 0;

            $vimconfig['operwidth'] = $operwidth + $owidth;
        }else{
            unset($vimconfig['operwidth']);
        }
        $vimconfig['oper'] = $oper;

        if($is['deletes'] || $is['export_checked'] || !empty($handles)){
            $vimconfig['is']['checkbox'] = true;
        }else{
            unset($vimconfig['is']['checkbox']);
        }
        $this->vimconfig = $vimconfig;
        return $config;
    }


    private function setHandles(&$handles){
        $tpl = $this->tpl;
        $ida = $this->getUserInfo()['menu_ida'];
        empty($md5s) && $md5s = [];
        $keys = array_keys($handles);
        foreach ($keys as $val){
            $hv = $handles[$val];
            $is_url = false;
            if(is_array($hv)){
                if(isset($hv['url'])){
                    $t = $tpl."/".$hv['url'];
                    $is_url = true;
                }
            }
            if(!$is_url){
                if(is_string($hv)) {
                    $tp = $val;
                    $tp == 'handle' && $tp = 'edit';
                    $t = $tpl . "." . $tp;
                }else{
                    continue;
                }
            }
            $md5 = substr(md5($t), 8, 8);
            if(isset($ida[$md5])){
                unset($handles[$val]);
            }
        }
    }

    public function setData($data){
        $this->data = $data;
    }


    /**
     * ini.php配置
     * @param $ini
     */
    public function setIni(&$ini){
        $sql = &$ini['#sql'];

        $vch = $this->vimconfig_handle;
        $vcf = $this->vimconfig_field;
        $afd = $this->allfield;
        foreach ($vcf as $key=>$val){
            $keyname = "";
            if (is_string($key)) {
                $keyname = $key;
            } elseif (is_string($val)) {
                $keyname = $val;
            }
            $tp = "";
            if(isset($val['type'])){
                $tp = $val['type'];
            }elseif(isset($vch[$key]['type'])){
                $tp = $vch[$key]['type'];
            }elseif(!empty($keyname)){
                if(in_array($keyname, ['create_time', 'update_time', 'time'])) $tp = $keyname;
            }
            if(empty($sql[$keyname]) && in_array($tp, ['create_time', 'update_time'])){
                $sql[$keyname] = [
                    ['date', 'Y-m-d H:i:s']
                ];
            }elseif($tp == 'time'){
                $atp = $afd[$keyname]['type'];
                if(strpos($atp, 'int') !== false) {
                    $sql[$keyname] = [
                        ['date', 'Y-m-d H:i:s']
                    ];
                }
            }

            if(isset($val['list']) && is_array($val['list'])){
                $sql[$keyname] = [
                    ['str_replace_list', $val['list']]
                ];
            }
        }
    }

    /**
     * 获取配置项
     */
    public function getConfig(){
        return $this->setConfig();
    }

    /**
     * 设置搜索功能
     */
    protected function setSearch(){
        $vconfig = $this->vconfig;

        //设置搜索功能
        $search = [];
        if(!empty($vconfig)) {
            $searcharr = [];
            $typedef = 'text';
            foreach ($vconfig as $key => $val) {
                if (isset($val['search'])) {
                    $slist = $val['list'];
                    empty($slist) && $slist = [];
                    $type = $val['type'];
                    empty($type) && $type = $typedef;
                    if($val['status']){
                        $type = "status";
                        list($l, $r) = explode("|", $val['text']);
                        empty($l) && $l = "启用";
                        empty($r) && $r = "禁用";
                        $slist = [
                            '1' => $l,
                            '0' => $r
                        ];
                    }
                    $s = $val['search'];
                    if(in_array($type, ['tree', 'trees'])) {
                        $width = 200;
                    }else{
                        $width = 100;
                    }
                    if (is_bool($s) && $s) {
                        $searcharr[0][$key] = [
                            'width' => $width,
                            'type' => $type,
                            'list' => $slist
                        ];
                    } elseif (is_array($s)) {
                        if (!isset($s['width'])) {
                            $s['width'] = $width;
                        }

                        if (!isset($s['type'])) {
                            $s['type'] = $type;
                        }

                        $index = 0;
                        if (isset($s['index'])) {
                            is_numeric($s['index']) && $index = $s['index'];
                            unset($s['index']);
                        }
                        $s['list'] = $slist;
                        $searcharr[$index][$key] = $s;
                    }
                }
            }
            if(!empty($searcharr)) {
                ksort($searcharr);
                foreach ($searcharr as $key => $val){
                    foreach ($val as $k=>$v){
                        $search[$k] = $v;
                    }
                }
            }
        }
        $this->search = $search;
    }

    /**
     * 获取关联层级数据
     * @param $tree
     * @param $values
     * @param $notvalues
     * @param array $retlist
     * @return array
     */
    private function getTreeList($tree, $values, $notvalues=[], $istree = true, &$retlist = []){
        if(empty($tree['name'])) {
            $oper_title = $this->oper_title;
        }else{
            $oper_title = $tree['name'];
        }
        $oper_title = strtolower($oper_title);
        $child = strtolower($tree['child']);
        $parent = strtolower($tree['parent']);
        $table = strtolower($tree['table']);
        if(is_array($table)){
            list($table, $conn) = $table;
        }else{
            $conn = "";
        }
        empty($table) && $table = "";
        if(is_null($values)){
            $values = 0;
        }
        $mod = $this->tplclass->db($table, $conn)->select($child, $parent, $oper_title)
            ->where($parent, $values);
        if(!empty($notvalues)) $mod->whereNotIn($child, $notvalues);
        $def_conn = $this->vim->config['config']['conn'];
        empty($def_conn) && $def_conn = $GLOBALS['DOMAIN_CONFIG']['conn'];
        $def_table = $this->vim->config['config']['table'];
        if((empty($conn) || $conn == $def_conn) && (empty($table) || $table == $def_table)) {
            $order = $this->retconfig['config']['order'];
            if(!empty($order)) {
                foreach ($order as $key => $val) {
                    $mod->orderBy($key, $val);
                }
            }
        }

        $tree_sort = $tree['sort'];
        if(!empty($tree_sort) && is_array($tree_sort)){
            if(is_string($tree_sort[0]) && is_string($tree_sort[1])){
                $mod->orderBy($tree_sort[0], $tree_sort[1]);
            } else {
                foreach ($tree_sort as $key => $val) {
                    if (is_string($key) && is_string($val)) {
                        $mod->orderBy($key, $val);
                    }
                }
            }
        }

        $childlist = $mod->get();
        $childs = [];
        foreach ($childlist as $key=>$val){
            $val = $this->tplclass->keyToLower($val);
            $childs[] = $val[$child];
            $retlist[$values]['list'][] = [
                'key' => $val[$child],
                'value' => $val[$oper_title]
            ];
        }

        $mod2 = $this->tplclass->db($table, $conn)->select(\DB::raw("count(*) as count, {$parent}"))
            ->whereIn($parent, $childs);
        if(!empty($notvalues)) $mod2->whereNotIn($child, $notvalues);
        $grouplist = $mod2->groupBy($parent)->get();
        foreach ($grouplist as $key=>$val){
            $val = $this->tplclass->keyToLower($val);
            $retlist[$values]['listmore'][$val[$parent]] = $val->count;
        }

        if($values == $tree['value']) {
            $retlist[$values]['name'] = "顶级";
        }else{
            $parentinfo = $this->tplclass->db($table, $conn)->select($child, $parent, $oper_title)->where($child, $values)->first();
            if(!empty($parentinfo)){
                $parentinfo = $this->tplclass->keyToLower($parentinfo);
                $nextvalues = trim($parentinfo[$parent]);
                $retlist[$values]['name'] = "请选择";
                if($istree && $nextvalues != "") return $this->getTreeList($tree, $nextvalues, $notvalues, $istree, $retlist);
            }else{
                $retlist[$values]['name'] = "顶级";
            }
        }
        return $retlist;
    }

    /**
     * 获取所有层级数据列表
     * @param $table
     * @param $parent
     * @param $child
     * @param $value
     * @param array $ret
     * @return array
     */
    private function getTreeListNext($table, $parent, $child, $value, $name, $sort, $where, &$ret = []){
        $mod = $this->tplclass->db($table)->select($child, $parent, $name)->whereIn($parent, $value);
        if(!empty($sort)){
            $mod->orderBy($sort[0], $sort[1]);
        }
        if(!empty($where)){
            $this->tplclass->setWhere($mod, $where, $this->allfield);
        }
        $list = $mod->get();
        $values = [];
        foreach ($list as $key=>$val){
            $val = $this->tplclass->keyToLower($val);
            $ret[$val[$parent]][$val[$child]] = [
                'name' => $val[$name],
            ];
            $values[] = $val[$child];
        }
        if(empty($values)) return $ret;
        return $this->getTreeListNext($table, $parent, $child, $values, $name, $sort, $where, $ret);
    }

    /**
     * 处理层级数据
     * @param $list
     */
    private function getTreeListNextDeal(&$list, $value, $arr, &$retlist = []){
        if(empty($list[$value])) return $retlist;
        $retlist['list'] = $list[$value];
        unset($list[$value]);
        foreach($retlist['list'] as $key=>$val){
            if($arr[$key]){
                $retlist['list'][$key]['checked'] = true;
            }
            $this->getTreeListNextDeal($list, $key, $arr, $retlist['list'][$key]);
        }
        return $retlist;
    }

    /**
     * 使JavaScript保持顺序不变
     * @param $odlist
     * @param array $retlist
     * @return array
     */
    private function getTreeListNextReturn($odlist, &$retlist = []){
        $i = 0;
        if(!empty($odlist) && is_array($odlist)) {
            foreach ($odlist as $key => $val) {
                $retlist[$i]['key'] = $key;
                !empty($val['name']) && $retlist[$i]['name'] = $val['name'];
                !empty($val['checked']) && $retlist[$i]['checked'] = $val['checked'];
                if (!empty($val['list']) && is_array($val['list'])) {
                    $this->getTreeListNextReturn($val['list'], $retlist[$i]['list']);
                }
                $i++;
            }
        }
        return $retlist;
    }

    /**
     * 获取所有层级数据
     * @param $tree
     * @return array
     */
    private function getTreeListAll($tree, $data=[]){
        $child = strtolower($tree['child']);
        $parent = strtolower($tree['parent']);
        $table = strtolower($tree['table']);
        $name = strtolower($tree['name']);
        $value = $tree['value'];
        $sort = $tree['sort'];
        $retlist = [];
        $endlist = $tree['end'];
        empty($endlist) && $endlist = [];
        if(empty($table) || empty($parent) || empty($child) || empty($name)) return $retlist;
        if(is_array($table)){
            list($table, $conn) = $table;
        }else{
            $conn = "";
        }
        $dbinfo = $this->tplclass->dbInfo($conn, $table);
        if(empty($dbinfo) || empty($dbinfo[$parent]) || empty($dbinfo[$child]) || empty($dbinfo[$name])) return $retlist;
        if(empty($data)) $data = "";
        $arrtmp = explode(",", $data);
        $arr = [];
        foreach ($arrtmp as $key=>$val){
            $arr[$val] = true;
        }
        if(empty($sort) || !is_array($sort)){
            $sort = [];
        }elseif(is_string($sort)) {
            $sort = trim(strtolower($sort));
            if(empty($dbinfo[$sort])){
                $sort = [];
            }else {
                $sort = [$sort, 'asc'];
            }
        }else{
            list($fd, $tp) = $sort;
            if(is_string($fd)){
                $fd = trim(strtolower($fd));
                if(empty($fd) || empty($dbinfo[$fd])){
                    $sort = [];
                }else{
                    !is_string($tp) && $tp = "asc";
                    $tp != 'asc' && $tp = 'desc';
                    $sort = [$fd, $tp];
                }
            }else{
                $sort = [];
            }
        }
        $twhere = $tree['where'];
        $where = $this->vim->getWhereRealList($twhere, $dbinfo);
        $list = $this->getTreeListNext($table, $parent, $child, [$value], $name, $sort, $where);
        if(!empty($endlist) && is_array($endlist)){
            foreach ($endlist as $key=>$val){
                if(is_array($val)){
                    if(isset($val[$parent]) && isset($val[$child]) && isset($val[$name])){
                        $list[$val[$parent]][$val[$child]]['name'] = $val[$name];
                    }
                }
            }
        }
        $odlist = $this->getTreeListNextDeal($list, $value, $arr)['list'];
        $retlist = $this->getTreeListNextReturn($odlist);
        return $retlist;
    }

    /**
     * 设置操作功能配置
     */
    protected function setHandleConfig(&$handle, $vconfig, $edit_type, $field_key){
        $type = "";
        if(!is_array($vconfig)){
            $vconfig = $handle;
        }elseif(is_array($handle)){
            foreach ($handle as $key=>$val){
                !isset($vconfig[$key]) && $vconfig[$key] = $val;
            }
        }
        if($vconfig['status']){
            $type = "status";
            $handle['text'] = $vconfig['text'];
        }elseif(isset($vconfig['type'])){
            $type = $vconfig['type'];
            if($type == 'tree'){
                $data = $this->data['_'];
                if(is_string($data)) $this->__exitError($data);
                if(count($data) > 0 || $edit_type == 'add'){
                    $tree = $vconfig['tree'];
                    $notvalues = [];
                    $is_this_table = false;
                    if(isset($tree['table'])){
                        $tvcc = $this->vim->config['config'];
                        if(is_array($tree['table'])){
                            list($tree_table, $conn) = $tree['table'];
                        }else{
                            $tree_table = $tree['table'];
                            $conn = "";
                        }
                        if($tvcc['table'] == $tree_table){
                            if(empty($conn)){
                                $is_this_table = true;
                            }else{
                                $tvcc_conn = $tvcc['conn'];
                                if(empty($tvcc_conn)){
                                    $tvcc_conn = $GLOBALS['DOMAIN_CONFIG']['conn'];
                                }
                                if($conn == $tvcc_conn){
                                    $is_this_table = true;
                                }
                            }
                        }
                    }else{
                        $is_this_table = true;
                    }
                    if($edit_type == 'add') {
                        $parent_value = $tree['value'];
                        $pk = $_GET['pk'];
                        if (!empty($pk)) {
                            $pks = json_decode($pk, true);
                            if (count($pks) > 0) {
                                $pv = json_decode($pks[0], true)[$tree['child']];
                                !empty($pv) && $parent_value = $pv;
                            }
                        }
                    }elseif($is_this_table){
                        $parent_value = $data[0][$tree['parent']];
                        foreach ($data as $key => $val) {
                            $notvalues[] = $val[$tree['child']];
                        }
                    }else {
                        $parent_value = $this->data['src'][0][$field_key];
                    }
                    $treelist = $this->getTreeList($tree, $parent_value, $notvalues);
                    $next = "";
                    foreach ($treelist as $key=>$val){
                        if($next != "") $treelist[$key]['next'] = $next;
                        $next = $key;
                    }
                    $treelist = array_reverse($treelist, true);
                    $newlist = [];
                    $i = 0;
                    foreach ($treelist as $key=>$val){
                        if($i > 0){
                            if(!empty($val['list'])){
                                $newlist[] = [
                                    'key' => $key,
                                    'list' => $val
                                ];
                            }
                        }else{
                            $newlist[] = [
                                'key' => $key,
                                'list' => $val
                            ];
                        }
                        $i ++;
                    }
                    $handle['text'] = $newlist;
                    $handle['notvalues'] = $notvalues;
                }
            }
        }
        !empty($type) && $handle['type'] = $type;
    }

    /**
     * 设置操作功能
     */
    protected function setHandle($type){
        $vconfig = $this->vconfig;
        if(!in_array($this->vim->config, ['sql', 'sqlfind']) && is_array($this->data_ext)){
            $this->data = $this->data_ext;
        }
        $handle = &$this->vimconfig['handle'];
        if(empty($handle)) return;

        if(!empty($handle) && is_array($handle)) {
            foreach ($handle as $key => $val) {
                $this->setHandleConfig($handle[$key], $vconfig[$key], $type, $key);
            }
        }
        $handles = &$this->vimconfig['handles'];
        if(empty($handles)) return;

        foreach ($handles as $key=>$val){
            foreach($val['field'] as $k=>$v){
                if(isset($handle[$k])){
                    $handles[$key]['field'][$k] = $handle[$k];
                }else {
                    $this->setHandleConfig($handles[$key]['field'][$k], $vconfig[$k], $type, $k);
                }
            }
        }
    }

    /**
     * 验证from关联
     * @param $from
     * @param $value
     */
    private function getFromCheck($from){
        list($vtable, $vthis, $vlink) = $from[3];
        if(is_array($vthis) && count($vthis) > 1 && is_array($vlink) && count($vlink) > 1){
            $def_conn = config('database.default');
            $def_table = $this->vim->config['config']['table'];
            $allfield = $this->allfield;
            if(is_array($vtable)){
                list($vtable, $vconn) = $vtable;
            }
            $vtable = strtolower(trim($vtable));
            $vconn = strtolower(trim($vconn));
            if(empty($vconn)){
                $vconn = $def_conn;
            }

            //判断关联表是否有效
            $vtables = $this->tplclass->dbInfo($vconn);
            if(!isset($vtables[$vtable])){
                $this->__exitError("{$vconn}->{$vtable}不存在！");
            }

            //关联表与本表字段对应关系
            list($vthiskey, $vthisfield) = $vthis;
            $vthiskey = strtolower(trim($vthiskey));
            $vthisfield = strtolower(trim($vthisfield));
            if(!isset($allfield[$vthiskey])){
                $this->__exitError("{$def_conn}->{$def_table}字段{$vthiskey}不存在！");
            }
            $vtableinfo = $vtables[$vtable]['field'];
            if(!isset($vtableinfo[$vthisfield])){
                $this->__exitError("{$vconn}->{$vtable}字段{$vthisfield}不存在！");
            }

            //关联表与属性表对应关系
            list($vlinkkey, $vlinkfield) = $vlink;
            $vlinkkey = strtolower(trim($vlinkkey));
            $vlinkfield = strtolower(trim($vlinkfield));

            $btable = $from[0];
            if(is_array($btable)){
                list($btable, $bconn) = $btable;
            }
            if(empty($bconn)){
                $bconn = $def_conn;
            }
            $btables = $this->tplclass->dbInfo($bconn);
            if(!isset($btables[$btable])){
                $this->__exitError("{$bconn}->{$btable}不存在！");
            }
            $btableinfo = $btables[$btable]['field'];
            if(!isset($btableinfo[$vlinkkey])){
                $this->__exitError("{$bconn}->{$btable}字段{$vlinkkey}不存在！");
            }

            if(!isset($vtableinfo[$vlinkfield])){
                $this->__exitError("{$vconn}->{$vtable}字段{$vlinkfield}不存在！");
            }

            return [
                'default' => [
                    'table' => $def_table,
                    'conn' => $def_conn,
                    'key' => $vthiskey,
                ],
                'this' => [
                    'table' => $btable,
                    'conn' => $bconn,
                    'name' => $from[2],
                    'key' => $vlinkkey,
                ],
                'link' => [
                    'table' => $vtable,
                    'conn' => $vconn,
                    'default_key' => $vthisfield,
                    'this_key' => $vlinkfield
                ]
            ];
            //查询关联表数据
//            $varr = explode(",", $value);
//            $vlist = $this->tplclass->db($vtable, $vconn)->get();
        }else{
            return null;
        }
    }

    /**
     *
     */
    protected function listConfig($vimconfig, $allfield){
        $export = $_GET['_@export@_'];
        $this->is_export = false;
        if(in_array($export, ['all', 'checked', 'this', 'print'])){
            $_POST['type'] = 'all';
            $this->is_export = true;
        }
        $vconfig_src = [];
        $vs_hiden = [];
        $vs_is_disabled = false;
        foreach ($this->vconfig as $key=>$val){
            if($val['hidden']) {
                $vs_hiden[$key] = true;
            }else{
                if($val['title']){
                    $val['disabled'] = true;
                    $vs_is_disabled = true;
                }
                $vconfig_src[$key] = $val;
            }
        }
        $userinfo = $this->getUserInfo();
        $dc = $GLOBALS['DOMAIN_CONFIG'];
        if(empty($userinfo) && $dc['backstage']){
            $this->__exitError("用户未登录");
        }
        $_mid_ = $_GET['_mid_'];

        $retconfig = $this->retconfig;
        $rwhere = $retconfig['config']['where'];
        empty($rwhere) && $rwhere = [];
        $is_tree = false;
        if(isset($vimconfig['tree'])){
            $tree = $vimconfig['tree'];
            if(isset($tree['parent']) && isset($tree['child']) && isset($tree['value'])){
                $p = $tree['parent'];
                $c = $tree['child'];
                $v = $tree['value'];
                if(isset($allfield[$p]) && isset($allfield[$c])){
                    $is_tree = true;
                    $where = $rwhere;
                    $where[] = [$p, "=", $v];
                    $retconfig['config']['where'] = $where;
                    $retconfig['config']['tree'] = [
                        'parent' => $p,
                        'child' => $c,
                        'value' => $v,
                    ];
                }
            }
        }

        //字段筛选查询功能
        if(count($vconfig_src) > 4){
            $user_id = $userinfo['id'];
            $menu_field = $this->tplclass->cache(function () use($_mid_, $user_id){
                $dc = $GLOBALS['DOMAIN_CONFIG'];
                $mf_info = $this->tplclass->db($dc['conn']."_menu_field", 'user')->where('menu_id', '=', $_mid_)->where('user_id', '=', $user_id)->first();
                if(empty($mf_info)){
                    return false;
                }
                $mf = trim($mf_info->field);
                if(empty($mf)){
                    return [];
                }
                $mfarr = array_unique(explode(",", $mf));
                $ret = [];
                foreach ($mfarr as $m){
                    $m = trim(strtolower($m));
                    if(!empty($m)){
                        $ret[$m] = true;
                    }
                }
                return $ret;
            }, "info_{$_mid_}_{$user_id}");
            if($menu_field === false) {
                foreach ($vconfig_src as $key=>$val){
                    $vconfig_src[$key]['selected'] = true;
                }
            }else{
                $del_keys = [];
                foreach ($this->vconfig as $key => $val) {
                    if (isset($menu_field[$key])) {
                        $vconfig_src[$key]['selected'] = true;
                    }elseif(!$vs_hiden[$key]){
                        $del_keys[] = $key;
                    }
                }
                foreach ($del_keys as $dk){
                    unset($this->vconfig[$dk]);
                }
            }
            if($is_tree){
                $p = $vimconfig['tree']['parent'];
                $c = $vimconfig['tree']['child'];
                if(isset($vconfig_src[$p])){
                    $vconfig_src[$p]['disabled'] = true;
                    $vs_is_disabled = true;
                }
                if(isset($vconfig_src[$c])){
                    $vconfig_src[$c]['disabled'] = true;
                    $vs_is_disabled = true;
                }
            }
            if(!$vs_is_disabled){
                foreach ($vconfig_src as $key=>$val){
                    $vconfig_src[$key]['disabled'] = true;
                    break;
                }
            }
            $auto_widths = [];
            $vconfig_src_top = [];
            $vconfig_src_down = [];
            foreach ($vconfig_src as $key=>$val){
                if(!$val['fixed'] && isset($this->vconfig[$key])) {
                    $auto_widths[] = $key;
                }
                $vname = trim($val['name']);
                empty($vname) && $vname = $key;
                $val['name'] = $vname;
                if($val['disabled']){
                    $vconfig_src_top[$key] = $val;
                }else{
                    $vconfig_src_down[$key] = $val;
                }
            }

            if(empty($auto_widths)){
                $width_add = 0;
                foreach ($this->vconfig as $key=>$val){
                    unset($this->vconfig[$key]['fixed']);
                    if($val['width'] > 0){
                        $width_add += $val['width'];
                    }
                }
                foreach ($this->vconfig as $key=>$val){
                    $this->vconfig[$key]['width'] = floor(($val['width'] * 100) / $width_add) . "%";
                }
            }else{
                $width_add = 0;
                $awkv = [];
                foreach ($auto_widths as $aw){
                    $tw = $this->vconfig[$aw]['width'];
                    if(empty($tw) || $tw <= 0){
                        $tw = 20;
                    }
                    $awkv[$aw] = $tw;
                    $width_add += $tw;
                }
                foreach ($awkv as $k=>$v){
                    $this->vconfig[$k]['width'] = floor(($v * 100) / $width_add) . "%";
                }
            }
            $vconfig_src_new = [];
            foreach ($vconfig_src_top as $key=>$val){
                $vconfig_src_new[$key] = $val;
            }
            foreach ($vconfig_src_down as $key=>$val){
                $vconfig_src_new[$key] = $val;
            }
            $this->vconfig_src = $vconfig_src_new;
        }

        //树状结构分析
        $this->is_tree_all = false;
        if($is_tree){
            $retconfig['ispage'] = false;
            if($this->tplclass->isPost()){
                if($_POST['type'] == 'all'){
                    unset($retconfig['config']['where']);
                    $this->is_tree_all = true;
                }else {
                    !isset($_POST["value"]) && $this->__exitError("没有数据传递");
                    $value = $_POST["value"];
                    $retconfig['config']['where'] = [$tree['parent'], "=", $value];
                }
            }
        }else{
            $field_kv = $this->vim->field_key_val;
            !is_array($field_kv) && $field_kv = [];
            $this->setSearch();
            //设置配置数据查询功能
            $getdata = $_GET;
            unset($getdata['p']);
            unset($getdata['psize']);
            unset($getdata['_sort']);
            unset($getdata['_order']);
            $vconfig = $this->vconfig;
            if(!empty($getdata)){
                $where = $rwhere;
                $allfield = $this->allfield;
                $vfield = $vimconfig['field'];
                $find_wheres = [];
                foreach ($getdata as $key => $val){
                    if(!isset($vfield[$key])){
                        //当搜索时不存在设置字段则不进行条件查找
                        continue;
                    }
                    $keylen = strlen($key);
                    if(!isset($allfield[$key]) && $keylen > 2){
                        $key_2 = substr($key, $keylen - 2);
                        if($key_2 == "__"){
                            $key = substr($key, 0, $keylen - 2);
                        }
                    }
                    $atype = $allfield[$key]['type'];
                    $tp = $vfield[$key]['type'];
                    $oper = $vfield[$key]['oper'];
                    if ($tp == 'time') {
                        $is_int = strpos($atype, 'int') === false ? false : true;
                        $s_val = $getdata[$key];
                        $e_val = $getdata[$key."__"];
                        if(!empty($s_val)){
                            $s_val = $s_val." 00:00:00";
                            if($is_int){
                                $where[] = [$key, ">=", strtotime($s_val)];
                            }else{
                                $where[] = [$key, ">=", $s_val];
                            }
                        }
                        if(!empty($e_val)){
                            $e_val = $e_val." 23:59:59";
                            if($is_int){
                                $where[] = [$key, "<=", strtotime($e_val)];
                            }else{
                                $where[] = [$key, "<=", $e_val];
                            }
                        }
                    } elseif ($tp == 'tree'){
                        $vtree = $vfield[$key]['tree'];
                        $vtable = strtolower($vtree['table']);
                        $val = str_replace("/", " ", $val);
                        if(is_array($vtable)){
                            list($vtable, $vconn) = $vtable;
                        }else{
                            $vconn = "";
                        }
                        $vchild = strtolower($vtree['child']);
                        $vparent = strtolower($vtree['parent']);
                        $vname = strtolower($vtree['name']);
                        if(empty($vname)){
                            $vname = $vchild;
                        }
                        $varr_tmp = explode(' ', $val);
                        $varr = [];
                        foreach ($varr_tmp as $vt){
                            !empty($vt) && $varr[] = $vt;
                        }
                        if(empty($varr)){
                            $where = true;
                            break;
                        }
                        $varr = array_unique($varr);
                        $tree_mod = $this->tplclass->db($vtable, $vconn);
                        foreach ($varr as $vr){
                            $tree_mod->orWhere($vname, 'like', "%{$vr}%");
                        }
                        $vlist = $tree_mod->get();
                        if(count($vlist) <= 0){
                            $where = true;
                            break;
                        }
                        $vids = [];
                        $parents = [];
                        foreach ($vlist as $vk => $vv) {
                            $vv = $this->tplclass->keyToLower($vv);
                            $vids[] = $vv[$vchild];
                            $parents[$vv[$vchild]] = [
                                'name' => $vv[$vname],
                                'pid' => $vv[$vparent]
                            ];
                        }
                        $vids = array_unique($vids);

                        if(count($vids) > 1) {
                            // 如果搜索组合大于1时需要进行拼合处理
                            $pids = [];
                            foreach ($vlist as $vk => $vv) {
                                $vv = $this->tplclass->keyToLower($vv);
                                if (!isset($parents[$vv[$vparent]])) {
                                    $pids[] = $vv[$vparent];
                                }
                            }

                            while (!empty($pids)) {
                                $vlist = $this->tplclass->db($vtable, $vconn)->whereIn($vchild, $pids)->get();
                                $pids = [];
                                foreach ($vlist as $vk => $vv) {
                                    $vv = $this->tplclass->keyToLower($vv);
                                    if (!isset($parents[$vv[$vchild]])) {
                                        $parents[$vv[$vchild]] = [
                                            'name' => $vv[$vname],
                                            'pid' => $vv[$vparent]
                                        ];
                                        $pids[] = $vv[$vparent];
                                    }
                                }
                            }

                            $vnames = [];
                            foreach ($vids as $vd) {
                                $vnames[$vd] = [];
                                $ovd = $vd;
                                while (isset($parents[$ovd])) {
                                    $vnames[$vd][] = $parents[$ovd]['name'];
                                    $ovd = $parents[$ovd]['pid'];
                                }
                            }

                            $sh_ids = [];
                            foreach ($vnames as $vk => $vns) {
                                $b_out = true;
                                foreach ($varr as $vr) {
                                    $b = false;
                                    foreach ($vns as $vn) {
                                        if (stripos($vn, $vr) !== false) {
                                            $b = true;
                                            break;
                                        }
                                    }
                                    if (!$b) {
                                        $b_out = false;
                                        break;
                                    }
                                }
                                $b_out && $sh_ids[] = $vk;
                            }

                            if (empty($sh_ids)) {
                                $where = true;
                                break;
                            }
                            $vids = $sh_ids;
                        }

                        $idskv = [];
                        foreach ($vids as $vid) {
                            $idskv[$vid] = true;
                        }
                        while (true) {
                            $vlist = $this->tplclass->db($vtable, $vconn)->whereIn($vparent, $vids)->get();
                            $vids = [];
                            foreach ($vlist as $vk => $vv) {
                                $vv = $this->tplclass->keyToLower($vv);
                                if (!$idskv[$vv[$vchild]]) {
                                    $idskv[$vv[$vchild]] = true;
                                    $vids[] = $vv[$vchild];
                                }
                            }
                            if (empty($vids)) {
                                break;
                            }
                        }
                        if (!empty($idskv)) {
                            $vids = [];
                            foreach ($idskv as $k => $v) {
                                $vids[] = $k;
                            }
                            $where[] = [$key, "=", $vids];
                        }
                    } elseif ($tp == 'between'){
                        $s_val = $getdata[$key];
                        $e_val = $getdata[$key."__"];
                        !empty($s_val) && $where[] = [$key, ">=", $s_val];
                        !empty($e_val) && $where[] = [$key, "<=", $e_val];
                    } elseif ($tp == 'select' || $tp == 'selects' || $tp == 'checkbox'){
                        $vfrom = $vfield[$key]['from'];
                        if(isset($vfrom[3]) && is_array($vfrom[3])){
                            $gfc = $this->getFromCheck($vfrom);
                            $defkey = strtolower($gfc['link']['default_key']);
                            $thiskey = $gfc['link']['this_key'];
                            $gfcids = [];
                            $gfclist = $this->tplclass->db($gfc['link']['table'], $gfc['link']['conn'])->whereIn($thiskey, explode(",", $val))->select($defkey, $thiskey)->get();
                            foreach ($gfclist as $gfcval){
                                $gfcval = $this->tplclass->keyToLower($gfcval);
                                $gfcids[] = $gfcval[$defkey];
                            }
                            $gfcids = array_unique($gfcids);
                            if(empty($gfcids)){
                                $where[] = [$gfc['default']['key'], '=', '_#NULL#_'];
                            }else {
                                $where[] = [$gfc['default']['key'], '=', $gfcids];
                            }
                        }else {
                            if(is_array($vfrom[1]) && count($vfrom[1]) > 1 && is_string($vfrom[1][1])){
                                $keyname = $vfrom[1][1];
                            }else{
                                $keyname = $key;
                            }
                            if (isset($allfield[$keyname])) {
                                $wvarr = explode(",", $val);
                                $cwhere = [];
                                if (strpos($atype, 'int') === false) {
                                    $w = [];
                                    foreach ($wvarr as $wv) {
                                        if (!empty($wv) || $wv == 0) {
                                            $w[] = [$keyname, "like", $wv . ",%"];
                                            $w[] = 'or';
                                            $w[] = [$keyname, "like", "%," . $wv . ",%"];
                                            $w[] = 'or';
                                            $w[] = [$keyname, "like", "%," . $wv];
                                            $w[] = 'or';
                                        }
                                    }
                                    if (count($w) > 0) {
                                        $cwhere = $w;
                                    }
                                }
                                if (empty($cwhere)) {
                                    $where[] = [$keyname, "=", $wvarr];
                                } else {
                                    $where[] = [
                                        [$keyname, "=", $wvarr],
                                        'or',
                                        $cwhere
                                    ];
                                }
                            }
                        }
                    } elseif (isset($allfield[$key])) {
                        if(!empty($oper) && $oper == '='){
                            $where[] = [$key, "=", $val];
                        }else{
                            $where[] = [$key, "like", "%{$val}%"];
                        }
                    } elseif(is_array($vconfig[$key]) && isset($vconfig[$key]['find'])) {
                        $finds = $vconfig[$key]['find'];
                        $find_key = md5(json_encode($finds, true));
                        if(empty($find_wheres[$find_key])){
                            $find_wheres[$find_key]['find'] = $finds;
                        }
                        if(isset($field_kv[$key])){
                            $fkey = $field_kv[$key];
                        }else{
                            $fkey = $key;
                        }
                        $find_wheres[$find_key]['list'][$fkey] = $val;
                    }
                }
                $is_break = false;
                if(!empty($find_wheres)){
                    $comlist = [];
                    foreach ($find_wheres as $findw){
                        $find = array_reverse($findw['find']);
                        $flist = $findw['list'];
                        if(count($find) > 0 && count($flist) > 0){
                            $find0 = $find[0];
                            unset($find[0]);
                            $ftable = $find0[0];
                            $ffld = strtolower($find0[1]);
                            $fconn = "";
                            if(is_array($ftable)){
                                list($ftable, $fconn) = $ftable;
                            }
                            $fmod = $this->tplclass->db($ftable, $fconn)->select($ffld);
                            foreach ($flist as $fk=>$fv){
                                $fmod->where($fk, 'like', "%{$fv}%");
                            }
                            $fl = $fmod->get();
                            $fls = [];
                            foreach ($fl as $fv){
                                $fv = $this->tplclass->keyToLower($fv);
                                $fls[] = $fv[$ffld];
                            }
                            if(empty($fls)){
                                $is_break = true;
                                break;
                            }
                            $fu_field = $find0[2];
                            foreach ($find as $fk=>$fv){
                                $ftable = $fv[0];
                                $ffld = $fv[1];
                                $fconn = "";
                                if(is_array($ftable)){
                                    list($ftable, $fconn) = $ftable;
                                }
                                $fl = $this->tplclass->db($ftable, $fconn)->select($ffld)->whereIn($fv[2], $fls)->get();
                                $fls = [];
                                foreach ($fl as $fvv){
                                    $fvv = $this->tplclass->keyToLower($fvv);
                                    $fls[] = $fvv[$ffld];
                                }
                                $fu_field = $fv[2];
                            }
                            $comlist[$fu_field][] = $fls;
                        }
                    }
                    foreach ($comlist as $key=>$val){
                        $vcots = [];
                        $vcot = count($val);
                        foreach ($val as $v){
                            foreach ($v as $vv) {
                                if (isset($vcots[$vv])) {
                                    $vcots[$vv] ++;
                                } else {
                                    $vcots[$vv] = 1;
                                }
                            }
                        }
                        $realv = [];
                        foreach ($vcots as $k=>$v){
                            if($v >= $vcot){
                                $realv[] = $k;
                            }
                        }
                        $where[] = [$key, '=', $realv];
                    }
                }
                if($is_break){
                    $retconfig['config']['where'] = true;
                }else {
                    $retconfig['config']['where'] = $where;
                }
            }
        }
        $this->is_tree = $is_tree;
        $this->retconfig = $retconfig;
        return $retconfig;
    }

    /**
     * 设置子节点数量
     * @param $tree
     * @param $srcdata
     */
    private function setTreeChildCount($tree, &$srcdata){
        $cids = [];
        $c = strtolower($tree['child']);
        $p = strtolower($tree['parent']);
        if($srcdata === null){
            return;
        }
        foreach ($srcdata as $key=>$val){
            $cids[] = $val[$c];
        }
        $countlist = $this->tplclass->db()->select(\DB::raw("count(*) as count, {$p}"))->whereIn($p, $cids)->groupBy($p)->get();
        $countkv = [];
        foreach ($countlist as $key=>$val){
            $val = $this->tplclass->keyToLower($val);
            $countkv[$val[$p]] = $val->count;
        }
        foreach ($srcdata as $key => $val) {
            if(isset($countkv[$val[$c]])){
                $child = $countkv[$val[$c]];
            }else{
                $child = 0;
            }
            $srcdata[$key]['@child'] = $child;
        }
    }

    protected function _list(){
        if($this->is_export){
            unset($_POST['type']);
        }
        $tpl = $this->tplclass->tpl_init;
        $viewpath = "{$this->tplclass->tplname}.vim";
        !view()->exists($viewpath) && page404();
        $data = $this->data;
        $tpath = "layout/vim/list/";
        $tplpath = $this->base_tpl_path . $tpath . "tpl";
        if(!view()->exists($tplpath)) $tplpath = 'sys.vim.list';
        $config = $this->config;

        $oper_title = $this->oper_title;
        $pks = [];
        if(!empty($data['field'])) {
            foreach ($data['field'] as $key => $val) {
                if($val["key"] == "PRI"){
                    $pks[] = $key;
                }
            }
            if(empty($pks) && !empty($oper_title)){
                $pks = [$oper_title];
            }
        }
        $pklist = [];
        if(!empty($pks)) {
            foreach ($data['src'] as $key => $val) {
                $tpk = [];
                foreach ($pks as $v){
                    $tpk[$v] = $val[$v];
                }
                $pklist[$key] = json_encode($tpk, true);
            }
        }
        $is_tree = $this->is_tree;
        if($is_tree){
            $retconfig = $this->retconfig;
            $tree = $retconfig['config']['tree'];
            $this->setTreeChildCount($tree, $data['src']);
        }

        if(is_string($data['_'])) page404($data['_']);
        //关联查询功能
        foreach ($this->vconfig as $key=>$val){
            $vfrom = $val['from'];
            $tp = $val['type'];
            $tlst = $val['list'];
            if(is_array($vfrom) || $tlst){
                if(empty($vfrom[3]) && !is_array($vfrom[3])){
                    if(is_array($vfrom[1]) && count($vfrom[1]) > 1){
                        $vkey = $vfrom[1][1];
                    }elseif(is_string($vfrom[1])){
                        $vkey = $key;
                    }
                    if(!empty($vkey)) {
                        foreach ($data['src'] as $k => $v) {
                            if (isset($v[$vkey])) {
                                $valx = $v[$vkey];
                                $data['src'][$k][$key] = $valx;
                                if ($tp == 'select' || $tp == 'selects' || $tp == 'checkbox') {
                                    $lst = explode(",", $valx);
                                    foreach ($lst as $kk => $vv) {
                                        if (isset($tlst[$vv])) {
                                            $lst[$kk] = $tlst[$vv];
                                        }
                                    }
                                    $data['_'][$k][$key] = implode(", ", $lst);
                                }
                            }
                        }
                    }
                }else{
                    $linfo = $this->getFromCheck($vfrom);
                    $lkeys = [];
                    $lkey = $linfo['default']['key'];
                    foreach ($data['src'] as $k=>$v){
                        $lkeys[] = $v[$lkey];
                    }
                    $lkv = [];
                    $dk = $linfo['default']['key'];
                    $lk = strtolower($linfo['link']['default_key']);
                    $lv = strtolower($linfo['link']['this_key']);
                    $llist = $this->tplclass->db($linfo['link']['table'], $linfo['link']['conn'])->whereIn($lk, $lkeys)->select($lk, $lv)->get();
                    foreach ($llist as $lval){
                        $lval = $this->tplclass->keyToLower($lval);
                        $lkv[$lval[$lk]][] = $lval[$lv];
                    }
                    foreach ($data['src'] as $k=>$v){
                        $fval = $lkv[$v[$dk]];
                        if(!empty($fval)) {
                            $data['src'][$k][$key] = implode(", ", $fval);
                            foreach ($fval as $kk=>$vv){
                                if(isset($tlst[$vv])) {
                                    $fval[$kk] = $tlst[$vv];
                                }
                            }
                            $data['_'][$k][$key] = implode(", ", $fval);
                        }
                    }
                }
            }elseif($tp == 'tree'){
                unset($this->vconfig[$key]['edit']);
                $tree_ids = [];
                foreach($data['src'] as $k=>$v){
                    $tree_ids[] = $v[$key];
                }
                $tree_ids = array_unique($tree_ids);
                $vtree = $val['tree'];
                $vt_name = $vtree['name'];
                if(empty($vt_name)){
                    $vt_name = $vtree['child'];
                }
                $vt_name = strtolower($vt_name);
                $is_this = false;
                $vtable = $vtree['table'];
                if(is_array($vtable)){
                    list($vtable, $vconn) = $vtable;
                }else{
                    $vconn = "";
                }
                $def_table = $this->vim->config['config']['table'];
                $def_conn = $this->vim->config['config']['conn'];
                empty($def_conn) && $def_conn = $GLOBALS['DOMAIN_CONFIG']['conn'];
                if((empty($vtable) || $vtable == $def_table) && (empty($conn) || $conn == $def_conn)){
                    $is_this = true;
                }
                // 如果不为该表和数据库则执行操作，否则会发生循环BUG
                if(!$is_this) {
                    $vtopvalue = $vtree['value'];
                    $vparent_name = strtolower($vtree['parent']);
                    $vchild_name = strtolower($vtree['child']);
                    $vlist = $this->tplclass->db($vtable, $vconn)->whereIn($vchild_name, $tree_ids)->get();
                    $vkv = [];
                    $parent_ids = [];
                    foreach ($vlist as $vval) {
                        $vval = $this->tplclass->keyToLower($vval);
                        $vkv[$vval[$vchild_name]] = [
                            'parent' => $vval[$vparent_name],
                            "name" => $vval[$vt_name]
                        ];
                        if ($vval[$vparent_name] != $vtopvalue) {
                            $parent_ids[] = $vval[$vparent_name];
                        }
                    }
                    if (!empty($parent_ids)) {
                        $parent_kvs = [];
                        while (true) {
                            $vlist = $this->tplclass->db($vtable, $vconn)->whereIn($vchild_name, $parent_ids)->get();
                            $parent_ids = [];
                            foreach ($vlist as $vval) {
                                $vval = $this->tplclass->keyToLower($vval);
                                $parent_kvs[$vval[$vchild_name]] = [
                                    'parent' => $vval[$vparent_name],
                                    "name" => $vval[$vt_name]
                                ];
                                if ($vval[$vparent_name] != $vtopvalue) {
                                    $parent_ids[] = $vval[$vparent_name];
                                }
                            }
                            if (empty($parent_ids)) {
                                break;
                            }
                        }
                    }

                    foreach ($data['src'] as $k => $v) {
                        $src_vk = $v[$key];
                        $src_str = $vkv[$src_vk]['name'];
                        if (isset($src_str)) {
                            $src_parent_id = $vkv[$src_vk]['parent'];
                            while (true) {
                                $p_src = $parent_kvs[$src_parent_id];
                                if (!isset($p_src)) {
                                    break;
                                }
                                $src_str = $p_src['name'] . " / " . $src_str;
                                $src_parent_id = $p_src['parent'];
                            }
                            $data['_'][$k][$key] = $src_str;
                        } else {
                            $data['_'][$k][$key] = "";
                        }
                    }
                }
            }
        }
        if($this->tplclass->isPost()){
            $src = $data['src'];
            $pkmd5s = $this->getPkMd5s($src);
            $retdata = [];
            $srcdata = [];
            $pkmd5kv = [];
            $sortlist = [];
            foreach ($data['_'] as $key => $val) {
                $sortkey = $pkmd5s[$key]['md5'];
                $retdata[$sortkey] = $val;
                $srcdata[$sortkey] = $src[$key];
                $pkmd5kv[$sortkey] = $pkmd5s[$key]['pk'];
                $sortlist[] = $sortkey;
            }
            if(!($this->is_tree_all && $is_tree)) {
                EXITJSON(1, "操作成功", [
                    'show' => $retdata,
                    'src' => $srcdata,
                    'pks' => $pkmd5kv,
                    'sort' => $sortlist
                ]);
            }

            $pts = [];
            $md5keyval = [];
            foreach ($data['_'] as $key => $val) {
                $sortkey = $pkmd5s[$key]['md5'];
                $md5keyval[$val[$tree['child']]] = $sortkey;
                $pts[$val[$tree['parent']]][] = $sortkey;
            }

            $parents = [];
            foreach ($pts as $key=>$val){
                $_shows = [];
                $_srcs = [];
                $_pks = [];
                foreach ($val as $vkey){
                    $_shows[$vkey] = $retdata[$vkey];
                    $_srcs[$vkey] = $srcdata[$vkey];
                    $_pks[$vkey] = $pkmd5kv[$vkey];
                }
                $pkey = $md5keyval[$key];
                empty($pkey) && $pkey = 'top';
                $parents[$pkey] = [
                    'show' => $_shows,
                    'src' => $_srcs,
                    'pks' => $_pks,
                    'sort' => $val
                ];
            }
            EXITJSON(1, "操作成功", $parents);
        }
        $types = [];
        foreach($this->vconfig as $key=>$val){
            isset($val['type']) && $types[$val['type']] = true;
        }

        $export = $_GET['_@export@_'];
        if(in_array($export, ['all', 'checked', 'this', 'print'])){
            $tv = [];
            foreach ($this->vconfig as $key=>$val){
                if(!$val['hidden']) {
                    $tv[$key] = empty($val['name']) ? $key : $val['name'];
                }
            }
            if($export == 'checked'){
                $dt = [];
                $_chk_ = trim($_GET['_@check@_']);
                if(!empty($_chk_)){
                    $checks = explode(",", $_chk_);
                    if(!empty($checks)){
                        $chk = [];
                        foreach ($checks as $_c){
                            $chk[trim($_c)] = true;
                        }
                        $src = $data['src'];
                        $pkmd5s = $this->getPkMd5s($src);
                        foreach ($pkmd5s as $key=>$val){
                            if($chk[$val['md5']]){
                                $dt[] = $data['_'][$key];
                            }
                        }
                    }
                }
            }else{
                $dt = $data['_'];
            }
            $new_dt = [];
            foreach ($dt as $dtk=>$dtv){
                foreach ($tv as $tvk=>$tvv){
                    $new_dt[$dtk][$tvk] = $dtv[$tvk];
                }
            }

            $mid = $_GET['_mid_'];
            if(empty($mid)){
                $title = '';
            }elseif($mid > 0){
                $title = $this->tplclass->db($GLOBALS['DOMAIN_CONFIG']['conn']."_menu", "user")->where("id", "=", $mid)->first()->name;
            }else{
                $menu_list = tpl("/sys/json/menu/add");
                foreach ($menu_list as $key=>$val){
                    if($mid == $val['id']){
                        $title = $val['name'];
                        break;
                    }
                }
            }
            empty($title) && $title = "Default";

            if($export == 'print') {
                $printpath = $this->base_tpl_path . $tpath . "print/tpl";
                if(!view()->exists($printpath)) $printpath = 'sys.vim.list.print';
                $vconf = $this->vconfig;
                $fkv = [];

                $cot_w = 0;
                $cot_p = 0;
                foreach ($vconf as $vk=>$vc){
                    $vcw = $vc['width'];
                    $fkv[$vk] = $vcw;
                    if(is_numeric($vcw)){
                        $cot_w += $vcw;
                    }else{
                        $cot_p ++;
                    }
                }
                if($cot_p > 0){
                    $cot_w = $cot_w / $cot_p;
                }
                return view($printpath, [
                    'field' => $tv,
                    'field_kv' => $fkv,
                    'list' => $new_dt,
                    'cot_width' => $cot_w,
                    'title' => $title
                ]);
            }
            import('Excel')->export($tv, $new_dt, $title);
            EXITJSON(1, "操作成功");
        }

        $sql_limit = env('SQL_LIMIT', 10000);
        !is_numeric($sql_limit) && $sql_limit = 10000;
        $viewdata = $this->tplclass->viewdata;
        empty($viewdata) && $viewdata = [];
        $viewdata['tpl_base'] = $this->base_tpl_path;
        $viewdata['tpl_path'] = $tpl;
        $viewdata['tpl_root'] = $this->base_tpl_path . $tpath;
        $viewdata['tpl_type'] = $this->type;
        $viewdata['list'] = $data['_'];
        $viewdata['srclist'] = $data['src'];
        $viewdata['field'] = $this->vconfig;
        $viewdata['field_src'] = $this->vconfig_src;
        $viewdata['vim'] = $this->vimconfig;
        $viewdata['config'] = $config;
        $viewdata['pklist'] = $pklist;
        $viewdata['oper_title'] = $oper_title;
        $viewdata['search'] = $this->search;
        $viewdata['is_tree'] = $is_tree;
        $viewdata['types'] = $types;
        $viewdata['_DC_'] = $GLOBALS['DOMAIN_CONFIG'];
        $viewdata['sql_limit'] = $sql_limit;

        if($is_tree){
            $viewdata['tree'] = $tree;
        }

        if(!empty($data['pageinfo'])){
            $data['pageinfo']['sizedef'] = $this->tplclass->page['pagesizedef'];
            $viewdata['pageinfo'] = $data['pageinfo'];
        }
        $od = $config['config']['order'];
        if(isset($od) && is_array($od)){
            foreach ($od as $key=>$val){
                $viewdata['_sort'] = $key;
                $viewdata['_order'] = $val;
                break;
            }
        }
        return view($tplpath, $viewdata);
    }

    /**
     * 最后的配置
     * @param $type
     */
    protected function editSetHandle($type){
        $this->setHandle($type);
        if($type == 'add') {
            $handle = $this->vimconfig['handle'];
        }else{
            if($this->is_edit_batch){
                if(isset($_GET['handle_id'])) {
                    $hid = $_GET['handle_id'];
                    empty($hid) && $hid = 0;
                    $vhandles = $this->vimconfig['handles'][$hid]['field'];
                    !empty($vhandles) && $handle = $vhandles;
                }
            }else{
                $handle = $this->vimconfig['handle'];
            }
        }

        if(!empty($handle) && is_array($handle)) {
            $vch = $this->vimconfig_handle;
            $vcf = $this->vimconfig_field;
            $allfield = $this->allfield;
            $data = $this->data['_'][0];
            foreach ($handle as $key => $val) {
                $fd = [];
                $isok = false;
                if (empty($val['type'])) unset($val['type']);
                if(is_array($vch) && is_array($vch[$key]) && !empty($vch[$key]['type'])){
                    $val['type'] = $vch[$key]['type'];
                }
                if (isset($vch[$key])) {
                    if (is_string($vch[$key]) || isset($vch[$key]['name'])) {
                        $fd = $vch[$key];
                        $isok = true;
                    }
                    if (is_array($vch[$key])) {
                        foreach ($vch[$key] as $k => $v) {
                            !isset($val[$k]) && $val[$k] = $v;
                        }
                    }
                }

                if (!$isok) {
                    if (isset($vcf[$key])) {
                        unset($vcf[$key]['hidden']);
                        if (is_string($vcf[$key]) || isset($vcf[$key]['name'])) {
                            $fd = $vcf[$key];
                            $isok = true;
                        }

                        if (is_array($vcf[$key])) {
                            foreach ($vcf[$key] as $k => $v) {
                                !isset($val[$k]) && $val[$k] = $v;
                            }
                        }
                    }
                }

                $inarray = [
                    'hidden', //隐藏文本
                    'textarea', //文本域
                    'checkbox', //多选框
                    'radio', //单选框
                    'select', //下拉
                    'selects', //下拉组合
                    'sql', //数据库
                    'json', //JSON数据
                    'field', //字段类型数据
                    'tree', //树状结构
                    'trees', //树状结构多选框
                    'dir', //目录树状结构
                    'status', //状态
                    'article', //文章类型
                    'markdown', //MarkDown文档
                    'date', //日期控件
                    'time', //时间控件
                    'password', //密码
                    'segment', //分割线
                    'image', //上传图片
                    'file', //上传文件
                    'tpl', //模板
                ];
                if (empty($val['type']) || !in_array($val['type'], $inarray)) {
                    $val['type'] = 'text';
                }
                $handle[$key] = $val;

                if (!$isok) {
                    if (!empty($allfield[$key]['name'])) {
                        $fd = $allfield[$key];
                        $isok = true;
                    }
                }

                if (!$isok) continue;
                if (isset($fd['name'])) {
                    $handle[$key]['name'] = $fd['name'];
                } elseif (is_string($fd)) {
                    $handle[$key]['name'] = $fd;
                }

                $tp = $val['type'];
                if($tp == 'trees'){
                    if($type == 'add'){
                        $handle[$key]['list'] = $this->getTreeListAll($val['tree']);
                    }else{
                        $handle[$key]['list'] = $this->getTreeListAll($val['tree'], $data[$key]);
                    }
                }
            }
        }
        $this->showhandle = $handle;
    }

    /**
     * 设置数据
     * @param $data
     */
    protected function editConfigSetdata($type){
        $vch = &$this->vimconfig_handle;
        $vcf = $this->vimconfig_field;
        $afd = $this->allfield;
        $is_post = $this->tplclass->isPost();
        $is_post && $data = &$_POST;
        $field_unsets = [];
        if(!empty($vch) && is_array($vch)) {
            foreach ($vch as $key => $val) {
                $keyname = $key;
                if (!is_string($keyname)) {
                    is_string($val) && $keyname = $val;
                }
                if (isset($val['type'])) {
                    $t = $val['type'];
                } elseif (isset($vcf[$keyname]['type'])) {
                    $t = $vcf[$keyname]['type'];
                } elseif (in_array($keyname, ['create_time', 'update_time', 'time'])) {
                    $t = $keyname;
                }
                if (in_array($t, ['create_time', 'update_time'])) {
                    $field_unsets[] = $keyname;
                    if ($is_post) {
                        if ($type == 'add') {
                            $data[$keyname] = time();
                        } else {
                            $t == 'update_time' && $data[$keyname] = time();
                        }
                    }
                } elseif ($t == 'time'){
                    if(strpos($afd[$keyname]['type'], 'int') !== false){
                        $data[$keyname] = strtotime($data[$keyname]);
                    }
                }
            }
        }
        $this->field_unsets = $field_unsets;
    }


    /**
     * 获取提交数据
     * @return array
     */
    protected function getEditPostData(){
        $pdata = $_POST;
        $vh = $this->vimconfig_handle;
        if(!is_array($vh)) return $pdata;
        $dt = [];
        $data = [];
        $vconf = $this->vconfig;
        $afd = $this->allfield;
        foreach ($pdata as $key=>$val){
            $vfrom = $vconf[$key]['from'];
            !isset($vfrom) && isset($vh[$key]) && isset($vh[$key]['from']) && $vfrom = $vh[$key]['from'];
            if(isset($vfrom[3])){
                unset($afd[$key]);
                $gfc = $this->getFromCheck($vfrom);
                if(!empty($gfc)){
                    $data[$key] = [
                        'info' => $gfc,
                        'value' => $val
                    ];
                }
            }else {
                $dkey = "";
                if (is_array($vfrom[1]) && count($vfrom[1]) > 1) {
                    $dkey = $vfrom[1][1];
                }elseif(!empty($vfrom[1]) && is_string($vfrom[1])) {
                    $dkey = $vfrom[1];
                }
                if (!empty($dkey)) {
                    $dv = $data[$dkey];
                    if (!empty($dv)) {
                        if (empty($val)) {
                            $val = $dv;
                        } else {
                            $val = $dv . "," . $val;
                        }
                        $valarr = explode(",", $val);
                        $val = implode(",", array_unique($valarr));
                    }
                }
                $data[$key] = $val;
            }
        }
        foreach ($vh as $key=>$val){
            $keyname = "";
            if(is_string($key)){
                $keyname = $key;
                if(is_array($val)){
                    foreach ($val as $k=>$v){
                        !isset($vconf[$key][$k]) && $vconf[$key][$k] = $v;
                    }
                }
            }elseif(is_string($val)){
                $keyname = $val;
                !isset($dt[$keyname]) && $dt[$keyname] = $data[$keyname];
                continue;
            }
            if(empty($keyname)) continue;
            $json = $val['json'];
            if(empty($json) || !is_string($json)){
                !isset($dt[$keyname]) && $dt[$keyname] = $data[$keyname];
                continue;
            }
            if(isset($dt[$json]) && !is_array($dt[$json])){
                unset($dt[$json]);
                $dt[$json] = [];
            }
            if($data[$keyname] == 0 || !empty($data[$keyname])) $dt[$json][$keyname] = $data[$keyname];
        }
        $realdata = [];
        $ext_data = []; //配置中其他数据
        foreach ($data as $key=>$val){
            if(isset($afd[$key])){
                if(!isset($dt[$key])){
                    $dt[$key] = $val;
                }
            }elseif(isset($vconf[$key])){
                $ext_data[$key] = $val;
            }
        }
        foreach ($dt as $key=>$val){
            if(isset($val)) {
                if (isset($afd[$key])) {
                    $tp = $vconf[$key]['type'];
                    if (is_array($val)) {
                        if (in_array($tp, ['select', 'selects', 'checkbox', 'trees'])) {
                            $v = implode(",", $val);
                        } else {
                            $v = json_encode($val, true);
                        }
                    } else {
                        empty($val) && in_array($tp, ['select', 'selects', 'checkbox', 'trees']) && $val = "";
                        $v = $val;
                    }

                    $isset = true;
                    if (isset($vh[$key])) {
                        $vhv = $vh[$key];
                        if (is_array($vhv)) {
                            if (empty($v)) {
                                !isset($v) && $isset = false;
                            }else{
                                if($vhv['md5']){
                                    // 优先md5加密
                                    if (empty($vhv['salt']) || !isset($afd[$vhv['salt']])) {
                                        $v = md5($v);
                                    } else {
                                        $salt = $this->strRand();
                                        $realdata[$vhv['salt']] = $salt;
                                        $v = md5($v . $salt);
                                    }
                                }elseif ($vhv['aes']){
                                    // aes加密
                                    if(!isset($xcrypto)){
                                        $xcrypto = import('XCrypto');
                                    }
                                    $v = $xcrypto->aesEncrypt($v);
                                }elseif ($vhv['des']){
                                    // des加密
                                    if(!isset($xcrypto)){
                                        $xcrypto = import('XCrypto');
                                    }
                                    $v = $xcrypto->desEncrypt($v);
                                }
                            }
                        }
                    }
                    $isset && $realdata[$key] = $v;
                } elseif (is_array($val) && isset($val['info']) && isset($val['value'])) {
                    $realdata[$key] = $val;
                }
            }
        }
        return [$realdata, $ext_data];
    }

    /**
     * 数据编辑功能
     * @return string
     */
    protected function editConfig($type='edit'){
        $config = $this->retconfig;
        $ttc = $this->tplclass->config;
        if(!in_array($ttc['type'], ['sql', 'sqlfind']) || !isset($ttc['config']) || empty($ttc['config']['table'])) {
            return $config;
        }

        $tp = "edit";
        is_string($type) && $tp = $type;
        $this->editConfigSetdata($tp);
        $tfroms = [];
        if($this->tplclass->isPost()){
            list($gedata, $ext_data) = $this->getEditPostData();
            if(empty($gedata) && empty($ext_data)) $this->__exitError("没有数据传递");
            if(!empty($gedata)) {
                $data = [];
                foreach ($gedata as $key => $val) {
                    if (is_array($val) && isset($val['info']) && isset($val['value'])) {
                        $tfroms[$key] = $val;
                    } else {
                        $data[$key] = $val;
                    }
                }
                $config['config'][$tp] = $data;
            }
        }
        $this->froms = $tfroms;
        $is_edit_batch = false;
        if(!empty($_GET['pk'])) { //单列编辑
            $pkstr = $_GET['pk'];
        }elseif(!empty($_GET['pks'])){ //多列编辑
            $pkstr = $_GET['pks'];
            $is_edit_batch = true;
        }

        $vhi = $this->vimconfig_handleinit;
        if(!empty($vhi)){
            if(!empty($pkstr)) {
                try{
                    $_pks = json_decode(json_decode($pkstr, true)[0], true);
                    if(!is_array($_pks)){
                        $_pks = [];
                    }
                }catch (\Exception $e){
                    $_pks = [];
                }
                foreach ($_pks as $key=>$val){
                    $vhi[$key] = $val;
                }
            }
            $pkstr = json_encode([json_encode($vhi, true)], true);
        }
        $pk = [];
        if (empty($pkstr)){
            if($tp != 'add') $this->__exitError("参数传递错误");
        }else {
            $pktmp = json_decode($pkstr, true);
            if (empty($pktmp) || !is_array($pktmp)){
                if($tp != 'add') $this->__exitError("参数传递错误");
            }
            foreach ($pktmp as $pkval){
                !empty($pkval) && $pk[] = $pkval;
            }
        }
        $wheres = [];
        $notwheres = [];
        if(!empty($pk)) {
            foreach ($pk as $key => $val) {
                if (!is_string($val)) continue;
                $varr = json_decode($val, true);
                if (empty($varr) && is_array($varr)) continue;
                foreach ($varr as $k => $v) {
                    $wheres[] = [$k, "=", $v];
                    $notwheres[] = [$k, "<>", $v];
                }
            }
        }

        $this->wherenull = true;
        if(!empty($wheres)){
            !($tp == 'add' && $this->tplclass->isPost()) && $config['config']['where'] = $wheres;
            $this->wherenull = false;
            $config['pagesize'] = 10000;
        }
        $this->is_edit_batch = $is_edit_batch;
        $vch = $this->vimconfig_handle;
        $c = $config['config']['field'];
        empty($c) && $c = [];
        $keywheres = [];
        $keyfieldnames = [];
        if(!empty($vch) && is_array($vch)) {
            foreach ($vch as $key => $val) {
                if (is_string($key)) {
                    $c[] = $key;
                    if (is_array($val)) {
                        if(!empty($val['json']) && is_string($val['json'])) {
                            $c[] = $val['json'];
                        }
                        if(isset($val['key']) && $val['key']){
                            if(isset($data[$key])) {
                                $keywheres[] = [$key, '=', $data[$key]];
                            }else{
                                $keywheres[] = [$key, '='];
                            }
                            $keyfieldnames[] = $key;
                        }
                    }
                } elseif (is_string($val)) {
                    $c[] = $val;
                }
            }
        }

        if ($this->tplclass->isPost()) {
            if(!empty($keywheres)){
                $keydb = $this->tplclass->db();
                $kinfo = [];
                foreach ($keywheres as $key=>$val){
                    if(count($val) <= 2){
                        $k = strtolower($val[0]);
                        empty($kinfo) && $kinfo = $this->tplclass->db()->where($wheres)->first();
                        $kinfo = $this->tplclass->keyToLower($kinfo);
                        $keywheres[$key][] = $kinfo[$k];
                    }
                }
                if(!empty($notwheres)){
                    $keydb->where($notwheres);
                }
                $keydb->where($keywheres);
                $fst = $keydb->first();
                if(!empty($fst)){
                    $str = "";
                    $fst = $this->tplclass->keyToLower($fst);
                    if(!empty($pk)) {
                        foreach ($pk as $val) {
                            $js = json_decode($val, true);
                            foreach ($js as $k=>$v) {
                                $str .= " {$k}=" . $fst[$k]." ";
                            }
                        }
                    }
                    if(count($keyfieldnames) > 1){
                        $this->__exitError("字段组合 (".implode("、", $keyfieldnames).") 不能重复，在".$str."中");
                    }else{
                        $this->__exitError("字段 (".$keyfieldnames[0].") 不能重复，在".$str."中");
                    }
                }
            }
        }

        $c = array_unique($c);
        $config['config']['field'] = $c;
        return $config;
    }

    /**
     * 获取表单实际值（存储在JSON中的转化）
     * @param $handle
     * @param $field
     * @return array
     */
    protected function getEditData($handle, $field){
        $fd = [];
        $fdjson = [];
        if(!is_array($handle)) return $field;
        foreach ($handle as $key=>$val){
            $json = $val['json'];
            if(empty($json)){
                if(isset($field[$key])) $fd[$key] = $field[$key];
            }else{
                $fdjson[$json][$key] = true;
            }
        }

        foreach ($fdjson as $key=>$val){
            if(isset($fd[$key])) unset($fd[$key]);
            if(isset($field[$key])){
                $jsonarr = json_decode($field[$key], true);
                if(!empty($jsonarr) && is_array($jsonarr)){
                    foreach ($jsonarr as $k=>$v){
                        $fd[$k] = $v;
                    }
                }
                unset($field[$key]);
            }
        }

        if(is_array($field)) {
            foreach ($field as $key => $val) {
                !isset($fd[$key]) && $fd[$key] = $val;
            }
        }

        $afd = $this->allfield;
        foreach ($fd as $key=>$val){
            if(isset($handle[$key]['set_value'])){
                $fd[$key] = $handle[$key]['set_value'];
            }else{
                $tp = $handle[$key]['type'];
                if($tp == 'time'){
                    $atype = $afd[$key]['type'];
                    if(empty($atype)){
                        $is_int = false;
                    }else {
                        $is_int = strpos($atype, 'int') === false ? false : true;
                    }
                    if($is_int){
                        if(empty($val)){
                            $val = time();
                        }
                        $fd[$key] = date("Y-m-d H:i:s", $val);
                    }
                }elseif($tp == 'password'){
                    if($handle[$key]['md5']){
                        $fd[$key] = "";
                    }elseif($handle[$key]['aes']){
                        // aes解密
                        if(!isset($xcrypto)){
                            $xcrypto = import('XCrypto');
                        }
                        $fd[$key] = $xcrypto->aesDecrypt($fd[$key]);
                    }elseif($handle[$key]['des']){
                        // des解密
                        if(!isset($xcrypto)){
                            $xcrypto = import('XCrypto');
                        }
                        $fd[$key] = $xcrypto->desDecrypt($fd[$key]);
                    }
                }elseif(in_array($tp, ['select', 'selects', 'checkbox'])){
                    if(empty($val)){
                        $fkv = "";
                        if($this->type == 'add' && in_array($tp, ['select', 'selects']) && strpos($afd[$key]['type'], 'int') !== false){
                            $hk_list = $handle[$key]['list'];
                            if(is_array($hk_list)){
                                foreach ($hk_list as $k =>$v){
                                    $fkv = $k;
                                    break;
                                }
                            }
                        }
                        $fd[$key] = $fkv;
                    }
                }
            }
        }
        return $fd;
    }

    /**
     * 数据编辑功能
     * @return string
     */
    protected function edit($type='edit'){
        $this->editSetHandle($type);
        $data = $this->data['_'];
        is_string($data) && $this->__exitError($data);
        $src = $this->data['src'];
        empty($src) && $src = [];
        $pkmd5s = $this->getPkMd5s($src);
        $retdata = [];
        $srcdata = [];
        if(is_array($data)) {
            foreach ($data as $key => $val) {
                $retdata[$pkmd5s[$key]['md5']] = $val;
                $srcdata[$pkmd5s[$key]['md5']] = $src[$key];
            }
        }

        //from关联数据-开始
        $afd = $this->allfield;
        $vconf = $this->vimconfig['handle'];
        $vcreps = [];
        $links = [];
        if(is_array($vconf)) {
            foreach ($vconf as $key => $val) {
                if(is_array($val)) {
                    $vfrom = $val['from'];
                    if (is_array($vfrom)) {
                        if(is_array($vfrom[3])) {
                            if (is_string($vfrom[1])) {
                                $vkey = $vfrom[1];
                            } elseif (is_array($vfrom[1]) && count($vfrom[1]) > 1) {
                                $vkey = $vfrom[1][1];
                            }
                            if (!empty($vkey) && is_string($vkey)) {
                                if (!is_array($vfrom[3]) && empty($vfrom[3])) {
                                    if (isset($afd[$vkey])) {
                                        $vcreps[$key] = $vkey;
                                    }
                                } else {
                                    $links[$key] = $val;
                                }
                            }
                        }
                    }
                }
            }
        }
        //from关联数据-结束

        if($this->tplclass->isPost()){
            if(empty($retdata)){
                $this->__exitError("操作失败！");
            }else{
                $tree = $this->vimconfig['tree'];
                if(!empty($tree) && !empty($srcdata)){
                    $this->setTreeChildCount($tree, $srcdata);
                }

                if(count($vcreps) > 0) {
                    foreach ($srcdata as $key => $val) {
                        foreach ($vcreps as $k=>$v){
                            $srcdata[$key][$k] = $val[$v];
                        }
                    }
                    foreach ($retdata as $key => $val) {
                        foreach ($vcreps as $k=>$v){
                            $retdata[$key][$k] = $val[$v];
                        }
                    }
                }

                $froms = $this->froms;
                empty($froms) && $froms = [];
                foreach ($retdata as $key=>$val){
                    foreach ($froms as $k=>$v) {
                        $fval = $v['value'];
                        $retdata[$key][$k] = $fval;
                        $finfo = $v['info'];
                        $fkey = $val[$finfo['default']['key']];
                        if(empty($fval) && $fval !== '0') {
                            $fvals = [];
                        }elseif(is_string($fval)){
                            $fvals = explode(",", $fval);
                        }elseif(is_array($fval)){
                            $fvals = [];
                            foreach ($fval as $fv){
                                $fvals[] = $fv;
                            }
                        }
                        $fmod = $this->tplclass->db($finfo['link']['table'], $finfo['link']['conn']);
                        $fdkey = $finfo['link']['default_key'];
                        $ftkey = $finfo['link']['this_key'];
                        $fmod->where($fdkey, '=', $fkey)->delete();
                        $finsert = [];
                        $tlst = $vconf[$k]['list'];
                        $vlst = [];
                        foreach ($fvals as $fv){
                            $finsert[] = [
                                $fdkey => $fkey,
                                $ftkey => $fv
                            ];
                            $vlst[] = $tlst[$fv];
                        }
                        $srcdata[$key][$k] = implode(", ", $fvals);
                        $retdata[$key][$k] = implode(", ", $vlst);
                        $fmod->insert($finsert);
                        unset($val[$k]);

                    }

                    foreach ($val as $k=>$v) {
                        $tp = $vconf[$k]['type'];
                        $tlst = $vconf[$k]['list'];
                        if(in_array($tp, ['select', 'selects', 'checkbox', 'trees'])) {
                            $tpafd = $afd[$k]['type'];
                            if (strpos($tpafd, "int") === false) {
                                $vlst = explode(",", $v);
                                foreach ($vlst as $kk=>$vv){
                                    isset($tlst[$vv]) && $vlst[$kk] = $tlst[$vv];
                                }
                                $retdata[$key][$k] = implode(", ", $vlst);
                            }
                        }elseif($tp == 'tree'){
                            $vtree = $vconf[$k]['tree'];
                            if(is_array($vtree)){
                                $vchild = strtolower($vtree['child']);
                                $vparent = strtolower($vtree['parent']);
                                $vtable = strtolower($vtree['table']);
                                $vvalue = $vtree['value'];
                                $vname = strtolower($vtree['name']);
                                $vsort = $vtree['sort'];
                                empty($name) && $name = $vchild;
                                if(is_array($vtable)){
                                    list($vtable, $vconn) = $vtable;
                                }else{
                                    $vconn = "";
                                }
                                $vids = [$v];

                                $vorder = [];
                                if(!empty($vsort) && is_array($vsort)) {
                                    if (is_string($vsort[0]) && is_string($vsort[1])) {
                                        $vorder[$vsort[0]] = $vsort[1];
                                    }else {
                                        foreach ($vsort as $key => $val) {
                                            if (is_string($key) && is_string($val)) {
                                                $vorder[$key] = $val;
                                            }
                                        }
                                    }
                                }

                                $names = [];
                                while(true){
                                    $vmod = $this->tplclass->db($vtable, $vconn)->whereIn($vchild, $vids);
                                    if(!empty($vsort) && is_array($vsort)){
                                        foreach ($vsort as $vs){
                                            list($vs_k, $vs_v) = $vs;
                                            if(is_string($vs_k) && is_string($vs_v)){
                                                $vmod->orderBy($vs_k, $vs_v);
                                            }
                                        }
                                    }
                                    $vlist = $vmod->get();
                                    $vids = [];
                                    foreach ($vlist as $kk=>$vv){
                                        $vv = $this->tplclass->keyToLower($vv);
                                        if($vv[$vparent] != $vvalue){
                                            $vids[] = $vv[$vparent];
                                        }
                                        $names[] = $vv[$vname];
                                    }
                                    if(empty($vids)){
                                        break;
                                    }
                                }
                                $retdata[$key][$k] = implode(" / ", array_reverse($names));
                            }
                        }
                    }
                }
                $retdata = [
                    'src' => $srcdata,
                    'show' => $retdata
                ];
                $type == 'add' && $retdata['pks'] = $pkmd5s;

                EXITJSON(1, "操作成功！", $retdata);
            }
        }else{
            if(count($vcreps) > 0) {
                foreach ($src as $key => $val) {
                    foreach ($vcreps as $k=>$v){
                        $src[$key][$k] = $val[$v];
                    }
                }
            }

            if(count($links) > 0) {
                foreach ($links as $key => $val) {
                    $gfc = $this->getFromCheck($val['from']);
                    $defkey = $gfc['default']['key'];
                    foreach ($src as $k => $v) {
                        $defv = $this->tplclass->db($gfc['link']['table'], $gfc['link']['conn'])->where($gfc['link']['default_key'], "=", $v[$defkey])->get();
                        $defids = [];
                        foreach ($defv as $dv){
                            $dv = $this->tplclass->keyToLower($dv);
                            $dvk = strtolower($gfc['link']['this_key']);
                            $defids[] = $dv[$dvk];
                        }
                        $src[$k][$key] = implode(",", $defids);
                    }
                }
            }
            $tpath = "layout/vim/handle/";
            $tplpath = $this->base_tpl_path . $tpath . "tpl";
            if(!view()->exists($tplpath)) $tplpath = 'sys.vim.handle';
            $viewdata = $this->tplclass->viewdata;
            empty($viewdata) && $viewdata = [];
            $viewdata['data'] = $data;
            $viewdata['src'] = $src;
            if($type == 'add') {
                $fd = [];
                isset($src[0]) && $fd = $src[0];
                $field = [];
                if(!empty($this->vimconfig['handle']) && is_array($this->vimconfig['handle'])) {
                    foreach ($this->vimconfig['handle'] as $key => $val) {
                        $v = $val['value'];
                        $tp = $val['type'];
                        if ($tp == 'tree') {
                            $tree = $this->vconfig[$key]['tree'];
                            !$this->wherenull && $v = $fd[$tree['child']];
                            if (empty($v)) $v = $tree['value'];
                        } else {
                            if (empty($v)) $v = "";
                        }
                        $field[$key] = $v;
                    }
                }
                $viewdata['field'] = $field;
            }else{
                if (count($src) > 0) {
                    $viewdata['field'] = $src[0];
                }
            }
            if($type == 'view'){
                $is_view = true;
            }else{
                $is_view = false;
            }

            $showhandle = $this->showhandle;
            $field_unsets = $this->field_unsets;
            if(is_array($field_unsets)){
                foreach ($field_unsets as $val){
                    unset($showhandle[$val]);
                }
            }
            $f_fmt = $this->file_formats;
            $types = [];
            if(!empty($showhandle) && is_array($showhandle)) {
                $i = 0;
                foreach ($showhandle as $key => $val) {
                    $vtp = $val['type'];
                    isset($val['type']) && $types[$vtp] = true;
                    $showhandle[$key]['src_name'] = $val['name'];
                    if(in_array($vtp, ['file', 'image'])) {
                        if ($vtp == 'file') {
                            $fmt = $val['format'];
                            if (empty($fmt)) {
                                $fmt = $f_fmt[$vtp];
                            }
                        } else {
                            $fmt = $f_fmt[$vtp];
                        }
                        $fmtstr = implode(", ", $fmt);
                        $val['remark'] = "支持的格式：" . $fmtstr;
                    }
                    if($i <= 0){
                        $cls = 'layui-layer-tips-first';
                    }else{
                        $cls = '';
                    }
                    if(!empty($val['remark'])){
                        $showhandle[$key]['name'] = '<span class="js_name_remark">'.$val['name'].'</span><div class="layui-layer layui-layer-tips '.$cls.'"><div class="layui-layer-content"><div class="in">'.$val['remark'].'</div><i class="layui-layer-TipsG layui-layer-TipsT"></i><i class="layui-layer-TipsG layui-layer-TipsT layui-layer-TipsTOut"></i></div><span class="layui-layer-setwin"></span></div>';
                    }
                    $i ++;
                }
            }
            $viewdata['allfield'] = $this->allfield;
            $viewdata['field'] = $this->getEditData($showhandle, $viewdata['field']);
            $viewdata['is_view'] = $is_view;
            $viewdata['tpl_base'] = $this->base_tpl_path;
            $viewdata['tpl_path'] = $this->tplclass->tpl_init;
            $viewdata['tpl_type'] = $this->type;
            $viewdata['types'] = $types;
            $viewdata['_DC_'] = $GLOBALS['DOMAIN_CONFIG'];
            $handle_group = [];
            $handle_group_checked = -1;
            if(is_array($showhandle)) {
                foreach ($showhandle as $key => $val) {
                    $gkey = $val['group'];
                    empty($gkey) && $gkey = "";
                    $handle_group[$gkey][$key] = $val;
                    if($handle_group_checked == -1 || $val['checked']){
                        $handle_group_checked = $gkey;
                    }
                }
            }
            $viewdata['handle_group'] = $handle_group;
            $viewdata['handle_group_checked'] = $handle_group_checked;
            return view($tplpath, $viewdata);
        }
    }

    /**
     * @param $info
     * @param $values
     */
    private function getTreePks($info, $values, &$retkvs = []){
        if(!empty($values) && !empty($info)){
            $db = $this->tplclass->db();
            $parent = $info['parent'];
            $child = $info['child'];
            $tmpkvs = $db->whereIn($parent, $values)->get();
            if(!empty($tmpkvs)){
                $tmpvals = [];
                foreach ($tmpkvs as $key=>$val){
                    $ch = $this->tplclass->keyToLower($val)[$child];
                    $retkvs[] = $ch;
                    $tmpvals[] = $ch;
                }
                if(!empty($tmpvals)){
                    return $this->getTreePks($info, $tmpvals, $retkvs);
                }
            }
        }
        return $retkvs;
    }

    /**
     * 删除关联表内容
     * @param $dbinfo
     * @param $conf
     * @param $pklist
     */
    private function deletesConfigLinktable($dbinfo, $pklist){
        if(empty($dbinfo) || empty($pklist)) return;
        $tablename = "";
        $connname = "";
        if(is_string($dbinfo)){
            $tablename = $dbinfo;
        }elseif(is_array($dbinfo)){
            list($table, $conn) = $dbinfo;
            if(!is_string($table)) return;
            if(empty($conn) || !is_string($conn)){
                $tablename = $table;
            }else{
                $connname = $conn;
            }
        }else{
            return;
        }

        if(empty($connname)){
            $mod = $this->tplclass->db($tablename);
            $moddel = $this->tplclass->db($tablename);
        }else{
            $mod = $this->tplclass->db($tablename, $connname);
            $moddel = $this->tplclass->db($tablename, $connname);
        }

        foreach ($pklist as $key=>$val){
            $moddel->orWhereIn($key, $val);
        }
        try {
            $moddel->delete();
            if ($mod->count() <= 0) {
                $mod->truncate();
            }
        }catch (\Exception $e){
            $this->__exitError($e->getMessage());
        }
    }

    /**
     * 批量删除数据
     */
    protected function deletesConfig($type="all"){
        $ttc = $this->tplclass->config;
        if(!in_array($ttc['type'], ['sql', 'sqlfind']) || !isset($ttc['config']) || empty($ttc['config']['table'])) {
            return false;
        }

        if(!$this->tplclass->isPost()) $this->__exitError("500 ERROR");
        $data = $_POST['data'];
        if(empty($data) && !is_array($data)) $this->__exitError("无数据传递！");
        $db = $this->tplclass->db();
        $wi = 0;
        $pkarr = [];
        $pkkv = [];
        $delete = $this->vimconfig_delete;
        foreach ($data as $key=>$val){
            $varr = json_decode($val, true);
            if(is_array($varr)){
                foreach ($varr as $k=>$v){
                    if(!empty($k)) {
                        $db->orWhere($k, $v);
                        $wi ++;
                        $pkarr[] = $v;
                        $pkkv[$k][] = $v;
                    }
                }
            }
        }

        $tree = $this->vimconfig_tree;
        if(is_array($tree) && !empty($tree)) {
            $parent = strtolower(trim($tree['parent']));
            $child = strtolower(trim($tree['child']));
            $tree['parent'] = $parent;
            $tree['child'] = $child;
            $kvs = $this->getTreePks($tree, $pkarr);
            if (!empty($kvs)) {
                foreach ($kvs as $val) {
                    $db->orWhere($child, $val);
                    $wi++;
                }
            }
        }

        $db_list = json_decode(json_encode($db->get(), true), true);
        $delete_pk = [];
        foreach ($db_list as $key=>$val){
            foreach ($delete as $k=>$v){
                foreach ($v[1] as $kk=>$vv){
                    $delete_pk[$v[0]][$vv][] = $val[$kk];
                }
            }
        }
        //删除关联数据库
        if(!empty($delete) && is_array($delete) && !empty($pkkv)){
            foreach ($delete as $key=>$val){
                if(empty($val) || !is_array($val) || count($val) < 2 || !is_array($val[1])) continue;
                $this->deletesConfigLinktable($val[0], $delete_pk[$val[0]]);
            }
        }
        if($wi <= 0) $this->__exitError("数据操作失败！");
        $status = $db->delete();
        if($status > 0){
            $dbcot = $this->tplclass->db();
            if($dbcot->count() <= 0){
                $dbcot->truncate();
            }
            if($type == "all" || !empty($tree)) {
                EXITJSON(1, "删除成功，删除数据数量({$status})！");
            }else{
                EXITJSON(1, "删除成功！");
            }
        }else{
            $this->__exitError("删除失败！");
        }
    }

    /**
     * 删除单个数据
     */
    protected function deleteConfig(){
        $this->deletesConfig("one");
    }

    /**
     * 清空关联表内容
     * @param $dbinfo
     * @param $conf
     * @param $pklist
     */
    private function clearConfigLinktable($dbinfo){
        if(empty($dbinfo)) return;
        $tablename = "";
        $connname = "";
        if(is_string($dbinfo)){
            $tablename = $dbinfo;
        }elseif(is_array($dbinfo)){
            list($table, $conn) = $dbinfo;
            if(!is_string($table)) return;
            if(empty($conn) || !is_string($conn)){
                $tablename = $table;
            }else{
                $connname = $conn;
            }
        }else{
            return;
        }

        if(empty($connname)){
            $mod = $this->tplclass->db($tablename);
        }else{
            $mod = $this->tplclass->db($tablename, $connname);
        }

        try {
            $mod->truncate();
        }catch (\Exception $e){
            $this->__exitError($e->getMessage());
        }
    }

    /**
     * 清空操作
     */
    protected function clear(){
        if($this->tplclass->isPost()) {
            $vim = $this->vimconfig;
            if (!$vim['is']['clear'] || $_POST['bool'] != 'true') {
                $this->__exitError("不允许清空操作！");
            }
            $this->tplclass->db()->truncate();

            $delete = $vim['delete'];
            //删除关联数据库
            if(!empty($delete) && is_array($delete)){
                foreach ($delete as $key=>$val){
                    if(empty($val) || !is_array($val) || count($val) < 2 || !is_array($val[1])) continue;
                    $this->clearConfigLinktable($val[0]);
                }
            }
            EXITJSON(1, "数据清空成功！");
        }
        page404();
    }

    /**
     * 增加
     */
    protected function addConfig(){
        return $this->editConfig('add');
    }

    /**
     * 增加
     */
    protected function add(){
        return $this->edit('add');
    }

    /**
     * 编辑初始设置，存在则编辑，不存在则增加
     */
    protected function handleConfig(){
        // 当没有设置数据库信息时不执行表操作
        $ttc = $this->tplclass->config;
        if(empty($ttc) || !in_array($ttc['type'], ['sql', 'sqlfind']) || empty($ttc['config']) || empty($ttc['config']['table'])){
            return false;
        }
        $pks = $this->getPks();
        if(empty($pks) || !is_array($pks)) $this->__exitError("主键设置无效");
        $hinit = $this->vimconfig_handleinit;
        if(empty($hinit)) $this->__exitError("参数设置无效");
        $this->tplclass->getDataForArgs($hinit);
        $unset = [];
        foreach ($hinit as $key=>$val){
            empty($val) && $unset[] = $key;
        }
        foreach ($unset as $val){
            unset($hinit[$val]);
        }
        if(empty($hinit)) $this->__exitError("参数设置无效");
        $list = $this->tplclass->db()->where($hinit)->get();
        $cot = count($list);
        if($cot <= 0){
            if($this->tplclass->db()->insert($hinit)) {
                $data = $this->tplclass->db()->where($hinit)->first();
            }
        }else{
            $data = $list[0];
        }
        if(empty($data)) $this->__exitError("数据设置出错");

        $pkarr = [];
        $data = $this->tplclass->keyToLower($data);
        foreach ($pks as $val){
            $pkarr[$val] = $data[$val];
        }

        if(!empty($pkarr) && $cot > 1){
            $delmod = $this->tplclass->db();
            foreach ($pkarr as $key=>$val){
                $delmod->where($key, "<>", $val);
            }
            $delmod->delete();
        }

        $_GET['pk'] = json_encode([json_encode($pkarr, true)], true);
        return $this->editConfig('edit');
    }

    /**
     * 编辑初始设置，存在则编辑，不存在则增加
     */
    protected function handle(){
        return $this->edit('edit');
    }

    /**
     * 预览
     */
    protected function viewConfig(){
        return $this->editConfig();
    }

    /**
     * 预览
     */
    protected function view(){
        return $this->edit('view');
    }

    /**
     * 上传文件
     */
    protected function upload(){
        $field = $_GET['field'];
        $handle = $this->vconfig;
        $hf = $handle[$field];
        empty($hf) && $hf = [];
        $vhandle = $this->vimconfig_handle[$field];
        if(!empty($vhandle) && is_array($vhandle)){
            foreach ($vhandle as $key=>$val){
                !isset($hf[$key]) && $hf[$key] = $val;
            }
        }
        $this->tplclass->getDataForArgs($hf);
        if(isset($hf['path'])){
            $path = $hf['path'];
        }else{
            $path = "";
        }
        $thumbs = $hf['thumbs'];
        empty($thumbs) && $thumbs = [];

        $vtp = $hf['type'];
        $f_fmt = $this->file_formats;
        if($vtp == 'image' || $vtp == 'file'){
            $format = [];
            $vfm = $hf['format'];
            if($vtp == 'image'){
                $format = $f_fmt['image'];
            }elseif(empty($vfm)) {
                $format = $f_fmt['file'];
            }else{
                if(is_string($vfm)){
                    $vfm = explode(",", $vfm);
                }
                if(is_array($vfm)){
                    foreach ($vfm as $v){
                        if(!empty($v) && is_string($v)){
                            $v = strtolower(trim($v));
                            !empty($v) && $format[] = $v;
                        }
                    }
                }
            }

            if(!empty($format)){
                foreach ($_FILES as $key=>$val) {
                    $name = $val['name'];
                    $pos = strrpos($name, ".");
                    if ($pos > 0) {
                        $file_ext = strtolower(trim(substr($name, $pos + 1)));
                        if(!in_array($file_ext, $format)){
                            $this->__exitError("格式 {$file_ext} 不支持");
                        }
                    }else{
                        $this->__exitError("上传后缀名不能为空");
                    }
                }
            }
        }else{
            $this->__exitError("{$vtp} 不被允许");
        }

        $fileinfo = $this->tplclass->upload($thumbs, false, $path, $hf['filename'])->urls("_file_".$field);
        if(empty($fileinfo)) $this->__exitError("404 ERR");
        EXITJSON(1, "上传成功", $fileinfo);
    }

    protected function selectTreeConfig(){
        $key = $_POST['key'];
        $tree = $this->vconfig[$key]['tree'];
        if(empty($tree)){
            $vh = $this->vimconfig_handle;
            if(isset($vh[$key]) && isset($vh[$key]['tree'])){
                $tree = $vh[$key]['tree'];
            }
        }
        empty($tree) && $this->__exitError("数据传递无效");
        (!isset($_POST['value'])) && $this->__exitError("数据传递无效");
        $value = $_POST['value'];
        $notvalues = $_POST['notvalues'];
        empty($notvalues) && $notvalues = [];
        $treelist = $this->getTreeList($tree, $value, $notvalues, false);
        EXITJSON(1, "OK", $treelist);
    }

    /**
     * 接口页面数据返回
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function test(){
        $data = $this->tplclass->getDataForArgsArray();
        $api_config = $data['api'];
        $gets = $api_config['get'];
        $posts = $api_config['post'];
        empty($api_config) && $api_config = [];
        empty($gets) && $gets = [];
        empty($posts) && $posts = [];
        if(!empty($data['get'])) {
            foreach ($data['get'] as $val) {
                if (!array_key_exists($val, $gets)) {
                    $gets[$val] = [];
                }
            }
        }

        if(!empty($data['post'])) {
            foreach ($data['post'] as $val) {
                if (!array_key_exists($val, $posts)) {
                    $posts[$val] = [];
                }
            }
        }

        if($data['type'] == 'sql') {
            empty($gets['p']) && $gets['p'] = ['分页'];
            empty($gets['psize']) && $gets['psize'] = ['分页列数'];
        }

        $api_config['get'] = $gets;
        $api_config['post'] = $posts;
        return view('web.api', [
            'api_path' => $this->tplclass->tpl_init,
            'api_config' => $api_config
        ]);
    }

    /**
     * 获取用户登录信息
     * @return mixed
     */
    private function getUserInfo(){
        $userinfo = $this->vim->userinfo;
        if(empty($userinfo)){
            $userinfo = $this->tplclass->userinfo;
            if(empty($userinfo)){
                $btpt = BASE_TPL_PATH_TOP;
                $btpt = trim(trim($btpt), "/");
                $btpt = str_replace("/", "_", $btpt);
                $userinfo = \Session::get($btpt."_sys_user_login_userinfo");
                if(
                    empty($userinfo) &&
                    $GLOBALS['DOMAIN_CONFIG']['backstage'] &&
                    !in_array($this->tplclass->tpl_init, [
                        'sys/user/login',
                        'sys/user/login/captcha'
                    ])
                ){
                    redirect("/sys/user/login")->send();
                }
            }
        }
        return $userinfo;
    }

    /**
     * 系统配置
     */
    protected function sysConfig(){
        $type = trim($_GET['type']);
        if(empty($type)){
            $this->__exitError("Err 404!");
        }
        $type = strtolower($type);
        $is_post = $this->tplclass->isPost();
        if($type == 'menu_field'){
            if(!$is_post){
                $this->__exitError("参数传递错误");
            }
            $userinfo = $this->getUserInfo();
            $dc = $GLOBALS['DOMAIN_CONFIG'];
            if(empty($userinfo) && $dc['backstage']){
                $this->__exitError("用户未登录");
            }
            $id = $_POST['id'];
            $field = $_POST['field'];
            if(!is_numeric($id)){
                $this->__exitError("参数传递错误");
            }
            $user_id = $userinfo['id'];
            $conn = $dc['conn'];
            $menu_count = $this->tplclass->db($conn."_menu_field", 'user')->where('menu_id', '=', $id)->where('user_id', '=', $user_id)->count();
            if($menu_count > 0){
                $this->tplclass->db($conn."_menu_field", 'user')->where('menu_id', '=', $id)->where('user_id', '=', $user_id)->update([
                    'field' => $field
                ]);
                $msg = '添加成功';
            }else{
                $this->tplclass->db($conn."_menu_field", 'user')->insert([
                    'menu_id' => $id,
                    'user_id' => $user_id,
                    'field' => $field
                ]);
                $msg = '保持成功';
            }
            $this->tplclass->unCache("info_{$id}_{$user_id}");
            EXITJSON(1, $msg);
        }
        $this->__exitError("命令错误");
    }

    protected function sys(){
    }

}
