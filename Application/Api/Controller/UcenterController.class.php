<?php

namespace Api\Controller;


class UcenterController extends BaseController{
    private $user;
    public function __construct(){
        parent::__construct();
        $this->user=I('get.uid');
        if(!$this->user){
            $this->apiError(0,'未知的用户');
        }
    }

    /*用户信息*/
    public function userInfo(){
        $info=$this->getUserInfo($this->uid);
        if($info){
            $data['info']=$info;
            $this->apiSuccess('success',$data);
        }else{
            $this->apiError(0,'未知的用户');
        }
    }

    /*加密修改密码*/
    public function changepwd1(){
        if(IS_POST){
            $arr=I('post.');
            if(!$arr['password'] || !$arr['token'] || !$arr['str']){
                $this->apiError(0,'参数错误');
            }
            if(!$this->checkEncrypt($arr['token'],$arr['str'])){
                $this->apiError(0,'非法请求');
            }
            $data['password']=think_ucenter_md5($arr['password'], UC_AUTH_KEY);
            $data['id']=$this->uid;
            $data['update_time']=time();
            $res=M('ucenter_member')->save($data);
            if($res['status']){
                $this->apiSuccess('success');
            }else{
                $this->apiError(0,'修改失败');
            }

        }

    }

    /*修改密码*/

    public function changepwd(){
        if(IS_POST){
            $arr=I('post.');
            if(!$arr['oldpwd'] || !$arr['password']){
                $this->apiError(0,'参数错误');
            }

            $user=new UserApi;
            $data['password']=$arr['password'];
            $res=$user->updateInfo($this->uid,$arr['oldpwd'],$data);
            if($res['status']){
                $this->apiSuccess('success');
            }else{
                $this->apiError(0,'修改失败');
            }
        }
    }

    /*修改个人信息*/
    public function updateInfo(){
        if(IS_POST){
            $arr=I('post.');
            $password=$arr['password'];
            if(!$password){
                $this->apiError(0,'参数错误');
            }
            $user=new UserApi;
            unset($arr['password']);
            $data=$arr;
            if(empty($data)){
                $this->apiError(0,'非法请求');
            }
            $res=$user->updateInfo($this->uid,$password,$data);
            if($res['status']){
                S('userinfo_'.$this->uid,null);
                $this->apiSuccess('success');
            }else{
                $this->apiError(0,'修改失败');
            }
        }
    }

    /*上传头像*/

    public function headimg(){
        $info=$this->upload();
        if($info['path']){
            $data['headimg']=$info['path'];
            $res=M('member')->where(array('uid'=>$this->uid))->save($data);
            if($res){
                S('userinfo_'.$this->uid,null);
                $this->apiSuccess('success');
            }
        }
        $this->apiError(0,'上传失败');
    }
    /*检测视频认证*/
    protected function checkVideo($uid){
        $map['uid']=$uid;
        $isVideo=M('member')->where($map)->getField('video');
        if($isVideo){
            return true;
        }
        $map['status']=array('in','1,2');
        $isUpVideo=M('user_video')->where($map)->getField('id');
        if($isUpVideo){
            /*已经上传了*/
            return false;
        }else{
            return true;
        }
    }
    /*上传视频*/
    public function video(){
        if(!$this->checkVideo($this->uid)){
            $this->apiError(0,'请等待视频认证才能上传视频');
        }
        $info=$this->uploadFile();
        if($info['path']){
            $data['path']=$info['path'];
            $this->apiSuccess('success',$data);
        }
        $this->apiError(0,'上传失败');
    }

    /*上传相册*/
    public function album(){
        $info=$this->upload();
        if($info['path']){
            $data['path']=$info['path'];
            crop_image($info['path']);
            crop_image($info['path'],360,270);
            $this->apiSuccess('success',$data);
        }
        $this->apiError(0,'上传失败');
    }
    /*存储图片*/
    public function saveImage(){
//        $input=file_get_contents("php://input");
//        if(!$input){
//            $this->apiError(0,'非法请求');
//        }
//        $input=substr($input,strpos($input,'{'));
//        $arr=json_decode($input,true);
//        $data=$this->checkData($arr);
//        if(!$data){
//            $this->apiError(0,'非法请求');
//        }
//        $res=M('user_album')->addAll($data);
        $arr=I('post.');
        if(!$arr['path'] || !$arr['type']){
            $this->apiError(0,'非法请求');
        }
        if(!$arr['firsturl']){
            $arr['firsturl']=$this->getUpUrl();
        }
        $arr['pic']=$arr['path'];
//        $arr['firsturl']=$v['firsturl'];
//        $arr['type']=$v['type'];
        $arr['uid']=$this->uid;
        $arr['thumb_720']=crop_path($arr['path'],720,540);
        $arr['thumb_360']=crop_path($arr['path'],360,270);
        $arr['create_time']=time();
        $arr['update_time']=time();
        $arr['status']=2;
        $res=M('user_album')->add($arr);
        if($res){
            $this->apiSuccess('success');
        }else{
            $this->apiError(0,'存储失败,请重试');
        }
    }
    /*存储视频*/
    public function saveVideo(){
//        $input=file_get_contents("php://input");
//        if(!$input){
//            $this->apiError(0,'非法请求');
//        }
//        $input=substr($input,strpos($input,'{'));
//        $arr=json_decode($input,true);
//        $data=$this->checkData($arr,2);
//        if(!$data){
//            $this->apiError(0,'非法请求');
//        }
//        $res=M('user_video')->addAll($data);
        $arr=I('post.');
        if(!$arr['path']){
            $this->apiError(0,'非法请求');
        }
        if(!$arr['firsturl']){
            $arr['firsturl']=$this->getUpUrl();
        }
        $arr['uid']=$this->uid;
        $arr['create_time']=time();
        $arr['update_time']=time();
        $arr['status']=2;
        $res=M('user_video')->add($arr);
        if($res){
            $this->apiSuccess('success');
        }else{
            $this->apiError(0,'存储失败,请重试');
        }
    }
    /*检测数据*/
    protected function checkData($data,$type=1){
        if(empty($data)){
            return false;
        }
        foreach($data as $key=>$v){
            if($type==1){
                /*图片数据*/
                if($v['path'] && $v['firsturl'] && $v['type']){
                    $arr[$key]['pic']=$v['path'];
                    $arr[$key]['firsturl']=$v['firsturl'];
                    $arr[$key]['type']=$v['type'];
                    $arr[$key]['uid']=$this->uid;
                    $arr[$key]['thumb_720']=crop_path($v['path'],720,540);
                    $arr[$key]['thumb_360']=crop_path($v['path'],360,270);
                    $arr[$key]['create_time']=time();
                    $arr[$key]['update_time']=time();
                    $arr[$key]['status']=2;
                }else{
                    continue;
                }
            }elseif($type==2){
                /*视频数据*/
                if($v['path'] && $v['firsturl']){
                    $arr[$key]['pic']=$v['path'];
                    $arr[$key]['firsturl']=$v['firsturl'];
                    $arr[$key]['uid']=$this->uid;
                    $arr[$key]['create_time']=time();
                    $arr[$key]['update_time']=time();
                    $arr[$key]['status']=2;
                }else{
                    continue;
                }
            }
        }
        return $arr;
    }
    /*我的相册*/
    public function myAlbum(){
        $type=I('get.type');
        $num=I('get.num');
        $num=$num?intval($num):20;
        $types=array(1,2);
        if($type && in_array($type,$types)){
            $map['type']=$type;
        }
        $map['status']=array('neq',-1);
        $map['uid']=$this->uid;
        $field=array('id','type','pic','firsturl','thumb_360','is_top','status');
        $count=M('user_album')->field($field)->where($map)->count();
        $page=new \Think\Page($count,$num);
        $list=M('user_album')->field($field)->where($map)
            ->order('is_top desc,create_time desc')
            ->limit($page->firstRow,$page->listRows)
            ->select();
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['path']=$v['firsturl'].$v['pic'];
                $list[$key]['thumb_360']=$v['firsturl'].$v['thumb_360'];
                unset($list[$key]['pic']);
                unset($list[$key]['firsturl']);
                unset($list[$key]['thumb_360']);
            }
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }else{
            $this->apiError(0,'未查找到数据');
        }
    }
    /*我的视频*/
    public function myVideo(){
        $num=I('get.num');
        $num=$num?intval($num):20;
        $map['uid']=$this->uid;
        $map['status']=array('neq',-1);
        $field=array('id','path','firsturl','status');
        $count=M('user_video')->field($field)->where($map)->count();
        $page=new \Think\Page($count,$num);
        $list=M('user_video')->field($field)->where($map)
            ->order('create_time desc')
            ->limit($page->firstRow,$page->listRows)
            ->select();
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['path']=$v['firsturl'].$v['path'];
                unset($list[$key]['firsturl']);
            }
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }else{
            $this->apiError(0,'未查找到数据');
        }
    }
    /*删除我的照片*/
    public function delAlbum(){
        $id=I('get.id');
        if(!$id){
            $this->apiError(0,'未知的照片');
        }
        $map['uid']=$this->uid;
        $map['id']=array('in',$id);
        $arr['update_time']=time();
        $arr['status']=-1;
        $res=M('user_album')->where($map)->save($arr);
        if($res){
            $this->apiSuccess('success');
        }else{
            $this->apiError(0,'删除失败,请重试');
        }
    }
    /*删除我的视频*/
    public function delVideo(){
        $id=I('get.id');
        if(!$id){
            $this->apiError(0,'未知的视频');
        }
        $map['uid']=$this->uid;
        $map['id']=array('in',$id);
        $arr['update_time']=time();
        $arr['status']=-1;
        $res=M('user_video')->where($map)->save($arr);
        if($res){
            $this->apiSuccess('success');
        }else{
            $this->apiError(0,'删除失败,请重试');
        }
    }
    /*访问用户中心*/
    public function oneUserInfo(){
        $uid=I('get.uid');
        if(!$uid){
            $this->apiError(0,'未知的用户');
        }
        $self=$this->getUserInfo($this->uid);
        if($self['sex']==0){
            $this->apiError(0,'请设置自己的性别');
        }
        $info=$this->getUserInfo($uid);
        if(!$info){
            $this->apiError(0,'未查找到数据,换个人看看');
        }
        if($self['sex']==$info['sex']){
            $this->apiError(0,'同性别就不要看了,O(∩_∩)O~');
        }
        if(!$this->compareVip($this->uid,$uid)){
            /*对方等级比你高*/
            $info['phone']='VIP等级不够';
        }else{
            /*检测付费*/
            if($info['phoneout']==1 && !$this->checkPayPhone($uid)){
                $info['phone']='需付费查看';
            }
        }
        $data['info']=$info;
        $this->apiSuccess('success',$data);
    }
    /*访问用户相册*/
    public function oneUserAlbum(){
        $uid=I('get.uid');
        if(!$uid){
            $this->apiError(0,'未知的用户');
        }
        $type=I('get.type');
        $num=I('get.num');
        $num=$num?intval($num):20;
        $types=array(1,2);
        if($type && in_array($type,$types)){
            $map['type']=$type;
            if($type==2){
                if(!$this->compareVip($this->uid,$uid)){
                    $this->apiError(0,'vip等级不够');
                }
            }
        }else{
            if(!$this->compareVip($this->uid,$uid)){
                $map['type']=1;
            }
        }
        $map['status']=1;
        $map['uid']=$uid;
        $field=array('id','type','pic','firsturl','thumb_360','is_top');
        $count=M('user_album')->field($field)->where($map)->count();
        $page=new \Think\Page($count,$num);
        $list=M('user_album')->field($field)->where($map)
            ->order('is_top desc,create_time desc')
            ->limit($page->firstRow,$page->listRows)
            ->select();
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['path']=$v['firsturl'].$v['pic'];
                $list[$key]['thumb_360']=$v['firsturl'].$v['thumb_360'];
                unset($list[$key]['pic']);
                unset($list[$key]['firsturl']);
                unset($list[$key]['thumb_360']);
            }
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }else{
            $this->apiError(0,'未查找到数据');
        }
    }
    /*访问用户视频*/
    public function oneUserVideo(){
        $uid=I('get.uid');
        if(!$uid){
            $this->apiError(0,'未知的用户');
        }
        $num=I('get.num');
        $num=$num?intval($num):20;
        $map['uid']=$uid;
        $map['status']=1;
        $field=array('id','path','firsturl');
        $count=M('user_video')->field($field)->where($map)->count();
        $page=new \Think\Page($count,$num);
        $list=M('user_video')->field($field)->where($map)
            ->order('create_time desc')
            ->limit($page->firstRow,$page->listRows)
            ->select();
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['path']=$v['firsturl'].$v['path'];
                unset($list[$key]['firsturl']);
            }
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }else{
            $this->apiError(0,'未查找到数据');
        }
    }
    /*查看用户手机*/
    public function getUserPhone(){
        $uid=I('get.uid');
        if(!$uid){
            $this->apiError(0,'未知的用户');
        }
        $map['uid']=$uid;
        $field=array('phone','phoneout');
        $phone=M('member')->field($field)->where($map)->find();
        if(!$this->compareVip($this->uid,$uid)){
            /*对方等级比你高*/
            $this->apiError(0,'对方VIP等级较高');
        }
        if($phone['phoneout']==1){
            /*手机公开*/
            $data['phone']=$phone['phone'];
            $this->apiSuccess('success',$data);
        }
        /*不公开*/
        if(!$this->checkDate($uid)){
            /*未约会*/
            if(!$this->checkPayPhone($uid)){
                /*未付费*/
                $data['coin']=C('USER_WATCH_PHONE');
                $this->apiError(0,'请付费查看,需'.$data['coin'].'金币',$data);
            }
        }


        $data['phone']=$phone['phone'];
        $this->apiSuccess('success',$data);
    }
}