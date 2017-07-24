<?php
/*============================================================================
*     FileName: CryptAES.php
*         Desc: AES加密解密
*       Author: tangyongping@baidu.com
*      Version: 1.0
*   LastChange: 2016-01-13
*      History:
=============================================================================*/
namespace App\Http\Controllers\Api\V1;

class CryptAES {
    /**
     * @brief 加密
     *
     * @param $usData 需要加密的明文
     * @param $from   渠道标示
     * @param $secret secret
     * @param $iv     iv
     *
     * @return string
     */
    public static function encode($usData, $from, $secret, $iv) {
        if (!self::_validParams($from, $secret, $iv)) {
            return false;
        }

        list($key, $iv) = self::_init($from, $secret, $iv);
        $usData         = self::pad($usData);

//        $enmcrypt       = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
//        mcrypt_generic_init($enmcrypt, $key, $iv);
//
//        // 加密
//        $data = mcrypt_generic($enmcrypt, $usData);
//        $data = urlencode(base64_encode($data));
//        mcrypt_generic_deinit($enmcrypt);
//        mcrypt_module_close($enmcrypt);

        $data = openssl_encrypt($usData,'AES-128-CBC',$key,OPENSSL_ZERO_PADDING,$iv);

        return $data;
    }

    /**
     * @brief 解密
     *
     * @param $usData 需要解密的密文
     * @param $from   渠道标示
     * @param $secret secret
     * @param $iv     iv
     *
     * @return string
     */
    public static function decode($usData, $from, $secret, $iv) {
        if (empty($usData) || !self::_validParams($from, $secret, $iv)) {
            return false;
        }

        list($key, $iv) = self::_init($from, $secret, $iv);
        $usData         = base64_decode(urldecode($usData));
//        $enmcrypt       = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
//        mcrypt_generic_init($enmcrypt, $key, $iv);
//        $data = mdecrypt_generic($enmcrypt, $usData);
//        mcrypt_generic_deinit($enmcrypt);
//        mcrypt_module_close($enmcrypt);

        $data = openssl_decrypt($usData,'AES-128-CBC',$key,OPENSSL_ZERO_PADDING,$iv);
        return self::unpad($data);
    }

    /**
     * @brief 初始化加密参数
     *
     * @param $from
     * @param $secret
     * @param $iv
     *
     * @return
     */
    private static function _init($from, $secret, $iv) {
        // 获取加密key
        $key = strtoupper(substr(md5($from . $secret), -16));
        // 如果iv长度不足16字节，进行补足
        $iv  = str_pad(substr($iv, 0, 16), 16, chr(0));

        return array($key, $iv);
    }

    /**
     * @brief 验参
     *
     * @param $from
     * @param $secret
     * @param $iv
     *
     * @return
     */
    private static function _validParams($from, $secret, $iv) {
        if (empty($from) || empty($secret) || empty($iv)) {
            return false;
        }
        return true;
    }
    /**
     * @brief pkcs 填充
     *
     * @param $text string
     *
     * @return string
     */
    public static function pad($text) {
        $blocksize = 16;
        $pad       = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    /**
     * @brief 解pkcs
     *
     * @param $text string
     *
     * @return string
     */
    public static function unpad($text) {
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text)) return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
        return substr($text, 0, -1 * $pad);
    }
}

// echo CryptAES::encode('1231414124131231', 'testiD', 'testiD', '12345678');

// echo CryptAES::deCode('oHYCp0y3sur6VerprhWrjFParjYcUZkIUpVsqQEpPTU%3D', 'testiD', 'testiD', '12345678');
