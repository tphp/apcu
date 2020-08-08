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
			public static function getHttpData($url, $para=null, $type=null, $header=NULL, $output_encoding = false, $iscurl = true) {
				if ($iscurl) {
					$type = strtolower($type);
					if($type == 'get' || empty($para)){
						if(!empty($para)) {
							$content = http_build_query($para);
							if (strpos($url, '?') !== false) {
								$url .= "&{$content}";
							} else {
								$url .= "?{$content}";
							}
						}
						$curl = curl_init($url);
						if(!empty($header)) curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//获取数据返回
						curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);//在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
					}elseif($type == 'json') {
						$para_string = json_encode($para);
						$curl = curl_init($url);
						curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
						curl_setopt($curl, CURLOPT_POSTFIELDS, $para_string);
						curl_setopt($curl, CURLOPT_RETURNTRANSFER,true);
						$headernew = [];
						$headernew[] = 'Content-Type: application/json';
						$headernew[] = 'Content-Length: ' . strlen($para_string);
						foreach($header as $val){
							$headernew[] = $val;
						}
						curl_setopt($curl, CURLOPT_HTTPHEADER, $headernew);
					}else{
						$curl = curl_init();
						curl_setopt($curl, CURLOPT_URL, $url) ;
						if(!empty($header)){
							curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
						}else{
							curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
						}
						curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
						curl_setopt($curl,CURLOPT_POST, count($para)); // post传输数据
						curl_setopt($curl,CURLOPT_POSTFIELDS, $para);// post传输数据
					}
                    curl_setopt($curl, CURLOPT_TIMEOUT, 15);

					$responseText = curl_exec($curl);
					curl_close($curl);
				}else{
					$responseText = file_get_contents($url);

				}
				//设置编码格式
				if ($output_encoding) {
					$html_encoding = Api::getEncode($responseText);
					$responseText = iconv($html_encoding, $output_encoding, $responseText);
				}
				return $responseText;
			}

			/**
			 * 获取文件编码
			 * @param $string
			 * @return string
			 */
			private static function getEncode($string) {
				return mb_detect_encoding($string, array('ASCII', 'GB2312', 'GBK', 'UTF-8'));
			}
		}
	}
	return $data;
};
