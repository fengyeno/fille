<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/5/15
 * Time: 10:58
 */

namespace Addons\Umeng\Controller;
use Think\Controller;
require_once('Addons/Umeng/src/Demo.php');
class PushController extends Controller{
    protected $push;
    //1:android,2:ios
    public function __construct($appkey='',$appMasterSecret='',$type=1){
        parent::__construct();
        if(!$appkey){
            $config=$this->config();
            if($type==1){
                $appkey=trim($config['a_appkey']);
                $appMasterSecret=trim($config['a_msecret']);
            }elseif($type==2){
                $appkey=trim($config['i_appkey']);
                $appMasterSecret=trim($config['i_msecret']);
            }
        }
        $this->push=new \Demo($appkey,$appMasterSecret);
    }
    public function push($arr,$type=1){
//        $pc=$arr['pctype'];
        $afun='sendAndroid'.ucfirst($arr['type']);
        $ifun='sendIOS'.ucfirst($arr['type']);
        if($type==1){
            $res=$this->push->$afun($arr);
        }elseif($type==2){
            $res=$this->push->$ifun($arr);
        }
        return $res;
    }
    /*获取配置*/
    protected function config(){
        $path='Addons/Umeng/src/config.txt';
        $fp=fopen($path,'r');
        $str=fread($fp,1024);
        fclose($fp);
        if($str){
            $info=json_decode($str,true);
        }
        return $info;
    }
} 