<?php

/**
 * 接口系统页面处理
 * Class ApiHtml
 */
class ApiHtml {
    protected function getExtUrl($dir, $obj){
        $sid_md5 = SESSION_ID_MD5;
        if($obj->viewdata['is_login']){
            $is_path = 'false';
            if(!empty($_GET['path'])){
                $path = base64_decode($_GET['path']);
                if(!empty($path)){
                    $is_path = 'true';
                }
            }
            $url_ext = "?dir={$obj->tpl_path}&ext_dir={$dir}&is_login=true&pro={$GLOBALS['DOMAIN_CONFIG']['key']}&type={$obj->tpl_type}&is_path={$is_path}&crf={$sid_md5}";
        }else{
            $url_ext = "?dir={$dir}&is_ext=true&crf={$sid_md5}";
        }
        return $url_ext;
    }

    // 获取扩展信息
    private function getExtInfo($data, $obj){
        $ext = [];
        $d_ext = $data['ext'];
        if(!empty($d_ext)){
            if($obj->viewdata['is_login']){
                $ext_menu = $data['ext_menu'];
                if(!empty($ext_menu)){
                    foreach($d_ext as $key=>$val){
                        if($ext_menu[$val['dir']]){
                            $ext[$key] = $val;
                        }
                    }
                }
            }else{
                $ext = $d_ext;
            }
        }
        return $ext;
    }

    // 列表模式
    public function setList($dir, $obj, $url_more, $is_return=false){
        if($obj->viewdata['is_flow']){
            // 流程模式
            return $this->__setFlow($dir, $obj, $url_more, $is_return);
        }else{
            // 普通页面模式
            return $this->__setList($dir, $obj, $url_more, $is_return);
        }
    }

    private function __setList($dir, $obj, $url_more, $is_return=false){
        $api_conf = import("ApiConf", $obj);
        $info = $api_conf->getRelationInfoWeb($dir);
        $err_dir = '业务关系：'.$dir;
        if($info['code'] === 0){
            if($is_return){
                return [false, $info['msg']];
            }
            $api_conf->webExit([
                $err_dir,
                $info['msg'],
            ], 500, "内部错误");
        }
        $data = $info['data'];
        $child_title = $obj->viewdata['child_title'];
        if(empty($child_title)){
            $title = $data['default']['remark'];
            if(empty($title)){
                $title = $dir;
            }else{
                $title .= " | " . $dir;
            }
        }else{
            $title = $child_title;
        }
        SEO([
            'title' => $title
        ]);
        $as = import('ApiSystem');
        $g_keys = $as->getKeys($data['keys']);
        $no_edits = [];
        foreach ($g_keys as $val){
            $no_edits[$val] = true;
        }
        $fields = $as->getFieldsInfo($data['fields'], $data['src']);
        // 删除主键编辑
        $no_shows = [];
        foreach ($fields as $key=>$val){
            if($no_edits[$val['save_field']]){
                $fields[$key]['list_edit'] = 0;
                $fields[$key]['form_add'] = 0;
                $fields[$key]['form_edit'] = 0;
                $no_shows[$key] = true;
            }
        }
        $list = $as->getListsInfo($fields);
        $list_rules = $as->getListRules($list);
        $form = $as->getFormInfo($data['fields'], $fields);
        $rules = $as->getFormRules($form);
        $keys = $as->getFieldKeys($data['src']);
        if(empty($list)){
            if($is_return){
                return [false, '列表配置无效'];
            }
            $api_conf->webExit([
                $err_dir,
                '列表配置无效',
            ]);
        }
        $types = [];
        foreach ($fields as $val){
            $tp = $val['data_type'];
            !isset($types[$tp]) && $types[$tp] = true;
        }
        $gets = $_GET;
        unset($gets['__dir__']);
        unset($gets['__show__']);
        $is_delete = true;
        if(isset($data['is_delete'])){
            $is_delete = $data['is_delete'];
        }
        $url_conf = rtrim(trim(getenv('URL_CONF')), '/') . "/";
        $url_ext = $this->getExtUrl($dir, $obj);
        $obj->setView('search', $as->search($fields));
        $obj->setView('list', $list);
        $obj->setView('form', $form);
        $obj->setView('form_info', $as->getFormInfoEdit($fields));
        $obj->setView('list_rules', $list_rules);
        $obj->setView('is_delete', $is_delete);
        $obj->setView('app_list_name', 'app_list');
        $obj->setView('rules', $rules);
        $obj->setView('gets', $gets);
        $obj->setView('keys', $keys);
        $obj->setView('no_shows', $no_shows);
        $obj->setView('crf', SESSION_ID_MD5);
        $obj->setView('search_show', $_GET['__show__'] == 'yes' ? 'true' : 'false');
        $obj->setView('data', $data);
        $obj->setView('types', $types);
        $obj->setView('file_format', $data['format']);
        $obj->setView('file_max_size', $data['file_max_size']);
        $obj->setView('ext', $this->getExtInfo($data, $obj));
        $obj->setView('url', "{$url_conf}###{$url_ext}");
        $obj->setView('url_handle', "{$url_conf}page.handle{$url_ext}&key=");
        $obj->setView('url_upload', "{$url_conf}page.upload{$url_ext}&key=");
        $obj->setView('url_more', $url_more);
        if($is_return){
            return [true, 'ok'];
        }
    }

    private function __setFlow($dir, $obj, $url_more, $is_return=false){
        $api_conf = import("ApiConf", $obj);
        $info = $api_conf->getRelationInfoWeb($dir);
        $err_dir = '业务关系：'.$dir;
        if($info['code'] === 0){
            if($is_return){
                return [false, $info['msg']];
            }
            $api_conf->webExit([
                $err_dir,
                $info['msg'],
            ], 500, "内部错误");
        }
        $data = $info['data'];
        $child_title = $obj->viewdata['child_title'];
        if(empty($child_title)){
            $title = $data['default']['remark'];
            if(empty($title)){
                $title = $dir;
            }else{
                $title .= " | " . $dir;
            }
        }else{
            $title = $child_title;
        }
        SEO([
            'title' => $title
        ]);
        $url_conf = rtrim(trim(getenv('URL_CONF')), '/') . "/";
        $url_ext = $this->getExtUrl($dir, $obj);
        $obj->setView('url', "{$url_conf}###{$url_ext}");
//        dump($info);
//        $as = import('ApiSystem');
//        $g_keys = $as->getKeys($data['keys']);
//        $no_edits = [];
//        foreach ($g_keys as $val){
//            $no_edits[$val] = true;
//        }
//        $fields = $as->getFieldsInfo($data['fields'], $data['src']);
//        // 删除主键编辑
//        $no_shows = [];
//        foreach ($fields as $key=>$val){
//            if($no_edits[$val['save_field']]){
//                $fields[$key]['list_edit'] = 0;
//                $fields[$key]['form_add'] = 0;
//                $fields[$key]['form_edit'] = 0;
//                $no_shows[$key] = true;
//            }
//        }
//        $list = $as->getListsInfo($fields);
//        $list_rules = $as->getListRules($list);
//        $form = $as->getFormInfo($data['fields'], $fields);
//        $rules = $as->getFormRules($form);
//        $keys = $as->getFieldKeys($data['src']);
//        if(empty($list)){
//            if($is_return){
//                return [false, '列表配置无效'];
//            }
//            $api_conf->webExit([
//                $err_dir,
//                '列表配置无效',
//            ]);
//        }
//        $types = [];
//        foreach ($fields as $val){
//            $tp = $val['data_type'];
//            !isset($types[$tp]) && $types[$tp] = true;
//        }
//        $gets = $_GET;
//        unset($gets['__dir__']);
//        unset($gets['__show__']);
//        $is_delete = true;
//        if(isset($data['is_delete'])){
//            $is_delete = $data['is_delete'];
//        }
//        $obj->setView('search', $as->search($fields));
//        $obj->setView('list', $list);
//        $obj->setView('form', $form);
//        $obj->setView('form_info', $as->getFormInfoEdit($fields));
//        $obj->setView('list_rules', $list_rules);
//        $obj->setView('is_delete', $is_delete);
//        $obj->setView('rules', $rules);
//        $obj->setView('gets', $gets);
//        $obj->setView('keys', $keys);
//        $obj->setView('no_shows', $no_shows);
//        $obj->setView('crf', SESSION_ID_MD5);
//        $obj->setView('search_show', $_GET['__show__'] == 'yes' ? 'true' : 'false');
//        $obj->setView('data', $data);
//        $obj->setView('types', $types);
//        $obj->setView('file_format', $data['format']);
//        $obj->setView('file_max_size', $data['file_max_size']);
//        $obj->setView('ext', $this->getExtInfo($data, $obj));
//        $obj->setView('url_handle', "{$url_conf}page.handle{$url_ext}&key=");
//        $obj->setView('url_upload', "{$url_conf}page.upload{$url_ext}&key=");
//        $obj->setView('url_more', $url_more);
        if($is_return){
            return [true, 'ok'];
        }
    }

    public function setMore($dir, $obj, $tpl_ext){
        $child_title = $obj->viewdata['child_title'];
        $key = $_GET['key'];
        $api_conf = import("ApiConf", $obj);
        $info = $api_conf->getRelationInfoWeb($dir);
        $err_dir = '业务关系：'.$dir;
        if($info['code'] === 0){
            $api_conf->webExit([
                $child_title,
                $err_dir,
                $info['msg'],
            ]);
        }
        if(empty($key)){
            $api_conf->webExit([
                $child_title,
                $err_dir,
                '无效键值传递',
            ]);
        }
        $keystr = base64_decode($key);
        if(empty($keystr)){
            $api_conf->webExit([
                $child_title,
                $err_dir,
                '无效键值传递',
            ]);
        }
        $keyarr = json_decode($keystr, true);
        if(empty($keyarr)){
            $api_conf->webExit([
                $child_title,
                $err_dir,
                '无效键值传递',
            ]);
        }
        $data = $info['data'];
        $ext = $this->getExtInfo($data, $obj);
        if(empty($ext)){
            $api_conf->webExit([
                $child_title,
                $err_dir,
                '没有详情数据',
            ]);
        }
        if(empty($child_title)){
            $title = $data['default']['remark'];
            if(empty($title)){
                $title = $dir;
            }else{
                $title .= " | " . $dir;
            }
            SEO([
                'title' => "详情： " . $title
            ]);
        }else{
            $title = $child_title;
            SEO([
                'title' => $title
            ]);
        }
        $obj->setView('sub_title', $title);
        $as = import('ApiSystem');
        $g_keys = $as->getKeys($data['keys']);
        $no_edits = [];
        foreach ($g_keys as $val){
            $no_edits[$val] = true;
        }
        $fields = $as->getFieldsInfo($data['fields'], $data['src']);
        // 删除主键编辑
        foreach ($fields as $key=>$val){
            unset($fields[$key]['form_add']);
            if($no_edits[$val['save_field']]){
                $fields[$key]['form_edit'] = 0;
            }
        }
        $form = $as->getFormInfo($data['fields'], $fields);
        $rules = $as->getFormRules($form);
        $keys = $as->getFieldKeys($data['src']);
        foreach ($keys as $k=>$v){
            if(!isset($keyarr[$k])){
                $api_conf->webExit([
                    $child_title,
                    $err_dir,
                    "无效键值传递： {$k}",
                ]);
            }
        }
        $types = [];
        foreach ($fields as $val){
            $tp = $val['data_type'];
            !isset($types[$tp]) && $types[$tp] = true;
        }
        $err_page = $api_conf->getWebExit([
            $child_title,
            $err_dir,
            '{{show_msg}}'
        ]);
        $url_conf = rtrim(trim(getenv('URL_CONF')), '/') . "/";
        $url_ext = $this->getExtUrl($dir, $obj);
        $obj->setView('form', $form);
        $obj->setView('form_info', $as->getFormInfoEdit($fields));
        $obj->setView('rules', $rules);
        $obj->setView('keys', $keys);
        $obj->setView('crf', SESSION_ID_MD5);
        $obj->setView('data', $data);
        $obj->setView('types', $types);
        $obj->setView('keystr', $keystr);
        $obj->setView('file_format', $data['format']);
        $obj->setView('file_max_size', $data['file_max_size']);
        $obj->setView('err_page', $err_page);
        $obj->setView('app_list_name', 'app_list');
        $obj->setView('ext', $ext);
        $obj->setView('tpl_ext', $tpl_ext);
        $obj->setView('url', "{$url_conf}###{$url_ext}");
        $obj->setView('url_handle', "{$url_conf}page.handle{$url_ext}&key=");
        $obj->setView('url_upload', "{$url_conf}page.upload{$url_ext}&key=");
        $obj->setView('url_more', "/".$obj->tpl_init."?__dir__={$dir}&key=");
        $obj->setView('url_init', $obj->tpl_init);
    }

    public function setExt($obj, $url_more){
        $api_conf = import("ApiConf", $obj);
        $src_key_str = $_GET['key'];
        $src_key = [];
        if(!empty($src_key_str)){
            $src_key = json_decode(base64_decode($src_key_str), true);
        }
        $ext_key = $_GET['ext_key'];
        $obj->setView('ext_key', $ext_key);
        $obj->setView('app_list_name', 'app_list_'.$ext_key);

        $error_msg = '';
        $is_error = false;
        if(empty($src_key)){
            $error_msg = '键值扩展传递错误';
            $is_error = true;
        }
        if($is_error){
            $obj->setView('is_error', $is_error);
            $obj->setView('error_msg', $error_msg);
            return;
        }else{

            $dir = $_GET['dir'];
            $info = $api_conf->getRelationInfoWeb($dir);
            $err_dir = '业务关系：'.$dir;
            if($info['code'] === 0){
                $obj->setView('is_error', true);
                $obj->setView('error_msg', $err_dir."<br>".$info['msg']);
                return;
            }
            $data = $info['data'];
            $as = import('ApiSystem');
            $no_edits = [];
            $g_json = $_GET['json'];
            if(!empty($g_json)){
                foreach ($g_json as $val){
                    $k = strtolower(trim($val[1]));
                    $no_edits[$k] = true;
                }
            }
            $g_keys = $as->getKeys($data['keys']);
            foreach ($g_keys as $val){
                $no_edits[$val] = true;
            }
            $fields = $as->getFieldsInfo($data['fields'], $data['src']);
            // 删除主键编辑
            $no_shows = [];
            foreach ($fields as $key=>$val){
                if($no_edits[$val['save_field']]){
                    $fields[$key]['list_edit'] = 0;
                    $fields[$key]['form_add'] = 0;
                    $fields[$key]['form_edit'] = 0;
                    $no_shows[$key] = true;
                }
            }
            $list = $as->getListsInfo($fields);
            $list_rules = $as->getListRules($list);
            $form = $as->getFormInfo($data['fields'], $fields);
            $rules = $as->getFormRules($form);
            $keys = $as->getFieldKeys($data['src']);
            if(empty($list)){
                $obj->setView('is_error', true);
                $obj->setView('error_msg', $err_dir."<br>列表配置无效");
                return;
            }
            $types = [];
            foreach ($fields as $val){
                $tp = $val['data_type'];
                !isset($types[$tp]) && $types[$tp] = true;
            }
            $gets = $_GET;
            unset($gets['__dir__']);
            unset($gets['__show__']);
            $is_delete = true;
            if(isset($data['is_delete'])){
                $is_delete = $data['is_delete'];
            }
            $url_conf = rtrim(trim(getenv('URL_CONF')), '/') . "/";
            $url_ext = $this->getExtUrl($dir, $obj);
            $obj->setView('list', $list);
            $obj->setView('form', $form);
            $obj->setView('form_info', $as->getFormInfoEdit($fields));
            $obj->setView('list_rules', $list_rules);
            $obj->setView('rules', $rules);
            $obj->setView('is_delete', $is_delete);
            $obj->setView('gets', $gets);
            $obj->setView('keys', $keys);
            $obj->setView('no_shows', $no_shows);
            $obj->setView('crf', SESSION_ID_MD5);
            $obj->setView('data', $data);
            $obj->setView('types', $types);
            $obj->setView('file_format', $data['format']);
            $obj->setView('file_max_size', $data['file_max_size']);
            $obj->setView('ext', $this->getExtInfo($data, $obj));
            $obj->setView('url', "{$url_conf}###{$url_ext}");
            $obj->setView('url_handle', "{$url_conf}page.handle{$url_ext}&key=");
            $obj->setView('url_upload', "{$url_conf}page.upload{$url_ext}&key=");
            $obj->setView('url_more', $url_more);
        }
    }
}
