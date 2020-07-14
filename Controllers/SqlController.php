<?php

namespace Apcu\Controllers;

use DB;
use Illuminate\Support\Facades\Cache;

class SqlController{

	function __construct() {
		$this->sqlconfig = $this->getSqlConfig();
//		dump($this->getDbConfig());
//		dump($this->getDbInfo());
//		dump($this->getDbInfo()['smchk']['tb']['t_cwdd1']);
		$this->last_sql = "";
	}

	/**
	 * 获取MySql配置
	 * @return array
	 */
	private function getSqlConfig(){
		$config = config('database.connections');
		$retcof = [];
		foreach ($config as $key=>$val){
			if(in_array($val['driver'], ['mysql', 'sqlsrv'])){
				$retcof[$key] = $val;
			}
		}
		return $retcof;
	}

	/**
	 * 获取数据库配置
	 * @return array
	 */
	private function getDbConfig(){
		$config = $this->sqlconfig;
		$sqldbs = SqlConfig::databases();
		$ret = [];
		foreach ($config as $key=>$val){
			$db = strtolower(trim($val['database']));
			$keylower = strtolower(trim($key));
			$ret[$keylower] = [
				'name' => $sqldbs[$db],
				'database' => $db,
				'key' => $key
			];
		}
		return $ret;
	}

	/**
	 * 获取MYSQL字段信息
	 * @param $db
	 * @param $database
	 * @return array
	 */
	private function getDbMysql($conn, $database){
        try {
            $db = DB::connection($conn);
            $tablesqls = $db->select("select table_name, table_comment from information_schema.tables where table_schema='{$database}'");
            $fieldsqls = $db->select("select table_name, column_name, column_comment, column_type, column_key from information_schema.columns where table_schema='{$database}'");

            $dbdata = [];
            foreach ($tablesqls as $key => $val) {
                $dbdata[$val->table_name]['name'] = $val->table_comment;
            }

            foreach ($fieldsqls as $key => $val) {
                $cn = strtolower(trim($val->column_name));
                $dbdata[$val->table_name]['field'][$cn] = [
                    'name' => $val->column_comment,
                    'key' => $val->column_key,
                    'type' => $val->column_type,
                ];
            }
            $newdbdata = [];
            foreach ($dbdata as $key => $val) {
                $key = strtolower(trim($key));
                $newdbdata[$key] = $val;
            }
            unset($tablesqls);
            unset($fieldsqls);
            unset($dbdata);
            return $newdbdata;
        }catch (\Exception $e){
            $sql_error_pass = 'SQL_ERROR_PASS';
            if(getenv($sql_error_pass)){
                return [];
            }
            $smsg = "Mysql 查询错误： 可修改.env文件 {$sql_error_pass}=true 跳过";
            $msg = $e->getMessage();
            if(count($_POST) > 0){
                EXITJSON(0, $smsg."\n".$msg);
            }else{
                exit("<div>$smsg</div><div>$msg</div>");
            }
        }
	}

	/**
	 * 获取MSSQL字段信息， 版本MSSQL2012
	 * @param $db
	 * @param $database
	 * @return array
	 */
	private function getDbMssql($conn){
        try{
            $db = DB::connection($conn);
            //遍历表
            $tablenames = [];
            foreach($db->select("select top 10000 name, object_id from sys.tables order by name asc") as $key=>$val){
                $tablenames[strtolower($val->name)] = $val->object_id;
            }

            //遍历字段类型
            $typenames = [];
            foreach($db->select("select user_type_id, name, max_length from sys.types") as $key=>$val){
                $typenames[$val->user_type_id] = $val->name;
            }

            //遍历注释
            $remarknames = [];
            foreach($db->select("select top 10000 major_id, minor_id, value from sys.extended_properties") as $key=>$val){
                $remarknames[$val->major_id][$val->minor_id] = $val->value;
            }

            //遍历字段
            $fieldnames = [];
            $sqlstr =	"select top 10000 user_type_id, name, object_id, column_id, COLUMNPROPERTY(object_id, name,'PRECISION') as max_length from sys.columns";
            foreach($db->select($sqlstr) as $key=>$val){
                $fieldnames[$val->object_id][$val->column_id] = [
                    'name' => $val->name,
                    'user_type_id' => $val->user_type_id,
                    'max_length' => $val->max_length,
                    'object_id' => $val->object_id
                ];
            }

            //查询主键
            $pknames = [];
            $sqlstr = "select a.table_name, b.column_name from information_schema.table_constraints a ";
            $sqlstr .= "inner join information_schema.constraint_column_usage b ";
            $sqlstr .= "on a.constraint_name = b.constraint_name ";
            $sqlstr .= "where a.constraint_type = 'PRIMARY KEY'";
            foreach($db->select($sqlstr) as $key=>$val){
                $t = strtolower(trim($val->table_name));
                $f = strtolower(trim($val->column_name));
                $pknames[$t][$f] = true;
            }

            $dbdata = [];
            foreach($tablenames as $key=>$val){
                $dbdata[$key]['name'] = $remarknames[$val][0];
                //dump($fieldnames[$val]);
                if(!empty($fieldnames[$val]) && is_array($fieldnames[$val])) {
                    foreach ($fieldnames[$val] as $k => $v) {
                        $cn = strtolower(trim($v['name']));
                        $pknames[$key][$cn] ? $keyname = "PRI" : $keyname = "";
                        $dbdata[$key]['field'][$cn] = [
                            'name' => $remarknames[$val][$k],
                            'key' => $keyname,
                            'type' => $typenames[$v['user_type_id']] . "(" . $v['max_length'] . ")",
                        ];
                    }
                }
            }

            $newdbdata = [];
            foreach ($dbdata as $key=>$val){
                $key = strtolower(trim($key));
                $newdbdata[$key] = $val;
            }
            unset($tablenames);
            unset($typenames);
            unset($remarknames);
            unset($fieldnames);
            unset($pknames);
            unset($dbdata);
            return $newdbdata;
        }catch (\Exception $e){
            $sql_error_pass = 'SQL_ERROR_PASS';
            if(getenv($sql_error_pass)){
                return [];
            }
            $smsg = "Sqlserver 查询错误： 可修改.env文件 {$sql_error_pass}=true 跳过";
            $msg = $e->getMessage();
            if(count($_POST) > 0){
                EXITJSON(0, $smsg."\n".$msg);
            }else{
                exit("<div>$smsg</div><div>$msg</div>");
            }
        }
	}

	/**
	 * 获取数据库字段命名信息
	 * @param $conn
	 * @param $database
	 * @return array
	 */
	private function getDb($conn, $database){
		$type = $this->sqlconfig[$conn]['driver'];

		try {
            if ($type == 'mysql') {
                return $this->getDbMysql($conn, $database);
            } elseif ($type == 'sqlsrv') {
                return $this->getDbMssql($conn, $database);
            }
        }catch (\Exception $e){
		    return [];
        }

	}

	/**
	 * 获取数据库配置
	 * @return array
	 */
	private function getDbInfoList(){
		$cacheid = "apcu_sql_getDbInfoList";
		$dbinfo = Cache::get($cacheid);
		if(!empty($dbinfo)) return $dbinfo;
		$dbinfo = $this->getDbConfig();
		foreach ($dbinfo as $key=>$val){
			$dbinfo[$key]['tb'] = $this->getDb($val['key'], $val['database']);
		}
		Cache::put($cacheid, $dbinfo, 60 * 60);
		return $dbinfo;
	}

	/**
	 * 获取数据库配置
	 * @return array
	 */
	private function getDbInfo($name = ""){
		$dbinfo = $this->getDbInfoList();
		if(empty($name)) return $dbinfo;
		return $dbinfo[$name];
	}

	/**
	 * 验证数据库是否可用
	 * @param $data
	 * @return array
	 */
	private function checkDb(&$data){
		$table = strtolower($data['table']);
		if(empty($table)) return [0, '表不能为空'];
		$data['table'] = $table;

		$conn = $data['conn'];
		empty($conn) && $conn = config('database.default');

		$db = $this->getDbInfo($conn);
		if(empty($db)) return [0, $conn.'数据库未找到'];
		$data['conn'] = $conn;

		$prefix = strtolower(trim($this->sqlconfig[$conn]['prefix']));

		$dbtb = $db['tb'];
		$tb = $dbtb[$prefix.$table];
		if(empty($tb)) return [0, $conn."->".$prefix.$table.'表不存在'];

		$field = $data['field'];
		$tbfield = $tb['field'];
		if(empty($field)) return [1, [
			'set' => $tbfield,
			'show' => $tbfield,
			'all' => $tbfield
		]];

		/**处理设置字段,字段信息如下：
		'field' => ['id', 'id', 'name',
			[
				['order', 'id', 'sourceid', ['type'=>'order_type']],
				['member', 'id', 'mid', 'account']
			]...
		]*/

		$fieldstr = []; //字符串字段
		$fieldarr = []; //数组字段（高级处理字段）
		foreach ($field as $val) {
			if(is_string($val)){
				$fieldstr[] = $val;
			}elseif(is_array($val) && count($val) > 0){
				$fieldarr[] = $val;
			}
		}
		$fieldstr = array_unique($fieldstr);

		$fieldshow = [];
		$fieldset = [];
		foreach ($fieldstr as $val){
			$val = strtolower(trim($val));
			if(!empty($tbfield[$val])){
				$fieldshow[$val] = $fieldset[$val] = $tbfield[$val];
			}
		}

		$fieldnext = [];
		$fieldadds = [];
		foreach ($fieldarr as $key=>$val){
			$ttb = $tb;
			$ttb['table'] = $table;

			$vallen = count($val) - 1;
			for($i = $vallen; $i >= 0; $i --){
				if(empty($val[$i][3])){
					unset($val[$i]);
				}else{
					break;
				}
			}
			if(empty($val)) continue;

			foreach ($val as $k=>$v) {
			    if(!is_array($v)) return [0, $conn."->{$table} 配置有误: {$k}=>{$v}"];
				$v[1] = strtolower(trim($v[1]));
				$v[2] = strtolower(trim($v[2]));
				$vtbs = $v[0];
				$vconn = $conn;
				if(is_array($vtbs)){
					if(empty($vtbs[1])){
						$vtb = strtolower(trim($vtbs[0]));
						$vprefix = $prefix;
						$vdbtb = $dbtb;
					}else{
						$vtb = strtolower(trim($vtbs[0]));
						$vconn = $vtbs[1];
						empty($vconn) && $vconn = config('database.default');
						$vdb = $this->getDbInfo($vconn);
						if(empty($vdb)) return [0, $vconn.'数据库未找到'];
						$vprefix = strtolower(trim($this->sqlconfig[$vconn]['prefix']));

						$vdbtb = $vdb['tb'];
						$vvtb = $vdbtb[$vprefix.$vtb];
						if(empty($vvtb)) return [0, $vconn."中".$vprefix.$vtb.'表不存在'];
					}
				}else{
					$vtb = strtolower(trim($vtbs));
					$vprefix = $prefix;
					$vdbtb = $dbtb;
				}
				$ttbnext = $vdbtb[$vprefix . $vtb];
				if(!isset($ttbnext['field'])) return [0, $vconn."->{$vtb} 不存在"];
				if(!isset($ttbnext['field'][$v[1]])) return [0, $vconn."->{$vtb} 中的字段 {$v[1]} 不存在"];
				if(!isset($ttb['field'][$v[2]])) return [0, $vconn."->{$ttb['table']} 中的字段 {$v[2]} 不存在"];
				if(!empty($v[3])) {
					$tas = [];
					if (is_string($v[3])) {
						$vas = strtolower(trim($v[3]));
						$tas[$vas] = $vas;
					} else {
						foreach ($v[3] as $kk => $vv) {
							if (is_string($vv) || is_numeric($vv)) {
								if (is_string($kk)) {
									$tas[strtolower(trim($kk))] = strtolower(trim($vv));
								} else {
									$vas = strtolower(trim($vv));
									$tas[$vas] = $vas;
								}
							}
						}
					}

					foreach ($tas as $kk => $vv){
						if(!isset($ttbnext['field'][$kk])) return [0, $vconn."->{$vtb} 中的字段 {$kk} 不存在"];
						if(isset($fieldshow[$vv])) return [0, $vconn."->{$vtb} 中字段 {$vv} 与主字段重复"];
						if(isset($fieldadds[$vv])) return [0, $vconn."->{$vtb} 中字段 {$vv} 与其他字段重复"];
						$fieldadds[$vv] = $ttbnext['field'][$kk];
					}
					$v[3] = $tas;
				}
				if($k == 0 && !empty($tbfield[$v[2]])){
					empty($fieldset[$v[2]]) && $fieldset[$v[2]] = $tbfield[$v[2]];
				}
				$ttb = $ttbnext;
				$ttb['table'] = $vtb;

				$val[$k] = $v;
			}
			$fieldnext[$key] = $val;
		}

		foreach ($fieldadds as $key=>$val){
			$fieldshow[$key] = $val;
		}

		return [
			1, [
				'set' => $fieldset,
				'show' => $fieldshow,
				'next' => $fieldnext,
				'all' => $tbfield
			]
		];

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
	 * 条件语句处理
	 * @param $where
	 */
	public function getWhere($where, $fieldall){
		if(empty($where)) return [1, $where];
		if(is_string($where[0])) $where = [$where];
        $flag = 0;
        $child_i = 0;
		$wherenew = [];
		ksort($where);
		foreach ($where as $key=>$val){
			if(is_string($val)){
				if(strtolower(trim($val)) == "or"){
					$flag ++;
                    $wherenew[$flag] = 'or';
                    $flag ++;
				}
			}elseif(is_array($val) && count($val) >= 2){
			    $is_child = true;
			    if(is_string($val[0]) && is_string($val[1]) && strtolower(trim($val[1])) != 'or'){
                    $is_child = false;
                }
                if($is_child){
                    if (!empty($val) && is_array($val)) {
                        list($status, $vwhere) = $this->getWhere($val, $fieldall);
                        if ($status) {
                            $wherenew[$flag]['child']["_#c#_{$child_i}"] = $vwhere;
                            $child_i ++;
                        }
                    }
                }else {
                    $f = strtolower(trim($val[1]));
                    $n = strtolower(trim($val[0]));
                    isset($val[2]) ? $v = $val[2] : $v = [];
                    if (is_string($v) || is_numeric($v)) {
                        $wherenew[$flag][$f][$n][] = "{$v}";
                    } else {
                        if ($f == 'between' || $f == 'notbetween') {
                            if (count($v) == 2 && $v[1] > $v[0]) {
                                $wherenew[$flag][$f][$n][] = $v;
                            } else {
                                return [0, "字段 {$n} 条件范围出错"];
                            }
                        } elseif ($f == 'null' || $f == 'notnull') {
                            $wherenew[$flag][$f][$n] = 'null';
                        } else {
                            foreach ($v as $vv) {
                                (is_string($vv) || is_numeric($vv)) && $wherenew[$flag][$f][$n][] = "{$vv}";
                            }
                        }
                    }
                }
			}
		}

		$fields = [];
		$whereret = [];
		foreach ($wherenew as $key=>$val){
		    if(is_string($val)){
                $whereret[$key] = $val;
		        continue;
            }
			foreach ($val as $k=>$v){
				foreach ($v as $kk=>$vv){
                    if($k == 'child'){
                        $whereret[$key][$k][$kk] = $vv;
                    }else {
                        $fields[] = $kk;
                        if ($k == 'between' || $k == 'notbetween') {
                            $mins = [];
                            $maxs = [];
                            foreach ($vv as $vvv) {
                                $mins[] = $vvv[0];
                                $maxs[] = $vvv[1];
                            }
                            if ($k == 'between') {
                                $min = max($mins);
                                $max = min($maxs);
                            } else {
                                $min = min($mins);
                                $max = max($maxs);
                            }
                            if ($min <= $max) {
                                $whereret[$key][$k][$kk] = [$min, $max];
                            } else {
                                return [1, -1];
                            }
                        } elseif ($k == 'column') {
                            $tp = strtolower(trim($vv[0]));
                            $tf = strtolower(trim($vv[1]));
                            if (!empty($tp) && !empty($tf)) {
                                if (!isset($fieldall[$tf])) return [0, "条件字段 {$tf} 不存在"];
                                if (!in_array($tp, ['=', '<>', '>', '>=', '<', '<='])) return [0, "条件字段 {$tf} 判断语句错误"];
                                $whereret[$key][$k][$kk] = [$tp, $tf];
                            }
                        } else {
                            if(is_array($vv)){
                                $whereret[$key][$k][$kk] = array_unique($vv);
                            }else{
                                $whereret[$key][$k][$kk] = $vv;
                            }
                        }
                    }
				}
			}
		}

		$fields = array_unique($fields);
		foreach ($fields as $val){
			if(!isset($fieldall[$val])) return [0, "条件字段 {$val} 错误"];
		}
		return [1, $whereret];
	}

	/**
	 * 排序处理
	 * @param $order
	 * @param $fieldall
	 * @return array
	 */
	public function getOrder($order, $fieldall){
		if(!is_array($order)) return [0, "数据库排序错误"];
		$orderret = [];
		foreach($order as $key=>$val){
			$key = strtolower(trim($key));
			if(!isset($fieldall[$key])) return [0, "排序字段 {$key} 不存在"];
			$val = strtolower(trim($val));
			if(!in_array($val, ["asc", "desc"])){
				$val = "asc";
			}
			$orderret[$key] = $val;
		}
		return [1, $orderret];
	}

	private function setWhereQuery(&$query, $where, $is_or=false){
        if($is_or){
            $cmd_w      = 'orWhere';
            $cmd_wi     = 'orWhereIn';
            $cmd_wni    = 'orWhereNotIn';
            $cmd_wbt    = 'orWhereBetween';
            $cmd_wnbt   = 'orWhereNotBetween';
            $cmd_wn     = 'orWhereNull';
            $cmd_wnn    = 'orWhereNotNull';
            $cmd_wc     = 'orWhereColumn';
        }else{
            $cmd_w      = 'where';
            $cmd_wi     = 'whereIn';
            $cmd_wni    = 'whereNotIn';
            $cmd_wbt    = 'whereBetween';
            $cmd_wnbt   = 'whereNotBetween';
            $cmd_wn     = 'whereNull';
            $cmd_wnn    = 'whereNotNull';
            $cmd_wc     = 'whereColumn';
        }

        $keyflags = [
            '=' => $cmd_wi,
            '<>' => $cmd_wni,
            'between' => $cmd_wbt,
            'notbetween' => $cmd_wnbt,
            'null' => $cmd_wn,
            'notnull' => $cmd_wnn,
            'column' => $cmd_wc,
        ];
		foreach ($where as $key=>$val){
            $cmd = $keyflags[$key];
            foreach ($val as $k => $v) {
                if (in_array($key, ['=', '<>', 'between', 'notbetween'])) {
                    //等于、不等于、区间之内、区间之外
                    $query->$cmd($k, $v);
                } elseif (in_array($key, ['null', 'notnull'])) {
                    //值为空、值不为空
                    $query->$cmd($k);
                } elseif ($key == 'column') { //判断两个字段是否符合条件$v[0]为=,>和<
                    $query->$cmd($k, $v[0], $v[1]);
                } elseif ($key == 'like') { //模糊查询
                    if (count($v) > 1) {
                        $query->$cmd_w(function ($q) use ($v, $k, $key) {
                            foreach ($v as $vv) $q->where($k, $key, $vv);
                        });
                    } else {
                        foreach ($v as $vv) $query->$cmd_w($k, $key, $vv);
                    }
                } elseif ($key == 'child') { //子查询
                    $ws = $v;
                    if (count($ws) > 1) {
                        $query->$cmd_w(function ($q) use ($ws) {
                            $this->setWhereMod($q, $ws);
                        });
                    } else {
                        $this->setWhereMod($query, $ws, $is_or);
                    }
                } else {
                    if ($key == '>' || $key == '>=') {
                        $query->$cmd_w($k, $key, max($v));
                    } elseif ($key == '<' || $key == '<=') {
                        $query->$cmd_w($k, $key, min($v));
                    }
                }
            }
		}
	}
	/**
	 * 条件构造查询
	 * @param $mod 数据库构造器
	 * @param $where 条件查询
	 */
	public function setWhereMod(&$mod, $where, $is_or = false){
	    if(empty($where) || !is_array($where)){
	        return;
        }

        foreach ($where as $w){
            if($w == 'or'){
                $is_or = true;
                continue;
            }
            if($is_or){
                $cmd = 'orWhere';
            }else{
                $cmd = 'where';
            }
            if(count($w) > 1) {
                $mod->$cmd(function ($q) use ($w) {
                    $this->setWhereQuery($q, $w);
                });
            }else{
                $this->setWhereQuery($mod, $w, $is_or);
            }
            $is_or = false;
        }
	}

    /**
     * 获取分页信息
     * @param $page
     * @param $mod
     */
	private function selectGetPageInfo($page, &$mod){
        $pagesize = $page['pagesize'];
        $p = $_GET['p'];
        $p <= 0 && $p =1;
        $cot = 0;
        if($p > 0){
            $cot = $mod->count();
            $pmaxf = $cot / $pagesize;
            $pmax = intval($pmaxf);
            $pmaxf > $pmax && $pmax++;
            $p > $pmax && $p = $pmax;
        }
        $pages = $mod->paginate($pagesize, ['*'], 'p', $p);
        $pages->page = $p;
        $pages->pagesize = $pagesize;
        $pages->pagesizedef = $page['pagesizedef'];
        $pages->count = $cot;
        return $pages;
    }

	/**
	 * 查找数据库
	 * @param $data
	 */
	private function _select($data, $field, $where, $whereAdd = [], $page = [], $order = []){
        $is_query = $this->is_query;
        if(!$is_query) {
            $fieldstr = "";
            foreach ($field as $key => $val) {
                if (empty($fieldstr)) {
                    $fieldstr = "'{$key}'";
                } else {
                    $fieldstr .= "," . "'{$key}'";
                }
            }
            if (empty($fieldstr)) return [0, "查询字段不能为空"];
        }
		$table = $data['table'];
		$conn = $data['conn'];
		if($is_query){
            $mod = DB::connection($conn)->table(DB::raw("(".$data['query'].") as query_table"));
            $pages = $this->selectGetPageInfo($page, $mod);
            $mod_list = $mod->get();
            $this->last_sql = $mod->toSql();
            return [1, json_decode(json_encode($mod_list), true), $pages];
        }else {
            $mod = DB::connection($conn)->table($table);
            !empty($where) && $this->setWhereMod($mod, $where);
            !empty($whereAdd) && $this->setWhereMod($mod, $whereAdd);

            $cdata = [];
            $isadd = false;
            if (!empty($data['edit'])) {
                //编辑操作
                $cdata = $data['edit'];
            } elseif (!empty($data['add'])) {
                //增加操作
                $cdata = $data['add'];
                $isadd = true;
            }
            if (!empty($cdata)) {
                $newdata = [];
                foreach ($cdata as $key => $val) {
                    if (is_string($key)) {
                        if (is_array($val)) {
                            $v = json_encode($val, true);
                        } elseif (is_numeric($val) || is_string($val)) {
                            $v = $val;
                        } else {
                            continue;
                        }
                        $newdata[$key] = $v;
                    }
                }
                if (!empty($newdata)) {
                    try {
                        if ($isadd) {
                            $id = $mod->insertGetId($newdata);
                            if ($id > 0) {
                                $this->setWhereMod($mod, [['=' => ['id' => [$id]]]]);
                                return [1, json_decode(json_encode($mod->get()), true)];
                            } else {
                                return [0, "增加失败！"];
                            }
                        } else {
                            !empty($where) && $mod->update($newdata);
                        }
                    } catch (\Exception $e) {
                        return [0, "ERROR: " . $e->getMessage() . "<BR>File: " . $e->getFile() . "<BR>Line: " . $e->getLine()];
                    }
                }
            }

            eval("\$mod->select({$fieldstr});");
        }

		$ispage = $page['ispage'];
		$export_type = $_GET['_@export@_'];
		$pages = [];
		if($export_type === 'all'){
            $sql_limit = env('SQL_LIMIT', 10000);
            !is_numeric($sql_limit) && $sql_limit = 10000;
            $mod->limit($sql_limit);
        }elseif($ispage){
            $pages = $this->selectGetPageInfo($page, $mod);
		}else {
			$limit = $data['limit'];
			$offset = $data['offset'];
			(empty($offset) || $offset <= 0) && $offset = 0;
			if ($limit != -1) {
				(empty($limit) || $limit <= 0) && $limit = 100;
				$mod->limit($limit)->offset($offset);
			}
		}
		if(!empty($order)){
			foreach($order as $key=>$val){
				$mod->orderBy($key, $val);
			}
		}
		$this->last_sql = $mod->toSql();
//        dump($this->last_sql );
		return [1, json_decode(json_encode($mod->get()), true), $pages];
	}

	/**
	 * 设置字段高级关联值
	 * @param $list
	 * @param $fieldnext
	 */
	private function setFieldList($config, &$list, $fieldnext){
		$fields = [];
		foreach ($fieldnext as $val){
			!empty($val[0][2]) && $fields[] = $val[0][2];
		}
		$fields = array_unique($fields);

		$data = [];
		foreach ($list as $key=>$val){
			foreach ($fields as $v){
				$data[$v][] = $val[$v];
			}
		}

		foreach ($data as $key=>$val){
			$data[$key] = array_unique($val);
		}
		$conn = $config['conn'];
		$field_tops = [];
        foreach ($fieldnext as $val){
            foreach ($val as $v){
                if(is_string($v[2]) && !isset($field_tops[$v[2]])){
                    $field_tops[$v[2]] = true;
                }
                break;
            }
        }
        $field_sets = [];
        foreach ($list as $val){
            foreach ($field_tops as $fkey=>$fval){
                $field_sets[$fkey][] = $val[$fkey];
            }
        }
        foreach ($field_sets as $key=>$val){
            $field_sets[$key] = array_unique($val);
        }
        $listadds = [];
        foreach ($fieldnext as $val){
            $i = 0;
            $top_field = '';
            foreach ($val as $v){
                if($i > 0){
                    $val[$i - 1][] =  $v[2];
                }else{
                    $top_field = $v[2];
                }
                $i ++;
            }
            $fsets = $field_sets;
            $next = [];
            foreach ($list as $lk=>$lv){
                $next[$lv[$top_field]] = $lv[$top_field];
            }
            foreach ($val as $v){
                $v1 = $v[1];
                $v2 = $v[2];
                $fkvs = $fsets[$v2];
                if(!isset($fkvs)){
                    continue;
                }
                $vtable = $v[0];
                $vconn = $conn;
                if(is_array($vtable)){
                    list($vtable, $vconn) = $vtable;
                    empty($vconn) && $vconn = $conn;
                }
                $v3 = $v[3];
                $tfkv = [];
                $tfvals = [];
                if(is_string($v3)){
                    $tfkv[$v3] = $v3;
                    $tfvals[] = $v3;
                }elseif(is_array($v3)){
                    foreach ($v3 as $v3k=>$v3v){
                        if(is_int($v3k)){
                            $tfkv[$v3v] = $v3v;
                            $tfvals[] = $v3v;
                        }elseif(is_string($v3v)){
                            $tfkv[$v3k] = $v3v;
                            $tfvals[] = $v3k;
                        }
                    }
                }
                $tfvals_select = $tfvals;
                $tfvals_select[] = $v1;
                $v4 = $v[4];
                if(!empty($v4)){
                    $tfvals_select[] = $v4;
                }
                $vlist = DB::connection($vconn)->table($vtable)->whereIn($v1, $fkvs)->select($tfvals_select)->get();
                $nnext = [];
                if(!empty($v4)){
                    $fsets = [];
                    foreach ($vlist as $vv){
                        $fsets[$v4][] = $vv->$v4;
                        $nnext[$vv->$v1] = $vv->$v4;
                    }
                }
                $dnext = [];
                foreach ($next as $nk=>$nv){
                    $dnext[$nv] = $nk;
                }
                foreach ($vlist as $vv){
                    foreach ($tfkv as $tkk=>$tvv) {
                        if(isset($dnext[$vv->$v1])) {
                            $listadds[$top_field][$tvv][$dnext[$vv->$v1]] = $vv->$tkk;
                        }
                    }
                }
                foreach ($next as $nk=>$nv){
                    $next[$nk] = $nnext[$nv];
                }
            }
        }
		foreach ($list as $key=>$val){
			foreach ($listadds as $k=>$v){
				foreach ($v as $kk=>$vv){
					$list[$key][$kk] = $vv[$val[$k]];
				}
			}
		}
	}

	/**
	 * 查找表数据
	 * @param $config
	 * @return array
	 */
	private function getSelectData($config, $tplwhere = [], $page = []){

        $query = $config['query'];
        $is_query = false;
        if(!empty($query) && is_string($query)){
            $query = trim($query);
            !empty($query) && $is_query = true;
        }
        $this->is_query = $is_query;

        if(!$is_query) {
            if(isset($config['where']) && !is_array($config['where'])){
                return [1, []];
            }
            //验证数据库是否正确
            list($status, $field) = $this->checkDb($config);
            if (!$status) return [$status, $field];
            list($status, $where) = $this->getWhere($config['where'], $field['all']);
            if (!$status) return [$status, $where];
            if ($where == -1) return [1, null];
            if (!empty($tplwhere)) {
                list($status, $whereAdd) = $this->getWhere($tplwhere, $field['all']);
                if (!$status) return [$status, $whereAdd];
            }

            if (!empty($config['order'])) {
                list($status, $order) = $this->getOrder($config['order'], $field['all']);
                if (!$status) return [$status, $order];
            }
        }
		list($status, $list, $pageinfo) = $this->_select($config, $field['set'], $where, $whereAdd, $page, $order);
		if(!$status) return [$status, $list];
		if(empty($list)) return [1, $list];

		$fieldnext = $field['next'];
		$fieldshow = $field['show'];
		if(empty($fieldnext)) return [1, $list, $fieldshow, $pageinfo, $this->last_sql];

		$this->setFieldList($config, $list, $fieldnext);
		return [1, $list, $fieldshow, $pageinfo, $this->last_sql];
	}

	/**
	 * 查找数据库列表
	 * @param $config
	 * @return array
	 */
	public function select($config, $where = [], $page = []){
		$config = $this->setKeyLower($config);
		return $this->getSelectData($config, $where, $page);
	}

	/**
	 * 查找数据库一个列表
	 * @param $config
	 * @return array
	 */
	public function find($config, $where = []){
		$config = $this->setKeyLower($config);
		if(empty($config['offset']) || $config['offset'] < 0) $config['offset'] = 0;
		$config['limit'] = 1;
		list($status, $list, $fieldshow) = $this->getSelectData($config, $where);
		if(is_string($list)){
		    $retlist = $list;
        }else{
            $retlist = $list[0];
        }
		return [$status, $retlist, $fieldshow, [], $this->last_sql];
	}

	/**
	 * 获取数据库表字段信息
	 * @param string $connname 数据库配置名称
	 * @param string $tablename 表名称
	 * @param string $fieldname 字段名称
	 * @return array
	 */
	public function dbInfo($connname="", $tablename="", $fieldname=""){
		$connname = strtolower(trim($connname));
		$info = $this->getDbInfo($connname);

		$tablename = strtolower(trim($tablename));
		if(empty($tablename)) return $info['tb'];

		$prefix = strtolower(trim($this->sqlconfig[$connname]['prefix']));

		$fieldname = strtolower(trim($fieldname));
		$tb = $info['tb'][$prefix.$tablename];
		if(empty($tb)) return [];
		if(empty($fieldname)) return $tb['field'];

		$f = $tb['field'][$fieldname];
		if(empty($f)){
			return [];
		}
		return $f;
	}
}
