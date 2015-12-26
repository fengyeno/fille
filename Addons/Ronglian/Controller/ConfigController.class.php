<?php
namespace Addons\Ronglian\Controller;
use Admin\Controller\AddonsController;
class ConfigController extends AddonsController{
    /*配置*/
    public function config(){
        $path='Addons/Ronglian/src/config.txt';
        if(IS_POST){
            $arr=I('post.');
            $str=json_encode($arr);
            $fp=fopen($path,'w');
            $res=fwrite($fp,$str);
            fclose($fp);
            if($res){
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }
        }else{
            $fp=fopen($path,'r');
            $str=fread($fp,1024);
            fclose($fp);
            if($str){
                $info=json_decode($str,true);
                $this->assign('info',$info);
            }
            $this->display(T('Addons://Ronglian@Ronglian/config'));
        }
    }

}
