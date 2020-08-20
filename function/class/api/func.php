<?php

/**
 * api接口模块
 * 调用外部erp系统
 */
return function($data){
	if(!class_exists('Api')) {
		class Api {

			/**
			 * 获取ERP接口
			 * @param $url
			 * @param null $para
			 * @param null $type
			 * @param null $header
			 * @return mixed|string
			 */
			public static function erp($erp, $url, $para=null, $type=null, $header=NULL, $output_encoding = false, $iscurl = true){
				$erp_host = $erp['api_host'];
				$erp_user = $erp['api_user'];
				$erp_password = $erp['api_password'];

				$host = trim(trim($erp_host, "/"));
				if(!empty($host)) $host .= "/";
				$url = trim(trim($url, "/"));
				$url = $host.$url;
				if(empty($header)){
					$header = [];
				}

				$header[] = 'Authorization:Basic '.base64_encode($erp_user.":".$erp_password);
				$datastr = Api::getHttpData($url, $para, $type, $header, $output_encoding, $iscurl);
				if(!isset($GLOBALS['ERP_API_ERR_URL'])){
                    $GLOBALS['ERP_API_ERR_URL'] = [];
                }
                if(empty($datastr)) {
                    $GLOBALS['ERP_API_ERR_URL'][] = $url;
                }else{
                    $data = json_decode($datastr, true);
                    if (json_last_error() != JSON_ERROR_NONE) {
                        $data = ['code' => 100, 'errmsg' => $datastr];
                        $GLOBALS['ERP_API_ERR_URL'][] = $url;
                    }
                }
				return $data;
			}

			/**
			 * 获取ERP接口数据并缓存数
			 * @param $url
			 * @param null $para
			 * @param null $type
			 * @param null $header
			 * @param bool $output_encoding
			 * @param bool $iscurl
			 * @return mixed|string
			 */
			public static function erpCache($url, $para=null, $type=null, $header=NULL, $output_encoding = false, $iscurl = true){
				$erp = config("erp");
				$http_expire_time = $erp['http_expire_time'];
				$cacheid = Api::getHttpMd5($url, $para, $type, $header);
				$data = \Illuminate\Support\Facades\Cache::get($cacheid);
				if(!empty($data)) return $data;
				$data = Api::erp($url, $para, $type, $header, $output_encoding, $iscurl);
				\Illuminate\Support\Facades\Cache::put($cacheid, $data, $http_expire_time);
				return $data;
			}

			/**
			 * 获取http提交数据的唯一标示并作为缓存保存
			 * @param $url
			 * @param null $para
			 * @param null $type
			 * @return string
			 */
			public static function getHttpMd5($url, $para=null, $type=null){
				if(empty($type)){
					$retmd5 = md5($url);
				}else{
					$retmd5 = md5($url."#".$type);
				}

				if(!empty($para) && is_array($para)){
					$parastr = "";
					ksort($para);
					foreach($para as $key=>$val){
						$parastr .= "&{$key}={$val}";
					}
					$retmd5 = md5($retmd5.$parastr);
				}
				return 'http_'.substr($retmd5, 8, 16);
			}

			/**
			 * 远程获取数据，GET和POST模式
			 * @param $url 指定URL完整路径地址
			 * @param $para 请求的数据
			 * @param $type GET或POST类型
			 * @param null $header 请求头部信息
			 * @param bool $output_encoding 输出编码格式，如：utf-8
			 * @param bool $iscurl 获取远程信息类型
			 */
			public static function getHttpData($url, $para=null, $method=null, $header=NULL, $output_encoding = false, $iscurl = true) {
				return Tphp\Apcu\Controllers\HttpController::getHttpData($url, $para, $method, $header, $output_encoding, $iscurl);
			}
		}
	}
	return $data;
};
