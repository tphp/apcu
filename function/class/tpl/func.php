<?php

/**
 * 解析tpl模板功能
 * 智能加载css、js
 * 使用Apcu配置处理数据
 */
return function($data){
	if(!class_exists('Tpl')) {
        if(class_exists("InitController")) {
            class ExtController extends \InitController{
                public function __construct()
                {
                    global $domains_path;
                    parent::__construct($domains_path->tpl_path, $domains_path->tpl_type, $domains_path->args);
                }
            }
        }else{
            class ExtController{}
        }

		class Tpl extends ExtController{
			public static $class;
			public static $js;
			public static $css;
			public static $thistpl;
            public static $roottpl = "";
            public static $is_root = true;
			public static $toptpl = "";

			private static $tmp_field = []; //临时存储字段信息
			private static $tmp_pages = []; //临时存储分页信息
            private $_data_type_list = ['sql', 'sqlfind', 'api']; //查询格式

			function __construct($tpl = '', $roottpl = '') {
			    if($tpl === false){
			        $tpl_is_false = true;
                }else{
                    $tpl_is_false = false;
                }
                if($tpl !== false && is_callable('InitController::__construct')) {
                    parent::__construct();
                }
				$this->config_tplbase = get_tphp_html_path();
				$this->tpl_type = "";
                $tpl === false && $tpl = '';
				if(!empty($tpl)){
					$tpl = str_replace("\\", "/", $tpl);
					$pos = strrpos($tpl, ".");
					if($pos > 0){
						$tpl_type = strtolower(trim(substr($tpl, $pos + 1)));
						if(strrpos($tpl_type, "/") === false) {
							$this->tpl_type = $tpl_type;
							$tpl = substr($tpl, 0, $pos);
						}
					}
				}
				empty($this->tpl_type) && $this->tpl_type = 'html';
				$this->tpl_init = $tpl;
				empty(Tpl::$toptpl) && !empty($tpl) && Tpl::$toptpl = $tpl;
				if($tpl_is_false){
                    $this->apifun = null;
                }else{
                    $this->apifun = new \Tphp\Apcu\Controllers\ApiController($tpl, $this);
                }
                $rtt = "";
				if(TPL::$is_root) {
                    $this->is_root = true;
                    TPL::$is_root = false;
                }else{
                    $this->is_root = false;
                    if(empty($roottpl)) {
                        $rtt = $this->getRealTplTop();
                        !empty($rtt) && $rtt .= "/";
                    }
                }
				$tpl = $this->getRealTpl($tpl, $roottpl);
                $this->is_root = true;
				$this->tplname = $tpl;
				Tpl::$thistpl = $this->tplname; //当前模板路径
				$this->tpl = "{$tpl}.tpl"; //模板指向
				$this->tplbase = base_path($this->config_tplbase); //TPL根目录
				$this->tplpath = $this->tplbase.$rtt.$tpl."/"; //TPL路径
                if(!is_dir($this->tplpath)){
                    $this->tplpath = TPHP_PATH."/html/".$rtt.$tpl."/"; //TPL路径
                    if(!is_dir($this->tplpath)){
                        $this->tplpath = TPHP_PATH."/html/sys/public/".$rtt.$tpl."/"; //TPL系统路径
                    }
                }
                if(!is_dir($this->tplpath)){
                    $t_rtt = $this->getRealTplTop();
                    !empty($t_rtt) && $t_rtt .= "/";
                    $t_path = $this->tplbase.$t_rtt.$tpl."/";
                    if(is_dir($t_path)){
                        $rtt = $t_rtt;
                        $this->tplpath = $t_path;
                    }
                }
                $this->tplpath = str_replace('//', '/', $this->tplpath);
				$this->datapath = $this->tplpath; //数据路径
				$this->class = $tpl; //样式路径
				$this->cachetime = 30 * 60; //过期时间30分钟
				$this->set = []; //设置TPL值，如果该值一旦设置则不进行数据库查询处理
				$this->html = '_#_#_'; //设置TPL的HTML代码，如果该值一旦设置则不进行任何处理
				$this->__json_type_name = "_IS_OPERATE_JSON_";
                $this->cacheid = $this->getCacheIdStr($rtt.$tpl);
                $this->cacheidmd5 = md5($rtt.$tpl);
                $this->cookies = []; //设置cookies
                $this->cookies_forget = []; //设置cookies
                $this->cookies_now = Cookie::get();
                $this->exitjson = "";
                $this->viewdata = [];
                $g_d_path = $this->datapath."data.php";
                if(isset($GLOBALS['DATA_FILE_INFO_INC'][$g_d_path])){
                    unset($GLOBALS['DATA_FILE_INFO'][$g_d_path]);
                }else{
                    $GLOBALS['DATA_FILE_INFO_INC'][$g_d_path] = true;
                }
			}

            /**
             * 获取SQL实例实现
             * @return |null
             */
			private function getSqlController(){
                return \Tphp\Apcu\Controllers\SqlController::getSqlInit();
            }

            /**
             * 获取当前cacheid替换字符串
             * @param $str
             * @return mixed
             */
			private function getCacheIdStr($str){
                $cacheid = str_replace("\\", "_", $str);
                $cacheid = str_replace(".", "_", $cacheid);
                $cacheid = str_replace("/", "_", $cacheid);
                return $cacheid;
            }

            /**
             * 获取制定的cacheid对于的路径
             * @param $tpl
             * @param string $ext
             * @return mixed|string
             */
            public function getCacheId($tpl="", $ext=""){
			    $path = $this->getRealTpl($tpl, Tpl::$toptpl, true);
                $path = trim($path, "/");
                $cacheid = $this->getCacheIdStr($path);
                !empty($ext) && $cacheid .= "_".$ext;
                return $cacheid;
            }

            /**
             * 修改POST提交设置
             * @param $keyname
             * @param $value
             */
            public function setPostValue($keyname, $value){
                $this->config['config'][$this->tpl_type][$keyname] = $value;
            }

            /**
             * 返回为json格式数据并退出
             * @param $obj
             */
            public function exitJson(){
                $argnums = func_num_args();
                $args = func_get_args();
                if($argnums <= 0){
                    $args = [1, "操作成功"];
                }elseif($argnums == 1){
                    $args0 = $args[0];
                    if(is_string($args0) || $this->isarray){
                        $this->exitjson = $args0;
                        return;
                    }elseif(is_array($args0) || is_object($args0)){
                        $this->exitjson = json_encode($args0, JSON_UNESCAPED_UNICODE);
                        return;
                    }
                    $args[1] = "操作成功";
                }
                $obj = [];
                foreach ($args as $key=>$val){
                    if($key == 0){
                        $obj['code'] = $val;
                    }elseif($key == 1){
                        if(is_array($val)){
                            $obj['msg'] = json_encode($val, JSON_UNESCAPED_UNICODE);
                        }else{
                            if(empty($val)) $val = "";
                            $obj['msg'] = $val;
                        }
                    }elseif($key == 2){
                        $obj['data'] = $val;
                    }elseif($key == 3){
                        $obj['url'] = $val;
                    }
                }

                if($this->isarray){
                    $this->exitjson = $obj;
                }else {
                    $json = json_encode($obj, JSON_UNESCAPED_UNICODE);
                    if ($json === false) {
                        $json = json_encode([
                            'code' => 0,
                            'msg' => '数据解析失败'
                        ], JSON_UNESCAPED_UNICODE);
                    }
                    $this->exitjson = $json;
                }
            }

			/**
			 * 判断是否是POST提交
			 * @return bool
			 */
			public function isPost(){
				return count($_POST) > 0;
			}

            /**
             * 获取数据保存信息
             * @param null $keyname
             * @return array|mixed|null
             */
            public function getHandle($keyname=null){
			    if(!$this->isPost()){
			        return null;
                }
                if(!empty($keyname) && !is_string($keyname)){
                    return null;
                }
                if(!in_array($this->tpl_type, ['add', 'edit', 'handle'])){
                    return null;
                }

                if($this->tpl_type == 'add'){
                    $ttype = 'add';
                }else{
                    $ttype = 'edit';
                }
                $ret = [];
                $tconf = $this->config;
                if(is_array($tconf['config'])){
                    if(!empty($tconf['config'][$ttype])){
                        $ret = $tconf['config'][$ttype];
                    }
                }
                if(empty($keyname)){
                    return $ret;
                }else{
                    return $ret[$keyname];
                }
            }

            /**
             * 设置数据保存操作
             * @param null $obj
             * @param null $value
             */
            public function setHandle($obj=null, $value=null){
			    $post = [];
			    if(is_string($obj)){
                    $post[$obj] = $value;
                }elseif(is_array($obj)){
			        foreach ($obj as $key=>$val){
			            if(is_string($key)){
                            $post[$key] = $val;
                        }
                    }
                }
                if(empty($post)){
			        return;
                }
                if(in_array($this->tpl_type, ['add', 'edit', 'handle'])){
			        if($this->tpl_type == 'add'){
			            $ttype = 'add';
                    }else{
                        $ttype = 'edit';
                    }
                    $tconf = &$this->config;
			        if(is_array($tconf['config'])){
			            if(empty($tconf['config'][$ttype])){
                            $tconf['config'][$ttype] = [];
                        }
                        foreach ($post as $key=>$val){
                            if(!isset($val)){
                                unset($tconf['config'][$ttype][$key]);
                            }else{
                                $tconf['config'][$ttype][$key] = $val;
                            }
                        }
                    }
                }
            }

            /**
             * 获取字段数据
             * @param null $keyname
             * @return array|mixed|null
             */
            public function getField($keyname=null){
                $field = $this->getView('field');
                if(empty($keyname) || !is_string($keyname)){
                    return $field;
                }
                return $field[$keyname];
            }

            /**
             * 获取字段数据
             * @param null $obj
             * @param null $value
             */
            public function setField($obj=null, $value=null) {
                $new_field = [];
                if (is_string($obj)) {
                    $new_field[$obj] = $value;
                } elseif (is_array($obj)) {
                    foreach ($obj as $key => $val) {
                        if (is_string($key)) {
                            $new_field[$key] = $val;
                        }
                    }
                }
                if (empty($new_field)) {
                    return;
                }
                if(empty($this->__handle_field__)){
                    $this->__handle_field__ = $new_field;
                }else{
                    foreach ($new_field as $key=>$val){
                        $this->__handle_field__[$key] = $val;
                    }
                }
                $field = $this->getField();
                if(empty($old_field) || !is_array($old_field)){
                    $field = $new_field;
                }else{
                    foreach ($field as $key=>$val){
                        $field[$key] = $val;
                    }
                }
                $this->setView('field', $field);
            }

            /**
             * 判断是否是增加选项
             * @return bool
             */
			public function isAdd($ispost = true){
			    if($ispost){
                    return $this->isPost() && $this->tpl_type == 'add';
                }
                return $this->tpl_type == 'add';
            }

            /**
             * 判断是否是编辑选项
             * @return bool
             */
            public function isEdit($ispost = true){
                if($ispost){
                    return $this->isPost() && $this->tpl_type == 'edit';
                }
                return $this->tpl_type == 'edit';
            }

            /**
             * 判断是否是编辑选项
             * @return bool
             */
            public function isHandle($ispost = true){
                $is_handle = $this->tpl_type == 'edit' || $this->tpl_type == 'add';
                if($ispost){
                    return $this->isPost() && $is_handle;
                }
                return $is_handle;
            }

            /**
             * 判断是否是删除选项
             * @return bool
             */
            public function isDelete(){
                return $this->isPost() && $this->tpl_type == 'delete';
            }

            /**
             * 判断是否是列表选项
             * @return bool
             */
            public function isList(){
                return $this->tpl_type == 'list';
            }

            /**
             * 获取顶部路径
             * @param bool $is_top
             * @return string
             */
            public function getRealTplTop($is_top = false){
                $btp = "";
                if ($is_top) {
                    if (defined("BASE_TPL_PATH_TOP")) {
                        $btp = trim(trim(BASE_TPL_PATH_TOP, "/"));
                    }
                } else {
                    if (defined("BASE_TPL_PATH")) {
                        $btp = trim(trim(BASE_TPL_PATH, "/"));
                    }
                }
                if(empty($btp)){
                    // 如果根目录未找到则使用domians配置tpl目录
                    $dc = $GLOBALS['DOMAIN_CONFIG'];
                    if(is_array($dc) && !empty($dc['tpl'])){
                        $dct = str_replace("\\", "/", trim($dc['tpl']));
                        $btp = trim($dct, "/");
                    }
                }
                return $btp;
            }

            /**
             *
             * @param $url
             * @return string
             */
            public function getRealUrl($url=""){
                $tmp_root = $this->is_root;
                $this->is_root = false;
                if(empty($url)) {
                    $returl = $this->tpl_init;
                }else{
                    $returl = $this->getRealTpl($url, $this->tpl_init);
                    $this->is_root = $tmp_root;
                }
                $argsurl = trim($this->config['args']['url'], "/\\");
                $returl = trim($returl, "/\\");
                !empty($argsurl) && $returl = $argsurl."/".$returl;
                return "/".$returl;
            }

            /**
             * 获取TPL相对路径对应的绝对路径
             * @param $tpl
             * @param string $next
             * @param bool $is_top
             * @return mixed|string
             */
			public function getRealTpl($tpl, $next = "", $is_top = false){
			    if($this->is_root) {
                    $btp = $this->getRealTplTop($is_top);
                    if (!empty($btp)) {
                        if (empty($next)) {
                            $next = $btp;
                        } else {
                            $next = $btp . "/" . $next;
                        }
                    }
                }

				if(empty($tpl)){
					if(empty($next)) {
						return "";
					}else{
						return $next;
					}
				}
				if(!is_string($tpl) || !is_string($next)) return "";

				$flag = "~";
				$flaglen = strlen($flag);

				$tpl = str_replace("\\", "/", $tpl);
				$tpl = str_replace("../", $flag, $tpl);
				$tpl = str_replace("./", "", $tpl);
				$tpl = trim($tpl);

				//当路径的首字母为'/'时直接返回对应模板路径
				if($tpl[0] == '/'){
					$tpl = trim(trim($tpl, "/"));
					if(strpos($tpl, $flag) === false){
						return $tpl;
					}
					return "";
				}
				$tpl = trim(trim($tpl, "/"));
				$next = trim(trim($next, "/"));
				if(empty($next)) return $tpl;

				if(strpos($tpl, $flag) === false){
					return $next."/".$tpl;
				}

				//获取相对路径对应的绝对路径
				$nextarr = explode("/", $next);
				while(substr($tpl, 0, $flaglen) == $flag){
					$cnext = count($nextarr);
					if($cnext <= 0) break;
					$tpl = substr($tpl, $flaglen);
					unset($nextarr[$cnext - 1]);
				}

				$pos = strrpos($tpl, $flag);
				if($pos !== false){
					return "";
				}
				$basedir = trim(trim(implode("/", $nextarr), "/"));
				return $basedir."/".$tpl;
			}

			/**
			 * 开始加载模板
			 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
			 */
			public function run($isarray = false) {
			    $this->isarray = $isarray;
				$tpl = $this->tpl;
				if(!is_dir($this->tplpath)){
					Tpl::$roottpl = "";
					$is_show_error = true;
                    if(method_exists('InitController','__run')) {
                        $ret = parent::__run();
                        $ret && $is_show_error = false;
                    }
                    if(method_exists('InitController','__last')) {
                        $ret = parent::__last('');
                        $ret && $is_show_error = false;
                    }
                    if($is_show_error){
                        return $this->tplname." Is Error";
                    }else{
                        return '';
                    }
				}
                Tpl::$roottpl = $this->tplname;

                $this->addConfig($this->apifun->getConfig());
                $this->setConfig();

				// 先运行根目录下的_init.php文件
                if(method_exists('InitController','__run')) {
                    parent::__run();
                }

                // 在运行当前目录下的_init.php文件
                $di = $this->getDataInit();
                if($di !== true){
                    return $di;
                }

                $tpl_type = $this->tpl_type;
				if(empty($tpl_type) || $tpl_type == 'json') {
					return $this->runJson();
				}elseif($tpl_type == 'data'){
                    return $this->runData();
				}

                $grd = $this->getRetData();
                if($grd === false){
                    $retdata = "";
                }elseif(is_array($grd) && $grd[0] === -100100){
                    $retdata = $grd;
                    $isarray = false;
                }elseif(empty($this->exitjson)){
                    list($status, $retdata) = $grd;
                    Tpl::$class[] = $this->class;
                    if (in_array($this->tpl_type, ['html', 'htm'])) {
                        if ($status) {
                            foreach ($this->viewdata as $key => $val) {
                                $retdata[$key] = $val;
                            }
                            $grd[1] = $retdata;
                            $view = $this->config['view'];
                            if (!empty($view) && is_array($view)) {
                                foreach ($view as $key => $val) {
                                    if (is_string($key)) {
                                        $retdata[$key] = $val;
                                    }
                                }
                            }
                        }
                        if (file_exists($this->tplpath."tpl.blade.php")) {
                            if ($status) {
                                $args_url = $this->config['args']['url'];
                                empty($args_url) && $args_url = "";
                                $retdata['args_url'] = $args_url;
                                $tplview = view($tpl, $retdata);
                            } else {
                                $tplview = $retdata['_'];
                            }
                            $c = $this->config;
                            if((isset($c['layout']) && $c['layout'] === false) || (isset($c['tpl_delete']) && $c['tpl_delete'])){
                                $retdata = $tplview;
                            }else {
                                $class = str_replace(".", "_", $this->class);
                                $class = str_replace("/", "_", $class);
                                $tplname = str_replace("/", "_", $this->tplname);
                                $retdata = "<div class=\"{$class}\" tpl=\"{$tplname}\">\r\n{$tplview}\r\n</div>";
                            }
                        } else {
                            if(!$status) {
                                if (empty($retdata['_'])) {
                                    $retdata = "404 ERROR";
                                } else {
                                    $retdata = $retdata['_'];
                                }
                            }
                        }
                    } else {
                        $retdata['src'] = $grd[2];
                        $this->apifun->setData($retdata);
                        $retdata = $this->apifun->html();
                    }
                }else{
                    $retdata = $this->exitjson;
                }

                if($isarray) {
                    $retdata = [$retdata, $grd[1], [$this->cookies, $this->cookies_forget], $this->exitjson];
                }

                if(method_exists('InitController','__last')) {
                    parent::__last($retdata);
                }
                return $retdata;
			}

			/**
			 * 返回JSON格式数据
			 * @param $status
			 * @param $msg
			 * @param null $data
			 * @return string
			 */
			private function retJson($status, $msg, $data=null){
				$ret = [];
				$ret['status'] = $status;
				$ret['msg'] = $msg;

				if(is_string($data)){
					$data != "" && $ret['data'] = $data;
				}elseif(!empty($data)){
					if(is_array($data)){
						if(array_key_exists('status', $data) && array_key_exists('msg', $data)){
							$ret = $data;
						}else{
							$ret['data'] = $data;
						}
					}else{
						$ret['data'] = $data;
					}
				}
				exit(json_encode($ret, JSON_UNESCAPED_UNICODE));
			}

			/**
			 * 获取JSON数据
			 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
			 */
			private function runJson() {
				if(!is_dir($this->tplpath)){
					$this->retJson(0, "403 Dir Not Found");
				}
				$tplapipath = $this->datapath.'api.php';
				if(!file_exists($tplapipath)) {
					$this->retJson(0, "404 Page Not Found");
				}
				list($status, $retdata) = $this->getRetData();
                if($status === -100100){
                    $this->retJson($status, 'error',  $retdata);
                }
				if($status){
					if($this->page['ispage']) {
						$this->retJson($status, 'success', [
							'page' => $retdata['pageinfo'],
							'list' => $retdata['_']
						]);
					}else{
						$this->retJson($status, 'success', $retdata['_']);
					}
				}

				$msgerr = $retdata['_'];
				if(is_string($msgerr)){
					$this->retJson($status, $msgerr);
				}
				$this->retJson($status, 'error', $msgerr);
			}

			/**
			 * 获取原数据
			 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|string
			 */
			private function runData() {
				if(!is_dir($this->tplpath)){
					return [false, $this->tplname." Not Found"];
				}
				list($status, $retdata) = $this->getRetData();
				if($status === -100100){
                    return [$status, $retdata];
                }
				if($status){
					if($this->page['ispage']) {
						return [true, [
							'page' => $retdata['pageinfo'],
                            'list' => $retdata['_']
						]];
					}else{
						return [true, $retdata['_']];
					}
				}

				$msgerr = $retdata['_'];
				if(is_string($msgerr)){
					return $msgerr;
				}
				return [false, $msgerr];
			}

			/**
			 * 获取模板数据
			 * @return array
			 */
			private function getRetData(){
                $gd = $this->getData();
                if($gd === false) return false;
                if(is_array($gd) && count($gd) === 2){
                    if($gd[0] === -100100){
                        return $gd;
                    }
                }
                list($status, $gdata, $field, $pages, $srcdata) = $gd;
				self::$tmp_field = $field;
				self::$tmp_pages = $pages;

				//设置返回数据到模板
				$retdata['_'] = $gdata;

				if(!empty($pages)) {
					$pagearr = $pages->toArray();
					//设置分页信息
					$retdata['pageinfo'] = [
						'size' => $pagearr['per_page'],
						'listcount' => count($pagearr['data']),
						'total' => $pagearr['total'],
						'max' => $pagearr['last_page'],
						'now' => $pagearr['current_page']
					];
				}
				\Tphp\Apcu\Controllers\PageController::$pages = $pages;

				//设置字段信息
				!empty($field) && $retdata['field'] = $field;
				return [$status, $retdata, $srcdata];
			}

			/**
			 * 增加配置
			 * @param $config
			 */
			public function addConfig($config){
				$tconfig = $this->config;
				if(empty($tconfig)){
					$tconfig = $config;
				}elseif(is_array($config)){
					foreach($config as $key => $val){
						$tconfig[$key] = $val;
					}
				}
				$view = $config['view'];
				if(!empty($view) && is_array($view)){
				    $t_view = $this->viewdata;
				    if(empty($t_view)){
                        $t_view = [];
                    }
				    foreach ($view as $key=>$val){
                        $t_view[$key] = $val;
                    }
                    $this->viewdata = $t_view;
                }
				$this->config = $tconfig;
			}

			public function setConfig(){
				$info = $this->keyToLower($this->config);
				//数据处理路径
				!empty($info['data']) && $this->datapath = $this->tplbase.$info['data']."/";
				//class合并路径
				!empty($info['class']) && $this->class = $info['class'];
				//数据库条件查询扩展（针对数据库查询有效）
				!empty($info['where']) && $this->where = $info['where'];
				//数据库分页处理（针对数据库查询有效）
				$ispage = $info['ispage']; //为True表示启用分页
				empty($ispage) && $ispage = false;
				$info['pagesize'] <= 0 ? $pagesize = 20 : $pagesize = $info['pagesize']; //默认分页大小为20条记录
                $pagesizedef = $pagesize;
                if($_GET['psize'] > 0) $pagesize = $_GET['psize'];
				$this->page = [
					'ispage' => $ispage,
                    'pagesizedef' => $pagesizedef,
                    'pagesize' => $pagesize
				];

				//设置TPL值，如果该值一旦设置则不进行数据库查询处理
				empty($info['set']) ? $this->set = [] : $this->set = $info['set'];
				empty($info['html']) ? $this->html = '_#_#_' : $this->html = $info['html'];
			}

			/**
			 * 设置传递数据
			 * @param $data
			 */
			public function setData($data){
				$this->tpldata = $data;
			}

            /**
             * 初始化设置数据库默认信息
             */
            private function setDataInfo(){
                $data_default = $this->getDataFile("db");
                $this->data_default = $data_default;
                return $data_default;
            }

            /**
             * 操作其他规则数据库，自定义数据查询
             * @param string $table
             * @param string $conn
             * @return mixed
             */
			public function db($table = "", $conn = ""){
                $data = $this->data_default;
                if(!isset($data)){
                    $data = $this->setDataInfo();
                }
                if(in_array($data['type'], $this->_data_type_list)){
                    if(empty($conn)) {
                        $conn = $data['config']['conn'];
                        if (empty($conn)) {
                            $conn = config('database.default');
                        }
                    }
                    if($table !== false){
                        empty($table) && $table = $data['config']['table'];
                    }
                }
                if(empty($table) || $table === false) {
                    $mod = \DB::connection($conn);
                }else{
                    $mod = \DB::connection($conn)->table($table);
                }
                return $mod;
			}

            /**
             * 获取数据库表字段信息
             * @param string $conn 数据库配置名称
             * @param int $table 表名称
             * @param string $field 字段名称
             * @return array
             */
			public function dbInfo($conn="", $table=-100, $field=""){
                $data = $this->setDataInfo();

                if(in_array($data['type'], $this->_data_type_list)){
                    $isconfig = true;
                }else{
                    $isconfig = false;
                }

                if(empty($conn)) {
                    if ($isconfig) {
                        $conn = $data['config']['conn'];
                    } else {
                        $conn = "";
                    }
                }
                if(empty($conn)){
                    $conn = config('database.default');
                }

                $sql = $this->getSqlController();
                if($table == -100) return $sql->dbInfo($conn);

                if(empty($table) || !is_string($table)) {
                    if ($isconfig) {
                        $table = $data['config']['table'];
                    } else {
                        $table = "";
                    }
                }

			    return $sql->dbInfo($conn, $table, $field);
            }

			/**
			 * 数据库连接
			 * @param string $conn
			 * @return mixed
			 */
			public function dbSelect($sqlstr, $conn = ""){
                $data = $this->setDataInfo();
				if(in_array($data['type'], $this->_data_type_list)){
					empty($conn) && $conn = $data['config']['conn'];
				}

				if(empty($conn)){
					$conn = config('database.default');
				}
				$max_row = 1000;
				$sqltype = strtolower(trim(config("database.connections.{$conn}.driver")));
				if(in_array($sqltype, ['mysql', 'sqlite', 'pgsql'])){
					if(stripos($sqlstr, " limit ") <= 0){
                        $sqlstr = $sqlstr." limit {$max_row} offset 0";
					}
				}elseif($sqltype == 'sqlsrv'){
                    if(stripos($sqlstr, " top ") <= 0){
                        $sqlstr = str_ireplace("select", "select top {$max_row}", $sqlstr);
                    }
                }

				try{
				    $list = \DB::connection($conn)->select($sqlstr);
                    $list_arr =  json_decode(json_encode($list), JSON_UNESCAPED_UNICODE);
                    foreach ($list_arr as $key=>$val){
                        $list_arr[$key] = $this->keyToLower($val);
                    }
                    return $list_arr;
                }catch (Exception $e){
				    return $e->getPrevious()->errorInfo[2];
                }
			}

            /**
             * 设置条件查询
             * @param $mod
             * @param $where
             */
            public function setWhere(&$mod, $where=[], $fieldall=[]){
                $sql = $this->getSqlController();
                list($status, $w) = $sql->getWhere($where, $fieldall);
                !$status && $w = [];
                $sql->setWhereMod($mod, $w);
            }

			/**
			 * 数组key转化为小写（过滤空值）
			 * @param $data
			 * @return array
			 */
			public function keyToLower($data){
                if(!is_array($data)){
                    if(is_object($data)){
                        $data = json_decode(json_encode($data, true), true);
                    }
                    if(!is_array($data)) {
                        return $data;
                    }
                }
				$newdata = [];
                if(empty($data)) return $newdata;
				foreach ($data as $key=>$val){
					(isset($val) || is_bool($val) || is_numeric($val)) && $newdata[strtolower(trim($key))] = $val;
				}
				return $newdata;
			}

            /**
             * 数组key转化为小写（不过滤空值）
             * @param $data
             * @return array
             */
            public function keyToLowerOrNull($data){
                if(!is_array($data)){
                    if(is_object($data)){
                        $data = json_decode(json_encode($data, true), true);
                    }
                    if(!is_array($data)) {
                        return $data;
                    }
                }
                $newdata = [];
                if(empty($data)) return $newdata;
                $fn = $this->fieldisnum;
                foreach ($data as $key=>$val){
                    if(is_null($val)){
                        if($fn[$key]){
                            $val = 0;
                        }else {
                            $val = "";
                        }
                    }
                    $newdata[strtolower(trim($key))] = $val;
                }
                return $newdata;
            }

            /**
             * 数组key转化为小写（转化所有）
             * @param $data
             */
            public function keyToLowers(&$data) {
                if(is_array($data)) {
                    $newdata = $data;
                    $data = [];
                    foreach ($newdata as $key=>$val){
                        if(is_int($key)){
                            $key = (int)$key;
                        }
                        is_string($key) && $key = strtolower(trim($key));
                        $data[$key] = $val;
                        if (is_array($val)) {
                            $this->keyToLowers($data[$key]);
                        }
                    }
                }
            }

            /**
             * 获取GET和POST属性替换
             * @param $str
             * @param $sleft
             * @param $sright
             * @param string $method
             * @return string
             */
			private function getDataForArgsPos($str, $sleft, $sright, $method="get"){
				$sleft_len = strlen($sleft);
				$sright_len = strlen($sright);
				$pos = strpos($str, $sleft);
				if(is_numeric($pos) && $pos >= 0){
					$str_r = substr($str, $pos + $sleft_len);
					$pos_r = strpos($str_r, $sright);
					if($pos_r > 0){
						$tstr = substr($str_r, $pos_r + $sright_len);
						!empty($tstr) && $tstr = $this->getDataForArgsPos($tstr, $sleft, $sright, $method);
						$v = trim(substr($str_r, 0, $pos_r));
						if($method == 'get'){
							$vval = $_GET[$v];
							$this->req_args['get'][] = $v;
						}elseif($method == 'post'){
                            $vval = $_POST[$v];
                            $this->req_args['post'][] = $v;
                        }else{
						    $varr = explode(".", $v);
						    $dv = Illuminate\Support\Facades\Session::get($varr[0]);
						    if(empty($dv)){
                                $dv = "";
                            }else {
                                unset($varr[0]);
                                foreach ($varr as $val) {
                                    if (is_string($val) || is_int($val)) {
                                        $dv = $dv[$val];
                                        if(empty($dv)){
                                            $dv = "";
                                            break;
                                        }
                                    } else {
                                        break;
                                    }
                                }
                            }
                            $vval = $dv;
                            $this->req_args['session'][] = $v;
                        }
						$strtmp = substr($str, 0, $pos).$vval.$tstr;
					}else{
						$strtmp = $str;
					}
				}else{
					$strtmp = $str;
				}
				return $strtmp;
			}

			/**
			 * 数据获取提交处理
			 * @param $data
			 * @return mixed
			 */
			public function getDataForArgs(&$data){
				foreach($data as $key=>$val){
					if(is_string($val)){
						$strtmp = $this->getDataForArgsPos($val, "_[", "]_");
                        $data[$key] = $this->getDataForArgsPos($strtmp, "_#", "#_", "post");
                        $data[$key] = $this->getDataForArgsPos($strtmp, "_%", "%_", "session");
					}elseif(is_array($val)){
						$data[$key] = $this->getDataForArgs($data[$key]);
					}
				}
				return $data;
			}

            /**
             * 设置浏览器默认参数配置
             * @param $data
             */
			private function setDataForInitUrl(&$config){
                if(isset($config['config'])) {
                    $sort = $_GET['_sort'];
                    if (!empty($sort)) {
                        $order = $_GET['_order'];
                        if (!empty($order) && in_array($order, ['asc', 'desc'])) {
                            $co = $config['config']['order'];
                            $conew = [$sort => $order];
                            if(!empty($co) && is_array($co)){
                                foreach ($co as $key=>$val){
                                    if($key != $sort){
                                        $conew[$key] = $val;
                                    }
                                }
                            }
                            $config['config']['order'] = $conew;
                        }
                    }
                }
            }

            /**
             * 合并配置数据循环下标
             * @param null $data
             * @param array $retkey
             * @param array $retval
             * @return array|void
             */
            private function arrayMergeLoop($data = null, $retkey = [], &$retval = []){
                if(empty($data)) return;
                foreach ($data as $key=>$val){
                    $newkey = $retkey;
                    $newkey[] = $key;
                    if(!is_array($val)){
                        if(empty($val) && $val !== 0 && $val !== '0' && $val !== '') {
                            $retval[] = [$newkey];
                        }else{
                            $retval[] = [$newkey, $val];
                        }
                    }else{
                        $this->arrayMergeLoop($val, $newkey, $retval);
                    }
                }
                return $retval;
            }

            /**
             * 合并配置数据设置
             * @param $arrdata
             * @param null $key
             * @param null $value
             */
            private function arrayMergeSet(&$arrdata, $key = null, $value = null){
                if (is_array($key)) { //如果$key是数组
                    $keystr = "";
                    $keyarr = [];
                    foreach ($key as $v) {
                        $v = str_replace('\\', '\\\\', $v);
                        $v = str_replace('\'', '\\\'', $v);
                        $keystr .= "['{$v}']";
                        $keyarr[] = $keystr;
                        eval("if(!is_array(\$arrdata{$keystr})) { unset(\$arrdata{$keystr});}");
                    }

                    if (empty($value) && $value !== 0 && $value !== '0' && $value !== '') {
                        eval("unset(\$arrdata{$keystr});");
                        foreach ($keyarr as $v) {
                            $vbool = false;
                            eval("if(empty(\$arrdata{$v})) { unset(\$arrdata{$v}); \$vbool = true;}");
                            if ($vbool) break;
                        }
                    } else {
                        eval("\$arrdata{$keystr} = \$value;");
                    }
                } else { //如果$key是字符串
                    $key = str_replace('\\', '\\\\', $key);
                    $key = str_replace('\'', '\\\'', $key);
                    if (empty($value) && $value !== 0 && $value !== '0' && $value !== '') {
                        unset($arrdata[$key]);
                    } else {
                        eval("if(!is_array(\$arrdata['{$key}'])) { unset(\$arrdata['{$key}']);}");
                        $arrdata[$key] = $value;
                    }
                }
            }

            /**
             * 字段合并处理
             * @param $config
             * @param $new_config
             * @param $arr_config
             * @param $kvs_config
             */
            private function arrayMergeField($config, &$new_config, &$arr_config, &$kvs_config){
                foreach ($config as $c){
                    if(is_array($c)){
                        foreach ($c as $ck=>$cv){
                            if(is_array($cv) && is_string($cv[3])){
                                $c[$ck][3] = [$cv[3]];
                            }
                        }
                    }
                    $k = md5(json_encode($c, true));
                    if(!isset($kvs_config[$k])){
                        $kvs_config[$k] = true;
                        if(is_array($c)){
                            $arr_config[] = $c;
                        }else{
                            $new_config[] = $c;
                        }
                    }
                }
            }

            /**
             * 合并数组数据
             * @param $data
             * @param $adddata
             */
            private function arrayMerge(&$data, $adddata){
                if(isset($adddata['view'])){
                    // 删除view防止大数据传输
                    unset($adddata['view']);
                }
                if(isset($adddata['config']) && !empty($adddata['config']['field'])){
                    // 删除config.field配置避免重复合并
                    if(isset($data['config']) && !empty($data['config']['field'])) {
                        $kvs_config = [];
                        $new_config = [];
                        $arr_config = [];
                        $this->arrayMergeField($data['config']['field'], $new_config, $arr_config, $kvs_config);
                        $this->arrayMergeField($adddata['config']['field'], $new_config, $arr_config, $kvs_config);
                        foreach ($arr_config as $c){
                            $new_config[] = $c;
                        }
                        $data['config']['field'] = $new_config;
                    }
                    unset($adddata['config']['field']);
                }
                $newdata = $this->arrayMergeLoop($adddata);
                if(empty($newdata)) return;
                foreach ($newdata as $val){
                    if(count($val[0]) == 1){
                        $this->arrayMergeSet($data, $val[0][0], $val[1]);
                    }else{
                        $this->arrayMergeSet($data, $val[0], $val[1]);
                    }
                }
            }

			/**
			 * 获取Data文件中的GET和POST参数设置
			 * @return array
			 */
			public function getDataForArgsArray(){
				$this->req_args = [];
				$tplapipath = $this->datapath.'api.php';
				if(file_exists($tplapipath)) {
					$datatmp = include $tplapipath;
					if(is_array($datatmp)){
						$this->req_args['api'] = $datatmp;
					}else{
						$this->req_args['api'] = True;
					}
				}else{
					exit("404 ERROR");
				}

                $tpldatapath = $this->datapath.'data.php';
				if(file_exists($tpldatapath)) {
                    $datatmp = include $tpldatapath;
                    !is_array($datatmp) && $datatmp = [];
                    $this->arrayMerge($datatmp, $this->config);
					$this->getDataForArgs($datatmp);
					$this->setDataForInitUrl($datatmp);
					$this->req_args['type'] = $datatmp['type'];
					is_array($this->req_args['get']) && $this->req_args['get'] = array_unique($this->req_args['get']);
                    is_array($this->req_args['post']) && $this->req_args['post'] = array_unique($this->req_args['post']);
                    is_array($this->req_args['session']) && $this->req_args['session'] = array_unique($this->req_args['session']);
				}
				return $this->req_args;
			}

			/**
			 * 获取data.php文件信息
			 * @return array|mixed
			 */
			public function getDataFile(){
				//先是数据配置读取
				$tpldatapath = $this->datapath.'data.php';
				if(file_exists($tpldatapath)) {
                    $tconf = $this->config;

                    if(isset($GLOBALS['DATA_FILE_INFO'][$tpldatapath])){
                        $datatmp = $GLOBALS['DATA_FILE_INFO'][$tpldatapath];
                    }else{
                        $GLOBALS['DATA_FILE_INFO'][$tpldatapath] = [];
                        $datatmp = include $tpldatapath;
                        $GLOBALS['DATA_FILE_INFO'][$tpldatapath] = $datatmp;
                    }

                    !is_array($datatmp) && $datatmp = [];
					$data = $this->keyToLower($datatmp);
                    $add = $tconf['config']['add'];
                    $edit = $tconf['config']['edit'];
                    $this->arrayMerge($data, $tconf);
					$data = $this->getDataForArgs($data);
                    !empty($add) && $data['config']['add'] = $add;
                    !empty($edit) && $data['config']['edit'] = $edit;
                    $this->setDataForInitUrl($data);
					if(!empty($data['css'])) Tpl::$css = $data['css'];
					if(!empty($data['js'])) Tpl::$js = $data['js'];
					if(!empty($data) && is_array($data)) {
					    $c_i = 0;
                        foreach ($data as $key=>$val) {
                            if(!isset($tconf[$key]) && isset($val)){
                                $tconf[$key] = $val;
                                $c_i ++;
                            }
                        }
                        $c_i > 0 && $this->config = $tconf;
                    }
				}else{
					$data = [];
				}
				return $data;
			}

            /**
             * 获取配置文件信息
             * @param $datatype
             * @return array|mixed
             */
			private function getIniInfo($datatype, $ini_file=""){
                //数据处理配置项
                if(empty($ini_file)) {
                    $inipath = $this->datapath . 'ini.php';
                } else {
                    $inipath = $ini_file;
                }
                $ini = [];
                if(file_exists($inipath)) {
                    $ini = include $inipath;
                    if(is_array($ini)) {
                        if (in_array($datatype, $this->_data_type_list)) {
                            if (!empty($ini)) {
                                $ini = $this->keyToLower($ini);
                                $sqlconfig = $ini['#sql'];
                                if (empty($sqlconfig)) {
                                    unset($ini['#sql']);
                                } else {
                                    $ini['#sql'] = $this->keyToLower($sqlconfig);
                                }
                            }
                        }
                    }else{
                        $ini = [];
                    }
                }
                return $ini;
            }

            /**
             * 当碰到配置项无法处理的情况吓，数据使用原始代码处理（可选项）
             * @param $retdata
             * @param array $params
             * @return array|mixed
             */
            private function getSetData(&$retdata, $params=[]){
                $setpath = $this->datapath.'set.php';
                if(file_exists($setpath)) {
                    $set = include $this->fileCachePath('set.php');
                    if(!empty($set) && gettype($set) == 'object') {
                        if (is_callable($set)) {
                            $retdata = $set($retdata, $params);
                            if(!empty($this->exitjson)) return [1, $this->exitjson];
                            !is_null($retdata) && is_array($retdata) && $retdata[$this->__json_type_name] && $this->exitStr($retdata['data']);
                        }else{
                            $retdata = $set;
                        }
                    }
                }
            }

			/**
			 * 处理data.php文件中的数据
			 * @param $data
			 * @return array
			 */
			public function getDataHandle($data){
				$params = [];
				isset($data['type']) && $datatype = $data['type']; //数据类型
                $srcdata = [];
				if(empty($this->set)) {
					if (empty($data['config'])) { //数据配置项
						$retdata = $data['default'];
					} else {
						list($status, $retdata, $fieldshow, $pageinfo, $sql) = $this->getDataInfo($datatype, $data['config'], $data['default']);
						$params['sql'] = $sql;
						$params['fields'] = $fieldshow;
						$this->fieldshow = $fieldshow;
                        $fieldisnum = [];
                        if(!empty($fieldshow)) {
                            foreach ($fieldshow as $key => $val) {
                                if (strpos($val['type'], "int") !== false || strpos($val['type'], "float") !== false) {
                                    $fieldisnum[$key] = true;
                                } else {
                                    $fieldisnum[$key] = false;
                                }
                            }
                        }
                        $this->fieldisnum = $fieldisnum;
                        $srcdata = $retdata;
						if (!$status) return [0, $retdata];
					}
				}else{ //当TPL已设置值的时候不执行数据处理操作
					$retdata = $this->set;
				}

				$ini = $this->getIniInfo($datatype);
                $this->apifun->setIni($ini);
                if(!empty($ini) && is_array($ini['#sql']) && !empty($ini['#sql'])) {
                    list($srcdata, $retdata) = $this->getDataToIni($retdata, $datatype, $ini);
                }

				$this->getSetData($retdata, $params);
				return [1, $retdata, $fieldshow, $pageinfo, $srcdata];
			}

			/**
			 * 打印数组并退出程序
			 * @param $data
			 */
			private function exitStr($data){
				if(is_array($data)){
					$show = json_encode($data, JSON_UNESCAPED_UNICODE);
				}elseif(is_object($data)){
				    try {
                        $show = json_encode($data, JSON_UNESCAPED_UNICODE);
                    } catch (Exception $e){
                        print_r($data);
                        exit();
                    }
				}elseif(is_numeric($data)){
					$show = $data."";
				}else{
					$show = $data;
				}
				exit($show);
			}

            /**
             * 缓存数据
             * @param array $fun 可以设置为function
             * @param string $cache_id 扩展id
             * @param int $expire 过期时间
             * @return array
             */
			public function cache($fun = [], $cache_id = "", $expire = 60 * 60){
                if(is_object($fun)){
                    $end = "";
                    if(!empty($cache_id)){
                        $end = "_".substr(md5($cache_id), 8, 8);
                    }
                    $cid = substr($this->cacheidmd5, 4, 8).$end;
                    $data = Cache::get($cid);
                    if(empty($data)){
                        $data = $fun();
                        if(!is_numeric($expire) && $expire < 0){
                            $expire = 0;
                        }
                        Cache::put($cid, $data, $expire);
                    }
                    return $data;
                }else{
                    return $fun;
                }
            }

            /**
             * 清除Cache
             * @param string $cache_id
             */
            public function unCache($cache_id = "", $top_balse = ""){
                if(empty($top_balse)){
                    $md5 = $this->cacheidmd5;
                }else{
                    $real_path = $this->getRealTpl($top_balse, $this->tpl_path);
                    $real_path = trim(trim(str_replace("\\", "/", $real_path), "/"));
                    $md5 = md5($real_path);
                }
                $end = "";
                if(!empty($cache_id)){
                    $end = "_".substr(md5($cache_id), 8, 8);
                }
                $cid = substr($md5, 4, 8).$end;
                Cache::forget($cid);
            }

            /**
             * 获取初始化数据
             * @return array|bool
             */
            private function getDataInit(){
                if($this->__data_int_flag === true || rtrim($this->base_path, '\\/') == rtrim($this->tplpath, '\\/')) return true;
                $this->__data_int_flag = true;
                $datapath = $this->datapath;
                //初始化
                $_initpath = $datapath.'_init.php';
                if(file_exists($_initpath)) {
                    $_init = include $this->fileCachePath('_init.php');
                    if(!empty($_init)) {
                        if (is_callable($_init)) {
                            $init = $_init();
                            if(!empty($this->exitjson)) return [-100100, $this->exitjson];
                            if($init === false) return false;
                            if(!is_null($init)) {
                                (is_string($init) || is_numeric($init) || is_object($init)) && $this->exitStr($init);
                                $init[$this->__json_type_name] && $this->exitStr($init['data']);
                                if (is_array($init) && $init['status'] != 1) { //当状态不为1成功的时候返回
                                    return [-100100, $init];
                                }
                            }
                        }
                    }
                }
                return true;
            }

            /*
             * 设置初始化配置
             */
            public function setDataFlag($bool=true){
                $this->__data_flag = $bool;
            }

			/**
			 * 获取数据
			 * @return array
			 */
			private function getData(){
			    $ret_info = [0, $this->html, self::$tmp_field, self::$tmp_pages];
				//设置TPL的HTML代码，如果该值一旦设置则直接返回html代码
				if($this->html != '_#_#_') return $ret_info;

                $di = $this->getDataInit();
                if($di !== true){
                    return $di;
                }

                if($this->__data_flag){
                    return $ret_info;
                }

				//先是数据配置读取
                $data = $this->setDataInfo();
				//处理data.php文件中的数据
				return $this->getDataHandle($data);
			}

			/**
			 * 获取数据信息
			 * @param $type 类型
			 * @param $config 配置参数
			 * @return mixed
			 */
			private function getDataInfo($type, $config, $default){
				if(in_array($type, $this->_data_type_list)){
                    $sql = $this->getSqlController();
					if($type == 'sql'){
						return $sql->select($config, $this->where, $this->page);
					}elseif($type == 'sqlfind'){
                        return $sql->find($config, $this->where);
                    }elseif($type == 'api'){
                        empty($config['get']) && $config['get'] = [];
                        $t_type = $this->tpl_type;
                        if($t_type == 'deletes'){
                            $t_type = 'delete';
                        }
                        $config['get']['__'] = $t_type;
                        if($this->isPost()){
                            empty($config['post']) && $config['post'] = [];
                            $config['post']['__'] = $t_type;
                        }
                        return $sql->api($config, $this->page, $this->apifun->allfield, $this);
                    }
				}
				return [1, $default];
			}

			/**
			 * 数据处理
			 * @param $data
			 * @param $type
			 * @param $config
			 * @return mixed
			 */
			private function getDataToIni($data, $type, $config){
				$type = trim($type);
				if(empty($type)) return apcu($config, $data);

				$type = strtolower($type);
                $srcdata = [];
				if(is_array($data)) {
					if(in_array($type, $this->_data_type_list)) {
						$sqlconfig = $config['#sql'];
						if(empty($sqlconfig)) return [$data, $data];
                        if ($type == 'sql' || $type == 'api') {
                            $c_config = [];
                            if(is_array($sqlconfig)){
                                foreach ($sqlconfig as $key=>$val){
                                    $c_config[] = [$key, $val];
                                }
                            }
                            list($srcdata, $data) = $this->getDataTypeSql($data, $c_config);
                        } elseif ($type == 'sqlfind') {
                            list($srcdata, $data) = $this->getDataTypeSqlFind($data, $sqlconfig);
                        }
                        return [$srcdata, $data];
					}
				}
				return [$srcdata, apcu($config, $data)];
			}

			// 处理文件类型的字段值
			public function getDataToDir($data=[], $datatype="sql", $ini_file=""){
                $ret_data = $this->getDataToIni($data, $datatype, $this->getIniInfo($datatype, $ini_file));
                if(is_array($ret_data) && is_array($ret_data[1])){
                    $this->getSetData($ret_data[1]);
                }
                return $ret_data;
            }

            /**
             * 字符规则替换
             * @param $tmpconf
             */
			private function getDataTypeSqlStrReplace(&$tmpconf){
                $fchr = '_=$#$=_';
                $dub = "#dub*";
                $flags = [
                    "\n" => "{$fchr}n",
                    "\t" => "{$fchr}t",
                    "\r" => "{$fchr}r",
                    "\e" => "{$fchr}e",
                    "\f" => "{$fchr}f",
                    "\v" => "{$fchr}v",

                    "\\n" => "{$fchr}n",
                    "\\t" => "{$fchr}t",
                    "\\r" => "{$fchr}r",
                    "\\e" => "{$fchr}e",
                    "\\f" => "{$fchr}f",
                    "\\v" => "{$fchr}v",

                    "\\{$dub}" => "{$fchr}{$dub}",
                    "\/" => "{$fchr}/",
                ];
                foreach ($flags as $fk=>$fv){
                    $tmpconf = str_replace($fk, $fv, $tmpconf);
                }
                $tmpconf = str_replace($dub, "\"", $tmpconf);
                $tmpconf = str_replace("#in*", "{$fchr}\"", $tmpconf);
                $tmpconf = str_replace("\\", "\\\\", $tmpconf);
                $tmpconf = str_replace($fchr, "\\", $tmpconf);
            }

            /**
             * APCU运算转换
             * @param $fun_addr
             * @param $jconfs
             */
            private function getDataTypeSqlConfigChg(&$fun_addr, $jconfs, $indata){
                !is_array($indata) && $indata = [];
                if(is_array($fun_addr)){
                    return;
                }
                $ftrim = trim($fun_addr);
                if(is_null($fun_addr) || strlen(trim($ftrim)) < 18){
                    return;
                }
                if(isset($jconfs[$ftrim])){
                    eval("\$fun_addr = {$jconfs[$ftrim]};");
                    return;
                }
                $lpos = strpos($ftrim, "#");
                if($lpos === false){
                    return;
                }

                $rpos = strrpos($ftrim, "#");
                if($rpos <= $lpos){
                    return;
                }

                foreach ($jconfs as $key=>$val){
                    if(strpos($ftrim, $key) !== false){
                        $obj = null;
                        eval("\$obj = {$val};");
                        if(is_null($obj)){
                            $obj = "";
                        }elseif(is_array($obj)){
                            $obj = json_encode($obj, JSON_UNESCAPED_UNICODE);
                        }
                        $fun_addr = str_replace($key, $obj, $fun_addr);
                    }
                }
            }

            /**
             * 运行外围运算 以':'为前缀
             * @param $ckey
             * @param $cval
             * @param $self_flag
             * @param $newdata
             */
            private function getDataTypeSqlConfigOut($ckey, $cval, $self_flag, $jconfs, &$newdata){
                $carr = explode(".", $ckey);
                $cinto = $newdata;
                foreach ($carr as $ck=>$ca){
                    $tca = ltrim($ca);
                    if($tca[0] == '#'){
                        $tca = trim(substr($tca, 1));
                        if($tca == ''){
                            $carr[$ck] = -1;
                        }elseif((preg_match("/^[1-9][0-9]*$/", $tca) || $tca == '0') && $tca >= 0){
                            $carr[$ck] = intval($tca);
                        }
                    }
                }

                //$val为$tmpconf内部运算
                $val = $newdata;
                if(trim($ckey) == ""){
                    $cinto = $newdata;
                }else{
                    foreach ($carr as $ca){
                        if(empty($cinto) || $ca === -1){
                            $cinto = "";
                            break;
                        }else{
                            $val = $cinto;
                            if(is_array($val)){
                                $cinto = $cinto[$ca];
                            }else{
                                $cinto = "";
                                break;
                            }
                        }
                    }
                }

                $cexp = [];
                foreach ($carr as $ca){
                    if($ca === -1){
                        $cexp[] = "[]";
                    }elseif(is_int($ca)){
                        $cexp[] = "[{$ca}]";
                    }else {
                        $ca = str_replace("'", "\\'", $ca);
                        $cexp[] = "['{$ca}']";
                    }
                }
                $cexpstr = implode("", $cexp);
                empty($cinto) && $cinto = "";


                $tmpconf = "";
                eval("\$tmpconf = \"{$cval}\";");
                $tmpconf = str_replace($self_flag, $cinto, $tmpconf);
                $this->getDataTypeSqlStrReplace($tmpconf);
                $tc = json_decode($tmpconf, true);

                foreach ($tc as $tckey=>$tcval){
                    foreach ($tcval as $k=>$v) {
                        if($k === 0 && $v === 'unset'){
                            eval("unset(\$newdata{$cexpstr});");
                            continue;
                        }else{
                            $this->getDataTypeSqlConfigChg($tc[$tckey][$k], $jconfs, $val);
                        }
                    }
                }

                try {
                    $cv = apcu($tc, $cinto);
                } catch (Exception $e) {
                    $cv = $e->getMessage();
                }

                if(trim($ckey) == ""){
                    $newdata = $cv;
                    return;
                }
                if(!is_null($cv)) {
                    if($cexpstr != '') {
                        $cexplen = count($cexp);
                        if($cexplen > 0) {
                            unset($cexp[$cexplen - 1]);
                            $is_replace = true;
                            foreach ($cexp as $ce){
                                if($ce == '[]'){
                                    $is_replace = false;
                                    break;
                                }
                            }
                            if($is_replace) {
                                $c2 = implode("", $cexp);
                                $nprev = NULL;
                                eval("!isset(\$newdata{$c2}) && \$newdata{$c2} = []; \$nprev = \$newdata{$c2};");
                                if(!is_array($nprev)){
                                    eval("\$newdata{$c2} = [];");
                                }
                                eval("\$newdata{$cexpstr} = \$cv;");
                            }
                        }
                    }
                }
            }

            /**
             * 内围运算
             * @param $ckey
             * @param $cval
             * @param $self_flag
             * @param $newdata
             */
            private function getDataTypeSqlConfigIn($ckey, $cval, $self_flag, $jconfs, &$newdata){
                foreach ($newdata as $nkey => $nval) {
                    if(is_array($nval)) {
                        $tmpconf = "";
                        eval("\$tmpconf = \"{$cval}\";");
                        $tmpconf = str_replace($self_flag, $nval[$ckey], $tmpconf);
                        $this->getDataTypeSqlStrReplace($tmpconf);
                        $tc = json_decode($tmpconf, true);

                        foreach ($tc as $tckey=>$tcval){
                            foreach ($tcval as $k=>$v) {
                                if($k === 0 && $v === 'unset'){
                                    unset($nval[$ckey]);
                                    continue;
                                }else {
                                    $this->getDataTypeSqlConfigChg($tc[$tckey][$k], $jconfs, $nval);
                                }
                            }
                        }

                        try {
                            $v_v = apcu($tc, $nval[$ckey]);
                            if (!is_null($v_v)) {
                                $nval[$ckey] = $v_v;
                            }
                        } catch (Exception $e) {
                            $nval[$ckey] = $e->getMessage();
                        }
                        $newdata[$nkey] = $nval;
                    }
                }
            }

            /**
             * Sql类型数据转换
             * @param $data
             * @param $config
             * @return array
             */
			public function getDataTypeSql($data, $config){
				$newdata = [];
                $srcdata = [];
				foreach ($data as $key=>$val){
                    $newdata[$key] = $this->keyToLower($val);
                    $srcdata[$key] = $this->keyToLowerOrNull($val);
				}

				if(empty($config)) return $newdata;

				$self_flag = "_#$#_";
				$jsonconfig = json_encode($config, JSON_UNESCAPED_UNICODE);
                $jsonconfig = str_replace("_[]_", $self_flag, $jsonconfig);


                $jclen = strlen($jsonconfig);
                $jc_bool = false;
                $jckeys = [];
                $jcstr = "";
                for($i = 0; $i < $jclen; $i ++){
                    if($jsonconfig[$i] == '_' && $jsonconfig[$i + 1] == '['){
                        $jc_bool = true;
                        $i = $i + 2;
                    }elseif($jsonconfig[$i] == ']' && $jsonconfig[$i + 1] == '_'){
                        $jc_bool = false;
                        $jckeys["_[{$jcstr}]_"] = $jcstr;
                        $jcstr = "";
                    }
                    if($jc_bool){
                        $jcstr .= $jsonconfig[$i];
                    }
                }

                //值字符串替换
                $jcvalues = [];
                foreach ($jckeys as $key=>$keyname){
                    $lkn = ltrim($keyname);
                    if($lkn == ''){
                        $jcvalues[$key] = "{\$val['{$keyname}']}";
                        continue;
                    }

                    if($lkn[0] == ':'){
                        $keyname = substr($lkn, 1);
                        if(trim($keyname) == ''){
                            $jcvalues[$key] = "\$newdata";
                            continue;
                        }
                    }
                    $keyname = str_replace("'", "\\'", $keyname);
                    $karr = explode(".", $keyname);
                    $kstep = [];
                    foreach ($karr as $ka){
                        $kal = ltrim($ka);
                        if($kal != '' && $kal[0] == '#'){
                            $kalin = trim(substr($kal, 1));
                            if($kalin != '' && (preg_match("/^[1-9][0-9]*$/", $kalin) || $kalin == '0') && $kalin >= 0){
                                $kstep[] = "[{$kalin}]";
                                continue;
                            }
                        }
                        $kstep[] = "['{$ka}']";
                    }

                    $keystr = implode("", $kstep);
                    if(substr($key, 0, 3) == '_[:'){
                        $jcvalues[$key] = "\$newdata{$keystr}";
                    }else{
                        $jcvalues[$key] = "\$indata{$keystr}";
                    }
                }

                $jconfs = [];
                foreach ($jcvalues as $key=>$val){
                    $keymd5 = "#".substr(md5($key), 8, 16)."#";
                    $jconfs[$keymd5] = $val;
                    $jsonconfig = str_replace($key, $keymd5, $jsonconfig);
                }

                $config = json_decode($jsonconfig, true);
                foreach ($config as $_key=>$_val){
                    list($key, $val) = $_val;
                    $vstr = json_encode($val, JSON_UNESCAPED_UNICODE);
                    $vstr = str_replace("\"", "#dub*", $vstr);
                    $tkey = ltrim($key);
                    if($tkey[0] == ':'){
                        $tkey = substr($tkey, 1);
                        $this->getDataTypeSqlConfigOut($tkey, $vstr, $self_flag, $jconfs, $newdata);
                    }else{
                        if($tkey[0] == '\\' && $tkey[1] == ':'){
                            $vstr = substr($vstr, 1);
                        }
                        $this->getDataTypeSqlConfigIn($key, $vstr, $self_flag, $jconfs, $newdata);
                    }
                }
				return [$srcdata, $newdata];
			}

            /**
             * Sql类型数据转换
             * @param $data
             * @param $config
             * @return array
             */
            public function getDataTypeSqlFind($data, $config){
				$newdata = $this->keyToLower($data);
                $srcdata = $this->keyToLowerOrNull($data);
				if(empty($config)) return $newdata;

                $self_flag = "_#$#_";
				$jsonconfig = json_encode($config, JSON_UNESCAPED_UNICODE);
                $jsonconfig = str_replace("_[]_", $self_flag, $jsonconfig);
				$jsonconfig = str_replace("_[", "{\$newdata['", $jsonconfig);
				$jsonconfig = str_replace("]_", "']}", $jsonconfig);
                $config = json_decode($jsonconfig, true);
                foreach ($config as $key=>$val){
                    $vstr = json_encode($val, JSON_UNESCAPED_UNICODE);
                    $vstr = str_replace("\"", "#dub*", $vstr);
                    $config[$key] = $vstr;
                }

                foreach ($config as $_key=>$_val){
                    list($key, $val) = $_val;
                    $tmpconf = "";
                    eval("\$tmpconf = \"{$val}\";");
                    $tmpconf = str_replace($self_flag, $newdata[$key], $tmpconf);
                    $this->getDataTypeSqlStrReplace($tmpconf);
                    $tc = json_decode($tmpconf, true);
				    try {
                        $v_v = apcu($tc, $newdata[$key]);
                        if(!is_null($v_v)){
                            $newdata[$key] = $v_v;
                        }
                    }catch (Exception $e){
                        $newdata[$key] = $e->getMessage();
                    }
				}
				return [$srcdata, $newdata];
			}

			private function getMd5info($type = 'css'){
				$class = Tpl::$class;
				$retarr = [];
				$type != 'css' && $type = 'js';
				if($type == 'css'){
					$arr = Tpl::$css;
				}else{
					$arr = Tpl::$js;
				}

				if(!is_array($arr)){
					if(empty($arr)){
						$arr = [];
					}else{
						$arr = [$arr];
					}
				}

				empty($arr) && $arr = [];

				$toptpl = Tpl::$toptpl;
				$tplarr = [];
				foreach($arr as $val){
					$val = $this->getRealTpl($val, $toptpl);
					if($val != $toptpl){
						$tplarr[] = $val;
					}
				}
                empty($class) && $class = [];

				$class = array_unique($class);
				sort($class);
				if($type == 'css') {
					foreach ($class as $key => $val) {
                        $fcsspath = $this->tplbase . $val . "/tpl.";
                        $tphp_fcsspath = TPHP_PATH . "/html/" . $val . "/tpl.";
						if (is_file($fcsspath . "{$type}") || is_file($fcsspath . "s{$type}") || is_file($tphp_fcsspath . "{$type}") || is_file($tphp_fcsspath . "s{$type}")) $retarr[] = $val;
					}
				}else{
					foreach ($class as $key => $val) {
						if (is_file($this->tplbase . $val . "/tpl.{$type}") || is_file(TPHP_PATH . "/html/" . $val . "/tpl.{$type}")) $retarr[] = $val;
					}
				}

				$addi = 0;
				$md5 = "";
				$str = "";
				$md5arr = array_merge($retarr, $tplarr);
				foreach ($md5arr as $val) {
					$str .= "#" . $val;
					if ($addi > 5) {
						$addi = 0;
						$md5 = md5($md5 . $str);
						$str = "";
					} else {
						$addi++;
					}
				}

				!empty($str) && $md5 = md5($md5 . $str);
				$md5 = substr($md5, 8, 16);
				\Illuminate\Support\Facades\Cache::put("{$type}_t_{$md5}", $retarr, $this->cachetime);
				\Illuminate\Support\Facades\Cache::put("{$type}_t_{$md5}_type", $tplarr, $this->cachetime);
				\Illuminate\Support\Facades\Cache::put("{$md5}_tpl", $toptpl, $this->cachetime);
				return $md5;
			}

			public function getCss(){
				return $this->getMd5info();
			}

			public function getJs(){
				return $this->getMd5info('js');
			}

            private function fileCachePath($filename){
			    $checkstr = "EXITJSON";
			    $srcfile = $this->datapath.$filename;
                $str = file_get_contents($srcfile);
                if(strpos($str, $checkstr) === false) return $srcfile;

                $datacachepath = storage_path("framework/cache/tpl/");
                $this->mkDir($datacachepath);
                $str = str_replace($checkstr, "return \$this->exitJson", $str);
                $filepath = $datacachepath.$this->cacheidmd5.".php";
                file_put_contents($filepath, $str);
                return $filepath;
            }

            /**
             * 创建目录
             * @param $path
             */
            private function mkDir($path){
                if (is_readable($path)) return;
                $plen = strlen($path);
                if($plen <= 0) return;
                $t_i = 0;
                if($path[0] == '/'){
                    $t_i = 1;
                }else{
                    if($plen > 2 && $path[1] == ':') $t_i = 3;
                }
                $bUrl = substr($path, 0, $t_i);
                for ($i = $t_i; $i < $plen; $i++) {
                    if (substr($path, $i, 1) == '\\' || substr($path, $i, 1) == '/') {
                        $bUrl = $bUrl . substr($path, $t_i, $i - $t_i);
                        if (!is_readable($bUrl)) mkdir($bUrl);
                        for ($j = $i + 1; $j < strlen($path) - 1; $j++) {
                            if (substr($path, $j, 1) == '\\' || substr($path, $j, 1) == '/') {
                                $i++;
                            } else {
                                break;
                            }
                        }
                        $t_i = $i;
                    }
                }
            }


            /**
             * 获取页面字段传递
             */
            public function getView($keyname=''){
                $vdata = $this->viewdata;
                if(empty($keyname)){
                    if(is_string($keyname)){
                        return $vdata;
                    }
                    return null;
                }elseif(!is_string($keyname)){
                    return null;
                }
                return $vdata[$keyname];
            }

            /**
             * 设置页面字段传递
             */
            public function setView(){
                $argsnum = func_num_args();
                if($argsnum <= 0) return;
                $args = func_get_args();
                $args0 = $args[0];
                $vdata = $this->viewdata;
                if($argsnum == 1){
                    if(is_array($args0)){
                        foreach ($args0 as $key=>$val){
                            if(is_string($key) && !empty($val)){
                                $vdata[$key] = $val;
                            }
                        }
                    }else {
                        return;
                    }
                }else{
                    $vdata[$args0] = $args[1];
                }
                $this->viewdata = $vdata;
            }

            /**
             * 设置Cookies
             */
			public function setCookie(){
                $argsnum = func_num_args();
                if($argsnum <= 0) return;
                $args = func_get_args();
                $args0 = $args[0];
                if($argsnum == 1){
                    if(is_array($args0)){
                        if(is_string($args0[0]) || is_numeric($args0[0])){
                            $clist = [$args0];
                        }else{
                            $clist = $args0;
                        }
                    }else {
                        return;
                    }
                }else{
                    $clist = [$args];
                }

                $cookies = $this->cookies;
                $cookies_now = $this->cookies_now;
                $cookies_forget = $this->cookies_forget;
                foreach ($clist as $val) {
                    if(count($val) < 2) continue;
                    $val0 = $val[0];
                    if (is_numeric($val0)) $val0 = $val0 . "";
                    if (!is_string($val0)) continue;

                    $val1 = $val[1];
                    if (is_array($val1) || is_object($val1)) { //如果是数组或对象则转化为json字符串数据
                        $val1 = json_encode($val1, JSON_UNESCAPED_UNICODE);
                    } elseif (is_string($val1) || is_numeric($val1)) { //如果是字符串或数字直接转换为字符串
                        $val1 = $val1 . "";
                    } elseif (!is_bool($val1)) { //如果不为bool值则直接返回
                        continue;
                    }

                    $expire = 0; //过期时间，0为永不过期
                    if (isset($val[2])) {
                        $val2 = $val[2];
                        if (is_numeric($val2) && $val2 > 0) {
                            $expire = $val2;
                        }
                    }
                    $cookies[] = [$val0, $val1, $expire];
                    $cookies_now[$val0] = $val1;
                    $key = array_search($val0, $cookies_forget);
                    if ($key !== false){
                        unset($cookies_forget[$key]);
                    }
                }
                $this->cookies = $cookies;
                $this->cookies_now = $cookies_now;
                $this->cookies_forget = $cookies_forget;
            }

            /**
             * 删除cookies
             */
            public function forgetCookie(){
                $cookies_now = $this->cookies_now;
                $cookies_forget = $this->cookies_forget;
                $cookies = $this->cookies;
                $args = func_get_args();
                foreach ($args as $val){
                    is_numeric($val) && $val .= "";
                    if(is_string($val)){
                        $cookies_forget[] = $val;
                        unset($cookies[$val]);
                        unset($cookies_now[$val]);
                    }
                }
                $this->cookies_forget = array_unique($cookies_forget);
                $this->cookies_now = $cookies_now;
                $this->cookies = $cookies;
            }

            /**
             * 删除所有cookies
             */
            public function forgetAllCookie(){
                $this->cookies = [];
                $cookies_forget = [];
                $cookies_now = $this->cookies_now;
                $this->cookies_now = [];
                foreach ($cookies_now as $key=>$val){
                    $cookies_forget[] = $key;
                }
                $this->cookies_forget = $cookies_forget;
            }

            /**
             * 获取Cookie值实时的值传递
             * @param $key
             * @param string $default
             * @return array|mixed|string
             */
            public function getCookie($keyname="", $default=""){
                $cookies_now = $this->cookies_now;
			    if(empty($keyname)) return $cookies_now;
			    if(isset($cookies_now[$keyname])) return $cookies_now[$keyname];
			    return $default;
            }

            /**
             * 跳转到指定页面
             * @param $url
             */
            public function redirect($url){
                if($url[0] == '/'){
                    $argsurl = $this->config['args']['url'];
                    !empty($argsurl) && $url = $argsurl.$url;
                }
                redirect($url)->send();
            }

            /**
             * 更新文件
             * @param array $resizes
             * @param bool $is_save_src
             * @param null $base_url
             * @return mixed
             */
            public function upload($resizes=[], $is_save_src = false, $base_url = null, $filename = ""){
                if(is_null($base_url)){
                    $base_url = $this->tpl_init;
                }
                $upload = import('Upload', $base_url, $filename);
                if(!empty($resizes) && is_array($resizes)){
                    //缩略图上传
                    foreach ($resizes as $key=>$val){
                        if(!empty($val) && is_array($val)){
                            list($w, $h) = $val;
                            if($w > 0 && $h > 0) {
                                $upload->addReSize($w, $h, $key);
                            }
                        }
                    }
                }
                return $upload->run($is_save_src);
            }
		}
	}
	return $data;
};
