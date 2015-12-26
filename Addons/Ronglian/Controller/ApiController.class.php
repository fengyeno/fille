<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/6/1
 * Time: 15:12
 */

namespace Addons\Ronglian\Controller;
use Api\Controller\BaseController;

class ApiController extends BaseController{
    /*配置信息*/
    public function config(){
        $map['status']=1;
        $map['name']='Ronglian';
        $config=M('addons')->where($map)->getField('config');
        if(!$config){
            $this->apiError(0,'未知的配置信息');
        }
        $arr=json_decode($config,true);
        if(!$arr['accountSid'] || !$arr['accountToken'] || !$arr['appId'] || !$arr['Rest_URL2'] || !$arr['Rest_URL1']){
            $this->apiError(0,'未知的配置信息');
        }
        $data['info']=$arr;
        $this->apiSuccess('success',$data);
    }
} 