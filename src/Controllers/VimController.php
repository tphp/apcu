<?php

namespace Tphp\Apcu\Controllers;

/**
 * 编辑模块
 * Class VimController
 * @package Tphp\Apcu\Controllers
 */
class VimController{
	public function __construct() {
	    $this->sql_init = SqlController::getSqlInit();
		if(!class_exists('Tpl')) {
			apcu(['class_tpl']); //加载Tpl类
		}
	}

    /**
     * 操作其他规则数据库，自定义数据查询
     * @param string $table
     * @return mixed
     */
    protected function db($table = "", $conn = ""){
        return $this->apifun->tplclass->db($table, $conn);
    }

    /**
     * 获取数据转换值
     * @param $data
     * @return array
     */
    private function getVimFileChangeData($data){
        $new_data = [];
        if(is_array($data)){
            foreach ($data as $key => $val){
                if(is_int($key)){
                    $new_data[$val] = [];
                }elseif(is_string($val)){
                    $new_data[$key] = [
                        'name' => $val
                    ];
                }else{
                    $new_data[$key] = $val;
                }
            }
        }
        return $new_data;
    }

    /**
     * 获取vim.php配置数据
     * @param $tplclass
     * @return array
     */
	private function getVimFile($tplclass){
		$vimpath = $tplclass->datapath.'vim.php';
		if(file_exists($vimpath)) {
		    foreach ($tplclass as $key=>$val){
		        if(empty($this->$key)){
                    $this->$key = $val;
                }
            }
			$datatmp = include $vimpath;
			$data = $tplclass->keyToLower($datatmp);
            if(isset($data['field'])){
                $data['field'] = $this->getVimFileChangeData($data['field']);
            }
            if(isset($data['handle'])){
                $data['handle'] = $this->getVimFileChangeData($data['handle']);
            }
		}else{
			$data = [];
		}
		return $data;
	}


    /**
     * 获取所有字段信息
     * @param $config
     * @return array
     */
    private function getAllField($config){
        if(in_array($config['type'], ['sql', 'sqlfind'])) {
            $c = $config['config'];
            empty($c['conn']) && $c['conn'] = config('database.default');
            return $this->sql_init->dbInfo($c['conn'], $c['table']); //获取某个表的所有字段信息
        }
        return [];
    }

    /**
     * 获取其他所有字段信息
     * @param $config
     * @return array
     */
    private function getAllFieldExt($table="", $conn=""){
        empty($conn) && $conn = config('database.default');
        return $this->sql_init->dbInfo($conn, $table); //获取某个表的所有字段信息
    }

	/**
	 * 获取新的配置文件
	 * @param $config
	 * @param $vimfield
	 */
	private function getNewConfig($config, $vimconfig, &$allfield){
        if(in_array($config['type'], ['sql', 'sqlfind'])) {
            $vimfield = $vimconfig['field'];
            $cfield = $config['config']['field']; //配置中的字段信息
        }else{
            $allfield = [];
            if(is_array($vimconfig['field'])){
                foreach ($vimconfig['field'] as $key=>$val){
                    if(is_array($val)){
                        $allfield[$key] = $val;
                    }elseif(is_string($val)){
                        $allfield[$val] = [
                            "name" => $val
                        ];
                    }
                }
            }
            $vimhandle = $vimconfig['handle'];
            if(is_array($vimhandle)){
                foreach ($vimhandle as $key=>$val){
                    if(is_array($val)){
                        foreach ($val as $k=>$v) {
                            $allfield[$key][$k] = $v;
                        }
                    }elseif(is_string($val)){
                        $allfield[$key][$val]['name'] = $val;
                    }
                }
            }
            $vimfield = $allfield;
            $cfield = $allfield;
        }
		$cfnames = [];

		if(empty($cfield)) $cfield = [];

		$field_key_val = [];
		foreach($cfield as $key=>$val){
			if(is_string($val)){
				if(!empty($allfield)) {
					$cfnames[] = $val;
				}
			}elseif(is_array($val)){
				foreach ($val as $k => $v) {
					if (!empty($v[3])) {
						if (is_string($v[3])) {
							$cfnames[] = $v[3];
						} elseif (is_array($v[3])) {
							foreach ($v[3] as $kk => $vv) {
								if (is_string($vv)) {
									$cfnames[] = $vv;
									if(is_string($kk)){
                                        $field_key_val[$vv] = $kk;
                                    }
								}
							}
						}
					}
				}
			}
		}
		$this->field_key_val = $field_key_val;

		$cfnames = array_unique($cfnames);

		$vimfieldnew = [];
        if(empty($vimfield)) $vimfield = [];
		foreach($vimfield as $key=>$val){
			$fname = "";
			if(is_string($key)){
				$fname = $key;
			}elseif(is_string($val)){
				$fname = $val;
			}
			if(empty($fname)) continue;
			if(empty($allfield[$fname]) && !in_array($fname, $cfnames)){
			    $is_continue = true;
			    if(is_array($val) && (is_array($val['from']) || $val['custom'])){
                    $is_continue = false;
                }
			    if($is_continue){
			        continue;
                }
            }
			$name = $allfield[$fname]['name'];

			if(is_int($key)){
				if(is_string($val)){
					if(empty($name) && is_string($val)) $name = $val;
					$vimfieldnew[$val] = [
						'name'=>$name
					];
				}
			}else{
				if(is_string($val)) {
					$vimfieldnew[$key] = [
						'name'=>$val
					];
				}elseif(is_array($val)){
                    if(empty($val['name'])){
                        if(empty($name)){
                            $val['name'] = $key;
                        }else {
                            $val['name'] = $name;
                        }
                    }
					foreach($val as $k=>$v){
						$vimfieldnew[$key][strtolower(trim($k))] = $v;
					}
				}
			}
		}

		//获取最终的数据库配置信息
		$cfieldnew = [];
		foreach($cfield as $key=>$val){
			if(is_string($val)){
				!empty($vimfieldnew[$val]) && $cfieldnew[] = $val;
			}elseif(is_array($val)){
				$tf = [];
				$tfdel = [];
				foreach ($val as $k => $v) {
					$tf_in = [$v[0], $v[1], $v[2]];
					$tfdel[] = $tf_in;
					$names = "";
					if(is_array($v[0])){
						$names = $this->sql_init->dbInfo($v[0][1], $v[0][0]);
					}
					if (!empty($v[3])) {
						if (is_string($v[3])) {
							if(!empty($vimfieldnew[$v[3]])){
								$tf_in[3][] = $v[3];
								$vimfieldnew[$v[3]]['find'] = $tfdel;

								if($vimfieldnew[$v[3]]['name'] == "#"){
									$vimfieldnew[$v[3]]['name'] = $names[$v[3]]['name'];
								}

								if(empty($vimfieldnew[$v[3]]['name'])){
									$vimfieldnew[$v[3]]['name'] = $v[3];
								}
							}
						} elseif (is_array($v[3])) {
							foreach ($v[3] as $kk => $vv) {
								if (is_string($vv) && !empty($vimfieldnew[$vv])) {
									$tf_in[3][$kk] = $vv;
									$vimfieldnew[$vv]['find'] = $tfdel;
									if($vimfieldnew[$vv]['name'] == "#"){
										$vimfieldnew[$vv]['name'] = $names[$vv]['name'];
									}
									if(empty($vimfieldnew[$vv]['name'])){
										$vimfieldnew[$vv]['name'] = $vv;
									}
								}
							}
						}
					}
					$tf[] = $tf_in;
				}
				$i = count($tf) - 1;
				for(; $i >= 0; $i --){
					if(empty($tf[$i][3])){
						unset($tf[$i]);
					}else{
						break;
					}
				}

				!empty($tf) && $cfieldnew[] = $tf;
			}
		}

        foreach($vimfieldnew as $key=>$val) {
            if (!$val['hidden']) {
                $tp = $val['type'];
                empty($tp) && $tp = $key;
                if (in_array($tp, ['create_time', 'update_time', 'time'])) {
                    !isset($val['fixed']) && $vimfieldnew[$key]['fixed'] = true;
                    !isset($val['width']) && $vimfieldnew[$key]['width'] = 115;
                }
            }
        }

		//增加data.php文件中未定义的但在vim.php中定义的字段
		$allwidth = 0;
		foreach($vimfieldnew as $key=>$val){
		    if(!$val['hidden']) {
                if (!in_array($key, $cfnames)) {
                    $cfieldnew[] = $key;
                }
                if (empty($val['width']) || $val['width'] <= 0) {
                    $dw = 20;
                } else {
                    $dw = $val['width'];
                }
                !isset($val['fixed']) && !$val['fixed'] && $allwidth += $dw;
                $vimfieldnew[$key]['width'] = $dw;
            }

		}

		foreach($vimfieldnew as $key=>$val){
            if(!$val['hidden']) {
                if($val['fixed']){
                    $vimfieldnew[$key]['width'] = $val['width'];
                }else {
                    $vimfieldnew[$key]['width'] = round($val['width'] * 100 / $allwidth, 2)."%";
                }
            }
		}

		$isfixed = 'true';
        $allwidth > 0 && $isfixed = 'false';
		return [$cfieldnew, $vimfieldnew, $isfixed];
	}

    /**
     * 设置字段相关值
     * @param $fields
     */
	private function setFields(&$fields, $config){
        if(empty($fields) || !is_array($fields)) return;
        if(isset($config['config']) && isset($config['config']['conn'])){
            $conndef = $config['config']['conn'];
        }
        empty($conndef) && $conndef = config('database.default');
        $from_isset = [];
        foreach ($fields as $key=>$val){
            if(!(is_string($key) && is_array($val))) continue;
            if(isset($val['from'])){
                $from = $val['from'];
                if(isset($from[0], $from[1], $from[2])){
                    $table = $from[0];
                    if(is_array($table)){
                        list($table, $conn) = $table;
                        if(empty($table)) continue;
                    }
                    if(empty($conn) && !empty($from[3]) && is_string($from[3])){
                        $conn = $from[3];
                    }
                    $fd = $from[1];
                    if(is_array($fd)){
                        list($fd) = $fd;
                    }
                    $fv = $from[2];
                    empty($conn) && $conn = $conndef;
                    if(!empty($table) && !empty($fd) && !empty($fv) && is_string($table) && is_string($fd) && is_string($fv)){
                        $fds = $this->sql_init->dbInfo($conn, $table);
                        $list = [];
                        if(!empty($fds) && !empty($fds[$fd]) && !empty($fds[$fv])){
                            $tflag = "{$conn}#{$table}#{$fd}#{$fv}";
                            if(isset($from_isset[$tflag])){
                                $lst = $from_isset[$tflag];
                            }else {
                                $lst_mod = \DB::connection($conn)->table($table)->select($fd, $fv);
                                // 排序设置
                                $fo = $val['from_order'];
                                if(!empty($fo) && is_array($fo)){
                                    foreach ($fo as $fok=>$fov){
                                        $fok = trim(strtolower($fok));
                                        $fov = trim(strtolower($fov));
                                        if(is_string($fok) && isset($fds[$fok]) && in_array($fov, ['asc', 'desc'])){
                                            $lst_mod->orderby($fok, $fov);
                                        }
                                    }
                                }
                                $fw = $val['from_where'];
                                if(!empty($fw) && is_array($fw)){
                                    $fw = $this->getWhereRealList($fw, $fds);
                                    $this->apifun->tplclass->setWhere($lst_mod, $fw, $fds);
                                }

                                $lst = $lst_mod->get();
                            }
                            foreach ($lst as $k=>$v){
                                $list[$v->$fd] = $v->$fv;
                            }
                            $from_isset[$tflag] = $lst;
                        }
                        $fields[$key]['list'] = $list;
                    }
                }
            }
            if(empty($val['type'])){
                if(is_array($fields[$key]['list'])){
                    $fields[$key]['type'] = 'select';
                }elseif($key == 'time'){
                    $fields[$key]['type'] = 'time';
                }
            }

            if(isset($val['trees']) && !empty($val['trees']) && is_array($val['trees'])){
                $fields[$key]['type'] = 'trees';
                $fields[$key]['tree'] = $val['trees'];
                unset($fields[$key]['trees']);
            }
        }
    }

    /**
     * 获取真实的条件查询语句
     * @param $where
     * @param array $field
     * @return array
     */
    public function getWhereRealList($where, $field=[]){
        $swhere = [];
        if(empty($where) || empty($field)){
            return $swhere;
        }

        if(!empty($where) && is_array($where)){
            foreach ($where as $key=>$val){
                if(is_int($key)){
                    if(is_string($val)){
                        if(!empty($where[1]) && is_string($where[1]) && isset($where[2])){
                            $swhere[] = [strtolower(trim($val)), $where[1], $where[2]];
                        }
                        break;
                    }elseif(is_array($val)){
                        if(!empty($val[0]) && !empty($val[1]) && isset($val[2])){
                            $swhere[] = [strtolower(trim($val[0])), $val[1], $val[2]];
                        }
                    }
                }
            }
        }
        $ret_where = [];
        foreach ($swhere as $key=>$val){
            $v0 = $val[0];
            $v1 = strtolower(trim($val[1]));
            $v2 = $val[2];
            if(isset($field[$v0]) && in_array($v1, ['=', '>', '>=', '<=', '<', '<>', 'like'])){
                if(!empty($v2) || $v2 === 0 || $v2 === '0') {
                    $ret_where[] = [$v0, $v1, $v2];
                }
            }
        }
        return $ret_where;
    }

	/**
	 * 获取data.php中的数据并进行修改配置
	 * @param $tpl
	 */
	public function getDataConfig($tplclass){
		$config = $tplclass->getDataFile("vim");
        $allfield = $this->getAllField($config);
		$vimconfig = $this->getVimFile($tplclass);
		if(is_array($vimconfig['field'])) {
            foreach ($vimconfig['field'] as $key => $val) {
                if (is_array($val) && is_array($vimconfig['handle'][$key])) {
                    foreach ($val as $k=>$v){
                        !isset($vimconfig['handle'][$key][$k]) && $vimconfig['handle'][$key][$k] = $v;
                    }
                }
            }
        }

        if(is_array($vimconfig['handle'])) {
            foreach ($vimconfig['handle'] as $key => $val) {
                if (is_array($val) && is_array($vimconfig['field'][$key])) {
                    foreach ($val as $k=>$v){
                        !isset($vimconfig['field'][$key][$k]) && $vimconfig['field'][$key][$k] = $v;
                    }
                }
            }
        }

        $this->setFields($vimconfig['field'], $config);
        $this->setFields($vimconfig['handle'], $config);

        if(isset($vimconfig['tree'])){
            $tree = $tplclass->keyToLower($vimconfig['tree']);
            if(isset($tree['edit'])){
                $tree_edit = $tree['edit'];
            }else{
                $tree_edit = true;
            }
            $parent = strtolower(trim($tree['parent']));
            $child = strtolower(trim($tree['child']));
            if(isset($allfield[$parent]) && isset($allfield[$child])){
                $tree['parent'] = $parent;
                $tree['child'] = $child;
                $vimconfig['tree'] = $tree;
                !empty($config['config']['table']) && $tree['table'] = $config['config']['table'];
                if(!isset($vimconfig['field'][$parent])){
                    $vimconfig['field'][$parent] = [
                        'hidden' => true,
                        'name' => '分类',
                        'type' => 'tree',
                        'tree' => $tree
                    ];
                }else{
                    $vimconfig['field'][$parent]['type'] = 'tree';
                    $vimconfig['field'][$parent]['tree'] = $tree;
                    !isset($vimconfig['field'][$parent]['name']) && $vimconfig['field'][$parent]['name'] = "分类";
                }

                if($tree_edit) {
                    $handle = [];
                    $handle[$parent] = [
                        'batch' => '批量分组'
                        //'verify' => 'required|phone'
                    ];
                    if (!empty($vimconfig['handle'])) {
                        foreach ($vimconfig['handle'] as $key => $val) {
                            $handle[$key] = $val;
                        }
                    }
                    $vimconfig['handle'] = $handle;
                }
            }else{
                unset($vimconfig['tree']);
            }
        }
        !isset($vimconfig['ispage']) && $vimconfig['ispage'] = true;
		list($fconfig, $vconfig, $isfixed) = $this->getNewConfig($config, $vimconfig, $allfield);
        $retconfig = [];
		if($vimconfig['ispage']) {
            $retconfig['ispage'] = true;
			if($vimconfig['pagesize'] > 0){
                $retconfig['pagesize'] = $vimconfig['pagesize'];
			}
		}
        foreach ($vconfig as $key=>$val){
            if(!isset($allfield[$key])) {
                //智能去除功能：编辑、排序
//                unset($vconfig[$key]['edit']);
                unset($vconfig[$key]['order']);
                if(!isset($val['from']) && !is_array($val['from']) && !isset($val['find']) && !is_array($val['find']) && $val['custom'] !== true) {
                    unset($vconfig[$key]['search']);
                }
            }
        }

		$tmpfo = [];
		foreach ($fconfig as $key=>$val){
            if(is_string($val)){
                $tmpfo[$val] = true;
                continue;
            }
		    if(is_string($key)){
                $tmpfo[$key] = true;
                continue;
            }
            if(!is_array($val)) continue;
		    foreach ($val as $k=>$v){
		        $v3 = $v[3];
                if(is_string($v3)){
                    $tmpfo[$v3] = true;
                    continue;
                }
                if(!is_array($v3)) continue;
                foreach ($v3 as $kk=>$vv){
                    if(is_string($vv)) $tmpfo[$vv] = true;
                }
            }
        }

        if(!empty($allfield)) {
            //增加主键字段（列表对应必须）
            foreach ($allfield as $key => $val) {
                if ($val['key'] == 'PRI') {
                    !isset($vconfig[$key]) && $fconfig[] = $key;
                } elseif (isset($vconfig[$key]) && !$tmpfo[$key]) {
                    $fconfig[] = $key;
                }
            }
        }

        $retconfig['config']['field'] = $fconfig;
        !empty($config['config']['order']) && $retconfig['config']['order'] = $config['config']['order'];
        !empty($config['config']['where']) && $retconfig['config']['where'] = $config['config']['where'];
		$handleinfo = $vimconfig['handleinfo'];
        empty($handleinfo) && $handleinfo = [];
        empty($handleinfo['width']) && $handleinfo['width'] = 500;
        empty($handleinfo['height']) && $handleinfo['height'] = 400;
        empty($handleinfo['fixed']) && $handleinfo['fixed'] = false;
        empty($handleinfo['ismax']) && $handleinfo['ismax'] = false;
        $vimconfig['handleinfo'] = $handleinfo;
        $vimconfig['isfixed'] = $isfixed;

        $handleinit = []; //默认处理配置
        $hinit = $vimconfig['handleinit'];
        if(!empty($hinit) && is_array($hinit)){
            foreach ($hinit as $key=>$val){
                if(!empty($key) && !empty($val) && is_string($key) && isset($allfield[$key])){
                    if(is_string($val) || is_numeric($val)){
                        $handleinit[$key] = $val;
                    }
                }
            }
            if(empty($handleinit)){
                unset($vimconfig['handleinit']);
            }else{
                $vimconfig['handleinit'] = $handleinit;
            }
        }

        if(!empty($handleinit)){
            $vhandle = $vimconfig['handle'];
            $keytmp = [];
            foreach ($vhandle as $key=>$val){
                $keyname = "";
                if(is_int($key)){
                    if(!empty($val) && is_string($val)) $keyname = $val;
                }elseif(is_string($key)){
                    $keyname = $key;
                    if($val['type'] == 'image' && is_array($val['thumbs'])){
                        foreach ($val['thumbs'] as $k=>$v){
                            if(isset($allfield[$k])){
                                $retconfig['config']['field'][] = $k;
                            }
                        }
                    }
                }
                if(!empty($keyname) && isset($handleinit[$keyname])){
                    $keytmp[] = $key;
                }
            }
            if(!empty($keytmp)){
                foreach ($keytmp as $val){
                    unset($vhandle[$val]);
                }
            }
            $vimconfig['handle'] = $vhandle;
        }

        //字段搜索加入
        $field_tmp = [];
        foreach ($retconfig['config']['field'] as $val){
            if(is_string($val)){
                $field_tmp[$val] = true;
            }
        }
        foreach ($vconfig as $key=>$val){
            $vfrom = $val['from'];
            if(is_array($vfrom) && is_array($vfrom[1]) && count($vfrom[1]) > 1){
                $vfkey = $vfrom[1][1];
                if(is_string($vfkey) && !isset($field_tmp[$vfkey]) && isset($allfield[$vfkey])){
                    $retconfig['config']['field'][] = $vfkey;
                    $field_tmp[$vfkey] = true;
                }
            }
        }
		return [$retconfig, $vconfig, $vimconfig, $allfield];
	}
}
