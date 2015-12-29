<?php

namespace Api\Controller;

use User\Api\UserApi;

class UserController extends BaseController{

    public function getRonglian(){
        if($this->checkBigLevel($this->uid)){
            /*注册容联*/
            $info=M('ucenter_member')->find($this->uid);
            if(!$info['subaccountsid']){
                $count=$this->newRonglian($info['username']);
                if($count['status']=='000000'){
                    $count['id']=$this->uid;
                    unset($count['status']);
                    M('ucenter_member')->save($count);
                }
                $arr['subAccountSid']=$count['subaccountsid'];
                $arr['subToken']=$count['subtoken'];
                $arr['voipAccount']=$count['voipaccount'];
                $arr['voipPwd']=$count['voippwd'];
            }else{
                $arr['subAccountSid']=$info['subaccountsid'];
                $arr['subToken']=$info['subtoken'];
                $arr['voipAccount']=$info['voipaccount'];
                $arr['voipPwd']=$info['voippwd'];
            }
            $this->apiSuccess("success",$arr);
        }else{
            $this->apiError(0,'不是最高等级');
        }
    }
    /*用户信息*/
    public function userInfo(){
        $info=$this->getUserInfo($this->uid);
        if($info){
            if($info['vip']){
                $info['vipinfo']=$this->getVipInfo($info['vip']);
            }
            $info['no']=$this->id2no($this->uid);
            $data['info']=$info;
            $this->apiSuccess('success',$data);
        }else{
            $this->apiError(0,'未知的用户');
        }
    }

    /*加密修改密码*/
    public function changepwd1(){
//        if(IS_POST){
            $arr=I('post.');
            if(!$arr['password'] || !$arr['token'] || !$arr['str'] || !$arr['username']){
                $this->apiError(0,'参数错误');
            }
            if(!$this->checkEncrypt($arr['token'],$arr['str'],$arr['username'])){
                $this->apiError(0,'非法请求');
            }
            $uid=M('ucenter_member')->where(array('username'=>$arr['username']))->getField('id');
            if(!$uid){
                $this->apiError(0,'未知的用户');
            }
            $data['password']=think_ucenter_md51($arr['password'], C('DATA_AUTH_KEY'));
            $data['id']=$uid;
            $data['update_time']=time();
            $res=M('ucenter_member')->save($data);


//        }
        if($res){
            $this->apiSuccess('success');
        }else{
            $this->apiError(0,'修改失败');
        }

    }

    /*修改密码*/

    public function changepwd(){
//        if(IS_POST){
            $arr=I('post.');
            if(!$arr['oldpwd'] || !$arr['password']){
                $this->apiError(0,'参数错误');
            }

            $user=new UserApi;
            $data['password']=$arr['password'];
            $res=$user->updateInfo($this->uid,$arr['oldpwd'],$data);

//        }
        if($res['status']){
            $this->apiSuccess('success');
        }else{
            $this->apiError(0,'修改失败');
        }
    }

    /*修改个人信息*/
    public function updateInfo(){
        $arr=I('post.');
        unset($arr['password']);
//        $password=$arr['password'];
//        if(!$arr){
//            $this->apiError(0,'参数错误');
//        }
        $data=$arr;
        if(empty($data)){
            $this->apiError(0,'非法请求');
        }
//        $password1=M('ucenter_member')->where(array('id'=>$this->uid))->getField('password');
//        if(!think_ucenter_md51($password,C('DATA_AUTH_KEY')) === $password1){
//            $this->apiError(0,'密码错误');
//        }
        $data['uid']=$this->uid;
        $res=M('member')->save($data);

        if($res!==false){
            S('userinfo_'.$this->uid,null);
            $this->apiSuccess('success');
        }else{
            $this->apiError(0,'修改失败');
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
        $arr['is_sign']=1;
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
            $path=$this->changeVideo($info['path']);
            if(!$path){
                $this->apiError(0,'上传失败');
            }
            $pic=$this->cropVideo($info['path']);
            $data['path']=$path;
            $data['pic']=$pic?$pic:'';
            $this->apiSuccess('success',$data);
        }
        $this->apiError(0,'上传失败');
    }
    protected function cropVideo($video){
        $first=substr($video,0,1);
        if($first=="/"){
            $video=substr($video,1);
        }
        if(!is_file($video)){
            return false;
        }
        set_time_limit(0);
        ini_set ('memory_limit', '600M');
        if(!function_exists("exec")){
            return false;
        }
        $img=$this->newpic($video);
        $str="ffmpeg -i $video -y -f image2 -ss 00:00:01 -s 360x270 $img";
        exec($str);
        cover_image1($img);
        return "/".$img;
    }
    protected function changeVideo($video){
        $first=substr($video,0,1);
        if($first=="/"){
            $video=substr($video,1);
        }
        if(!is_file($video)){
            return false;
        }
        set_time_limit(0);
        ini_set ('memory_limit', '600M');
        if(!function_exists("exec")){
            return false;
        }

//        $home=str_replace("Application/Api/Controller","",__DIR__);

        $new_video=$this->newVideo($video);
        $new_str="ffmpeg -i $video -vcodec libx264 -vpre fast -vpre baseline $new_video";
        exec($new_str);
        return "/".$new_video;
    }
    protected function newVideo($path){
        $new_video=substr($path,0,strrpos($path,"/")+1).time().".mp4";
        if(is_file($new_video)){
            $this->newVideo($path);
        }
        return $new_video;
    }
    protected function newpic($path){
        $new_video=substr($path,0,strrpos($path,"/")+1).time().".jpg";
        if(is_file($new_video)){
            $this->newVideo($path);
        }
        return $new_video;
    }
    /*上传相册*/
    public function album(){
        $info=$this->upload();
        if($info['path']){
            $data['path']=$info['path'];
                crop_image($info['path']);
                crop_image($info['path'],360,270);
//                crop_secret($info['path'],720,540);
                crop_secret($info['path'],360,270);
//                $data['secret_pic']=$data['thumb_360'];
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
        if($arr['type']==2){
            $arr['secret_pic']=crop_secret_path($arr['path'],360,270);;
        }
        $arr['create_time']=time();
        $arr['update_time']=time();
        $arr['status']=2;//$arr['type']==1?2:1;
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
//        $arr=I('post.');
        $arr['path']=I('path');
        $arr['pic']=I('pic');
        $arr['firsturl']=I('firsturl');
        if(!$arr['path'] || !$arr['pic']){
            $this->apiError(0,'非法请求');
        }
        if(!$arr['firsturl']){
            $arr['firsturl']=$this->getUpUrl();
        }
        $arr['uid']=$this->uid;
        $arr['create_time']=time();
        $arr['update_time']=time();
        $arr['status']=2;
        $map['uid']=$this->uid;
        $map['is_sign']=1;
        $map['status']=array('in','1,2');
        $count=M('user_video')->where($map)->count();
        if($count<1){
            $arr['is_sign']=1;
        }
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
//                unset($list[$key]['thumb_360']);
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
        $map['is_sign']=array('neq',1);
        $field=array('id','path','firsturl','status','pic');
        $count=M('user_video')->field($field)->where($map)->count();
        $page=new \Think\Page($count,$num);
        $list=M('user_video')->field($field)->where($map)
            ->order('create_time desc')
            ->limit($page->firstRow,$page->listRows)
            ->select();
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['path']=$v['firsturl'].$v['path'];
                $list[$key]['pic']=$v['firsturl'].$v['pic'];
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
        if($uid==$this->uid){
            if($self['vip']){
                $self['vipInfo']=$this->getVipInfo($self['vip']);
            }
            $data['info']=$self;
            $this->apiSuccess('success',$data);
        }
        $info=$this->getUserInfo($uid);
        if(!$info){
            $this->apiError(0,'未查找到数据,换个人看看');
        }
        if($self['sex']==$info['sex']){
            $this->apiError(0,'同性别就不要看了,O(∩_∩)O~');
        }
        if($info['phoneout']!=1){
            /*手机不公开*/
            if(!$this->compareVip($this->uid,$uid)){
                /*对方等级比你高*/
                $info['phone_see']=false;
                $info['phone']='VIP等级不够';
            }else{
                /*检测付费*/
                if(($this->checkDate($uid) || $this->checkPayPhone($uid))){
                    $info['phone_see']=true;
                }else{
                    $info['phone_see']=false;
                    $info['phone']='需付费查看';
                }
//            if($info['phoneout']==1 && !$this->checkPayPhone($uid)){
//
//            }
            }
        }else{
            $info['phone_see']=true;
            $info['phone']='用户未开放手机号，请通过意向进行联系!';
        }

        if($info['vip']){
            $info['vipInfo']=$this->getVipInfo($info['vip']);
        }
        $info['is_big']=$this->checkBigLevel($uid);
        $info['no']=$this->id2no($uid);
        $info['is_follow']=$this->checkFollow($uid);
        $data['info']=$info;
        $this->apiSuccess('success',$data);
    }
    /*voipAccount,subAccountSid查询用户*/
    public function vgetUser(){
        $voipAccount=I('voipAccount');
        $subAccountSid=I('subAccountSid');
        if(!$voipAccount && !$subAccountSid){
            $this->apiError(0,'参数不能为空');
        }
        if($voipAccount){
            $map['u.voipAccount']=$voipAccount;
        }elseif($subAccountSid){
            $map['u.subAccountSid']=$subAccountSid;
        }
        $map['u.status']=1;
        $pre=C('DB_PREFIX');
        $info=M()->field(array('m.uid','headimg','nickname'))
            ->table($pre."ucenter_member u")
            ->join($pre."member m on u.id=m.uid")
            ->where($map)
            ->find();
        if($info){
            $data['info']=$info;
            $this->apiSuccess("success",$data);
        }else{
            $this->apiError(0,'未查找到数据');
        }
    }
    /*访问用户相册*/
    public function oneUserAlbum(){
        $uid=I('get.uid');
        if(!$uid){
            $this->apiError(0,'未知的用户');
        }
//        $type=I('get.type');
        $num=I('get.num');
        $num=$num?intval($num):6;

        $map['status']=1;
        $map['uid']=$uid;

        $see=$this->compareVip($this->uid,$uid);
        /*相册*/
        $field=array('id','type','pic','firsturl','thumb_360','is_top','secret_pic');
        $count=M('user_album')->field($field)->where($map)->count();
        $page=new \Think\Page($count,$num);
        $album=M('user_album')->field($field)->where($map)
            ->order('is_top desc,create_time desc')
            ->limit($page->firstRow,$page->listRows)
            ->select();
        if($album){
            foreach($album as $key=>$v){
                $album[$key]['style']='album';
                if(!$see && $v['type']==2){
                    $album[$key]['see']=$see;
                    $album[$key]['path']=$v['secret_pic']?$v['firsturl'].$v['secret_pic']:'';
                    $album[$key]['thumb_360']=$v['secret_pic']?$v['firsturl'].$v['secret_pic']:'';;
                }else{
                    $album[$key]['see']=true;
                    $album[$key]['path']=$v['pic']?$v['firsturl'].$v['pic']:'';
                    $album[$key]['thumb_360']=$v['firsturl'].$v['thumb_360'];
//                    $album[$key]['secret_pic']=$v['firsturl'].$v['thumb_360'];
                }
//                $album[$key]['path']=$v['firsturl'].$v['pic'];
//                $album[$key]['thumb_360']=$v['firsturl'].$v['thumb_360'];
                unset($album[$key]['pic']);
                unset($album[$key]['firsturl']);
//                unset($album[$key]['thumb_360']);
            }
        }
//        print_r($album);
        /*视频*/
        $map['is_sign']=0;
        $field=array('id','path','firsturl','pic');
        $count=M('user_video')->field($field)->where($map)->count();
        $page=new \Think\Page($count,$num);
        $video=M('user_video')->field($field)->where($map)
            ->order('create_time desc')
            ->limit($page->firstRow,$page->listRows)
            ->select();
//        echo M()->getLastSql();die;
        if($video){
            foreach($video as $key=>$v){
                $video[$key]['style']='video';
//                $video[$key]['see']=$see;//

//                $video[$key]['thumb_360']=$v['firsturl'].$v['pic'];
                if($uid==$this->uid){
                    $video[$key]['see']=true;
                    $video[$key]['video_path']=$v['firsturl'].$v['path'];
                }else{
                    if($see && $this->checkPay('video',$v['id'])){
                        $video[$key]['see']=true;
                        $video[$key]['video_path']=$v['firsturl'].$v['path'];
                    }else{
                        $video[$key]['see']=false;
                        $video[$key]['video_path']='';
                    }
                }
                $video[$key]['path']=$v['firsturl'].$v['pic'];
                $video[$key]['thumb_360']=$v['firsturl'].$v['pic'];
                unset($video[$key]['firsturl']);
                unset($video[$key]['pic']);
            }
        }
//        print_r($video);
        if($album && $video){
            $list=array_merge($album,$video);
//            shuffle($list);
//            print_r($list);
        }else{
            $list=$album?$album:$video;
        }
        if($list){
            $data['video_coin']=C('USER_WATCH_VIDEO');
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }else{
            $this->apiError(0,'未查找到数据');
        }
    }
    /*查看视频*/
    public function getVideo(){
        $id=I('get.id');
        if(!$id){
            $this->apiError(0,'未知的的视频');
        }
        $map['status']=1;
        $map['id']=$id;
        $field=array('id','path','firsturl','pic','uid');
        $info=M('user_video')->field($field)->where($map)->find();
        if(!$info){
            $this->apiError(0,'视频不存在或已被删除');
        }
        if(!$this->compareVip($this->uid,$info['uid'])){
            $this->apiError(0,'您的等级不够');
        }
        if(!$this->checkPay('video',$id)){
            /*未付费*/
            $data['coin']=C('USER_WATCH_VIDEO');
            if(!$this->checkUserCoin($data['coin'])){
                $this->apiError(0,'观看视频需要'.$data['coin'].'信用豆，您的信用豆不够了');
            }
            /*扣除金币*/
            $videoCoin=$data['coin']*C('USER_VIDEO_SCALE')/100;
            if($data['coin']>0){
                $map1['uid']=$this->uid;
                $map1['type']='video';
                $map1['content_id']=$id;
                $map1['status']=1;
                $map1['foruid']=$info['uid'];
                $map1['coin']=$data['coin'];
                $map1['create_time']=time();
                $map1['update_time']=time();
                $order=M('user_order_coin')->add($map1);

                $order=$order?$order:0;
                /*付金币*/
                $this->deCoin($data['coin'],$order,$info['uid'],'video');
                /*获取分成*/
                if($videoCoin>0){
                    $this->addCoin($videoCoin,$info['uid'],$order,$this->uid,'video');
                }
            }
        }
        $data['info']=$info;
        $this->apiSuccess('success',$data);
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
    /*手机付费金额*/
    public function getPhoneCoin(){
        $data['coin']=C('USER_WATCH_PHONE');
        if(!$data['coin']){
            $this->apiError(0,'系统错误');
        }
        $this->apiSuccess('success',$data);
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
        $ondate=$this->checkDate($uid);
        if($ondate){
            $data['phone']=$phone['phone'];
            $this->apiSuccess('success',$data);
        }
        if($phone['phoneout']==1){
            /*手机公开*/
            $data['phone']="用户未开放手机号";
            $this->apiError(0,'用户未开放手机号，请通过意向进行联系！');
        }
        if(!$this->compareVip($this->uid,$uid)){
            /*对方等级比你高*/
            $this->apiError(0,'对方VIP等级较高');
        }
        /*不公开*/
        if(!$ondate){
            /*未约会*/
            if(!$this->checkPayPhone($uid)){
                /*未付费*/
                $data['coin']=C('USER_WATCH_PHONE');
                if(!$this->checkUserCoin($data['coin'])){
                    $this->apiError(0,'查看手机需要'.$data['coin'].'信用豆，您的信用豆不够了');
                }
                /*扣除金币*/
                $phoneCoin=$data['coin']*C('USER_PHONE_SCALE')/100;
                if($data['coin']>0){
                    $map1['uid']=$this->uid;
                    $map1['type']='phone';
                    $map1['content_id']=0;
                    $map1['status']=1;
                    $map1['foruid']=$uid;
                    $map1['coin']=$data['coin'];
                    $map1['create_time']=time();
                    $map1['update_time']=time();
                    $order=M('user_order_coin')->add($map1);
                }
                $order=$order?$order:0;
                /*付金币*/
                $this->deCoin($data['coin'],$order,$uid,'phone');
                /*获取分成*/
                if($phoneCoin>0){
                    $this->addCoin($phoneCoin,$uid,$order,$this->uid,'phone');
                }
//                $this->apiError(0,'请付费查看,需'.$data['coin'].'金币',$data);
            }
        }


        $data['phone']=$phone['phone'];
        $this->apiSuccess('success',$data);
    }
    /*访问用户约会列表*/
    public function dateList(){
        $uid=I('get.uid');
        if(!$uid){
            $this->apiError(0,'未知的用户');
        }
        $map['uid']=$uid;
        $map['type']=1;
        $map['status']=array('in','1,2');
        $map['date_time']=array('egt',date("Y-m-d H:i:s"));
        $field=array('id','cid','place','redbag_type','redbag','date_time');
        $count=M('user_date')->field($field)->where($map)->count();
        $page=new \Think\Page($count,10);
        $list=M('user_date')->field($field)->where($map)
            ->order('create_time desc')
            ->limit($page->firstRow,$page->listRows)
            ->select();
        if(empty($list)){
            $this->apiError(0,'该会员还没发布意向!');
        }else{
            foreach($list as $key=>$v){
                $cate=$this->getCateInfo($v['cid']);
                if($v['redbag_type']==1){
                    $str="愿付酬金".$v['redbag']."元";
                    $list[$key]['title']=$str.",需要".$cate['title'];
                }else{
                    $str="需要酬金".$v['redbag']."元";
                    $list[$key]['title']=$str.",提供".$cate['title'];
                }
                $list[$key]['style']=1;

                $list[$key]['is_sign']=$this->checkSign($this->uid,$v['id']);
            }
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }
    }
    /*上传通讯录*/
    public function up_adlist(){
//        if(IS_POST){
        $str=I('post.str');
//            $str=' [{"name":"test","phone":123456789},{"name":"test1","phone":123456789}]';
        if(!$str){
            $this->apiError(0,'未知的通讯录');
        }
        $arr=json_decode($str,true);
        if($arr){
            M('adlist')->where(array('status'=>1,'uid'=>$this->uid))->delete();//setField('status',-1);
            $search=array(" ","+86","-");
            $replace=array("","","");
            foreach($arr as $key=>$v){
                $arr[$key]['phone']=str_replace($search,$replace,$v['phone']);
                $arr[$key]['uid']=$this->uid;
                $arr[$key]['status']=1;
                $arr[$key]['create_time']=time();
            }
            $res=M('adlist')->addAll($arr);
        }
//        }
        if($res){
            $this->apiSuccess('上传成功');
        }else{
            $this->apiError(0,'上传失败');
        }
    }
    /*添加关注*/
    public function follow(){
        $uid=I('get.uid');
        if(!$uid){
            $this->apiError(0,'未知的会员');
        }
        if($this->checkFollow($uid)){
            $this->apiError(0,'已关注');
        }
        $map['uid']=$this->uid;
        $map['fuid']=$uid;
        $map['create_time']=time();
        $res=M('follow')->add($map);
        if($res){
            $this->apiSuccess('关注成功');
        }else{
            $this->apiError(0,'关注失败');
        }
    }
    /*取消关注*/
    public function delFollow(){
        $uid=I('get.uid');
        if(!$uid){
            $this->apiError(0,'未知的会员');
        }
        if(!$this->checkFollow($uid)){
            $this->apiError(0,'未关注');
        }
        $map['uid']=$this->uid;
        $map['fuid']=$uid;
        $res=M('follow')->where($map)->delete();
        if($res){
            $this->apiSuccess('成功取消关注');
        }else{
            $this->apiError(0,'取消关注失败');
        }
    }
    /*我的关注*/
    public function myFollow(){
        $map['uid']=$this->uid;
        $count=M('follow')->where($map)->count();
        $page=new \Think\Page($count,20);
        $list=M('follow')->field(array('fuid'))->where($map)
            ->limit($page->firstRow,$page->listRows)->select();
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['user']=$this->getUserInfo($v['fuid']);
                if($list[$key]['user']['vip']){
                    $list[$key]['vipInfo']=$this->getVipInfo($list[$key]['user']['vip']);
                }
            }
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }
        $this->apiError(0,'未查找到数据');
    }
    /*消费记录*/
    public function countList(){
        $style=I('get.style');
        $month=I('get.month');
        if($month && !preg_match("/^\d{4}-\d{2}$/",$month)){
            $this->apiError(0,"日期格式错误");
        }elseif($month){
            $first=strtotime(date($month.'-01'));
            $last=strtotime(date('Y-m-01', strtotime(date($month.'-01'))) . ' +1 month -1 day');
            $map['create_time']=array(array('elt',$last),array('egt',$first),'and');
        }

        $map['status']=1;

//        $map['type']=2;
        $map['uid']=$this->uid;
        $count=M('user_coin_log')->where($map)->count();
        $page=new \Think\Page($count,10);
        $fields=array('type','foruid','order_id','coin','style','create_time');
        $list=M('user_coin_log')->field($fields)->where($map)
            ->order('create_time desc')->limit($page->firstRow,$page->listRows)
            ->select();
        $map['type']=1;
        $in=M('user_coin_log')->where($map)->getField('sum(coin)');
        $map['type']=2;
        $out=M('user_coin_log')->where($map)->getField('sum(coin)');
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['typename']=$this->coinChangeType($v['style']);
                $list[$key]['create_time']=date("Y-m-d H:i:s",$v['create_time']);
                if($v['type']==1){
//                    $in+=$v['coin'];
                    $list[$key]['coin']="+".$v['coin'];
                }else{
//                    $out+=$v['coin'];
                    $list[$key]['coin']="-".$v['coin'];
                }
                if($v['foruid']){
                    $user=$this->getUserInfo($v['foruid']);
                    $arr['uid']=$user['uid'];
                    $arr['nickname']=$user['nickname'];
                    $arr['headimg']=$user['headimg'];
                    $list[$key]['foruser']=$arr;
                }
            }
        }
        if($list){
            $coin=M('member')->where(array('uid'=>$this->uid))->getField('coin');
            $data['in']=$in?$in:0;
            $data['out']=$out?$out:0;
            $data['coin']=$coin;
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }
        $this->apiError(0,'未查找到数据');
    }
    /*充值记录*/
    public function pay_coin_list(){
        $month=I('get.month');
        if($month && !preg_match("/^\d{4}-\d{2}$/",$month)){
            $this->apiError(0,"日期格式错误");
        }elseif($month){
            $first=strtotime(date($month.'-01'));
            $last=strtotime(date('Y-m-01', strtotime(date($month.'-01'))) . ' +1 month -1 day');
            $map['create_time']=array(array('elt',$last),array('egt',$first),'and');
        }
        $map['status']=2;

        $map['type']='coin';
        $map['uid']=$this->uid;
        $count=M('user_order_money')->where($map)->count();
        $page=new \Think\Page($count,10);
        $fields=array('type','money','coin','create_time');
        $list=M('user_order_money')->field($fields)->where($map)
            ->order('create_time desc')->limit($page->firstRow,$page->listRows)
            ->select();
        $money=0;
        $coin=0;
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['typename']=$this->coinChangeType($v['type']);
                $money+=$v['money'];
                $coin+=$v['coin'];
                $list[$key]['create_time']=date("Y-m-d H:i:s",$v['create_time']);
//                $list[$key]['nickname']=$this->getUserNickname($v['uid']);
            }
        }
        if($list){
            $data['money']=$money;
            $data['coin']=$coin;
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }
        $this->apiError(0,'未查找到数据');
    }
    /*金币变更类型*/
    protected function coinChangeType($type){
        switch($type){
            case 'video':
                $name="观看视频";
                break;
            case 'phone':
                $name="查看手机号码";
                break;
            case 'coin':
                $name="充值";
                break;
            case 'vip':
                $name="购买vip";
                break;
            case 'give':
                $name="赠送信用豆";
                break;
            case 'dategivef':
                $name="意向赠金冻结";
                break;
            case 'dategive':
                $name="获取意向赠金";
                break;
            case 'get':
                $name="获赠信用豆";
                break;
            case 'getmoney':
                $name="提现";
                break;
            case 'admin':
                $name="管理员操作";
                break;
            case 'datesys':
                $name="意向手续费";
                break;
            case 'im':
                $name="聊天";
                break;
            case 'refreezing':
                $name="解冻";
                break;
            case 'datefree':
                $name="意向解冻";
                break;
            case 'date':
                $name="意向冻结";
                break;
            default:
                break;
        }
        return $name;
    }
    /*推送消息*/
    public function get_push_list(){
        $num=I('get.num');
        $num=$num?$num:10;
        $map['status']=1;
        $map['uid']=array(array('eq',$this->uid),array('eq',0),'or');
        $count=M('baidu_msg')->where($map)->count();
        $page=new \Think\Page($count,$num);
        $list=M('baidu_msg')->where($map)->limit($page->firstRow,$page->listRows)->order('create_time desc')->select();
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['create_time']=date("Y-m-d H:i:s",$v['create_time']);
                if($v['content_id'] && $v['style']==0){
                    $list[$key]['date_type']=$this->getDateType($v['content_id']);
                }
            }
            $data['list']=$list;
//            print_r($data);
            $this->apiSuccess('success',$data);
        }else{
            $this->apiError(0,'未查找到数据');
        }
    }
    /*删除消息*/
    public function msg_del(){
        $id=I('id');
        if(!$id){
            $this->apiError(0,'未知的消息');
        }
        $map['uid']=$this->uid;
        $map['id']=array('in',$id);
        $res=M('baidu_msg')->where($map)->setField('status',-1);
        if($res!==false){
            $this->apiSuccess('删除成功');
        }else{
            $this->apiError(0,'删除失败');
        }
    }
    /*用户退出*/
    public function logOut(){
        $map['uid']=$this->uid;
        $res=M('baidu_user')->where($map)->delete();
        if($res!==false){
            $this->apiSuccess("success");
        }else{
            $this->apiError(0,'失败');
        }
    }
    /*确认消息*/
    public function onMsg(){
        $id=I('get.id');
        if(!$id){
            $this->apiError(0,'未知的消息');
        }
        $map['id']=$id;
        $map['uid']=$this->uid;
        $res=M('baidu_msg')->where($map)->setField('on_msg',1);
        if($res!==false){
            $this->apiSuccess('success');
        }else{
            $this->apiError(0,'确认失败');
        }
    }
}