<?php
namespace Addons\Umeng\Controller;
use Admin\Controller\AddonsController;
class UmengController extends AddonsController{
	/* 列表 */
	public function index(){

        $list = D('Addons://Umeng/Umeng')->umengList();
		$this->assign('list',$list);
		$this->display(T('Addons://Umeng@Umeng/index'));
	}
    /*编辑*/
    public function edit(){
        $id=I('get.id');
        if(IS_POST){
            $arr=I('post.');
            $arr['pctype']=array_sum($arr['pctype']);
            $arr['createtime']=time();
            if($arr['pctype']<3 && $arr['pctype']>0){
                $push=A('Addons://Umeng/Push');
                $push->__construct('','',$arr['pctype']);
                $result=$push->push($arr,$arr['pctype']);
            }else{
                $push=A('Addons://Umeng/Push');
                $push->__construct('','',1);
                $result=$push->push($arr,1);
                $push1=A('Addons://Umeng/Push');
                $push1->__construct('','',2);
                $result=$push1->push($arr,2);
            }
            if($result){
                $arr['result']=1;
            }else{
                $arr['result']=0;
            }
            $res=D('Addons://Umeng/Umeng')->update($arr);
            if($res){
                $this->success('操作成功',addons_url('Umeng://Umeng/index'));
            }else{
                $this->error('操作失败');
            }
        }else{
            if($id){
                $info=D('Addons://Umeng/Umeng')->detail($id);
                $this->assign('info',$info);
            }
            $this->display(T('Addons://Umeng@Umeng/edit'));
        }
    }
    /*删除*/
    public function del(){
        $ids=I('ids');
        if(is_array($ids)){
            $id=implode(',',$ids);
            $map['status']=1;
            $map['id']=array('in',$id);
            $res=M('umeng_list')->where($map)->setField('status',-1);
        }else{
            $map['status']=1;
            $map['id']=$ids;
            $res=M('umeng_list')->where($map)->setField('status',-1);
        }
        if($res){
            $this->success('删除成功',addons_url('Umeng://Umeng/index'));
        }else{
            $this->error('删除失败');
        }
    }
    /*配置*/
    public function config(){
        $path='Addons/Umeng/src/config.txt';
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
            $this->display(T('Addons://Umeng@Umeng/config'));
        }
    }

}
