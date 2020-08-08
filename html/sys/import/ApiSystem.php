<?php

/**
 * 接口系统处理
 * Class ApiSystem
 */
class ApiSystem {
    function __construct($obj=null, $flag=""){
        $this->sort = 10000;
    }

    /**
     * 获取字段信息
     * @param $fields
     * @param $src
     * @return array
     */
    public function getFieldsInfo($fields, $src){
        $ret_fields = [];
        if(empty($fields)){
            return $ret_fields;
        }
        foreach ($fields as $key=>$val){
            $save_field = $key;
            $save_style = 'text';
            $vfy = $val['verify'];
            $input_type = 'text';
            if(!empty($vfy)){
                if(!empty($vfy['save_field'])){
                    $save_field = strtolower(trim($vfy['save_field']));
                }
                if(!empty($vfy['save_style'])){
                    $save_style = strtolower(trim($vfy['save_style']));
                }
                if(in_array($val['data_type'], ['value', 'key_value', 'table', 'api'])){
                    $i_type = $vfy['input_type'];
                    empty($i_type) && $i_type = 'select';
                    $i_save = $vfy['save_type'];
                    empty($i_save) && $i_save = 'single';
                    if($i_type == 'select'){
                        if($i_save == 'single'){
                            $input_type = 'select';
                        }elseif($i_save == 'multi'){
                            $input_type = 'selects';
                        }
                    }elseif($i_type == 'input'){
                        if($i_save == 'single'){
                            $input_type = 'radio';
                        }elseif($i_save == 'multi'){
                            $input_type = 'checkbox';
                        }
                    }
                    if($val['data_type'] === 'table' && isset($val['config']['top_value'])){
                        $input_type = 'link';
                    }
                }
            }
            if(!isset($src[$save_field])){
                continue;
            }
            $val['save_field'] = $save_field;
            $val['save_style'] = $save_style;
            $val['input_type'] = $input_type;
            $val['fields'] = $src[$save_field];
            $ret_fields[$key] = $val;
        }
        return $ret_fields;
    }

    /**
     * 获取主键
     * @param $src
     * @return array
     */
    public function getFieldKeys($src){
        $ret = [];
        if(empty($src)){
            return $ret;
        }
        foreach ($src as $key=>$val){
            if($val['is_key'] === 1){
                $ret[$key] = true;
            }
        }
        return $ret;
    }

    /**
     * 获取选择框键值
     * @param $list
     * @param string $type
     * @return array
     */
    private function __getKeyValue($list, $type='value'){
        $ret_list = [];
        if(is_array($list)) {
            if ($type == 'value') {
                foreach ($list as $k => $v) {
                    $ret_list[] = [
                        'key' => $v,
                        'value' => $v
                    ];
                }
            } elseif ($type == 'key_value') {
                foreach ($list as $k => $v) {
                    $ret_list[] = [
                        'key' => $v[0],
                        'value' => $v[1]
                    ];
                }
            }
        }
        return $ret_list;
    }

    public function search($fields){
        $ret = [];
        if(empty($fields) || !is_array($fields)){
            return $ret;
        }
        $groups = [
            "default" => []
        ];
        $keys = [];
        $null_keys = [];
        $lists = [];
        foreach ($fields as $key => $val){
            if($val['save_style'] != 'text'){
                // 保存方式为JSON时不支持搜索
                continue;
            }
            if($val['list'] != 1){
                // 仅支持列表中字段的搜索
                continue;
            }
            if(is_array($val['verify']) && $val['verify']['search_status'] == 'open'){
                $verify = $val['verify'];
                $g = $verify['search_group'];
                empty($g) && $g = '';
                $g = trim($g);
                empty($g) && $g = 'default';
                !isset($groups[$g]) && $groups[$g] = [];
                if(empty($val['backup_name'])){
                    $name = $val['name'];
                }else{
                    $name = $val['backup_name'];
                }
                $dtype = $val['data_type'];
                $gk = $_GET[$key];
                $g_info = [];
                $is_area = false;
                if($verify['search_area'] === 'open' && $val['field_type'] === 'number'){
                    $is_area = true;
                }
                if($dtype == 'time' || $is_area){
                    if(isset($gk)){
                        !isset($gk['start']) && $gk['start'] = null;
                        !isset($gk['end']) && $gk['end'] = null;
                    }else{
                        $gk = [
                            'start' => null,
                            'end' => null,
                        ];
                    }
                }elseif(in_array($dtype, ['status', 'image', 'file'])){
                    if(is_null($gk)){
                        $gk = '';
                    }elseif($gk == 1 || $gk == '1'){
                        $gk = '1';
                    }else{
                        $gk = '0';
                    }
                }elseif(in_array($dtype, ['value', 'key_value'])){
                    $list = $this->__getKeyValue($val['config'], $dtype);
                    $g_info['list'] = $list;
                    $lists[$key] = $list;

                    if(!is_array($gk)){
                        $gk = trim($gk);
                        if(empty($gk)){
                            $gk = null;
                        }else {
                            $gk = trim($gk, ',');
                            $gk = explode(',', $gk);
                        }
                    }
                }elseif(in_array($dtype, ['table', 'api'])){
                    $lists[$key] = [];
                    if(!is_array($gk)){
                        $gk = trim($gk);
                        if(empty($gk)){
                            $gk = null;
                        }else {
                            $gk = trim($gk, ',');
                            $gk = explode(',', $gk);
                        }
                    }
                }

                if(in_array($dtype, ['value', 'key_value', 'table', 'api'])) {
                    $g_info['input_type'] = $verify['input_type'];
                    if($dtype === 'table' && isset($val['config']) && isset($val['config']['top_value'])){
                        $g_info['input_type'] = 'link';
                        $g_info['top_value'] = $val['config']['top_value'];
                    }
                    $keys[$key] = $gk;
                    $null_keys[$key] = null;
                }elseif($dtype == 'status'){
                    $keys[$key] = $gk;
                    $null_keys[$key] = "";
                }else{
                    if(is_array($gk)){
                        empty($keys[$key]) && $keys[$key] = [];
                        foreach ($gk as $k => $v){
                            $keys[$key][$k] = $v;
                            $null_keys[$key][$k] = null;
                        }
                    }else{
                        $keys[$key] = $gk;
                        $null_keys[$key] = null;
                    }
                }
                $g_info['name'] = $name;
                $g_info['sort'] = $verify['search_sort'];
                $g_info['type'] = $dtype;
                $g_info['field_type'] = $val['field_type'];
                if($is_area){
                    $g_info['is_area'] = true;
                }

                $groups[$g][$key] = $g_info;

            }
        }

        if(empty($groups['default'])){
            unset($groups['default']);
        }

        foreach ($groups as $key=>$val){
            $gs = [];
            foreach ($val as $k=>$v){
                $sort = $v['sort'];
                $v['_key_'] = $k;
                unset($v['sort']);
                if(empty($sort)){
                    $sort = $this->sort;
                }else if(is_numeric($sort)){
                    $sort = intval($sort);
                }else{
                    $sort = $this->sort + 1;
                }
                $gs[$sort][] = $v;
            }
            ksort($gs);
            $new_val = [];
            foreach ($gs as $k=>$v){
                foreach ($v as $kk=>$vv){
                    $_k_ = $vv['_key_'];
                    unset($vv['_key_']);
                    $new_val[$_k_] = $vv;
                }
            }
            $groups[$key] = $new_val;
        }
        return [$groups, $keys, $null_keys, $lists];
    }

    /**
     * 获取列表信息
     * @param $fields
     * @return array
     */
    public function getListsInfo($fields){
        $group_lists = [];
        foreach ($fields as $key => $val){
            if(isset($val['list_sort'])){
                $sort = $val['list_sort'];
            }else{
                $sort = 100000;
            }

            if($val['list'] === 1){
                $name = $val['backup_name'];
                !empty($name) && $name = trim($name);
                if(empty($name)){
                    $name = $val['name'];
                }
                $verify = $val['verify'];
                $align = 'auto';
                $width = '';
                if(!empty($val['list_width'])){
                    $width = $val['list_width'];
                    !empty($width) && $width = trim($width);
                }

                $v_decimal = null;
                if(!empty($verify)){
                    $align = $verify['align'];
                    if($align == 'auto' && $verify['data_type'] != 'string'){
                        $align = 'right';
                    }
                    $v_data_type = $verify['data_type'];
                    $v_decimal = $verify['decimal'];
                }
                if(empty($v_data_type)){
                    $v_data_type = 'string';
                }
                $ret_tmp = [
                    'name' => $name,
                    'align' => $align,
                    'input_type' => $val['input_type']
                ];
                if(!empty($verify)){
                    $ret_tmp['verify'] = $verify;
                }
                if(!empty($width)){
                    $ret_tmp['width'] = $width;
                }
                if(!empty($val['list_fixed'])){
                    $ret_tmp['fixed'] = $val['list_fixed'];
                }
                $ret_tmp['edit'] = $val['list_edit'] == 1 ? true : false;
                $ret_tmp['data_type'] = $val['data_type'];
                $ret_tmp['v_data_type'] = $v_data_type;
                $ret_tmp['field_type'] = $val['field_type'];
                if($ret_tmp['edit'] && !in_array($val['data_type'], ['text', 'status'])){
                    $ret_tmp['edit'] = false;
                }
                if(!empty($val['field_type_info'])){
                    $ft_info = $val['field_type_info'];
                    if($ft_info['type'] == 'float'){
                        if(isset($v_decimal)){
                            if(!is_int($v_decimal)){
                                $v_decimal = intval($v_decimal);
                            }
                            if($v_decimal < 0){
                                $v_decimal = 0;
                            }
                            if(isset($ft_info['decimal'])){
                                $decimal = $ft_info['decimal'];
                                if($v_decimal < $decimal){
                                    $decimal = $v_decimal;
                                }
                                if($decimal < 0){
                                    $decimal = 0;
                                }
                                $ft_info['decimal'] = $decimal;
                            }else{
                                $ft_info['decimal'] = $v_decimal;
                            }
                        }
                    }
                    $ret_tmp['field_type_info'] = $ft_info;
                }
                $config = $val['config'];
                if($val['data_type'] == 'time' && !empty($config)){
                    $format = $config['format'];
                    if($format == 'date_time'){
                        $format = 'datetime';
                    }
                    $ret_tmp['time_format'] = $format;
                }else{
                    $ret_tmp['time_format'] = $val['field_type'];
                }
                $group_lists[$sort][$key] = $ret_tmp;
            }
        }

        ksort($group_lists);

        $fixeds = [];
        $lists = [];
        foreach ($group_lists as $glist){
            foreach ($glist as $key=>$val){
                if($val['fixed']){
                    $fixeds[$key] = $val;
                }else{
                    $lists[$key] = $val;
                }
            }
        }
        foreach ($lists as $key=>$val){
            $fixeds[$key] = $val;
        }
        return $fixeds;
    }

    private function isOneLines($data){
        if(empty($data)){
            return false;
        }
        $onelines = ['textarea', 'editor', 'value', 'key_value', 'table', 'api'];
        $data_type = $data['data_type'];
        if(!in_array($data_type, $onelines)){
            return false;
        }
        if(in_array($data_type, ['textarea', 'editor'])){
            return true;
        }
        if($data_type === 'table'){
            if(isset($data['config']) && isset($data['config']['top_value'])){
                return true;
            }
        }
        $input_type = $data['input_type'];
        if(!in_array($input_type, ['radio', 'checkbox'])){
            return false;
        }
        return true;

    }
    /**
     * 数组排序
     * @param $list
     * @return array
     */
    private function getFormSort($list, $group_value){
        empty($group_value) && $group_value = "";
        $sorts = [];
        foreach ($list as $key=>$val){
            $sort = $val['sort'];
            unset($val['sort']);
            if(empty($sort)){
                $sort = $this->sort;
            }else if(is_numeric($sort)){
                $sort = intval($sort);
            }else{
                $sort = $this->sort + 1;
            }
            empty($field_sorts[$sort]) && $field_sorts[$sort] = [];
            $sorts[$sort][] = [$key, $val];
        }
        ksort($sorts);
        $groups = [];
        foreach ($sorts as $val){
            foreach ($val as $v){
                $vv = $v[1];
                $group = $vv['group'];
                empty($groups[$group]) && $groups[$group] = [];
                $groups[$group][] = $v;
            }
        }

        $group_100 = [];
        if(isset($groups[100])){
            $group_100 = $groups[100];
            unset($groups[100]);
        }
        ksort($groups);
        $groups = array_values($groups);
        $glen = count($groups);
        for($i = 3; $i < $glen; $i ++){
            foreach ($groups[$i] as $v){
                $group_100[] = $v;
            }
            unset($groups[$i]);
        }

        if(!empty($group_100)){
            if(isset($groups[0])){
                foreach ($group_100 as $val){
                    $groups[0][] = $val;
                }
            }else{
                $groups[] = $group_100;
            }
        }
        $glen = count($groups);
        $ret = [];
        if($glen <= 0){
            return [$glen, $ret, $group_value];
        }
        if($glen == 1){
            foreach ($groups[0] as $key=>$val){
                $ret[$key][$val[0]] = $val[1];
            }
            return [$glen, $ret, $group_value];
        }

        $g0 = $groups[0];
        $g1 = $groups[1];
        $g1_len = count($g1);
        $g1_inc = 0;
        foreach ($g0 as $key=>$val){
            list($keyname, $value) = $val;
            $arr = [
                $keyname => $value
            ];
            $ret[] = $arr;
            if($this->isOneLines($value) || $g1_inc >= $g1_len){
                continue;
            }
            $ret_i = count($ret) - 1;

            list($g1_key, $g1_val) = $g1[$g1_inc];
            while($this->isOneLines($g1_val)){
                if($g1_inc >= $g1_len){
                    break;
                }
                $ret[] = [
                    $g1_key => $g1_val
                ];
                $g1_inc ++;
                list($g1_key, $g1_val) = $g1[$g1_inc];
            }
            if($g1_inc >= $g1_len){
                continue;
            }
            $ret[$ret_i][$g1_key] = $g1_val;
            $g1_inc ++;
        }
        for(; $g1_inc < $g1_len; $g1_inc ++){
            list($g1_key, $g1_val) = $g1[$g1_inc];
            if($this->isOneLines($g1_val)){
                $ret[] = [
                    $g1_key => $g1_val
                ];
            }else{
                $ret[] = [
                    "" => "",
                    $g1_key => $g1_val
                ];
            }
        }

        $ret_len = count($ret);
        for($i = 0; $i < $ret_len; $i ++){
            if(count($ret[$i]) == 1 && !$this->isOneLines(array_values($ret[$i])[0])){
                $ret[$i][""] = "";
            }
        }
        if($glen == 2){
            return [$glen, $ret, $group_value];
        }

        $g2 = $groups[2];
        $g2_len = count($g2);
        $g2_inc = 0;
        $ret_new = [];
        foreach ($ret as $key=>$val){
            $ret_new[] = $val;
            if(count($val) == 1){
                continue;
            }
            $ret_new_i = count($ret_new) - 1;
            list($g2_key, $g2_val) = $g2[$g2_inc];
            while($this->isOneLines($g2_val)){
                if($g2_inc >= $g2_len){
                    break;
                }
                $ret_new[] = [
                    $g2_key => $g2_val
                ];
                $g2_inc ++;
                list($g2_key, $g2_val) = $g2[$g2_inc];
            }
            if($g2_inc >= $g2_len){
                continue;
            }
            $ret_new[$ret_new_i][$g2_key] = $g2_val;
            $g2_inc ++;
        }
        for(; $g2_inc < $g2_len; $g2_inc ++){
            list($g2_key, $g2_val) = $g2[$g2_inc];
            if($this->isOneLines($g2_val)){
                $ret_new[] = [
                    $g2_key => $g2_val
                ];
            }else{
                $ret_new[] = [
                    "" => "",
                    " " => "",
                    $g2_key => $g2_val
                ];
            }
        }
        foreach ($ret_new as $key=>$val){
            if(count($val) == 2){
                $ret_new[$key][" "] = "";
            }
        }
        return [$glen, $ret_new, $group_value];
    }

    /**
     * 获取编辑、查看状态
     * @param $fields
     * @return array
     */
    public function getFormInfoEdit($fields){
        $form_info = [
            'add' => 0,
            'edit' => 0,
            'view' => 0
        ];
        foreach($fields as $key=>$val){
            if($val['data_type'] === 'sub'){
                continue;
            }
            if($val['form'] === 1){
                $form_info['view'] ++;
                if($val['form_add'] === 1){
                    $form_info['add'] ++;
                }
                if($val['form_edit'] === 1){
                    $form_info['edit'] ++;
                }
            }
        }
        return $form_info;
    }

    /**
     * 获取列表信息
     * @param $fields
     * @return array
     */
    public function getFormInfo($ini_fields, $fields){
        $group_fields = [];
        if(empty($ini_fields)){
            return $group_fields;
        }
        $group_name = 'default';
        $group_names = [];
        foreach ($ini_fields as $key=>$val){
            $data_type = $val['data_type'];
            if($data_type == 'sub'){
                $group_name = $val['name'];
                empty($group_name) && $group_name = '';
                $group_name = trim($group_name);

                $b_name = $val['backup_name'];
                empty($b_name) && $b_name = '';
                $b_name = trim($b_name);
                $group_names[$group_name] = $b_name;
                continue;
            }
            if(!isset($fields[$key])){
                continue;
            }
            $fval = $fields[$key];
            if($fval['form'] !== 1){
                continue;
            }
            $config = $fval['config'];
            if($data_type == 'time' && isset($config) && $config['scene'] !== 'custom'){
                continue;
            }
            empty($group_fields[$group_name]) && $group_fields[$group_name] = [];

            $name = $fval['backup_name'];
            !empty($name) && $name = trim($name);
            if(empty($name)){
                $name = $fval['name'];
            }
            $input_type = $fval['input_type'];
            $data_type = $fval['data_type'];
            if($input_type == 'text'){
                if($data_type == 'time'){
                    if(!isset($fval['config']) || $fval['config']['display'] == 'hide'){
                        $input_type = 'hide';
                    }else{
                        $input_type = 'time';
                    }
                }
            }
            if($fval['form'] === 1 || $fval['form'] === '1'){
                $form = true;
            }else{
                $form = false;
            }
            if($fval['form_add'] === 1 || $fval['form_add'] === '1'){
                $add = true;
            }else{
                $add = false;
            }
            if($fval['form_edit'] === 1 || $fval['form_edit'] === '1'){
                $edit = true;
            }else{
                $edit = false;
            }
            $group = $fval['form_group'];
            !isset($group) && $group = 100;
            $lst = [
                'name' => $name,
                'form' => $form,
                'add' => $add,
                'edit' => $edit,
                'data_type' => $data_type,
                'input_type' => $input_type,
                'sort' => $fval['form_sort'],
                'field_type' => $fval['field_type'],
                'field_type_info' => $fval['field_type_info'],
                'group' => $group
            ];

            if(!empty($config)){
                $lst['config'] = $config;
                $lst['list'] = $this->__getKeyValue($config, $data_type);
            }
            if(!empty($fval['verify'])){
                $lst['verify'] = $fval['verify'];
            }
            if(!empty($fval['format'])){
                $lst['format'] = $fval['format'];
            }
            if(!empty($fval['file_max_size'])){
                $lst['file_max_size'] = $fval['file_max_size'];
            }
            $group_fields[$group_name][$key] = $lst;
        }

        foreach ($group_fields as $key => $val){
            $group_fields[$key] = $this->getFormSort($val, $group_names[$key]);
        }
        return $group_fields;
    }

    /**
     * 获取验证规则
     * @param $fields
     * @return array
     */
    public function __getRules($fields){
        $ret = [];
        if(empty($fields)){
            return $ret;
        }
        foreach ($fields as $key=>$val){
            $verify = $val['verify'];
            if(empty($verify)){
                continue;
            }
            $d_decimal = $verify['decimal'];
            $f_decimal = $val['field_type_info']['decimal'];
            $decimal = null;
            if(empty($d_decimal)){
                if(!empty($f_decimal)){
                    $decimal = $f_decimal;
                }
            }elseif(empty($f_decimal)){
                $decimal = $d_decimal;
            }else{
                if($d_decimal > $f_decimal){
                    $decimal = $f_decimal;
                }else{
                    $decimal = $d_decimal;
                }
            }
            $v_verify = $verify['verify'];

            $data_type = $val['data_type'];
            if($data_type === 'text'){
                if(isset($verify)){
                    $dvt = $verify['default_value_type'];
                    if($dvt == 'add'){
                        $val['add'] = false;
                    }elseif($dvt == 'edit'){
                        $val['add'] = false;
                        $val['edit'] = false;
                    }
                }
            }

            $info = [];
            $info['is_add'] = $val['add'];
            $info['is_edit'] = $val['edit'];
            $info['name'] = $val['name'];
            $fti = $val['field_type_info'];
            $is_null = $fti['is_null'];
            if(!isset($is_null)){
                $is_null = true;
            }
            $vfy = [];
            if(!$is_null){
                $vfy['required_def'] = true;
            }
            if(!empty($v_verify)){
                foreach ($v_verify as $v){
                    $vfy[$v] = true;
                }
            }
            if(!empty($vfy)){
                $info['verify'] = $vfy;
            }
            $type = $val['field_type'];
            $info['type'] = $type;
            $number = [];
            if(isset($verify['number_min'])){
                $number['min'] = $verify['number_min'];
            }
            if(isset($verify['number_max'])){
                $number['max'] = $verify['number_max'];
            }
            if(!empty($number)){
                $info['number'] = $number;
            }

            $length = [];
            if(isset($verify['length_min'])){
                $length['min'] = $verify['length_min'];
            }
            $field_length = $fti['length'];
            if(!isset($field_length)){
                $field_length = -1;
            }
            if(isset($verify['length_max'])){
                $length['max'] = $verify['length_max'];
                if($length['max'] > $field_length && $field_length > 0){
                    $length['max'] = $field_length;
                }
            }elseif($field_length > 0){
                $length['max'] = $field_length;
            }
            if(!empty($length)){
                $info['length'] = $length;
            }
            if(!empty($decimal) && is_numeric($decimal)){
                $info['decimal'] = intval($decimal);
            }

            if($verify['regular_status'] == 'open'){
                $regular = [];
                if(isset($verify['regular_code'])){
                    $regular['code'] = $verify['regular_code'];
                }
                if(isset($verify['regular_msg'])){
                    $regular['msg'] = $verify['regular_msg'];
                }
                if(!empty($regular)){
                    $info['regular'] = $regular;
                }
            }
            $ret[$key] = $info;
        }
        return $ret;
    }

    /**
     * 获取列表验证规则
     * @param $list
     * @return array
     */
    public function getListRules($list){
        $fields = [];
        foreach ($list as $key=>$val){
            if(is_array($val) && $val['edit']){
                $fields[$key] = $val;
            }
        }
        return $this->__getRules($fields);
    }

    /**
     * 获取表单验证规则
     * @param $form
     * @return array
     */
    public function getFormRules($form){
        $fields = [];
        foreach ($form as $key=>$val){
            foreach($val[1] as $k=>$v){
                foreach ($v as $kk=>$vv){
                    if(is_array($vv) && ($vv['add'] || $vv['edit'])){
                        $fields[$kk] = $vv;
                    }
                }
            }
        }
        return $this->__getRules($fields);
    }

    /**
     * 获取设置主键
     * @param $keys
     * @return array
     */
    public function getKeys($keys){
        $ret = [];
        if(empty($keys)){
            return $ret;
        }
        $inc = $keys['inc'];
        if(!empty($inc)){
            $ret[] = $inc;
        }
        $num = $keys['num'];
        if(!empty($num)){
            $ret[] = $num;
        }
        $no_inc = $keys['no_inc'];
        if(!empty($no_inc)){
            $ni_arr = json_decode($no_inc, true);
            if(is_array($ni_arr)){
                foreach ($ni_arr as $ni){
                    $ret[] = $ni;
                }
            }
        }
        $ret = array_unique($ret);
        return $ret;
    }
}