<?php

namespace App\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Jobs\SendRegisterEmail;
use App\Transformers\UserTransformer;
use App\Repositories\Contracts\UserRepository;

use App\Http\Controllers\Api\V1\CryptAES;
use GuzzleHttp\Client;

class TestApiController extends BaseController
{
    // 对称加密时用到必填
    protected $_encryptArr = array(
        'from'       => '1017206c',
        'api_secret' => 'ea766a248a1803a0509b6da6',
        'aes_iv'     => '12330021413a0b2d',
//        'from'       => 'testiD',
//        'api_secret' => '15d62a6a3dd737bafec0aa1a',
//        'aes_iv'     => '9984f8630fcbc635',
    );

    public function __construct(Client $Client){
        $this->Client = $Client;
    }

    /**
     * @brief 加密数据
     *
     * @param $originStr 原始数据
     *
     * @return
     */
    protected function _encryptData($originStr) {
        return CryptAES::encode($originStr, $this->_encryptArr['from'], $this->_encryptArr['api_secret'], $this->_encryptArr['aes_iv']);
    }

    /**
     * @brief cps sdk 订单
     *
     * @return
     */
    public function getCpsOrder() {
        //获取 EXT_APPKEY ：http://wiki.baidu.com/pages/viewpage.action?pageId=321247406
        $url = 'http://srsdk.baidu.com/cps/orderquery';

        $appKey = 'D39184A1-F951-4AAA-B5A5-9BD3B4AC9D28';
        $appId  = !empty($_GET['app_id']) ? intval($_GET['app_id']) : 9875829;

        $tmpQueryArr = array(
            'StartDateTime' => '2017-07-10 00:00:00',
            'EndDateTime'   => '2017-07-17 23:59:59',
            'PageSize'      => 20,
            'PageIndex'     => 1,
            'AppId'         => $appId,
            //'DataSource'    => 2,
            'OrderTimeType' => 1,
            // 'Cuid'          => '36525F8785B8C3904E997DD7B87D7322|151482230569268',
        );

        $queryArr['data'] = json_encode($tmpQueryArr);
        $q = array(
            'ActionID' => 'orderlist',
            'AccessID' => 10001,
            'Ver'      => 1,
        );
        $q['Sign'] = md5($q['AccessID'] . $q['ActionID']. json_encode($queryArr) . $appKey);
        $url .= '?' . http_build_query($q);

        $res = $this->Client->post($url, ['form_params'=>$queryArr]);
        return $res->getBody();
    }

    /**
     * @brief 手机支付 sdk 订单
     *
     * @return
     */
    public function getSdkOrder() {
        //获取 EXT_APPKEY ：http://wiki.baidu.com/pages/viewpage.action?pageId=321247406
        $url = 'http://srsdk.baidu.com/cash/orderquery';

        $appKey = 'B586F48E-50E3-11E7-B114-B2F933D5FE66';
        $appId  = !empty($_GET['app_id']) ? intval($_GET['app_id']) : 7037833;

        $tmpQueryArr = array(
            'StartDateTime' => '2017-06-01 00:00:00',
            'EndDateTime'   => '2017-06-30 23:59:59',
            'PageSize'      => 20,
            'PageIndex'     => 600,
            'AppId'         => $appId,
            'DataSource'    => 2,
            'OrderTimeType' => 2,
            'OrderStatus'   => 4,
            // 'Cuid'          => '36525F8785B8C3904E997DD7B87D7322|151482230569268',
        );

        $queryArr['data'] = json_encode($tmpQueryArr);
        $q = array(
            'ActionID' => 'orderlist',
            'AccessID' => 10001,
            'Ver'      => 1,
            'tt' => 1,
        );
        $q['Sign'] = md5($q['AccessID'] . $q['ActionID']. json_encode($queryArr) . $appKey);
        $url .= '?' . http_build_query($q);

        $res = $this->Client->post($url, ['form_params'=>$queryArr]);
        return $res->getBody();
    }

    /**
     * @brief 应用信息应用名称、包名，还需要返回AppKey
     *
     * @return
     */
    public function appInfo () {
        $url = 'http://mdev.baidu.com/index.php/api/open_get_app_key';
        $appId = !empty($_GET['app_id']) ? intval($_GET['app_id']) : 9572683;
        $qsArr = array(
            'app_id' => $appId,
            'sign'   => md5('app_id=' . $appId . 'D6D1D437BB28DBFFBB7CFA3B215CC25F'),
        );

        $res = $this->Client->get($url, ['query'=>$qsArr]);
        return $res->getBody();
    }

    /**
     * @brief 搜索接口 search
     *
     * @return
     */
    public function search($arr=array()) {
        $url = 'http://m.baidu.com/api';
        if (!empty($arr)) {
            $this->_encryptArr = $arr;
        }
        $queryArr = array(
            "from"       => $this->_encryptArr['from'],
            "token"      => "jingyan",
            "type"       => "app",
            "word"       => "微信",// 唯品会, 欢乐斗地主, 旅游
            "bdi_imei"   => "867266020918545",
            "bdi_loc"    => "北京市",
            "bdi_uip"    => "127.0.0.1",
            "bdi_bear"   => "WF",
            "resolution" => "720_1280",
            "dpi"        => "300",
            "apilevel"   => "18",
            "os_version" => "Android+5.1",
            "brand"      => "OPSSON",
            "model"      => "Q1",
            "pver"       => "3",
            "uid"        => "712ACBBE63076AEC76BE860AQDEWE880",
            "bdi_cid"    => "9177265119920",
            "bdi_mac"    => "ac:bc:32:9a:bf:33",
            "bdi_imsi"   => "5a3b287f2b13bef8",
            "class"      => "g",
            "ct"         => "1452249585", //time(),
            "cname"      => "WS:YY",
            "cver"       => "2.0",
            "cpack"      => "monkey",
            "rn"         => "10",
            "pn"         => "0",
        );
        if (!empty($_GET['docid'])) {
            $queryArr['docid'] = $_GET['docid'];
        }
        if (isset($queryArr['bdi_loc'])) {
            $queryArr['bdi_loc'] = base64_encode($queryArr['bdi_loc']);
        }
        if (isset($queryArr['bdi_mac'])) {
            $queryArr['bdi_mac'] = base64_encode($queryArr['bdi_mac']);
        }
        if ($queryArr['bdi_imsi']) {
            $queryArr['bdi_imsi'] = base64_encode($queryArr['bdi_imsi']);
        }
        $queryArr['bdi_imei'] = $this->_encryptData($queryArr['bdi_imei']);

        ksort($queryArr);
        $queryArr['sign']    = strtoupper(md5(http_build_query($queryArr)));
        $queryArr['action']  = 'search';
        $queryArr['format']  = 'json';

        $res = $this->Client->get($url, ['query'=>$queryArr]);
        return $res->getBody();
    }

    /**
     * @brief 移服接口 appdetail
     *
     * @return
     */
    public function appdetail($arr=array()) {
        $url = 'http://m.baidu.com/api';
        if (!empty($arr)) {
            $this->_encryptArr = $arr;
        }
        $queryArr = array(
            "from"       => $this->_encryptArr['from'],
            "token"      => "jingyan",
            "type"       => "app",
            "docid"      => 9403459,// 9560122
            "req_biz"    => 1,
            "bdi_imei"   => "86665602821656",
            "bdi_loc"    => "北京市",
            "bdi_uip"    => "127.0.0.1",
            "bdi_bear"   => "WF",
            "resolution" => "720_1280",
            "dpi"        => "300",
            "apilevel"   => "18",
            "os_version" => "4.3",
            "brand"      => "OPSSON",
            "model"      => "Q1",
            "pver"       => "3",
            "ua"         => "opera",
            "show_time"  => 1452249585,
            "refer_tag"  => "rec",
            "uid"        => "712ACBBE63076AEC76BE860AQDEWE880",
            "bdi_cid"    => "9177265119920",
            "bdi_mac"    => "ac:bc:32:9a:bf:33",
            "bdi_imsi"   => "5a3b287f2b13bef8",
            "ct"         => "1452249585", //time(),
            "cname"      => "WS:YY",
            "cver"       => "",
            "cpack"      => "monkey",
        );
        if (!empty($_GET['docid'])) {
            $queryArr['docid'] = $_GET['docid'];
        }
        if (isset($queryArr['bdi_loc'])) {
            $queryArr['bdi_loc'] = base64_encode($queryArr['bdi_loc']);
        }
        if (isset($queryArr['bdi_mac'])) {
            $queryArr['bdi_mac'] = base64_encode($queryArr['bdi_mac']);
        }
        if ($queryArr['bdi_imsi']) {
            $queryArr['bdi_imsi'] = base64_encode($queryArr['bdi_imsi']);
        }
        $queryArr['bdi_imei'] = $this->_encryptData($queryArr['bdi_imei']);

        if ($queryArr['pver'] > 2) {
            ksort($queryArr);
        }
        $queryArr['sign']    = strtoupper(md5(http_build_query($queryArr)));
        $queryArr['action']  = 'appdetail';
        $queryArr['format']  = 'json';

        $res = $this->Client->get($url, ['query'=>$queryArr]);
        return $res->getBody();
    }
}
