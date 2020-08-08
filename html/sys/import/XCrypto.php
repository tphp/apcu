<?php
/**
 * Created by PhpStorm.
 * User: TPHP
 * Date: 20-6-23
 * Time: 上午9:16
 */

class XCrypto{
    function __construct($password=''){
        $this->options = OPENSSL_RAW_DATA;
        empty($password) && $password = 'NUM_XCR';
        $this->password = $password;
    }

    /**
     * 加密
     * @param string $input
     * @param string $method
     * @return string
     */
    private function __encrypt($input='', $method = 'AES-128-ECB'){
        $data = openssl_encrypt($input, $method, $this->password, $this->options);
        $data = base64_encode($data);
        return $data;
    }

    /**
     * 解密
     * @param string $input
     * @param string $method
     * @return string
     */
    private function __decrypt($input='', $method = 'AES-128-ECB'){
        $data = openssl_decrypt(base64_decode($input), $method, $this->password, $this->options);
        if($data === false){
            $data = '';
        }
        return $data;
    }


    /**
     * AES加密
     * @param $input
     * @return string
     * @throws
     */
    public function aesEncrypt($input){
        return $this->__encrypt($input);
    }

    /**
     * AES解密
     * @param $input
     * @return string
     * @throws
     */
    public function aesDecrypt($input){
        return $this->__decrypt($input);
    }

    public function desEncrypt($input=''){
        return $this->__encrypt($input, 'DES-ECB');
    }

    public function desDecrypt($input=''){
        return $this->__decrypt($input, 'DES-ECB');
    }
}