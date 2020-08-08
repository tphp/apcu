<?php
/**
 * Created by PhpStorm.
 * User: TPHP
 * Date: 18-9-7
 * Time: 下午3:56
 * Remark: Mysql数据库字段同步脚本
 */

require_once "SqlClass.php";

class MySql extends SqlClass
{
    protected function getDatabaseDetail($db_info)
    {
        // TODO: Implement getDatabaseDetail() method.

        if(empty($db_info) || !is_array($db_info)) return [0, "配置为空"];
        $host = $db_info['host'];
        $database = $db_info['database'];
        $username = $db_info['username'];
        if(empty($host) || empty($database) || empty($username)) return [0, "配置错误"];
        $conn = $this->conn;
        list($status, $info) = $this->linkedTest($conn);
        if(!$status) return [0, "{$database}:{$info}"];
        $mod = DB::connection($conn);
        $version = $mod->select("select version() as v")[0]->v;
        $serverset = 'character_set_connection=utf8, character_set_results=utf8, character_set_client=binary';
        $serverset .= $version > '5.0.1' ? ', sql_mode=\'\'' : '';
        $mod->statement("SET {$serverset}");

        $detail = array('table' => array(), 'field' => array(), 'index' => array());
        $tables = (array)$mod->select("show table status");
        if ($tables) {
            foreach ($tables as $key_table => $table) {
                $table = (array)$table;
                $detail['table'][$table['Name']] = $table;
                //字段
                $fields = (array)$mod->select("show full fields from `" . $table['Name'] . "`");
                if ($fields) {
                    foreach ($fields as $key_field => $field) {
                        $field = (array)$field;
                        $fields[$field['Field']] = $field;
                        unset($fields[$key_field]);
                    }
                    $detail['field'][$table['Name']] = $fields;
                } else {
                    return [0, '无法获得表的字段:' . $database . ':' . $table['Name']];
                }
                //索引
                $indexes = (array)$mod->select("show index from `" . $table['Name'] . "`");
                if ($indexes) {
                    foreach ($indexes as $key_index => $index) {
                        $index = (array)$index;
                        $indexes[$key_index] = $index;
                        if (!isset($indexes[$index['Key_name']])) {
                            $index['Column_name'] = array($index['Seq_in_index'] => $index['Column_name']);
                            $indexes[$index['Key_name']] = $index;
                        } else
                            $indexes[$index['Key_name']]['Column_name'][$index['Seq_in_index']] = $index['Column_name'];
                        unset($indexes[$key_index]);
                    }
                    $detail['index'][$table['Name']] = $indexes;
                } else {
                    //$errors[]='无法获得表的索引信息:'.$database.':'.$table['Name'];
                    $detail['index'][$table['Name']] = array();
                }
            }
            return [1, "ok", $detail];
        } else {
            return [1, '无法获得数据库的表详情' . []];
        }
    }

    protected function compareDatabase($new, $old)
    {
        // TODO: Implement compareDatabase() method.

        $diff = array('table' => array(), 'field' => array(), 'index' => array());
        //table
        if(!empty($old['table'])) {
            foreach ($old['table'] as $table_name => $table_detail) {
                if (!isset($new['table'][$table_name]))
                    $diff['table']['drop'][$table_name] = $table_name; //删除表
            }
        }
        if(!empty($new['table'])) {
            foreach ($new['table'] as $table_name => $table_detail) {
                if (!isset($old['table'][$table_name])) {
                    //新建表
                    $diff['table']['create'][$table_name] = $table_detail;
                    $diff['field']['create'][$table_name] = $new['field'][$table_name];
                    $diff['index']['create'][$table_name] = $new['index'][$table_name];
                } else {
                    //对比表
                    $old_detail = $old['table'][$table_name];
                    $change = array();
                    if ($table_detail['Engine'] !== $old_detail['Engine'])
                        $change['Engine'] = $table_detail['Engine'];
                    if ($table_detail['Row_format'] !== $old_detail['Row_format'])
                        $change['Row_format'] = $table_detail['Row_format'];
                    if ($table_detail['Collation'] !== $old_detail['Collation'])
                        $change['Collation'] = $table_detail['Collation'];
                    //if($table_detail['Create_options']!=$old_detail['Create_options'])
                    //	$change['Create_options']=$table_detail['Create_options'];
                    if ($table_detail['Comment'] !== $old_detail['Comment'])
                        $change['Comment'] = $table_detail['Comment'];
                    if (!empty($change)){
                        $diff['table']['change'][$table_name] = $change;
                    }
                }
            }
        }

        //index
        if(!empty($old['index'])) {
            foreach ($old['index'] as $table => $indexs) {
                if (isset($new['index'][$table])) {
                    $new_indexs = $new['index'][$table];
                    foreach ($indexs as $index_name => $index_detail) {
                        if (!isset($new_indexs[$index_name])) {
                            //索引不存在，删除索引
                            $diff['index']['drop'][$table][$index_name] = $index_name;
                        }
                    }
                } else {
                    if (!isset($diff['table']['drop'][$table])) {
                        foreach ($indexs as $index_name => $index_detail) {
                            $diff['index']['drop'][$table][$index_name] = $index_name;
                        }
                    }
                }
            }
        }
        if(!empty($new['index'])) {
            foreach ($new['index'] as $table => $indexs) {
                if (isset($old['index'][$table])) {
                    $old_indexs = $old['index'][$table];
                    foreach ($indexs as $index_name => $index_detail) {
                        if (isset($old_indexs[$index_name])) {
                            //存在，对比内容
                            if ($index_detail['Non_unique'] !== $old_indexs[$index_name]['Non_unique'] || $index_detail['Column_name'] !== $old_indexs[$index_name]['Column_name'] || $index_detail['Collation'] !== $old_indexs[$index_name]['Collation'] || $index_detail['Index_type'] !== $old_indexs[$index_name]['Index_type']) {
                                $diff['index']['drop'][$table][$index_name] = $index_name;
                                $diff['index']['add'][$table][$index_name] = $index_detail;
                            }
                        } else {
                            //不存在，新建索引
                            $diff['index']['add'][$table][$index_name] = $index_detail;
                        }
                    }
                } else {
                    if (!isset($diff['table']['create'][$table])) {
                        foreach ($indexs as $index_name => $index_detail) {
                            $diff['index']['add'][$table][$index_name] = $index_detail;
                        }
                    }
                }
            }
        }

        //fields
        if(!empty($old['field'])) {
            foreach ($old['field'] as $table => $fields) {
                if (isset($new['field'][$table])) {
                    $new_fields = $new['field'][$table];
                    foreach ($fields as $field_name => $field_detail) {
                        if (!isset($new_fields[$field_name])) {
                            //字段不存在，删除字段
                            $diff['field']['drop'][$table][$field_name] = $field_detail;
                        }
                    }
                } else {
                    //旧数据库中的表在新数据库中不存在，需要删除
                }
            }
        }
        if(!empty($new['field'])) {
            foreach ($new['field'] as $table => $fields) {
                if (isset($old['field'][$table])) {
                    $old_fields = $old['field'][$table];
                    $last_field = '';
                    foreach ($fields as $field_name => $field_detail) {
                        if (isset($old_fields[$field_name])) {
                            //字段存在，对比内容
                            if (
                                $field_detail['Type'] !== $old_fields[$field_name]['Type'] ||
                                $field_detail['Collation'] !== $old_fields[$field_name]['Collation'] ||
                                $field_detail['Null'] !== $old_fields[$field_name]['Null'] ||
                                $field_detail['Default'] !== $old_fields[$field_name]['Default'] ||
                                $field_detail['Extra'] !== $old_fields[$field_name]['Extra'] ||
                                $field_detail['Comment'] !== $old_fields[$field_name]['Comment']
                            ) {
                                $diff['field']['change'][$table][$field_name] = $field_detail;
                            }
                        } else {
                            //字段不存在，添加字段
                            $field_detail['After'] = $last_field;
                            $diff['field']['add'][$table][$field_name] = $field_detail;
                        }
                        $last_field = $field_name;
                    }
                } else {
                    //新数据库中的表在旧数据库中不存在，需要新建
                }
            }
        }

        return $diff;
    }

    protected function buildQuery($diff)
    {
        // TODO: Implement buildQuery() method.
        
        if (empty($diff['table']) && empty($diff['field']) && empty($diff['index'])) {
            return [0, "数据相同，无需同步操作！"];
        }
        
        $sqls = array();
        if ($diff) {
            if (isset($diff['table']['drop'])) {
                foreach ($diff['table']['drop'] as $table_name => $table_detail) {
                    $sqls[$table_name]['__DROP__'][] = "DROP TABLE `{$table_name}`";
                }
            }
            if (isset($diff['table']['create'])) {
                foreach ($diff['table']['create'] as $table_name => $table_detail) {
                    $fields = $diff['field']['create'][$table_name];
                    $sql = "CREATE TABLE `$table_name` (";
                    $t = array();
                    $k = array();
                    foreach ($fields as $field) {
                        $t[] = "`{$field['Field']}` " . strtoupper($field['Type']) . $this->sqlNull($field['Null']) . $this->sqlDefault($field['Default']) . $this->sqlExtra($field['Extra']) . $this->sqlComment($field['Comment']);
                    }
                    if (isset($diff['index']['create'][$table_name]) && !empty($diff['index']['create'][$table_name])) {
                        $indexs = $diff['index']['create'][$table_name];
                        foreach ($indexs as $index_name => $index_detail) {
                            if ($index_name == 'PRIMARY')
                                $k[] = "PRIMARY KEY (`" . implode('`,`', $index_detail['Column_name']) . "`)";
                            else
                                $k[] = ($index_detail['Non_unique'] == 0 ? "UNIQUE" : "INDEX") . "`$index_name`" . " (`" . implode('`,`', $index_detail['Column_name']) . "`)";
                        }
                    }
                    list($charset) = explode('_', $table_detail['Collation']);
                    $sql .= implode(', ', $t) . (!empty($k) ? ',' . implode(', ', $k) : '') . ') ENGINE = ' . $table_detail['Engine'] . ' DEFAULT CHARSET = ' . $charset;
                    $sqls[$table_name]['__CREATE__'][] = $sql;
                }
            }
            if (isset($diff['table']['change'])) {
                foreach ($diff['table']['change'] as $table_name => $table_changes) {
                    if (!empty($table_changes)) {
                        $sql = "ALTER TABLE `$table_name`";
                        foreach ($table_changes as $option => $value) {
                            if ($option == 'Collation') {
                                list($charset) = explode('_', $value);
                                $sql .= " DEFAULT CHARACTER SET $charset COLLATE $value";
                            } else
                                $sql .= " " . strtoupper($option) . " = '$value' ";
                        }
                        $sqls[$table_name]['__CHANGE__'][] = $sql;
                    }
                }
            }
            if (isset($diff['index']['drop'])) {
                foreach ($diff['index']['drop'] as $table_name => $indexs) {
                    foreach ($indexs as $index_name => $index_detail) {
                        if ($index_name == 'PRIMARY')
                            $sqls[$table_name]['__DROP__'][] = "ALTER TABLE `$table_name` DROP PRIMARY KEY";
                        else
                            $sqls[$table_name]['__DROP__'][] = "ALTER TABLE `$table_name` DROP INDEX `$index_name`";
                    }
                }
            }
            if (isset($diff['field']['drop'])) {
                foreach ($diff['field']['drop'] as $table_name => $fields) {
                    foreach ($fields as $field_name => $field_detail) {
                        $sqls[$table_name]['__DROP__'][] = "ALTER TABLE `$table_name` DROP `$field_name`";
                    }
                }
            }
            if (isset($diff['field']['add'])) {
                foreach ($diff['field']['add'] as $table_name => $fields) {
                    foreach ($fields as $field_name => $field_detail) {
                        $sqls[$table_name][$field_name][] = "ALTER TABLE `$table_name` ADD `{$field_name}` " . strtoupper($field_detail['Type']) . $this->sqlCol($field_detail['Collation']) . $this->sqlNull($field_detail['Null']) . $this->sqlDefault($field_detail['Default']) . $this->sqlExtra($field_detail['Extra']) . $this->sqlComment($field_detail['Comment']) . " AFTER `{$field_detail['After']}`";
                    }
                }
            }
            if (isset($diff['index']['add'])) {
                foreach ($diff['index']['add'] as $table_name => $indexs) {
                    foreach ($indexs as $index_name => $index_detail) {
                        $field_name = implode('`,`', $index_detail['Column_name']);
                        if ($index_name == 'PRIMARY')
                            $sqls[$table_name][$field_name][] = "ALTER TABLE `$table_name` ADD PRIMARY KEY (`" . $field_name . "`)";
                        else
                            $sqls[$table_name][$field_name][] = "ALTER TABLE `$table_name` ADD" . ($index_detail['Non_unique'] == 0 ? " UNIQUE " : " INDEX ") . "`$index_name`" . " (`" . $field_name . "`)";
                    }
                }
            }
            if (isset($diff['field']['change'])) {
                foreach ($diff['field']['change'] as $table_name => $fields) {
                    foreach ($fields as $field_name => $field_detail) {
                        $sqls[$table_name]['__CHANGE__'][] = "ALTER TABLE `$table_name` CHANGE `{$field_name}` `{$field_name}` " . strtoupper($field_detail['Type']) . $this->sqlCol($field_detail['Collation']) . $this->sqlNull($field_detail['Null']) . $this->sqlDefault($field_detail['Default']) . $this->sqlExtra($field_detail['Extra']) . $this->sqlComment($field_detail['Comment']);
                    }
                }
            }
        }

        $sqls = $this->getRealSqls($sqls);
        if (empty($sqls)) {
            return [0, "数据相同，无需同步操作！"];
        }
        return [1, $sqls];
    }

    protected function updateDatabase()
    {
        // TODO: Implement updateDatabase() method.

        $db_info = $this->db_info;
        if(empty($db_info) || !is_array($db_info)) return [0, "配置为空"];
        $host = $db_info['host'];
        $database = $db_info['database'];
        $username = $db_info['username'];
        if(empty($host) || empty($database) || empty($username)) return [0, "配置错误"];
        $conn = $this->conn;
        list($status, $info) = $this->linkedTest($conn);
        if(!$status) return [0, "{$database}:{$info}"];
        $mod = DB::connection($conn);
        $version = $mod->select("select version() as v")[0]->v;
        $serverset = 'character_set_connection=utf8, character_set_results=utf8, character_set_client=binary';
        $serverset .= $version > '5.0.1' ? ', sql_mode=\'\'' : '';
        $mod->statement("SET {$serverset}");

        $cot_ok = 0;
        $cot_no = 0;
        $sqls = $this->sqls;
        $errors = [];
        $oks = [];
        foreach ($sqls as $key=>$val) {
            foreach ($val as $k=>$v) {
                try {
                    $ret = $mod->statement($v);
                    if($ret > 0){
                        $cot_ok += $ret;
                        $oks[] = $v;
                    }else{
                        $cot_no++;
                        $errors[] = $v;
                    }
                } catch (Exception $e) {
                    $cot_no++;
                    $errors[] = $e->getMessage();
                }
            }
        }
        
        $this->saveLogs($database."/".$conn, $oks, $errors);
        
        if($cot_ok > 0){
            $str = "操作成功数: {$cot_ok}";
            if($cot_no > 0){
                $str .= "<BR>操作失败数: {$cot_no}";
                return [1, $str, $errors];
            }
            return [1, $str];
        }
        return [0, "操作失败数: {$cot_no}", $errors];
    }

    /**
     * 获取真实数据库语句
     * @param $sqls
     * @return array
     */
    private function getRealSqls($sqls){
        $retsqls = [];
        if(empty($sqls)){
            return $retsqls;
        }
        foreach ($sqls as $key=>$val) {
            foreach ($val as $k=>$v) {
                $vlen = count($v);
                if ($vlen > 1) { //处理主键合并代码
                    $sql = $v[0];
                    $sql = str_replace("AFTER ``", "", $sql);
                    $sql2 = $v[1];
                    if (!empty($sql2)) {
                        $add = "ADD";
                        $pos = strpos($sql2, $add);
                        if ($pos > 0) {
                            $sql2 = substr($sql2, $pos);
                            if (!empty($sql2)) {
                                $sql = str_replace("ADD", "ADD COLUMN", $sql);
                                $sql .= "FIRST, {$sql2}";
                            }
                        }
                    }
                } elseif ($vlen > 0) {
                    $sql = $v[0];
                }
                if(!empty($sql)){
                    $retsqls[$key][$k] = $sql;
                }
            }
        }
        return $retsqls;
    }
    
    private function sqlCol($val)
    {
        switch ($val) {
            case null:
                return '';
            default:
                list($charset) = explode('_', $val);
                return ' CHARACTER SET ' . $charset . ' COLLATE ' . $val;
        }
    }

    private function sqlDefault($val)
    {
        if ($val === null) {
            return '';
        } else {
            return " DEFAULT '" . stripslashes($val) . "'";
        }
    }

    private function sqlNull($val)
    {
        switch ($val) {
            case 'NO':
                return ' NOT NULL';
            case 'YES':
                return ' NULL';
            default:
                return '';
        }
    }

    private function sqlExtra($val)
    {
        switch ($val) {
            case '':
                return '';
            default:
                return ' ' . strtoupper($val);
        }
    }

    private function sqlComment($val)
    {
        switch ($val) {
            case '':
                return '';
            default:
                return " COMMENT '" . stripslashes($val) . "'";
        }
    }
}
