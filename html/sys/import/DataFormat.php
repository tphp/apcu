<?php

class DataFormat{
    function __construct($dir_path = "", $obj = Null)
    {
        $this->filename = $dir_path."/_.ini";
        $this->obj = $obj;
        $this->strl = "_@[";
        $this->strr = "]@_";
        $this->loopkeys = [];
        if(strtolower(trim(getenv('INFO_SEND_SHOW'))) == 'false'){
            $this->info_send_show = false;
        }else{
            $this->info_send_show = true;
        }
    }

    /**
     * 设置ini配置信息
     */
    public function setIniData($ini_data = null){
        if(empty($ini_data)) {
            if(is_file($this->filename)){
                $this->ini_data = import('IniFile', $this->filename, "info")->readAll();
            }else{
                $this->ini_data = [];
            }
        }else{
            $this->ini_data = $ini_data;
        }
    }

    /**
     * JSON格式打印
     * @param $array
     * @return string
     */
    private function jsonEcho($array, $is_print = false){
        if(!is_array($array)) return $array;
        if(!$is_print) return json_encode($array, JSON_UNESCAPED_UNICODE);
        $jsonstr = json_encode($array, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        return $jsonstr;
    }

    /**
     * 设置json数据并替换
     * @param $info
     */
    private function setJson(&$info){
        foreach ($info as $key=>$val){
            if(is_array($val)){
                $this->setJson($info[$key]);
            }else{
                if(!(is_numeric($val) || is_bool($val))) {
                    $val = str_replace("{", $this->strl, $val);
                    $val = str_replace("}", $this->strr, $val);
                }
                $info[$key] = $val;
            }
        }
    }

    /**
     * 设置loop键值替换
     * @param $loops
     */
    private function setFieldLoop(&$loops){
        foreach ($loops as $key=>$val) {
            if (isset($val['keys'])) {
                foreach ($val['keys'] as $k => $v) {
                    $v = str_replace($this->strl, "{", $v);
                    $v = str_replace($this->strr, "}", $v);
                    $loops[$key]['keys'][$k] = $v;
                }
            }
            if (isset($val['next'])) {
                $this->setFieldLoop($loops[$key]['next']);
            }
        }
    }

    private function getField($inisets, $fieldlist){
        if(empty($fieldlist) || !is_array($fieldlist)) return [];
        $this->setJson($inisets);
        $retfield = [];
        foreach ($fieldlist as $key=>$val){
            $fval = str_replace("{", $this->strl, $key);
            $fval = str_replace("}", $this->strr, $fval);
            $retfield[$key] = $fval;
        }
        $loops = $inisets['loops'];
        $this->setFieldLoop($loops);
        unset($inisets['loops']);
        return [$this->jsonEcho($inisets), $loops, $retfield];
    }

    /**
     * 获取LOOP下标字符串
     * @param $str
     * @return array
     */
    private function getKeyIndexs($str){
        $str = preg_replace('/\[LOOP(.*?)\]/is', '$1', $str);
        $str = preg_replace('/<(.*?)>/is', '', $str);
        $str = preg_replace('/&nbsp;/is', '', $str);
        $str = strtolower(trim($str));
        $ret = [[], false];
        if($str == "") return $ret;
        preg_match_all('/\{(.*?)\}/is', $str, $arr);
        $reps = [];
        $i = 0;
        foreach($arr[0] as $a){
            $flag = '{#_'.$i.'_#}';
            $reps[$a] = $flag;
            $str = str_replace($a, $flag, $str);
            $i ++;
        }

        $is_out = false;
        $is_json = false;
        $str = ltrim($str);
        $strlen = strlen($str);
        $is_break = false;
        for($i = 0; $i < $strlen; $i ++){
            $s = $str[$i];
            if($s == ':'){
                $is_out = true;
            }elseif($s == '~'){
                $is_json = true;
            }elseif($s != '!'){
                $str = substr($str, $i);
                $is_break = true;
                break;
            }
        }
        if(!$is_break) return $ret;
        $str = ltrim($str);
        if(empty($str)) return $ret;

        $strarr = explode(".", $str);
        $retarr = [];
        foreach ($strarr as $key=>$val){
            $val = trim($val);
            if($val == "") continue;
            foreach ($reps as $k=>$v){
                $val = str_replace($v, $k, $val);
            }
            $val = trim($val);
            if($val == "") continue;
            $val = str_replace("\#", "@_!", $val);
            $val = str_replace("#", " ", $val);
            $val = str_replace("@_!", "#", $val);
            $retarr[$key] = $val;
        }
        return [$retarr, $is_out, $is_json];
    }

    /**
     * 获取
     * @param $content
     * @param $e
     */
    private function getStrLoopStep($content, $e){
        $elen = strlen($e);
        $loopkeys = $this->loopkeys[$this->type];
        $flag = '[LOOP';
        $flaglen = strlen($flag);
        $clen = strlen($content);
        $tmpi = 0;
        $realpos = -1;
        $isloop = false;
        for($i = 0; $i <= $clen - $elen; $i ++){
            $fstr = substr($content, $i, $flaglen);
            $estr = substr($content, $i, $elen);
            if($fstr == $flag){
                foreach ($loopkeys as $loop=>$looplen){
                    $rlen = $i + $looplen;
                    if($rlen <= $clen){
                        $kstr = substr($content, $i, $looplen);
                        if($kstr == $loop) {
                            $tmpi ++;
                            $isloop = true;
                        }
                    }
                }
            }elseif($estr == $e){
                if($tmpi > 0){
                    $tmpi --;
                }else{
                    $realpos = $i;
                    break;
                }
            }
        }
        if($realpos < 0){
            return [];
        }
        $inhtml = substr($content, 0, $realpos);
        $retcontent = substr($content, $realpos + $elen);
        return [$inhtml, $retcontent, $isloop];
    }

    /**
     * 设置循环变量查询
     * @param $content
     * @param string $s
     */
    private function setStrLoopIndex(&$content, $s = '[LOOP]', &$loops, &$keyinit){
        list($keyindexs, $is_out, $is_json) = $this->getKeyIndexs($s);
        $e = '[/LOOP]';
        $loopi = $this->loopi;
        $retstr = "";
        $not_text = "";
        $xstring = import('XString');
        while(!empty($content)){
            if(strpos($content, $s) === false) {
                $retstr .= $content;
                $not_text .= $content;
                break;
            }else{
                $retstr .= $xstring->getSubStr($content, null, $s);
                $not_text .= $retstr;
                $content = $xstring->getSubStr($content, $s);
                if(strpos($content, $e) === false){
                    $retstr .= $content;
                    $not_text .= $content;
                    break;
                }else{
                    $gsls = $this->getStrLoopStep($content, $e);
                    if(empty($gsls)){
                        $retstr .= $content;
                        break;
                    }
                    list($inhtml, $content, $isloop) = $gsls;
                    $keyname = "[LOOPS_{$loopi}]";
                    if($isloop) {
                        $retstr .= $keyname;
                        $loops[$keyname]['text'] = $inhtml;
                        $loops[$keyname]['is_table'] = false;
                    }else{
                        $is_tr = true;
                        $str_flag_s = "<tr>";
                        $str_flag_e = "</tr>";
                        $tr_l = $xstring->getSubStr($inhtml, null, $str_flag_s);
                        if (empty($tr_l)) {
                            $is_tr = false;
                            $str_flag_s = "<tbody>";
                            $str_flag_e = "</tbody>";
                            $tr_l = $xstring->getSubStr($inhtml, null, $str_flag_s);
                        }
                        $tr_r = $xstring->getSubStr($inhtml, $str_flag_s);
                        $tr_m = $xstring->getSubStr($tr_r, null, $str_flag_e);
                        $tr_r = $xstring->getSubStr($tr_r, $str_flag_e);
                        if (!empty($tr_l) && !empty($tr_m) && !empty($tr_r)) {
                            $retstr .= "{$tr_l}{$keyname}{$tr_r}";
                            if ($is_tr) {
                                $loops[$keyname]['text'] = "<tr>{$tr_m}</tr>";
                            } else {
                                $loops[$keyname]['text'] = $tr_m;
                            }
                        } else {
                            $retstr .= $keyname;
                            $loops[$keyname]['text'] = $inhtml;
                        }
                        $loops[$keyname]['is_table'] = $is_tr;
                    }
                    $loops[$keyname]['keys'] = $keyindexs;
                    $loops[$keyname]['is_out'] = $is_out;
                    $loops[$keyname]['is_json'] = $is_json;
                    $loopi ++;
                }
            }
        }

        if(!empty($keyinit) && is_array($keyinit)){
            $keyinit['text_loop'] = $not_text;
        }
        $this->loopi = $loopi;
        $content = $retstr;
    }

    private function setStrLoopNext(&$loops){
        foreach ($loops as $key=>$val){
            list($content, $lps) = $this->getStrLoopExt($val['text'], $loops[$key]);
            if(!empty($lps)){
                $loops[$key]['text'] = $content;
                $loops[$key]['next'] = $lps;
                $this->setStrLoopNext($loops[$key]['next']);
            }
        }
    }

    /**
     * 设置循环变量查询
     * @param $content
     * @return array
     */
    private function getStrLoopExt($content, &$keyinit = []){
        //遍历循环标签
        preg_match_all('/(\[LOOP[\s|<].*?\])|(\[LOOP\])/is', $content, $carrs);
        $carr = $carrs[0];
        if(empty($carr)) return [$content, []];
        $carr = array_unique($carr);
        $loops = [];
        $lks = [];
        foreach ($carr as $cstr){
            $lks[$cstr] = strlen($cstr);
        }
        $this->loopkeys[$this->type] = $lks;
        foreach ($carr as $cstr){
            $this->setStrLoopIndex($content, $cstr, $loops, $keyinit);
        }
        $content = preg_replace('/\[(\/?)LOOP(?!S)(.*?)\]/is', '', $content);
        return [$content, $loops];
    }

    /**
     * 设置循环变量查询
     * @param $content
     * @return array
     */
    private function getStrLoop($content){
        list($content, $loops) = $this->getStrLoopExt($content);
        $this->setStrLoopNext($loops);
        foreach ($loops as $key=>$val){
            $keys = $val['keys'];
            if(!$val['is_out'] && !empty($keys) && count($keys) == 1 && $keys[0] == 'list'){
                $loops[$key]['keys'] = [];
            }
            if($val['is_table'] && !empty($next)){
                $loops[$key]['is_table'] = false;
            }
        }
        return [$content, $loops];
    }

    /**
     * 获取字段解析列表
     * @param $fieldlist
     * @param $str
     */
    private function getFieldListShow(&$fieldlist, $str){
        $str = trim($str);
        if(empty($str)) return;
        $strlen = strlen($str);
        $bool = false;
        $pos = 0;
        $istep = 0;
        for($i = 0; $i < $strlen; $i ++){
            $s = $str[$i];
            if($s == '{'){
                if(!$bool){
                    $bool = true;
                    $pos = $i;
                }else{
                    $istep ++;
                }
            }elseif($s == '}'){
                if($istep > 0){
                    $istep --;
                }elseif($bool){
                    $bool = false;
                    $fieldlist[] = substr($str, $pos + 1, $i - $pos -1);
                }
            }
        }
    }

    private function getFieldListShowIn($str, &$fieldlist = []){
        $str = trim($str);
        if(empty($str)) return;
        $strlen = strlen($str);
        $bool = false;
        $pos = 0;
        $istep = 0;
        for($i = 0; $i < $strlen; $i ++){
            $s = $str[$i];
            if($s == '{'){
                if(!$bool){
                    $bool = true;
                    $pos = $i;
                }else{
                    $istep ++;
                }
            }elseif($s == '}'){
                if($istep > 0){
                    $istep --;
                }elseif($bool){
                    $bool = false;
                    $stmp = substr($str, $pos + 1, $i - $pos -1);
                    $posl = strpos($stmp, "{");
                    $posr = strpos($stmp, "}");
                    if($posl !== false && $posr > $posl){
                        $this->getFieldListShowIn($stmp, $fieldlist);
                    }else{
                        $fieldlist[] = $stmp;
                    }
                }
            }
        }
        return $fieldlist;
    }


    private function setFk(&$fk, $data){
        $keys = $this->getFieldListShowIn($fk);
        if(empty($keys)) return;

        if(!empty($keys)) {
            foreach ($keys as $keyname){
                $ktmp = $keyname;
                $kname = ltrim(trim($keyname), "!");
                if (isset($kname[0]) && $kname[0] == ':') {
                    $gv = $this->dbdata;
                } else {
                    $gv = $data;
                }
                $kind = ltrim($kname, ":");
                if(is_array($gv)) {
                    $kindarr = explode(".", $kind);
                    foreach ($kindarr as $kd){
                        if(!is_array($gv) || !isset($gv[$kd])){
                            $gv = "";
                            break;
                        }
                        $gv = $gv[$kd];
                    }
                }else{
                    $gv = "";
                }
                $fk = str_replace("{{$ktmp}}", $gv, $fk);
            }
            $this->setFk($fk, $data);
        }
    }

    /**
     * 匹配html标签
     * @param $str
     * @return array
     */
    private function getHtmlFlagKill($str){
        $s = preg_replace('/<(.*?)>/is', '', $str);
        $s = trim($s);
        preg_match_all('/<(.*?)>/is', $str, $array);
        $arr = [];
        $xstring = import('XString');
        foreach ($array[0] as $astr){
            $tstr = $astr;
            strpos($tstr, " ") !== false && $tstr = $xstring->getSubStr($tstr, null, " ").">";
            $arr[] = $tstr;
        }

        $rstr = implode("", $arr);
        return [$s, $rstr];
    }

    private function getFieldList(&$inisets){
        $confs = [
            'addressee',
            'copy',
            'title',
            'content',
            'recipient'
        ];
        //删除pre标签
        $content = $inisets['content'];
        if(!empty($content)) {
            $fl = [];
            $this->getFieldListShow($fl, $content);
            foreach ($fl as $fstr) {
                list($f, $fext) = $this->getHtmlFlagKill($fstr);
                if ($f != $fstr) {
                    $content = str_replace("{{$fstr}}", "{{$f}}{$fext}", $content);
                }
            }
            $content = str_replace("<p></p>", "", $content);
            $content = str_replace("<pre></pre>", "", $content);
            $content = str_replace("<a></a>", "", $content);
            $content = str_replace("<div></div>", "", $content);
            $content = str_replace("<span></span>", "", $content);
            $content = str_replace("<strong></strong>", "", $content);
            $inisets['content'] = $content;
        }

        $fieldlist = [];
        foreach ($confs as $conf){
            if(isset($inisets[$conf])){
                $this->getFieldListShow($fieldlist, $inisets[$conf]);
            }
        }
        $fieldlist = array_unique($fieldlist);
        $retflist = [];
        foreach ($fieldlist as $fieldname){
            $field = preg_replace("/<[^>]*>/is", "$1", $fieldname);
            $field = htmlspecialchars_decode($field);
            $field = strtolower(str_replace("&nbsp;", " ", $field));
            $field = ltrim(trim($field), "!:");
            $farr = explode(".", $field);
            foreach ($farr as $key=>$val){
                $val = trim($val);
                if($val == "") continue;
                $val = str_replace("\#", "@_!", $val);
                $val = str_replace("#", " ", $val);
                $val = str_replace("@_!", "#", $val);
                $farr[$key] = $val;
            }
            $retflist[$fieldname] = $farr;
        }
        return $retflist;
    }

    /**
     * 获取数组下标
     * 符号 '!' ：当值为空时不显示
     * 符号 ':' ：下标从最外层开始
     * @param $data
     * @param $keys
     * @return mixed|string
     */
    private function getArrayIndex($data, $keys, $keyname){
        $kname = ltrim(trim($keyname), ":");
        if(isset($kname[0]) && $kname[0] == '!'){
            $kstr = "";
        }else{
            $kstr = "{{$keyname}}";
        }
        $flag = "";
        $keynamelen = strlen($keyname);
        for ($i = 0; $i < $keynamelen; $i ++){
            $kn = $keyname[$i];
            if(in_array($kn, ['!', ':'])){
                $flag .= $kn;
            }else{
                break;
            }
        }
        $str = $kstr;
        if(!empty($keys)){
            $kname = ltrim(trim($keyname), "!");
            if(isset($kname[0]) && $kname[0] == ':'){
                $gv = $this->dbdata;
            }else{
                $gv = $data;
            }
            $keystmp = [];
            foreach ($keys as $fk) {
                $this->setFk($fk, $data);
                $keystmp[] = $fk;
                if(!is_array($gv) || !isset($gv[$fk])){
                    $gv = $kstr;
                    break;
                }
                $gv = $gv[$fk];
            }

            if(is_array($gv)){
                $str = $this->jsonEcho($gv);
            }else{
                $str = $gv;
            }
        }
        return $str;
    }

    /**
     * 邮件字符串替换：背景图片替换
     * @param $content
     * @return mixed|string
     */
    private function getReplaceEmailContent($content){
        $bg = import('XString')->getSubStr($content, "data-background=\"", "\">");
        if(!empty($bg)){
            $content = "<div style=\"{$bg}padding:5px;\">{$content}</div>";
        }
        return $content;
    }

    /**
     * 获取数据分组
     * @param $dblist
     * @param $inisets
     * @param $fieldlist
     * @param $json
     * @param $fields
     * @return array
     */
    private function getGroupList($dblist, $inisets, $fieldlist, $json, $fields, $is_loop){
        $retlist_ks = [];
        $_key_s = [];
        $_fields = [];
        $_group_content = [];

        $info_id = $this->ini_data['info']['id'];
        foreach ($dblist as $key => $val) {
            if(!is_array($val)) continue;
            if($this->info_send_show) {
                $company = $val['company'];
                $doctype = $val['doctype'];
                $docentry = $val['docentry'];
                if (empty($company) || empty($doctype) || empty($docentry)) {
                    continue;
                }
            }
            $tmpstr = $json;
            foreach ($fields as $k=>$v) {
                $str = $this->getArrayIndex($val, $fieldlist[$k], $k);
                $str = str_replace("\"", "\\\"", $str);
                $tmpstr = str_ireplace($this->strl . $v . $this->strr, $str, $tmpstr);
            }

            $tmpstr = str_replace($this->strl, "{", $tmpstr);
            $tmpstr = str_replace($this->strr, "}", $tmpstr);
            $tmpstr = str_replace("\n", "\r", $tmpstr);
            $tmpstr = str_replace("\r\r", "\r", $tmpstr);
            $tmpstr = str_replace("\r", "<BR>", $tmpstr);
            $tmpstr = str_replace("\t", "   ", $tmpstr);
            $rl = json_decode($tmpstr, true);


            if($this->info_send_show) {
                $rl['company'] = $company;
                $rl['doctype'] = $doctype;
                $rl['docentry'] = $docentry;
            }
            if($this->type == 'email'){
                $mem_name = 'addressee';
                $send_name = 'addresser';
            }else{
                $mem_name = 'recipient';
                $send_name = 'menu';
            }
            $mem = $rl[$mem_name];
            $sendid = $inisets[$send_name];
            $rl['mem'] = $mem;
            $rl['field'] = $val;
            if(empty($mem)) continue;
            if(!isset($_group_content[$mem])){
                $_group_content[$mem] = [
                    $mem_name => $mem,
                    $send_name => $sendid,
                    'content' => $rl['content']
                ];
                if($this->type == 'email'){
                    $_group_content[$mem]['title'] = $rl['title'];
                    $_group_content[$mem]['copy'] = [];
                }
            }
            if($this->type == 'email'){
                !empty($rl['copy']) && $_group_content[$mem]['copy'][] = $rl['copy'];
            }
            if($this->info_send_show) {
                $_k = $company . "#" . $doctype . "#" . $docentry;
            }elseif(!empty($val['id'])){
                $_k = $val['id'];
            }else{
                $_k = "_";
            }
            if(!empty($val['_key_'])){
                $_k .= "#".$val['_key_'];
            }
            $_k .= "#".$mem;
            $strk = substr(md5($_k), 0, 8);
            $strn = hexdec($strk)."";
            $t_key_ = $strn.str_pad($info_id, 5, "0", STR_PAD_LEFT);
            $rl['_key_'] = $t_key_;
            $_key_s[] = $t_key_;
            $retlist_ks[$t_key_] = $rl;
            $_fields[$t_key_] = $val;
        }

        if(!empty($_key_s)){
            if($this->type == 'qywx') {
                $_key_list = $this->obj->db("manage_qywx_keys")->whereIn("key", $_key_s)->get();
            }else{
                $_key_list = $this->obj->db("manage_email_keys")->whereIn("key", $_key_s)->get();
            }
            foreach ($_key_list as $key=>$val){
                unset($retlist_ks[$val->key.""]);
            }
        }

        //如果不是循环则返回逐个数据
        if(!$is_loop){
            $retlist_ks_nos = [];
            foreach ($retlist_ks as $key=>$val){
                $retlist_ks_nos[] = $val;
            }
            return $retlist_ks_nos;
        }

        foreach ($retlist_ks as $key=>$val){
            $key = $key."";
            $_group_content[$val['mem']]['_key_'][] = $key;
            $_group_content[$val['mem']]['_fields'][$key] = $val['field'];
        }

        $grouplist = [];
        foreach ($_group_content as $key => $val) {
            if(empty($val['_key_'])) continue;
            if($this->type == 'email') {
                $val['copy'] = implode("|", array_unique($val['copy']));
            }
            $val['_key_'] = implode(",", array_unique($val['_key_']));
            $grouplist[] = $val;
        }
        return $grouplist;
    }

    /**
     * 设置循环数据Key-Value属性
     * @param $content
     * @param $keyname
     * @param $values
     */
    private function setSendDataKeyValue(&$content, $keyname, $values){
        $jstr = '_!**!_';
        $xstring = import('XString');
        if(is_array($values)){
            $vstr = $this->jsonEcho($values, true);
            if($this->type == 'email') {
                $ctt = "";
                while (!empty($content) && strpos($content, $jstr) !== false) {
                    $strl = $xstring->getSubStr($content, null, $jstr);
                    $content = $xstring->getSubStr($content, $jstr);
                    $content = $xstring->getSubStr($content, ">");
                    $strllen = strlen($strl);
                    $bool = false;
                    for ($i = $strllen - 1; $i >= 0; $i--) {
                        if ($strl[$i] == '<') {
                            $strlr = substr($strl, $i);
                            $strlr = $xstring->getSubStr($strlr, " ", ">");
                            $strl = substr($strl, 0, $i);
                            if (empty($strlr)) {
                                $strl .= "<pre>";
                            } else {
                                $strl .= "<pre {$strlr}>";
                            }
                            $bool = true;
                            break;
                        }
                    }
                    !$bool && $ctt .= "<pre>";
                    $ctt .= $strl . $jstr . "</pre>";
                }
                !empty($content) && $ctt .= $content;
                $content = $ctt;
            }
        }else{
            $vstr = $values;
        }
        $content = str_replace('_!*!_', $keyname, $content);
        $content = str_replace($jstr, $vstr, $content);
    }

    /**
     * 发送数据循环
     * @param $loops
     * @param $fieldlist
     * @param $fields
     * @param $values
     * @param $content
     */
    private function getSendDataLoop($loops, $fieldlist, $fields, $values, &$content){
        $is_html = $this->is_html;
        foreach ($loops as $key => $val) {
            $keys = $val['keys'];
            $tstr = "";
            $next = $val['next'];
            if($is_html && $val['is_table']){
                $is_table = true;
            }else{
                $is_table = false;
            }
            if(empty($keys)) {
                $dt = $values;
            } else {
                if ($val['is_out']) {
                    $dt = $this->dbdata;
                } else {
                    $dt = $values;
                }
                foreach ($keys as $k => $v) {
                    $this->setFk($v, $dt);
                    if (isset($dt[$v])) {
                        $dt = $dt[$v];
                    } else {
                        $dt = [];
                        break;
                    }
                }
            }

            //JSON转换为数组
            if(is_string($dt) && $val['is_json']){
                $dt = json_decode($dt, true);
            }

            !is_array($dt) && $dt = [];
            if(!empty($dt)) {
                $b = false;
                foreach ($dt as $dtk=>$dtv) {
                    $tdtv = $dtv;
                    !is_array($dtv) && $tdtv = [];
                    if(is_array($dtv) || empty($val['text_loop'])){
                        $tstrin = $val['text'];
                    }else{
                        $tstrin = $val['text_loop'];
                    }
                    if(!empty($tstrin)) {
                        foreach ($fields as $fk => $fv) {
                            $str = $this->getArrayIndex($tdtv, $fieldlist[$fk], $fk);
                            $tstrin = str_ireplace($this->strl . $fv . $this->strr, $str, $tstrin);
                        }
                        $this->setSendDataKeyValue($tstrin, $dtk, $dtv);
                        if ($is_table) {
                            if ($b) {
                                $color = "EEE";
                                $b = false;
                            } else {
                                $color = "FFF";
                                $b = true;
                            }
                            $tstrin = str_replace("<tr>", "<tr style='background-color: #{$color}'>", $tstrin);
                        }
                        $tstr .= $tstrin;
                    }
                }
            }
            !empty($tstr) && $content = str_replace($key, $tstr.$key, $content);
            if(!empty($next)){
                $this->getSendDataLoop($next, $fieldlist, $fields, $dt, $content);
            }
        }

        foreach ($loops as $key => $val) {
            $content = str_replace($key, "", $content);
        }
    }

    /**
     * 获取数据（Email）
     * @param $retlist
     * @param $loops
     * @param $fieldlist
     * @param $fields
     * @return mixed
     */
    private function getSendDataHtml($retlist, $loops, $fieldlist, $fields){
        $this->is_html = true;
        $replaces = [
            'table' => 'margin-bottom:10px;border-collapse:collapse;display:table;border:0px;border-color:#333;',
            'td' => 'padding:10px;',
        ];
        foreach ($retlist as $key => $val) {
            $content = $val['content'];
            $values = $val['_fields'];
            $this->getSendDataLoop($loops, $fieldlist, $fields, $values, $content);
            $content = str_replace(" valign=\"top\"", '', $content);
            $content = str_replace("<table", '<table border="1"', $content);
            $content = str_replace('class="firstRow"', 'class="firstRow" style="background-color:#999;"', $content);
            foreach ($replaces as $repk=>$repv){
                preg_match_all('/<'.$repk.'(.*?)>/is', $content, $carr);
                $ca = array_unique($carr[0]);
                foreach ($ca as $castr){
                    $catmp = $castr;
                    if(strpos($castr, 'style="')){
                        $castr = preg_replace('/(.*?)style="(.*?)"(.*?)/is', '$1style="$2'.$repv.'"$3', $castr);
                    }else{
                        $castr = preg_replace('/<(.*?)>/is', '<$1 style="'.$repv.'">', $castr);
                    }
                    $content = str_replace($catmp, $castr, $content);
                }
            }
            $this->setSendDataKeyValue($content, $key, $values);
            $retlist[$key]['content'] = $content;
        }
        return $retlist;
    }

    /**
     * 获取数据（企业微信）
     * @param $retlist
     * @param $loops
     * @param $fieldlist
     * @param $fields
     * @return mixed
     */
    private function getSendDataText($retlist, $loops, $fieldlist, $fields){
        $this->is_html = false;
        foreach ($retlist as $key => $val) {
            $content = $val['content'];
            $values = $val['_fields'];
            $this->getSendDataLoop($loops, $fieldlist, $fields, $values, $content);
            $this->setSendDataKeyValue($content, $key, $values);
            $retlist[$key]['content'] = $this->getContent($content);
        }
        return $retlist;
    }

    /**
     * 初始化文本内容
     * @param $content
     */
    private function setSendDataContentInit(&$content){
        //去除循环头尾标签
        $content = str_replace('white-space: normal;', '', $content);
        $content = str_replace('style=""', '', $content);
        $content = str_replace('<br>', '', $content);
        $content = str_replace('<br/>', '', $content);
        preg_match_all('/\[[^\[][^\]](.*?)\]/is', $content, $carr);
        $clist = $carr[0];
        preg_match_all('/{[^{][^}](.*?)}/is', $content, $sarr);
        $slist = $sarr[0];
        foreach ($slist as $s){
            $clist[] = $s;
        }
        $clist = array_unique($clist);
        foreach ($clist as $c){
            $ctmp = $c;
            if(strpos($c, "&nbsp;") !== false){
                $c = str_replace("&nbsp;", " ", $c);
                $clen = strlen($c);
                $cs = $c[0];
                $ce = $c[$clen - 1];
                $c = trim(substr($c, 1, $clen - 2));
                $c = preg_replace('/<.*?>/is', '', $c);
                if(!empty($c)){
                    $c = "{$cs}{$c}{$ce}";
                }
                $content = str_replace($ctmp, $c, $content);
            }
        }
        $content = preg_replace('/<p[^>]*>([^<]*)(\[LOOP[\s|<].*?\])([^<\/p>]*)<\/p>/is', '$1$2$3', $content);
        $content = preg_replace('/<p[^>]*>([^<]*)(\[\/LOOP\])([^<\/p>]*)<\/p>/is', '$1$2$3', $content);
        $content = str_replace("{}", "", $content);
        $content = str_replace("{*}", "_!*!_", $content);
        $content = str_replace("{**}", "_!**!_", $content);
    }

    /**
     * 获取发送信息
     * @param $_type
     * @return array
     */
    private function getSendData($_type){
        $this->type = $_type;
        //如果是Python获取数据则读取文件内容
        if(!empty($_POST['system']) && $_POST['system'] == 'python') {
            if ($_type == 'qywx') {
                $inisets = $this->ini_data['push_qywx'];
            } else {
                $inisets = $this->ini_data['push_email'];
            }
        }else{
            unset($_POST['system']);
            $inisets = $_POST;
        }

        $dblist = $this->dbdata;
        (empty($dblist) || !is_array($dblist)) && $dblist = [];

        //初始化内容
        $this->setSendDataContentInit($inisets['content']);
        $fieldlist = $this->getFieldList($inisets);
        $content = $inisets['content'];

        //获取内容嵌套循环规则
        $this->loopi = 0;
        list($content, $loops) = $this->getStrLoop($content);

        //转换为邮箱内容
        if($_type == 'email'){
            $content = $this->getReplaceEmailContent($content);
        }

        $inisets['content'] = $content;
        $inisets['loops'] = $loops;
        unset($inisets['status']);
        $srcloops = $loops;
        list($json, $loops, $fields) = $this->getField($inisets, $fieldlist);
        if(empty($fields)){
            $inisetsjsonstr = $this->jsonEcho($inisets);
            if(!empty($srcloops)){
                foreach ($srcloops as $key=>$val){
                    $inisetsjsonstr = str_replace($key, "", $inisetsjsonstr);
                }
            }
            return [1, json_decode($inisetsjsonstr, true)];
        }
        empty($loops) ? $is_loop = false : $is_loop = true;
        $retlist = $this->getGroupList($dblist, $inisets, $fieldlist, $json, $fields, $is_loop);
        if(!$is_loop) {
            return [1, $retlist];
        }

        if($_type == 'email') {
            $retlist = $this->getSendDataHtml($retlist, $loops, $fieldlist, $fields);
        }else{
            $retlist = $this->getSendDataText($retlist, $loops, $fieldlist, $fields);
        }
        return [1, $retlist];
    }

    /**
     * 过滤HTML标签
     * @param $content
     * @param bool $isloop
     * @return string
     */
    private function getContent($content, $isloop=false){
        if($isloop) {
            $content = preg_replace('/\[LOOP(.*?)\]/is', '', $content);
            $content = str_replace("[/LOOP]", "", $content);
        }

        $content = str_replace('<a', "##_a", $content);
        $content = str_replace('</a>', "##_#a", $content);
        $content = preg_replace('/<\/p>|<\/table>|<\/tr>/is', "\n", $content);
        $content = preg_replace('/<\/td>|<\/th>/is', "\t", $content);
        $content = preg_replace('/<(\/?)(.*?)>/is', '', $content);
        $content = str_replace("##_a", '<a', $content);
        $content = str_replace("##_#a", '</a>', $content);

        $content = str_replace("<br/>", "", $content);
        $content = str_replace("&gt;", ">", $content);
        $content = str_replace("&lt;", "<", $content);
        $content = str_replace("&nbsp;", " ", $content);
        $content = str_replace("\t\n", "\n", $content);

        //去除a标签多余的属性
        preg_match_all('/<a.*?>/is', $content, $array);
        foreach ($array[0] as $val){
            $rval = preg_replace('/<a.*?href="(.*?)".*?>/is', '<a href="$1">', $val);
            $rval = preg_replace("/<a.*?href='(.*?)'.*?>/is", "<a href='$1'>", $rval);
            $content = str_replace($val, $rval, $content);
        }
        return $content;
    }

    /**
     * 获取数据
     * @param $data
     * @return array
     */
    public function getData($data){
        $this->dbdata = $data;
        if(empty($this->obj)) return $data;
        $ini_data = $this->ini_data;
        $ret_data = [];

        //邮箱信息
        if(isset($ini_data['push_email']) && isset($ini_data['push_email']['status']) && $ini_data['push_email']['status']){
            list($status, $email_info) = $this->getSendData('email');
            if(!empty($email_info)) {
                $ret_data['email'] = ['status' => $status, 'id' => $ini_data['push_email']['addresser'], 'info' => $email_info];
            }
        }

        //企业微信信息
        if(isset($ini_data['push_qywx']) && isset($ini_data['push_qywx']['status']) && $ini_data['push_qywx']['status']){
            list($status, $qywx_info) = $this->getSendData('qywx');
            foreach ($qywx_info as $key=>$val){
                $qywx_info[$key]['content'] = $this->getContent($val['content']);
            }
            if(!empty($qywx_info)) {
                $ret_data['qywx'] = ['status' => $status, 'id' => $ini_data['push_qywx']['menu'], 'info' => $qywx_info];
            }
        }
        return $ret_data;
    }

    /**
     * 获取数据
     * @param $data
     * @param string $type
     * @return array
     */
    public function getDataFor($data, $type='email'){
        $this->dbdata = $data;
        if(empty($this->obj)) return $data;
        list($status, $ret_data) = $this->getSendData($type);
        if($type == 'qywx'){
            if(isset($ret_data['content'])){
                $ret_data = [$ret_data];
            }
            foreach ($ret_data as $key=>$val){
                $ret_data[$key]['content'] = $this->getContent($val['content']);
            }
        }
        return [$status, $ret_data];
    }
}