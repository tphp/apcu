<?php

namespace Tphp\Apcu\Controllers;

class HttpController {
    /**
     * 获取文件编码
     * @param $string
     * @return string
     */
    private static function getEncode($string) {
        return mb_detect_encoding($string, ['ASCII', 'GB2312', 'GBK', 'UTF-8']);
    }

    /**
     * 获取URL地址和参数
     * @param string $url
     * @return array
     */
    public static function getUrlParams($url=''){
        $params = [];
        $pos = strpos($url, '?');
        if($pos === false){
            return [$url, $params];
        }
        $url_ext = substr($url, $pos + 1);
        $url = substr($url, 0, $pos);
        $url_ext_arr = explode("&", $url_ext);
        foreach ($url_ext_arr as $uea){
            $uea = trim($uea);
            if(empty($uea)){
                continue;
            }
            list($k, $v) = explode("=", $uea);
            $k = trim($k);
            if(empty($k)){
                continue;
            }
            if(isset($v)){
                $params[$k] = $v;
            }
        }
        return [$url, $params];
    }

    /**
     * 获取正确的Header头信息
     * @param array $header
     * @return array
     */
    private static function getHeader($header=[]){
        $new_header = [];
        if(empty($header) || !is_array($header)){
            return $new_header;
        }
        foreach ($header as $key=>$val){
            if(!is_string($val)){
                continue;
            }
            if(is_int($key)){
                $new_header[] = $val;
            }else{
                $new_header[] = $key . ": " . $val;
            }
        }
        return $new_header;
    }

    /**
     * 远程获取数据，GET和POST模式
     * @param $url 指定URL完整路径地址
     * @param $para 请求的数据
     * @param $method GET或POST类型
     * @param null $header 请求头部信息
     * @param bool $output_encoding 输出编码格式，如：utf-8
     * @param bool $iscurl 获取远程信息类型
     * @return bool|mixed|string
     */
    public static function getHttpData($url, $para=null, $method=null, $header=NULL, $output_encoding = false, $iscurl = true) {
        $header = self::getHeader($header);
        if ($iscurl) {
            $method = strtolower($method);
            $curl = curl_init();
            if($method == 'get' || empty($para)){
                if(!empty($para)) {
                    list($url, $data) = self::getUrlParams($url);
                    if (empty($data)) {
                        $data = $para;
                    }else{
                        foreach ($para as $key=>$val){
                            $data[$key] = $val;
                        }
                    }
                    $content = http_build_query($data);
                    $url .= "?{$content}";
                }
                curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);//在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
            }elseif($method == 'json') {
                $para_string = json_encode($para);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($curl, CURLOPT_POSTFIELDS, $para_string);
                $header[] = 'Content-Type: application/json';
                $header[] = 'Content-Length: ' . strlen($para_string);
            }else{
                curl_setopt($curl, CURLOPT_POST, count($para)); // post传输数据
                curl_setopt($curl, CURLOPT_POSTFIELDS, $para);// post传输数据
            }

            curl_setopt($curl, CURLOPT_URL, $url) ;
            curl_setopt($curl, CURLOPT_HEADER, 0); // 过滤HTTP头

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // 显示输出结果
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);  // 从证书中检查SSL加密算法是否存在
            if(!empty($header)){
                curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            }

            $responseText = curl_exec($curl);
//            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
        }else{
//            $http_code = 0;
            $responseText = file_get_contents($url);

        }
        //设置编码格式
        if ($output_encoding) {
            $html_encoding = self::getEncode($responseText);
            $responseText = iconv($html_encoding, $output_encoding, $responseText);
        }

        return $responseText;
    }
}
