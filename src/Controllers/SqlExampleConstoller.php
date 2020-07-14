<?php

namespace Apcu\Controllers;

/**
 * 增删改查工具
 * 为使代码更加精简的配置工具
 * 本功能优化查询，JOIN默认为单表查询
 * BY YX 2015-08-13
 */
class SqlExampleConstoller{
    
    /**
     * 在字段前面加前缀
     * @param type $modas 数据前缀
     * @param type $arr 字段
     */
    private function explodeArr($modas, $arr){
        if(is_array($arr)){
            $rarr = $arr;
            foreach ($rarr as $key => $val) {
                $pos = strpos($val, "(");
                if($pos === 0 || $pos > 0){
                    $val = preg_replace("/[{$modas}.]/", "", $val);
                    $val = preg_replace("/[(]/", "({$modas}.", $val);
                    $rarr[$key] = trim($val);
                }else{
                    $rarr[$key] = "{$modas}.".trim($val);
                }
                unset($rarr[$key]);
            }
        }else{
            $rarr = explode(',', $arr);
            foreach ($rarr as $key => $val) {
                $pos = strpos($val, "(");
                if($pos === 0 || $pos > 0){
                    $val = preg_replace("/[{$modas}.]/", "", $val);
                    $val = preg_replace("/[(]/", "({$modas}.", $val);
                    $rarr[$key] = trim($val);
                }else{
                    $rarr[$key] = "{$modas}.".trim($val);
                }
            }
        }
        
        return $rarr;
    }

    /**
     * 值KEY为小写
     * @param $data
     * @return array
     */
    private function setValueLower($data) {
        $datajson = json_encode($data, true);
        $datajson = strtolower($datajson);
        return json_decode($datajson, true);
    }

    /**
     * 设置KEY为小写
     * @param $data
     * @param $notin
     * @return array
     */
    private function setKeyLower($data) {
        if(empty($data) || is_string($data)) return $data;
        $newdata = array();
        foreach($data as $key=>$val){
            $newdata[strtolower($key)] = $val;
        }
        return $newdata;
    }

    /**
     * 设置小写
     * @param $data
     */
    private function setLower($data) {
        $data = $this->setKeyLower($data);
        if(!empty($data['where'])){
            $data['where'] = $this->setKeyLower($data['where']);
            foreach ($data['where'] as $key=>$val) {
                is_int($val) && $data['where'][$key] = "{$val}";
            }
        }
        if(!empty($data['data'])) $data['data'] = $this->setKeyLower($data['data']);
        if(!empty($data['join'])) $data['join'] = $this->setKeyLower($data['join']);
        if(!empty($data['field'])) $data['field'] = $this->setValueLower($data['field']);
        if(!empty($data['order'])) $data['order'] = $this->setValueLower($data['order']);
        if(!empty($data['group'])) $data['group'] = $this->setValueLower($data['group']);
        return $data;
    }

    /**
     * 字符串转化
     * @param type $string
     * @return type
     */
    private function explodeWhereString($string, $modas){
        if(empty($string)) return $string;
        $string = preg_replace("/[\s]+/is", " ", trim($string)); //合并空格
        if($string[0] != '(') $string = "{$modas}.{$string}";
        $string = preg_replace("/ AND /", " and ", $string); //转换and
        $string = preg_replace("/ OR /", " or ", $string); //转换or
        $string = preg_replace("/[\(](?:\s)+/", "(", $string); //清除括号直接的空格
        $string = preg_replace("/[\(](?!\()/", "({$modas}.", $string); //替换连在一起的最后一个左括号
        $string = preg_replace("/(and )(?!\()/", "and {$modas}.", $string); //转换and替换
        $string = preg_replace("/(or )(?!\()/", "or {$modas}.", $string); //转换or替换
        return $string;
    }
    
    /**
     * 在条件前面加前缀
     * @param type $modas 数据前缀
     * @param type $field 字段
     */
    private function explodeWhere($modas, $where){
        if(!is_array($where)) return $where;
        
        foreach ($where as $key => $val) {
            if(strtolower($key) == '_string'){
                $string = $this->explodeWhereString($val, $modas);
                if($key != '_string'){
                    unset($where[$key]);
                }
            }else{
                $where["{$modas}.{$key}"] = $val;
                unset($where[$key]);
            }
        }
        return array($where, $string);
    }
    
    /**
     * 数组转化为字符串
     * @param type $arr
     * @return string
     */
    private function ArrayToString($array, $sep=','){
        $str = "";
        foreach ($array as $key => $val) {
            if(empty($str)){
                $str = $val;
            }else{
                $str .= "{$sep}{$val}";
            }
        }
        return $str;
    }

    
    /**
     * 批量查询语句（关联多表查询）
     * @param type $data 需要查找的数据
     * @return type M()类型
     */
    private function _select($data){
        $table = $data['table'];
        $where = $data['where'];
        $join = $data['join'];
        $field = $data['field'];
        $order = $data['order'];
        $group = $data['group'];
        $cache = $data['cache'];
        $db_pre = C("DB_PREFIX");
        $modas = "m";
        $db_as = "f";
        
        $mod = M($table)->alias($modas);

        $cache && $mod->cache(true); //是否缓存

        $dwhere = array();
        $djoin = array();
        $dfield = array();
        $dorder = array();
        $dgroup = array();
        $stringArr = array();
        
        if(!isset($field)) $field = "*";
        $dfield = array_merge($dfield, $this->explodeArr($modas, $field));

        if(!empty($where)){
            list($rwhere, $str) = $this->explodeWhere($modas, $where);
            $dwhere = array_merge($dwhere, $rwhere);
            if(!empty($str)) $stringArr[] = $str;
        }
        
        if(!empty($order)) $dorder = $this->explodeArr($modas, $order);
        
        if(!empty($group)) $dgroup = $this->explodeArr($modas, $group);
        
        //处理JOIN条件问题
        if(!empty($join)){
            $i = 0;
            foreach ($join as $key => $val) {
                $as = "{$db_as}{$i}";
                $jn = "LEFT JOIN {$db_pre}{$val['table']} as {$as} ON {$as}.{$val['this']}={$modas}.{$val['parent']}";
                if(empty($djoin)){
                    $djoin = $jn;
                }else{
                    $djoin .= " {$jn}";
                }
                
                $w = $val['where'];
                if(!empty($w)){
                    list($rwhere, $str) = array_merge($dwhere, $this->explodeWhere($as, $w));
                    $dwhere = array_merge($dwhere, $rwhere);
                    if(!empty($str)) $stringArr[] = $str;
                }
                
                $f = $val['field'];
                if(!isset($f)) $f = "*";
                if(!empty($f)){
                    $dfield = array_merge($dfield, $this->explodeArr($as, $f));
                }
                
                $o = $val['order'];
                if(!empty($o)){
                    $dorder = array_merge($dorder, $this->explodeArr($as, $o));
                }
                
                $g = $val['group'];
                if(!empty($g)){
                    $dgroup = array_merge($dgroup, $this->explodeArr($as, $g));
                }
                $i++;
            }
        }
        
        //_string自动处理
        $string = "";
        foreach ($stringArr as $key => $val) {
            if(empty($string)){
                $string = "({$val})";
            }else{
                $string .= " and ({$val})";
            }
        }
        
        if(empty($string)){
            unset($dwhere['_string']);
        }else{
            $dwhere['_string'] = $string;
        }
        
        if(!empty($dwhere)) $mod = $mod->where($dwhere);
        if(!empty($dfield)){
            $tfid = implode(", ", $dfield);
            $mod = $mod->field($tfid);
        }
        
        if(!empty($djoin)) $mod = $mod->join($djoin);
        if(!empty($dorder)) $mod = $mod->order($this->ArrayToString($dorder));
        if(!empty($dgroup)) $mod = $mod->group($this->ArrayToString($dgroup));
        
        return $mod;
    }
    
    /**
     * 分解Field字段
     * @param type $field
     */
    private function resolveField($field){
        $fields = explode(',', $field);
        foreach ($fields as $key => $val) {
            if(empty($val)){
                unset($fields[$key]);
            }
        }

        return $fields;
    }
    
    /**
     * 获取Field字段
     * 将空字段填充
     * @param type $field
     */
    private function getFieldArr($field){
        $fields = array();
        foreach ($field as $key => $val) {
            $ind = explode(" ", $val);
            if(count($ind) > 1){
                $name = trim($ind[count($ind) - 1]);
            }else{
                $name = trim($val);
            }
            $fields[$name] = null;
        }
        return $fields;
    }
    
    /**
     * 批量查询语句（把JOIN转成单表查询）
     * @param type $data 需要查找的数据
     * @return type M()类型
     */
    private function _selectNoJoin($data, $type='select'){
        $table = $data['table'];
        $where = $data['where'];
        $join = $data['join'];
        $field = $data['field'];
        $order = $data['order'];
        $group = $data['group'];
        $cache = $data['cache'];
        $as = "xfd";

        $fields = $this->resolveField($field);
        $joins = array();
        $i = 0;
        foreach ($join as $key => $val) { //增加JOIN中的元素
            $asname = "{$as}{$i}";
            $fields[] = "{$val['parent']} as $asname";
            $joins[$asname] = $val;
            $i ++;
        }

        $mod = M($table);
        $cache && $mod->cache(true);

        !empty($where) && $mod = $mod->where($where);
        !empty($fields) && $mod = $mod->field($fields);
        !empty($order) && $mod = $mod->order($order);
        !empty($group) && $mod = $mod->group($group);
        if($type == 'select'){
            $list = $mod->select();
        }else{
            $list = array($mod->find());
        }
        
        foreach ($list as $key => $val) {
            foreach ($val as $k => $v) {
                if(!empty($joins[$k])){
                    $joins[$k]['data'][] = $v;
                }
            }
        }
        
        foreach ($joins as $key => $val) { //增加JOIN中的元素
            $joins[$key]['data'] = array_unique($val['data']);
        }
        
        //处理JOIN条件问题
        if(!empty($joins)){
            foreach ($joins as $key => $val) {
                $t = $val['table'];
                $w = $val['where'];
                $w[$key] = array('in', $val['data']);
                $f = $val['field'];
                $fs = $this->resolveField($f);
                $farr = $this->getFieldArr($fs); //如果查询为空则填充其属性并将其设置为空
                $fs[] = $val['this'];
                $inlist = M($t)->where($w)->field($fs)->select();
                $retlist = array();
                foreach ($inlist as $k => $v) {
                    $t = $v[$val['this']];
                    unset($v[$val['this']]);
                    $retlist[$t] = $v;
                }
                
                $joins[$key]['fieldarr'] = $farr;
                $joins[$key]['info'] = $retlist;
            }
        }
        
        foreach ($list as $key => $val) {
            foreach ($val as $k => $v) {
                if(!empty($joins[$k])){
                    unset($list[$key][$k]);
                    if(empty($joins[$k]['info'][$v])){
                        $list[$key] = array_merge($list[$key], $joins[$k]['fieldarr']);
                    }else{
                        $list[$key] = array_merge($list[$key], $joins[$k]['info'][$v]);
                    }
                }
            }
        }

        return $list;
    }


    /**
     * 设置数据库
     * @param $name 数据库名称
     */
    public function setDB($name){
        $cnames = C($name);
        if(!empty($cnames)){
            if(is_array($cnames)){
                foreach($cnames as $key=>$val){
                    C(strtoupper($key), $val);
                }
            }
        }
    }

    /**
     * 批量查询语句select
     * @param type $data 需要查找的数据
     * @param type $isJoin 是否关联表查询，默认不关联，如果不关联，则join中对应的order和group无效
     * @return type 数据列表
     */
    public function select($data, $isJoin=true){
        $data = $this->setLower($data);
        if($isJoin){
            $mod = $this->_select($data);
            $list = $mod->select();
            return $list;
        }else{
            return $this->_selectNoJoin($data);
        }
    }
    
    
    /**
     * 批量查询语句find
     * @param type $data 需要查找的数据
     * @param type $isJoin 是否关联表查询，默认不关联，则join中对应的order和group无效
     * @return type 数据列表
     */
    public function find($data, $isJoin=true){
        $data = $this->setLower($data);
        if($isJoin){
        	$mod = $this->_select($data);
            $list = $mod->find();
            $list = $list[0];
            return $list;
        }else{
            return $this->_selectNoJoin($data, 'find');
        }
    }
    
    
    /**
     * 批量查询语句count
     * @param type $data 需要查找的数据
     * @return type 数据列表
     */
    public function count($data){
        $data = $this->setLower($data);
        $table = $data['table'];
        $where = $data['where'];
        $cache = $data['cache'];
        $mod = M($table);
        $cache && $mod->cache(true);
        !empty($where) && $mod->where($where);
        return $mod->count();
    }
    
    
    /**
     * 高级更新数据
     * @param type $data 需要更新的数据
     * @param type $type NULL为正常更新, ADD为如果查询条件未找到则强制增加, ONE为只更新一个数据, 其他类似删除, ONEADD为在ONE的基础上未找到数据则强制增加
     * @return type 数据列表
     */
    public function update($data, $type="NULL"){
        $data = $this->setLower($data);
        $table = $data['table'];
        $where = $data['where'];
        $dat = $data['data'];
        if($type == "NULL" || $type == "ONE"){
            if($type == "ONE"){
                $count = M($table)->where($where)->count();
                if($count > 1){
                    M($table)->where($where)->limit($count - 1)->delete();
                }
            }
            return M($table)->where($where)->save($dat);
        }elseif($type == "ADD" || $type == "ONEADD"){
            $count = M($table)->where($where)->count();
            if($count <= 0){
                foreach ($where as $key => $val) {
                    if($key != '_string' && is_string($val) && empty($dat[$key])){
                        $dat[$key] = $val;
                    }
                }
                return M($table)->add($dat);
            }else{
                if($type == "ONEADD" && $count > 1){
                    M($table)->where($where)->limit($count - 1)->delete();
                }
                
                return M($table)->where($where)->save($dat);
            }
        }
    }
    
    
    /**
     * 增加数据
     * @param type $data 需要增加的数据
     * @return type 数据列表
     */
    public function add($data){
        $data = $this->setLower($data);
        $table = $data['table'];
        $dat = $data['data'];
        return M($table)->add($dat);
    }
    
    
    /**
     * 删除数据，此删除属于安全删除，避免误操作把表里面所有数据删掉
     * @param type $data 需要删除的数据
     * @param type $max 最多允许数量，默认为10
     * @return type 数据列表
     */
    public function delete($data, $max=10){
        $data = $this->setLower($data);
        $table = $data['table'];
        $where = $data['where'];
        if(empty($where)){
            return -2; //条件为空不删除
        }elseif($max = -1){ //不限制条数
            return M($table)->where($where)->delete();
        }else{ //限制条数
            return M($table)->where($where)->limit($max)->delete();
        }
    }
}