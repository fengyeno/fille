<?php
namespace Addons\Ronglian\Controller;
use Think\Controller;
include_once("Addons/Ronglian/src/SDK/CCPRestSDK.php");
class RonglianController extends Controller{
    protected $accountSid;
    protected $accountToken;
    protected $appId;
    protected $serverIP='sandboxapp.cloopen.com';/*请求地址，格式如下，不需要写https://*/
    protected $serverPort='8883';/*请求端口*/
    protected $softVersion='2013-12-26';/*REST版本号*/
	public function __construct(){
		parent::__construct();
        $map['status']=1;
        $map['name']='Ronglian';
        $config=M('addons')->where($map)->getField('config');
        if(!$config){
            $data['status']=false;
            $data['message']='不可用';
            $this->ajaxReturn($data);
        }
        $arr=json_decode($config,true);
        if(!$arr['accountSid'] || !$arr['accountToken'] || !$arr['appId'] || !$arr['Rest_URL2'] || !$arr['Rest_URL1']){
            $data['status']=false;
            $data['message']='不可用';
            $this->ajaxReturn($data);
        }
        $this->accountSid= $arr['accountSid'];
        $this->accountToken= $arr['accountToken'];
        $this->appId= $arr['appId'];
        if($arr['type']==1){
            $this->serverIP=$arr['Rest_URL1'];
        }elseif($arr['type']==2){
            $this->serverIP=$arr['Rest_URL2'];
        }
	}
    /**
     * 创建子帐号
     * @param friendlyName 子帐号名称
     */
    public function createSubAccount($friendlyName) {
        // 初始化REST SDK
//        global $accountSid,$accountToken,$appId,$serverIP,$serverPort,$softVersion;
        $rest = new \REST($this->serverIP,$this->serverPort,$this->softVersion);
        $rest->setAccount($this->accountSid,$this->accountToken);
        $rest->setAppId($this->appId);

        // 调用云通讯平台的创建子帐号,绑定您的子帐号名称
        $result = $rest->CreateSubAccount($friendlyName);
        if($result == NULL ) {
            return false;
        }
        if($result->statusCode!=0) {
            $data['status']=$result->statusCode;
            $data['msg']=$result->statusMsg;
            //TODO 添加错误处理逻辑
        }else {
            // 获取返回信息
            $subaccount = $result->SubAccount;
            $data['status']=$result->statusCode;
            $data['subAccountSid']=$subaccount->subAccountSid;
            $data['subToken']=$subaccount->subToken;
            $data['dateCreated']=$subaccount->dateCreated;
            $data['voipAccount']=$subaccount->voipAccount;
            $data['voipPwd']=$subaccount->voipPwd;
            $data['msg']='success';
            //TODO 把云平台子帐号信息存储在您的服务器上.
            //TODO 添加成功处理逻辑
        }
        return $data;
    }
}
