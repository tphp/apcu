<?php
/**
 * Created by PhpStorm.
 * User: TPHP
 * Date: 20-7-28
 * Time: 上午11:16
 * Remark: Sqlserver数据库字段同步脚本
 */

require_once "SqlClass.php";

class SqlSrv extends SqlClass{
    private $compare_field = ['flag', 'pk', 'type', 'length', 'point', 'isnull', 'default', 'remark'];
    private $is_length = ["binary", "char", "datetime2", "datetimeoffset", "decimal", "nchar", "numeric", "nvarchar", "time", "varbinary", "varchar"];

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
        //遍历表
        $ret = $this->getListToKeyValue($mod->select(<<<EOF
SELECT
    d.name as 'table',
    a.name as field,
    COLUMNPROPERTY( a.id,a.name,'IsIdentity') as flag,
    (
        SELECT count(*) FROM sysobjects
        WHERE (
            name in (
                SELECT name FROM sysindexes
                WHERE (id = a.id)  AND (
                    indid in (
                        SELECT indid FROM sysindexkeys
                        WHERE (id = a.id) AND (
                            colid in (
                                SELECT colid FROM syscolumns
                                WHERE (id = a.id) AND (name = a.name)
                            )
                        )
                    )
                )
            )
        )
        AND (xtype = 'PK')
    ) as pk,
    b.name as type,
    COLUMNPROPERTY(a.id,a.name,'PRECISION') as length,
    isnull(COLUMNPROPERTY(a.id,a.name,'Scale'),0) as point,
    a.isnullable as isnull,
    isnull(e.text,'') as 'default',
    isnull(g.[value],'') AS remark
FROM syscolumns a left join systypes b
    on a.xtype=b.xusertype
    inner join sysobjects d
    on a.id=d.id and d.xtype='U' and d.name<>'dtproperties'
    left join syscomments e
    on a.cdefault=e.id
    left join sys.extended_properties g
    on a.id=g.major_id AND a.colid = g.minor_id
order by a.id, a.colorder
EOF
), 'table', 'field', $this->compare_field);
        return [1, 'ok', $ret];
    }

    protected function compareDatabase($new, $old)
    {
        // TODO: Implement compareDatabase() method.

        $diff = [
            'table' => [],
            'change' => []
        ];

        // 查询表数量统计
        $table_counts = $this->getListToKeyValue(DB::connection($this->conn)->select(<<<EOF
SELECT
	a.name,
	b.rows
FROM
	sysobjects AS a
	INNER JOIN sysindexes AS b ON a.id = b.id
WHERE
	a.type = 'u'
	AND b.indid IN (0, 1)
	AND b.rows > 0
EOF
        ), 'name', 'rows');

        foreach ($old as $table_name => $table_detail){
            if (!isset($new[$table_name])){
                //删除表
                $diff['table']['drop'][$table_name] = $table_name;
            }
        }
        foreach ($new as $table_name => $new_detail){
            $old_detail = $old[$table_name];
            if (!isset($old_detail)){
                //创建表
                $diff['table']['create'][$table_name] = $new_detail;
                continue;
            }
            $field = [
                'add' => [],
                'delete' => [],
                'change' => [],
                'equal' => []
            ];
            foreach ($old_detail as $fkey=>$fval){
                $nd = $new_detail[$fkey];
                if(!isset($nd)){
                    $field['delete'][] = $fkey;
                    continue;
                }
                if($this->arrayIsEqual($nd, $fval, $this->compare_field)) {
                    $field['equal'][$fkey] = $nd;
                }else{
                    $field['change'][$fkey] = [$nd, $fval];
                }
            }
            foreach ($new_detail as $fkey=>$fval){
                $od = $old_detail[$fkey];
                if(!isset($od)){
                    $field['add'][$fkey] = $fval;
                }
            }
            if(empty($field['add'])){
                unset($field['add']);
            }
            if(empty($field['delete'])){
                unset($field['delete']);
            }
            if(empty($field['change'])){
                unset($field['change']);
            }
            if(empty($field['equal']) || (empty($field['add']) && empty($field['delete']) && empty($field['change']))){
                unset($field['equal']);
            }
            if(!empty($field)){
                if(isset($table_counts[$table_name])){
                    $diff['change'][$table_name] = $field;
                }else{
                    $diff['table']['recreate'][$table_name] = $new_detail;
                }
            }
        }
        return $diff;
    }

    protected function buildQuery($diff)
    {
        // TODO: Implement buildQuery() method.

        if (empty($diff['table']) && empty($diff['change'])) {
            return [0, "数据相同，无需同步操作！"];
        }

        $sqls = [];
        $table = $diff['table'];
        if(!empty($table)){
            // 创建表
            $table_create = $table['create'];
            if(!empty($table_create)){
                foreach ($table_create as $table_name => $table_detail){
                    $sqls[$table_name]['__CREATE__'][] = $this->getCreateTableSql($table_name, $table_detail);
                }
            }

            // 当列表数据为空时先删除原表再创建
            $table_recreate = $table['recreate'];
            if(!empty($table_recreate)){
                foreach ($table_recreate as $table_name => $table_detail){
                    $sqls[$table_name]['__DROP__'][] = "DROP TABLE [{$table_name}]";
                    $sqls[$table_name]['__CREATE__'][] = $this->getCreateTableSql($table_name, $table_detail);
                }
            }

            // 删除表
            $table_drop = $table['drop'];
            if(!empty($table_drop)){
                foreach ($table_drop as $table_name => $table_detail){
                    $tn = str_replace('"', '""', $table_name);
                    $sqls[$table_name]['__DROP__'][] = "DROP TABLE [{$tn}]";
                }
            }
        }

        $field = $diff['change'];
        if(!empty($field)){
            $del_tables = [];
            foreach ($field as $table_name=>$f){
                if(!empty($f['change']) || !empty($f['delete'])){
                    $del_tables[] = "'".str_replace("'", "''", $table_name)."'";
                }
            }
            if(empty($del_tables)) {
                $binds = [];
            }else{
                // 获取约束字段：主键或约束
                $del_table_str = implode(",", $del_tables);
                $binds = $this->getListToKeyValue(DB::connection($this->conn)->select(<<<EOF
SELECT
	TAB.NAME AS [table],
	IDX.NAME AS [name],
	COL.NAME AS [field]
FROM
	SYS.INDEXES IDX
	JOIN SYS.INDEX_COLUMNS IDXCOL ON (
		IDX.OBJECT_ID = IDXCOL.OBJECT_ID
		AND IDX.INDEX_ID = IDXCOL.INDEX_ID
		AND ( IDX.IS_PRIMARY_KEY = 1 OR IDX.IS_UNIQUE_CONSTRAINT = 1 OR ( IDX.IS_UNIQUE_CONSTRAINT = 0 AND IDX.IS_PRIMARY_KEY = 0 ) )
	)
	JOIN SYS.TABLES TAB ON ( IDX.OBJECT_ID = TAB.OBJECT_ID )
	JOIN SYS.COLUMNS COL ON ( IDX.OBJECT_ID = COL.OBJECT_ID AND IDXCOL.COLUMN_ID = COL.COLUMN_ID )
WHERE
	TAB.NAME IN({$del_table_str})

UNION
SELECT
	TAB.NAME AS [table],
	DCS.NAME AS [name],
	COL.NAME AS [field]
FROM
	SYS.DEFAULT_CONSTRAINTS DCS
	JOIN SYS.TABLES TAB ON ( DCS.PARENT_OBJECT_ID = TAB.OBJECT_ID )
	JOIN SYS.COLUMNS COL ON ( DCS.PARENT_OBJECT_ID = COL.OBJECT_ID AND DCS.PARENT_COLUMN_ID = COL.COLUMN_ID )
WHERE
	TAB.NAME IN({$del_table_str})
EOF
                ), 'table', 'field', 'name');
            }
            foreach ($field as $table_name => $field_info) {
                $f_add = $field_info['add'];
                $f_change = $field_info['change'];
                $f_delete = $field_info['delete'];
                $f_equal = $field_info['equal'];

                // 如果没有相同的字段则先删除表然后再创建
                if (empty($f_change) && empty($f_equal)) {
                    $sqls[$table_name]['__DROP__'][] = "DROP TABLE [{$table_name}]";
                    if (!empty($f_add)) {
                        $sqls[$table_name]['__CREATE__'][] = $this->getCreateTableSql($table_name, $f_add);
                    }
                    continue;
                }

                // 如果存在要删除的字段，先删除字段约束，再删除字段
                if(!empty($f_delete)){
                    $f_del_arr = [];
                    $f_bind_arr = [];
                    foreach ($f_delete as $f_del_key){
                        $f_del_arr[] = "[{$f_del_key}]";
                        if(is_array($binds[$table_name]) && isset($binds[$table_name][$f_del_key])){
                            $f_bind_arr[] = "[{$binds[$table_name][$f_del_key]}]";
                        }
                    }
                    if(count($f_bind_arr) > 0){
                        $f_bind_str = implode(",", $f_bind_arr);
                        $sqls[$table_name]['__CHANGE__'][] = "ALTER TABLE [{$table_name}] DROP CONSTRAINT {$f_bind_str}";
                    }
                    $f_del_str = implode(",", $f_del_arr);
                    $sqls[$table_name]['__CHANGE__'][] = "ALTER TABLE [{$table_name}] DROP COLUMN {$f_del_str}";
                }

                // 如果存在修改的字段
                $add_pks = [];
                $delete_pks = [];
                $is_update_pk = false;
                if(!empty($f_change)){
                    foreach ($f_change as $field_name=>list($new, $old)){
                        empty($sqls[$table_name][$field_name]) && $sqls[$table_name][$field_name] = [];
                        if(!$this->arrayIsEqual($new, $old, ['type', 'length', 'point', 'isnull'])) {
                            $sqls[$table_name][$field_name][] = $this->getChangeSql($new, $table_name, $field_name);
                        }
                        if($new['default'] != $old['default']){
                            if(isset($binds[$field_name])){
                                $sqls[$table_name][$field_name][] = "ALTER TABLE [{$table_name}] DROP CONSTRAINT [{$field_name}]";
                            }
                            if(!empty($new['default'])){
                                $sqls[$table_name][$field_name][] = "ALTER TABLE [{$table_name}] ADD DEFAULT {$new['default']} FOR [{$field_name}] WITH VALUES";
                            }
                        }
                        if(!$is_update_pk && $new['pk'] != $old['pk']){
                            $is_update_pk = true;
                        }
                        $remark = $new['remark'];
                        empty($remark) && $remark = '';
                        if($new['remark'] != $old['remark']){
                            $sqls[$table_name][$field_name][] = "execute sp_addextendedproperty 'MS_Description','{$remark}','user','dbo','table','{$table_name}','column','{$field_name}'";
                        }
                    }
                }

                // 如果存在新增字段
                if(!empty($f_add)){
                    foreach ($f_add as $field_name=>$fval){
                        $sqls[$table_name][$field_name][] = $this->getChangeSql($fval, $table_name, $field_name, true);
                        if($fval['pk'] == '1'){
                            if(!$is_update_pk){
                                $is_update_pk = true;
                            }
                            $add_pks[] = "[{$field_name}]";
                        }
                        if(!empty($fval['default'])){
                            $sqls[$table_name][$field_name][] = "ALTER TABLE [{$table_name}] ADD DEFAULT {$fval['default']} FOR [{$field_name}] WITH VALUES";
                        }
                        $remark = $fval['remark'];
                        if(!empty($remark)){
                            $sqls[$table_name][$field_name][] = "execute sp_addextendedproperty 'MS_Description','{$remark}','user','dbo','table','{$table_name}','column','{$field_name}'";
                        }
                    }
                }

                if($is_update_pk){
                    if(!empty($f_change)) {
                        foreach ($f_change as $field_name=>list($new, $old)){
                            if($new['pk'] != $old['pk']){
                                if($new['pk'] == '1'){
                                    $add_pks[] = "[{$field_name}] ASC";
                                }
                                if(isset($binds[$field_name])){
                                    $delete_pks[] = "[{$field_name}]";
                                }
                            }
                        }
                        if(!empty($delete_pks)){
                            $delete_pk_str = implode(",", $delete_pks);
                            $sqls[$table_name][$field_name][] = "ALTER TABLE [{$table_name}] DROP CONSTRAINT [{$delete_pk_str}]";
                        }
                        if(!empty($add_pks)){
                            $add_pk_str = implode(",", $add_pks);
                            $sqls[$table_name][$field_name][] = "ALTER TABLE [{$table_name}] ADD PRIMARY KEY ({$add_pk_str})";
                        }
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
        $cot_ok = 0;
        $cot_no = 0;
        $sqls = $this->sqls;
        $errors = [];
        $oks = [];
        $mod = DB::connection($this->conn);
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

        $this->saveLogs($db_info['driver']."/".$this->conn, $oks, $errors);

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
     * 获取创建表语句
     * @param $table_name
     * @param $table_detail
     * @return string
     */
    private function getCreateTableSql($table_name, $table_detail){
        $tn = str_replace('"', '""', $table_name);
        $lst = [];
        $pks = [];
        $has_flag = false;
        $remarks = [];
        foreach ($table_detail as $fk=>$fv){
            $fk = str_replace('"', '""', $fk);
            $len = $fv['length'];
            if(in_array($fv['type'], $this->is_length)){
                if($len >= 0){
                    if($fv['point'] > 0){
                        $len .= ", ".$fv['point'];
                    }
                }else{
                    $len = 'max';
                }
                $len = "({$len})";
            }else{
                $len = '';
            }
            $s = "[{$fk}] [{$fv['type']}]{$len} ";
            if($fv['flag'] == '0') {
                if ($fv['isnull'] == '1') {
                    $s .= "NULL ";
                } else {
                    $s .= "NOT NULL ";
                }
                if (!empty($fv['default'])) {
                    $s .= "DEFAULT {$fv['default']} ";
                }
            }else{
                $s .= "IDENTITY(1,1) ";
                if ($fv['pk'] == '1') {
                    $s .= "PRIMARY KEY ";
                }
                $has_flag = true;
            }
            if ($fv['pk'] == '1') {
                $pks[] = "[{$fk}] ASC";
            }
            $lst[] = trim($s);

            $remark = $fv['remark'];
            if(!empty($remark)){
                $remarks[] = "execute sp_addextendedproperty 'MS_Description','{$remark}','user','dbo','table','{$tn}','column','{$fk}'";
            }
        }
        if(count($pks) > 0 && !$has_flag){
            $pk_str = implode(",", $pks);
            $lst[] = "PRIMARY KEY CLUSTERED({$pk_str})";
        }
        $lst_str = implode(",", $lst);
        $ret_str = "CREATE TABLE [dbo].[{$tn}] ({$lst_str});";
        if(count($remarks) > 0){
            $ret_str .= implode(";", $remarks).";";
        }
        return $ret_str;
    }

    /**
     * 获取更改字段或新增字段语句
     * @param $info
     * @param bool $is_add
     * @return string
     */
    private function getChangeSql($info, $table_name, $field_name, $is_add=false){
        $len = $info['length'];
        if(in_array($info['type'], $this->is_length)){
            if($len >= 0){
                if($info['point'] > 0){
                    $len .= ", ".$info['point'];
                }
            }else{
                $len = 'max';
            }
            $len = "({$len})";
        }else{
            $len = '';
        }
        if($is_add){
            $f_change_str = "ALTER TABLE [{$table_name}] ADD ";
        }else{
            $f_change_str = "ALTER TABLE [{$table_name}] ALTER COLUMN ";
        }
        $f_change_str .= "[{$field_name}] [{$info['type']}]{$len} ";
        if ($info['isnull'] == '1') {
            $f_change_str .= "NULL ";
        } else {
            $f_change_str .= "NOT NULL ";
        }
        return trim($f_change_str);
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
                if(count($v) > 0){
                    $retsqls[$key][$k] = implode(";", $v);
                }
            }
        }
        return $retsqls;
    }
}
