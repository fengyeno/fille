<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/5/15
 * Time: 10:58
 */

namespace Addons\Baidupush\Controller;
use Api\Controller\BaseController;
class ApiController extends BaseController{
    /*绑定用户*/
    public function bindUser(){
        $arr['token']=I('token');
        $arr['type']=I('type');
        if(!$arr['token']){
            $this->apiError(0,'未知的设备标识');
        }
        if($this->checkBind($arr['token'])){
            $this->apiError(0,'已绑定');
        }
        $arr['type']=$arr['type']?$arr['type']:1;
        $arr['uid']=$this->uid;
        $arr['status']=1;
        $arr['create_time']=time();
        $res=M('baidu_user')->add($arr);
        if($res){
            $list=$this->getMsg($this->uid);
            if($list){
                foreach($list as $key=>$v){
                    $this->push2user1($this->uid,$v['msg'],$v['style'],$v['content_id'],$v['id'],$arr['type'],$arr['token']);
                }
            }
            $this->apiSuccess('success');
        }else{
            $this->apiError(0,'绑定失败');
        }
    }
    /*检测绑定*/
    protected function checkBind($token){
        $map['token']=$token;
        $map['uid']=$this->uid;
        $map['status']=1;
        $isExists=M('baidu_user')->where($map)->getField('id');
        return $isExists;
    }
    /*查询未接受消息*/
    protected function getMsg($uid){
        $map['uid']=$uid;
        $map['status']=1;
        $map['on_msg']=0;
        $map['type']=array('neq',1);
        $list=M('baidu_msg')->where($map)->order('create_time')->select();
        return $list;
    }
    /*推送*/
    protected function push2user1($uid,$msg,$style=0,$content_id=0,$id=0,$pc=1,$token=""){
        if(!$msg || !$token){
            return false;
        }
        $arr['title']=$msg;
        $arr['description']=$msg;
        $arr['aps']['alert']=$msg;
        $arr['custom_content']['style']=$style;
        $arr['custom_content']['date_id']=$content_id;
        $arr['custom_content']['uid']=$uid;
        $arr['custom_content']['success']=true;
        $arr['style']=$style==0||$style==3?0:$style;
        $arr['date_id']=$content_id;
        $arr['uid']=$uid;
        $arr['success']=true;
        if($content_id){
            $date_type=$this->getDateType($content_id);
            $arr['custom_content']['date_type']=$date_type;
            $arr['date_type']=$date_type;
        }
        $arr['custom_content']['id']=$id;
        $arr['id']=$id;
        $msg=json_encode($arr);
        $push=A('Addons://Baidupush/push');
        $push->__construct('','',$pc);
        if($pc==1){
            $push->pushMessage_android($msg,0,$token);
        }elseif($pc==2){
            $push->pushMessage_ios($msg,0,$token);
        }

    }
} 