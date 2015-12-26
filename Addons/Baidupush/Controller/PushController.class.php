<?php
namespace Addons\Baidupush\Controller;
use Think\Controller;
require 'Addons/Baidupush/ext/sdk.php';
class PushController extends Controller{
    private $apiKey, $secretKey,$path;
    public function __construct($apiKey='',$secretKey='',$type=1){
        parent::__construct();
        $this->apiKey		=	$apiKey;
        $this->secretKey	=	$secretKey;
        if(!$this->apiKey || !$this->secretKey){
            $path="Addons/Baidupush/ext/config.config";
            $fp=fopen($path,'r');
            $str=fread($fp,1024);
            fclose($fp);
            if($str){
                $arr=json_decode($str,true);
                if($type==1){
                    $this->apiKey=$arr['a_apiKey'];
                    $this->secretKey=$arr['a_secretKey'];
                }elseif($type==2){
                    $this->apiKey=$arr['i_apiKey'];
                    $this->secretKey=$arr['i_secretKey'];
                }
            }
        }

    }
    //推送android设备消息
    public function pushMessage_android ($message,$msg_type=1,$channelId=''){
        $sdk= new \PushSDK($this->apiKey,$this->secretKey);
        // message content.
//        $message = array (
//            // 消息的标题.
//            'title' => 'Hi!',
//            // 消息内容
//            'description' => "hello, this message from baidu push service."
//        );

        // 设置消息类型为 通知类型.
        $opts = array (
            'msg_type' => $msg_type
        );
        // 向目标设备发送一条消息
        $rs = $sdk -> pushMsgToSingleDevice($channelId, $message, $opts);
        return $rs;
    }

//推送ios设备消息
    public function pushMessage_ios ($message,$msg_type=1,$channelId=''){
        $sdk= new \PushSDK($this->apiKey,$this->secretKey);
//        $message = array (
//            'aps' => array (
//                // 消息内容
//                'alert' => "hello, this message from baidu push service.",
//            ),
//        );

        // 设置消息类型为 通知类型.
        $opts = array (
            'msg_type' => $msg_type,
            'deploy_status' => 1,// iOS应用的部署状态:  1：开发状态；2：生产状态； 若不指定，则默认设置为生产状态。
        );
        // 向目标设备发送一条消息
        $rs = $sdk -> pushMsgToSingleDevice($channelId, $message, $opts);
        return $rs;
    }
    public function pushMessage_all($msg){
        $sdk= new \PushSDK($this->apiKey,$this->secretKey);
//        $message = array (
//            'aps' => array (
//                // 消息内容
//                'alert' => "hello, this message from baidu push service.",
//            ),
//        );

        // 设置消息类型为 通知类型.
        $opts = array (
            'msg_type' => 1,
            'deploy_status' => 1,// iOS应用的部署状态:  1：开发状态；2：生产状态； 若不指定，则默认设置为生产状态。
        );
        // 向目标设备发送一条消息
        $rs = $sdk -> pushMsgToAll($msg, $opts);
        return $rs;
    }
}
?>
