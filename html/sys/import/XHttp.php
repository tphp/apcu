<?php

/**
 * HTTP处理
 * Class ApiHtml
 */
class XHttp {

    // 删除HTML代码中的BODY以外的代码
    private function webExitFormatHtml($html){
        $pos = strpos(strtolower($html), "<body");
        if($pos !== false){
            $html = substr($html, $pos);
            $pos = strpos(strtolower($html), ">");
            if($pos !== false){
                $html = substr($html, $pos + 1);
            }
        }
        $pos = strpos(strtolower($html), "</body>");
        if($pos !== false){
            $html = substr($html, 0, $pos);
        }
        return $html;
    }


    /**
     * 访问接口错误时退出页面
     * @param int $http_code
     * @param string $text
     */
    private function webExit($http_code = 0, $text = ""){
        $http_codes = [
            404 => '页面找不到',
            501 => '服务器错误',
            502 => '服务器错误',
            503 => '服务器错误'
        ];
        if($http_code !== 200) {
            $msg = "未知错误";
            if (isset($http_codes[$http_code])) {
                $msg = $http_codes[$http_code];
            }
            $text = $this->webExitFormatHtml($text);
            $ret_data = [
                'code' => $http_code,
                'msg' => $msg,
                'data' => $this->webExitFormatHtml($text)
            ];
            if(count($_POST) > 0){
                EXITJSON(0, "<div>{$http_code}: {$msg}</div><div>{$text}</div>");
            }else {
                echo view("sys.public.layout.tpl.error.page.html.tpl", $ret_data);
            }
            exit();
        }
    }

    /**
     * 远程获取数据，GET和POST模式
     * @param $url 指定URL完整路径地址
     * @param $para 请求的数据
     * @param $type GET或POST类型
     * @param null $header 请求头部信息
     * @param bool $output_encoding 输出编码格式，如：utf-8
     * @param bool $iscurl 获取远程信息类型
     * @param bool $isexit 是否错误退出
     * @return bool|mixed|string
     */
    public function getHttpData($url, $para=null, $method=null, $header=NULL, $output_encoding = false, $iscurl = true, $isexit = true) {
        $responseText = Tphp\Apcu\Controllers\HttpController::getHttpData($url, $para, $method, $header, $output_encoding, $iscurl);
        if($isexit){
            $this->webExit($http_code, $responseText);
        }
        return $responseText;
    }

    public function getHttpHtml($url, $para=null, $type=null, $header=NULL, $output_encoding = false, $iscurl = true) {
        return $this->getHttpData($url, $para, $type, $header, $output_encoding, $iscurl, false);
    }

    public function getHttpJson($url, $para=null, $type=null, $header=NULL, $output_encoding = false, $iscurl = true) {
        $html = $this->getHttpHtml($url, $para, $type, $header, $output_encoding, $iscurl);
        $json = json_decode($html, true);
        return $json;
    }
}