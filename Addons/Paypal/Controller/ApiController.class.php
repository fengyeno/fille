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
        $arr=I('post.');
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
} 