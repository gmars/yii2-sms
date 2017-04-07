# yii2-sms
注意：由于阿里云官方对于短信服务的调整，该组件只能支持阿里大于和阿里云短信服务(3月30日之前开通)的支持。对于阿里云并入消息服务的短信发送将会在另一个组件中实现并且以依赖的方式在后续的版本中兼容，谢谢支持！

yii2的sms扩展库，实现了阿里大鱼sms短信接口以及阿里云SMS短信接口，在后期还会实现其他一些主流短信接口。
usage：
可以在项目根目录下执行composer命令
    composer require gmars/yii2-sms
    
    也可以在项目的根目录的composer.json的require中添加
    
    "gmars/yii2-sms": "dev-master"
    
    然后运行composer update来更新项目
    
    运行成功后就可以在项目中直接实例化使用了。
    目前支持的接口有：阿里大鱼的短信接口、阿里云的云通信/短信服务接口
    
使用方式如下
    * @param string $sdkName 接口名称
    * @param array $option   接口的配置参数
    $smsObj = new \gmars\sms\Sms($sdkName, $options, $config = []);
     
    在options参数中需要以数组的形式传入appkey和secretkey
    
    $smsObj = new Sms('ALIDAYU',['appkey'=>'2344445','secretkey'=>'xasdgdfhsfhjsfhsfhs']);
    $smsObj->send([
                      'mobile' => '15730430000',
                      'signname' => 'NoStop',
                      'templatecode' => 'SMS_34000000',
                      'data' => [
                          'code' => 'asdg',
                          'time' => '2'
                      ],
                  ]);
                  
                  
                  
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
     
     引入阿里mns发布一个稳定包
