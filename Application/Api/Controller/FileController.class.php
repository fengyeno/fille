<?php
namespace Api\Controller;
use Think\Controller;
class FileController extends Controller{
    public function get_video_lists(){
        $status=I('get.status');
        if($status){
            $map['status']=$status;
        }
        $count=M('user_video')->where($map)->count();
        $page=new \Think\Page($count,50);
        $list=M('user_video')->field(array('firsturl','path','pic'))
            ->where($map)
            ->limit($page->firstRow,$page->listRows)
            ->order('create_time desc')
            ->select();
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['path']=$v['firsturl'].$v['path'];
                $list[$key]['pic']=$v['firsturl'].$v['pic'];
                unset($list[$key]['firsturl']);
            }
            exit(json_encode($list));
        }else{
            exit(1);
        }
    }
    public function get_album_lists(){
        $status=I('get.status');
        if($status){
            $map['status']=$status;
        }
        $count=M('user_album')->where($map)->count();
        $page=new \Think\Page($count,50);
        $list=M('user_album')->field(array('firsturl','thumb_720','pic'))
            ->where($map)
            ->limit($page->firstRow,$page->listRows)
            ->order('create_time desc')
            ->select();
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['thumb_720']=$v['firsturl'].$v['thumb_720'];
                $list[$key]['pic']=$v['firsturl'].$v['pic'];
                unset($list[$key]['firsturl']);
            }
            exit(json_encode($list));
        }else{
            exit(1);
        }
    }
}