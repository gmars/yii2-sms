<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 17-4-4
 * Time: 下午3:44
 * Author: weiyongqiang <weiyongqiang@163.com>
 * GitHub: www.github.org/gmars
 * WebSite: www.weiyongqiang.com
 */

namespace gmars\sms;


use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use Sms\Request\V20160927\SingleSendSmsRequest;
use yii\base\Component;
use yii\base\Exception;

class Sms extends Component
{
    const BEFOR_SEND_SMS = "befor_send_sms_event";

    const AFTER_SEND_SMS_SUCCESS = "after_send_sms_event_success";

    const AFTER_SEND_SMS_ERROR = "after_send_sms_event_error";

    /**
     * @var string 使用的sdk名称
     * 'ALIYUN' 阿里云平台的接口
     * 'ALIDAYU' 阿里大鱼平台的接口
     */
    public $sdkName;

    /**
     * @var object sdk接口对象
     */
    private $_sdkObj;

    public $errors = "";

    private $_grepMobile = "/^1[3,5,6,7,8]\d{9}$/";

    /**
     * Sms constructor.
     * @param string $sdkName 接口名称
     * @param array $option   接口的配置参数
     * @param array $config
     */
    public function __construct($sdkName="ALIDAYU", $option=[], array $config = [])
    {
        $this->sdkName = $sdkName;

        switch ($sdkName){
            case 'ALIDAYU':
                require 'driver/taobao/TopSdk.php';
                $this->_sdkObj = new \TopClient();

                if (!isset($option['appkey']) || empty($option['appkey'])) {
                    $this->errors = '参数缺失：appkey必须要设置';
                    return false;
                }

                if (!isset($option['secretkey']) || empty($option['secretkey'])) {
                    $this->errors = '参数缺失：secretkey必须要设置';
                    return false;
                }
                $this->_sdkObj->appkey = $option['appkey'];
                $this->_sdkObj->secretKey = $option['secretkey'];

                break;
            case 'ALIYUN':
                require 'driver/aliyun/aliyun-php-sdk-sms/aliyun-php-sdk-core/Config.php';

                $option['regionid'] = isset($option['regionid'])? $option['regionid']:'cn-hangzhou';
                if (!isset($option['regionid']) || empty($option['regionid'])) {
                    $this->errors = '参数缺失：regionid必须要设置';
                    return false;
                }

                if (!isset($option['appkey']) || empty($option['appkey'])) {
                    $this->errors = '参数缺失：appkey必须要设置';
                    return false;
                }

                if (!isset($option['secretkey']) || empty($option['secretkey'])) {
                    $this->errors = '参数缺失：secretkey必须要设置';
                    return false;
                }
                $clientProfile = \DefaultProfile::getProfile($option['regionid'], $option['appkey'], $option['secretkey']);
                $this->_sdkObj = new \DefaultAcsClient($clientProfile);
                break;
            default:
                $this->errors = '还未开启'.$sdkName.'sdk的集成，你可以给hayixia606@163.com发邮件开通分支';
                return false;
                break;
        }

    }


    /**
     * @param array $requestData
     * @return bool
     * 发送短信统一接口
     */
    public function send($requestData = [])
    {
        try{
            $this->trigger(self::BEFOR_SEND_SMS);
            switch ($this->sdkName){
                case 'ALIDAYU':
                    $this->_alidayunSend($requestData);
                    break;
                case 'ALIYUN':
                    $this->_aliyunSend($requestData);
                    break;
            }

            $this->trigger(self::AFTER_SEND_SMS_SUCCESS);
            return true;

        }catch (Exception $e) {
            $this->trigger(self::AFTER_SEND_SMS_ERROR);
            $this->errors = $e->getMessage();
            return false;
        }
    }


    /**
     * 传参方式必须符合一下规则
     * $args['data'] = [
     *      'mobile' => 'mobile',                   电话号码必须
     *      'signname' => 'signname',               签名必须要有
     *      'templatecode' => 'templatecode',       模板编码
     *      'smstype' => 'smstype',                 短信类型
     *      'extend' => 'extend',                   附加参数可以不传
     *      'data' => [                             数据必须以数组形式传参
     *          'code' => 'xxxx',
     *          'time' => '10'
     *      ],
     * ]
     * @param $args
     * @return bool
     * @throws Exception
     * 阿里大鱼信息发送
     */
    private function _alidayunSend($args)
    {

        if (empty($args) || !is_array($args)) {
            throw new Exception('发送参数错误请查看参数表',1001);
        }

        if (!isset($args['mobile'])) {
            throw new Exception('电话号码不能为空',1002);
        }

        if (!preg_match($this->_grepMobile, $args['mobile'])) {
            throw new Exception('电话号码格式错误', 1003);
        }

        if (!isset($args['signname']) || empty($args['signname'])) {
            throw new Exception('签名不能为空', 1004);
        }

        if (!isset($args['templatecode']) || empty($args['templatecode'])) {
            throw new Exception('模板编号不能为空', 1005);
        }

        if (!isset($args['data']) || !is_array($args['data']) || count($args['data'])==0) {
            throw new Exception('发送的数据参数必须为数组，不能为空');
        }

        $arrData = [];
        foreach ($args['data'] as $k=>$v)
        {
            $arrData[$k] = (string)$v;
        }

        $jsonData = json_encode($arrData);

        $extendData = isset($args['extend'])? $args['extend']:'';
        $smsType = isset($args['smstype'])? $args['smstype']:'normal';

        $requestObj = new \AlibabaAliqinFcSmsNumSendRequest();

        $requestObj->setExtend((string)$extendData);
        $requestObj->setSmsType((string)$smsType);
        $requestObj->setSmsFreeSignName((string)$args['signname']);
        $requestObj->setRecNum((string)$args['mobile']);
        $requestObj->setSmsTemplateCode((string)$args['templatecode']);
        $requestObj->setSmsParam($jsonData);
        $result = $this->_sdkObj->execute($requestObj);
        $resultObj = json_decode($result);

        if (isset($resultObj->error_response)) {
            throw new Exception($resultObj->error_response->sub_msg, $resultObj->error_response->code);
        }

        return true;
    }

    /**
     * @param $args
     * @return bool
     * @throws Exception
     * 传参方式必须符合一下规则
     * $args['data'] = [
     *      'mobile' => 'mobile',                   电话号码必须
     *      'signname' => 'signname',               签名必须要有
     *      'templatecode' => 'templatecode',       模板编码
     *      'data' => [                             数据必须以数组形式传参
     *          'code' => 'xxxx',
     *          'time' => '10'
     *      ],
     * ]
     */
    private function _aliyunSend($args)
    {
        if (empty($args) || !is_array($args)) {
            throw new Exception('发送参数错误请查看参数表',1001);
        }

        if (!isset($args['mobile'])) {
            throw new Exception('电话号码不能为空',1002);
        }

        if (!preg_match($this->_grepMobile, $args['mobile'])) {
            throw new Exception('电话号码格式错误', 1003);
        }

        if (!isset($args['signname']) || empty($args['signname'])) {
            throw new Exception('签名不能为空', 1004);
        }

        if (!isset($args['templatecode']) || empty($args['templatecode'])) {
            throw new Exception('模板编号不能为空', 1005);
        }

        if (!isset($args['data']) || !is_array($args['data']) || count($args['data'])==0) {
            throw new Exception('发送的数据参数必须为数组，不能为空');
        }

        $arrData = [];
        foreach ($args['data'] as $k=>$v)
        {
            $arrData[$k] = (string)$v;
        }

        $jsonData = json_encode($arrData);

        $requestObj = new SingleSendSmsRequest();
        $requestObj->setSignName((string)$args['signname']);
        $requestObj->setTemplateCode((string)$args['templatecode']);
        $requestObj->setRecNum((string)$args['mobile']);
        $requestObj->setParamString($jsonData);

        try{
            $this->_sdkObj->getAcsResponse($requestObj);
            return true;
        }catch (\ClientException $e){
            throw new Exception($e->getMessage(), $e->getCode());
        }catch (\ServerException $e){
            throw new Exception($e->getMessage(), $e->getCode());
        }

    }


}