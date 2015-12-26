<?php
namespace Addons\Baidupush\Controller;
use Admin\Controller\AddonsController;
class BaiduController extends AddonsController{
	/* 列表 */
	public function config(){
        $path="Addons/Baidupush/ext/config.config";
        if(IS_POST){
            $arr=I('post.');
            $str=json_encode($arr);
            $fp=fopen($path,'w+');
            fwrite($fp,$str);
            fclose($fp);
            $this->success('操作成功',addons_url('Baidupush://Baidu/config'));
        }else{
            $fp=fopen($path,'r');
            $str=fread($fp,1024);
            fclose($fp);
            if($str){
                $arr=json_decode($str,true);
                $this->assign('config',$arr);
            }
            $this->display(T('Addons://Baidupush@Baidu/config'));
        }
	}
    /*推送列表*/
    public function lists(){
        $map['status']=1;
        $map['type']=1;
        $count=M('baidu_msg')->where($map)->count();
        $page=new \Think\Page($count,10);
        $list=M('baidu_msg')->where($map)->limit($page->firstRow,$page->listRows)->order('create_time desc')->select();
        if($list){
            foreach($list as $key=>$v){
                if(strlen($v['msg'])>15){
                    $list[$key]['msg']=substr($v['msg'],0,15).'···';
                }
            }
        }
        $this->assign('_page',$page->show());
        $this->assign('_list',$list);
        $this->display(T('Addons://Baidupush@Baidu/index'));
    }
    public function edit(){
        if(IS_POST){
            $arr=I('post.');
            if(!$arr['msg']){
                $this->error('内容不能为空');
            }
            $arr['status']=1;
            $arr['uid']=0;
            $arr['type']=1;
            $arr['create_time']=time();
            $res=M('baidu_msg')->add($arr);
            if($res){
                $message = array (
                    // 消息的标题.
                    'title' => $arr['msg'],
                    // 消息内容
                    'description' =>  $arr['msg']
                );
                $message['custom_content']['style']=0;
                $message['custom_content']['date_id']=0;
                $message['custom_content']['uid']=0;
                $message['custom_content']['success']=true;
                $message['custom_content']['id']=$res;
                $message['style']=0;
                $message['date_id']=0;
                $message['uid']=0;
                $message['id']=$res;
                $message['success']=true;

                $push=A('Addons://Baidupush/push');
                $push->__construct('','',1);
                $push->pushMessage_all($message);
                $push->__construct('','',2);
                $push->pushMessage_all($message);
                $this->success('推送成功',addons_url("Baidupush://Baidu/lists"));
            }else{
                $this->error('推送失败');
            }
        }else{
            $this->display(T('Addons://Baidupush@Baidu/edit'));
        }
    }

}
