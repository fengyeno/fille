<?php// +----------------------------------------------------------------------// | OneThink [ WE CAN DO IT JUST THINK IT ]// +----------------------------------------------------------------------// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.// +----------------------------------------------------------------------// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>// +----------------------------------------------------------------------namespace Admin\Controller;/** * 后台用户控制器 * @author 麦当苗儿 <zuojiazi@vip.qq.com> */class CityController extends AdminController {    /**     * 城市管理首页     * @author 麦当苗儿 <zuojiazi@vip.qq.com>     */    public function index(){        $areaname=I('areaname');        if($areaname){            $map['areaname']=array('like',"%$areaname%");        }        $map['status']  =   1;        $count=M('city_area')->where($map)->count();        $page=new \Think\Page($count,10);        $list=M('city_area')->where($map)            ->order('arealevel,no')->limit($page->firstRow,$page->listRows)            ->select();        $this->assign('_page',$page->show());        $this->assign('_list', $list);        $this->meta_title = '城市信息';        $this->display();    }    public function edit(){        $id=I('get.id');        if(IS_POST){            $arr=I('post.');            if(!$arr['areaname']){                $this->error('参数不能为空');            }            switch($arr['arealevel']){                case 1:                    $arr['typename']='省';                    break;                case 2:                    $arr['typename']='市';                    break;                case 3:                    $arr['typename']='区';                    break;            }            if($id){                $arr['no']=$id;                $res=M('city_area')->save($arr);            }else{                $res=M('city_area')->add($arr);            }            if($res){                $this->success('操作成功',U('index'));            }else{                $this->error('操作失败');            }        }else{            if($id){                $info=M('city_area')->find($id);                $this->assign('info',$info);            }            $map['arealevel']=1;            $map['topno']=0;            $map['status']=1;            $sheng=M('city_area')->field(array('no','areaname'))->where($map)->order('no')->select();            $this->assign('sheng',$sheng);            $this->display();        }    }    /*获取子区列表*/    public function getChild(){        $topno=I('get.topno');        $map['topno']=intval($topno);        $map['status']=1;        $city=M('city_area')->field(array('no','areaname'))->where($map)->order('no')->select();        if(!empty($city)){            $data['status']=1;            $data['list']=$city;        }else{            $data['status']=0;        }        $this->ajaxReturn($data);    }    /*删除*/    public function del(){        $id=I('ids');        if(is_array($id)){            $ids=implode(',',$id);            $map['no']=array('in',$ids);            $map['status']=1;            $res=M('city_area')->where($map)->setField('status',-1);        }else{            $map['no']=$id;            $map['status']=1;            $res=M('city_area')->where($map)->setField('status',-1);        }        if($res){            $this->success('删除成功',U('index'));        }else{            $this->error('删除失败');        }    }}