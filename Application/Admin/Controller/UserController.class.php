<?php// +----------------------------------------------------------------------// | OneThink [ WE CAN DO IT JUST THINK IT ]// +----------------------------------------------------------------------// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.// +----------------------------------------------------------------------// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>// +----------------------------------------------------------------------namespace Admin\Controller;use User\Api\UserApi;/** * 后台用户控制器 * @author 麦当苗儿 <zuojiazi@vip.qq.com> */class UserController extends AdminController {    /**     * 用户管理首页     * @author 麦当苗儿 <zuojiazi@vip.qq.com>     */    public function index(){        $nickname       =   I('nickname');        $middle         =   I('middle');        $is_vip         =   I('is_vip');        $is_video       =   I('is_video');        $small_vip      =   I('small_vip');        $big_vip        =   I('big_vip');        $small_age      =   I('small_age');        $big_age        =   I('big_age');        $small_coin     =   I('small_coin');        $big_coin       =   I('big_coin');        $is_album       =   I('is_album');        $city           =   I('city');        $nowcity         =   I('nowcity');        if($is_vip==='0'){            $map['vip']=0;        }elseif($is_vip==1){            $map['vip']=array('gt',0);        }        if($is_video==='0'){            $map['video']=0;        }elseif($is_video==1){            $map['video']=1;        }        if($nowcity){            $map['nowcity']=array('like',"%$nowcity%");        }        if($city){            $cityno=M('city_area')->where(array('areaname'=>array('like',"&$city&"),'status'=>1))->order('arealevel desc')->getField('no');            if($cityno){                $map['city']=$cityno;            }        }        if($small_vip || $big_vip){            if($small_vip && $big_vip){                $vip_map['level']=array(array('egt',$small_vip),array('elt',$big_vip),'and');            }elseif($big_vip){                $vip_map['level']=array('elt',$big_vip);            }else{                $vip_map['level']=array('egt',$small_vip);            }            if($small_vip || $big_vip || ($small_vip && $big_vip && $small_vip<=$big_vip)){                $vip_map['status']=1;                $vips=M('user_vip')->where($vip_map)->getField('id',true);                $vips=implode(",",$vips);                $map['vip']=array('in',$vips);            }        }        if($small_age && $big_age){            $map['age']=array(array('egt',$small_age),array('elt',$big_age));        }elseif($small_age){            $map['age']=array('egt',$small_age);        }elseif($big_age){            $map['age']=array('elt',$big_age);        }        if($small_coin && $big_coin){            $map['coin']=array(array('egt',$small_coin),array('elt',$big_coin));        }elseif($small_coin){            $map['coin']=array('egt',$small_coin);        }elseif($big_coin){            $map['coin']=array('elt',$big_coin);        }        $map['status']  =   array('egt',0);        $map['uid']=array('neq',1);        if($is_album==='0'){            $users=M('user_album')->where(array('status'=>1))->group('uid')->getField('uid',true);            $users=implode(",",$users);            $map['uid']=array(array('not in',$users),array('neq',1),'and');        }elseif($is_album==1){            $users=M('user_album')->where(array('status'=>1))->group('uid')->getField('uid',true);            $users=implode(",",$users);            $map['uid']=array(array('in',$users),array('neq',1),'and');        }        if(is_numeric($nickname)){            $map['uid|nickname|phone']=   array(intval($nickname),array('like','%'.$nickname.'%'),'_multi'=>true);        }else{            $map['nickname']    =   array('like', '%'.(string)$nickname.'%');        }        if(is_numeric($middle)){            $map['middleman']=$middle;        }        $list   = $this->lists('Member', $map,'last_login_time desc');        int_to_string($list);//        $pre=C('DB_PREFIX');//        $fields=array('m.*','');//        $sql="select m.* from {$pre}member m LEFT JOIN {$pre}ucneter_member u on u.id=m.uid"        if($list){            foreach($list as $key=>$v){                $list[$key]['username']=M('ucenter_member')->where(array('id'=>$v['uid']))->getField('username');                if($v['middleman']){                    $list[$key]['middle']=M('middleuser')->where(array('id'=>$v['middleman']))->getField('name');                }                if($v['vip']){                    $list[$key]['vipInfo']=M('user_vip')->find($v['vip']);                }            }        }        $vip=M('user_vip')->where(array('status'=>1))->order('level')->select();        $this->assign('vip_list',$vip);        $this->assign('_list', $list);        $this->meta_title = '用户信息';        $this->display();    }    /**     * 会员详情     * @author tang     */    public function detail(){        $uid=I('get.uid');        if(IS_POST){            $arr=I('post.');            if(!$arr['nickname']){                $this->error("昵称不能为空");            }            unset($arr['coin']);            $arr['uid']=$uid;            $res=D('Member')->update($arr);            if(!$res){                $this->error("修改失败");            }else{                $this->success("修改成功",U('index'));            }        }else{            if(!$uid){                $this->error("未知的用户");            }            $info=D('member')->getUserInfo($uid);            $province=D('CityArea')->lists();            $city=D('CityArea')->getInfo($info['city']);            if($city){                $pro=D('CityArea')->getInfo($city['topno']);                $cityList=D('CityArea')->lists($city['topno']);                $this->assign("pro",$pro);                $this->assign("citylist",$cityList);            }            $map['status']=array('neq',-1);            $users=M('middleuser')->where($map)->field(array('name','id'))->select();            $vips=M('user_vip')->where(array('status'=>1))->order('level')->select();            $this->assign('_users',$users);            $this->assign('_vips',$vips);            $this->assign("info",$info);            $this->assign("province",$province);            $this->display();        }    }    /*介绍人*/    public function middle_user(){        $map['status']=array('neq',-1);        $count=M('middleuser')->where($map)->count();        $page=new \Think\Page($count,10);        $list=M('middleuser')->where($map)            ->order('create_time desc')            ->limit($page->firstRow,$page->listRows)            ->select();        $this->assign("_list",$list);        $this->assign("_page",$page->show());        $this->display();    }    /**     * 会员详情     * @author tang     */    public function addmiddle(){        $id=I('get.id');        if(IS_POST){            $arr=I('post.');            if(!$arr['name']){                $this->error("名称为空");            }            $arr['create_time']=time();            if($id){                $arr['id']=$id;                $res=M('middleuser')->save($arr);            }else{                $res=M('middleuser')->add($arr);            }            if(!$res){                $this->error("操作失败");            }else{                $this->success("操作成功",U('middle_user'));            }        }else{            $info=M('middleuser')->find($id);            $map['status']=array('neq',-1);            $users=M('member')->where($map)->field(array('nickname','uid','phone'))->select();            $this->assign('_users',$users);            $this->assign("info",$info);            $this->display();        }    }    /*投诉列表*/    public function tousu(){        $uid=I('get.uid');        if($uid){            $map['foruid']=$uid;        }        $count=M('tousu_log')->where($map)->count();        $page=new \Think\Page($count,9);        $list=M('tousu_log')->where($map)            ->order('create_time desc')->limit($page->firstRow,$page->listRows)            ->select();        foreach($list as $key=>$v){            $list[$key]['user']=$this->getUserInfo($v['uid']);            $list[$key]['foruser']=$this->getUserInfo($v['foruid']);        }        $this->assign('_page',$page->show());        $this->assign('_list', $list);        $this->display();    }    /**     * 会员相册     * @author tang     */    public function album(){        $uid=I('get.uid');        $map['uid']=$uid;//        $map['type']=1;        $field=array('id','thumb_360','pic','firsturl','type');        $count=M('user_album')->where($map)->count();        $page=new \Think\Page($count,9);        $list=M('user_album')->where($map)            ->order('create_time desc')->limit($page->firstRow,$page->listRows)            ->select();        foreach($list as $key=>$v){            $list[$key]['thumb']=$v['firsturl'].$v['thumb_360'];            $list[$key]['pic']=$v['firsturl'].$v['pic'];        }        $this->assign('_page',$page->show());        $this->assign('_list', $list);        $this->meta_title = '图片列表';        $this->display();    }    /**     * 删除会员相册     * @author tang     */    public function del_album(){        $id=I('get.id');        if(!$id){            $this->error("未知的图片");        }        $info=M('user_album')->find($id);        $res=M('user_album')->where(array('id'=>$id))->delete();        if($res){            $this->success("删除成功",U('album',array('uid'=>$info['uid'])));        }else{            $this->error("删除失败");        }    }    /**     * 会员视频     * @author tang     */    public function video(){        $uid=I('get.uid');        $map['uid']=$uid;        $count=M('user_video')->where($map)->count();        $page=new \Think\Page($count,10);        $list=M('user_video')->where($map)            ->limit($page->firstRow,$page->listRows)            ->select();        $this->assign('_list',$list);        $this->assign('_page',$page->show());        $this->display();    }    /**     * 删除会员相册     * @author tang     */    public function del_video(){        $id=I('get.id');        if(!$id){            $this->error("未知的视频");        }        $info=M('user_video')->find($id);        $res=M('user_video')->where(array('id'=>$id))->delete();        if($res){            $this->success("删除成功",U('video',array('uid'=>$info['uid'])));        }else{            $this->error("删除失败");        }    }    /**     * 获取市列表     * @author tang     */    public function getCityList(){        $no=I('get.no');        $city=D('CityArea')->lists($no);        if(!empty($city)){            $data['status']=1;            $data['list']=$city;        }else{            $data['status']=0;        }        $this->ajaxReturn($data);    }    /**     * vip会员类别     * @author huajie <banhuajie@163.com>     */    public function vipCate(){        $map['status']=array('neq',-1);        $list=M('user_vip')->where($map)->order('level')->select();        $this->assign('list', $list);        $this->meta_title = 'vip会员类别';        $this->display('vipcate');    }    /**     * vip会员类别     * @author huajie <banhuajie@163.com>     */    public function vipEdit(){        $id=I('get.id');        if(IS_POST){            $arr=I('post.');            if(!$arr['level'] || !$arr['title']){                $this->error('参数不能为空');            }            $arr['level']=intval($arr['level']);            if($arr['des']){                $arr['des']=str_replace(array("\r","\n","\r\n"), '', $arr['des']);            }            if($id){                $arr['id']=$id;                $arr['update_time']=time();                $res=M('user_vip')->save($arr);            }else{                $arr['create_time']=time();                $arr['update_time']=time();                $res=M('user_vip')->add($arr);            }            if($res){                $this->success('操作成功',U('vipCate'));            }else{                $this->error('操作失败');            }        }else{            if($id){                $info=M('user_vip')->find($id);                $this->assign('info',$info);            }            $this->display('vipedit');        }        $this->meta_title = 'vip会员类别';    }    /**     * 修改昵称初始化     * @author huajie <banhuajie@163.com>     */    public function updateNickname(){        $nickname = M('Member')->getFieldByUid(UID, 'nickname');        $this->assign('nickname', $nickname);        $this->meta_title = '修改昵称';        $this->display('updatenickname');    }    /**     * 修改昵称提交     * @author huajie <banhuajie@163.com>     */    public function submitNickname(){        //获取参数        $nickname = I('post.nickname');        $password = I('post.password');        empty($nickname) && $this->error('请输入昵称');        empty($password) && $this->error('请输入密码');        //密码验证        $User   =   new UserApi();        $uid    =   $User->login(UID, $password, 4);        ($uid == -2) && $this->error('密码不正确');        $Member =   D('Member');        $data   =   $Member->create(array('nickname'=>$nickname));        if(!$data){            $this->error($Member->getError());        }        $res = $Member->where(array('uid'=>$uid))->save($data);        if($res){            $user               =   session('user_auth');            $user['username']   =   $data['nickname'];            session('user_auth', $user);            session('user_auth_sign', data_auth_sign($user));            $this->success('修改昵称成功！');        }else{            $this->error('修改昵称失败！');        }    }    /**     * 修改密码初始化     * @author huajie <banhuajie@163.com>     */    public function updatePassword(){        $this->meta_title = '修改密码';        $this->display('updatepassword');    }    /**     * 修改密码提交     * @author huajie <banhuajie@163.com>     */    public function submitPassword(){        //获取参数        $password   =   I('post.old');        empty($password) && $this->error('请输入原密码');        $data['password'] = I('post.password');        empty($data['password']) && $this->error('请输入新密码');        $repassword = I('post.repassword');        empty($repassword) && $this->error('请输入确认密码');        if($data['password'] !== $repassword){            $this->error('您输入的新密码与确认密码不一致');        }        $Api    =   new UserApi();        $res    =   $Api->updateInfo(UID, $password, $data);        if($res['status']){            $this->success('修改密码成功！');        }else{            $this->error($res['info']);        }    }    /**     * 用户行为列表     * @author huajie <banhuajie@163.com>     */    public function action(){        //获取列表数据        $Action =   M('Action')->where(array('status'=>array('gt',-1)));        $list   =   $this->lists($Action);        int_to_string($list);        // 记录当前列表页的cookie        Cookie('__forward__',$_SERVER['REQUEST_URI']);        $this->assign('_list', $list);        $this->meta_title = '用户行为';        $this->display();    }    /**     * 新增行为     * @author huajie <banhuajie@163.com>     */    public function addAction(){        $this->meta_title = '新增行为';        $this->assign('data',null);        $this->display('editaction');    }    /**     * 编辑行为     * @author huajie <banhuajie@163.com>     */    public function editAction(){        $id = I('get.id');        empty($id) && $this->error('参数不能为空！');        $data = M('Action')->field(true)->find($id);        $this->assign('data',$data);        $this->meta_title = '编辑行为';        $this->display('editaction');    }    /**     * 更新行为     * @author huajie <banhuajie@163.com>     */    public function saveAction(){        $res = D('Action')->update();        if(!$res){            $this->error(D('Action')->getError());        }else{            $this->success($res['id']?'更新成功！':'新增成功！', Cookie('__forward__'));        }    }    /**     * 会员状态修改     * @author 朱亚杰 <zhuyajie@topthink.net>     */    public function changeStatus($method=null){        $id = array_unique((array)I('id',0));        if( in_array(C('USER_ADMINISTRATOR'), $id)){            $this->error("不允许对超级管理员执行该操作!");        }        $id = is_array($id) ? implode(',',$id) : $id;        if ( empty($id) ) {            $this->error('请选择要操作的数据!');        }        $map['uid'] =   array('in',$id);        switch ( strtolower($method) ){            case 'forbiduser':                $this->forbid('Member', $map );                break;            case 'resumeuser':                $this->resume('Member', $map );                break;            case 'deleteuser':                $this->delete('Member', $map );                break;            case 'forbidvip':                $this->forbid('user_vip', $map );                break;            case 'resumevip':                $this->resume('user_vip', $map );                break;            case 'deletevip':                $this->delete('user_vip', $map );                break;            default:                $this->error('参数非法');        }    }    public function add($username = '', $password = '', $repassword = '', $email = ''){        if(IS_POST){            $sex=I('sex');            $nickname=I('nickname');            /* 检测密码 */            if($password != $repassword){                $this->error('密码和重复密码不一致！');            }            if(!$nickname){                $this->error('昵称不能为空！');            }            /* 调用注册接口注册用户 */            $User   =   new UserApi;            $uid    =   $User->register($username, $password, $email);            if(0 < $uid){ //注册成功                $user = array('uid' => $uid, 'nickname' => $nickname, 'status' => 1,'sex'=>$sex);                if(!M('Member')->add($user)){                    $this->error('用户添加失败！');                } else {                    $this->success('用户添加成功！',U('index'));                }            } else { //注册失败，显示错误信息                $this->error($this->showRegError($uid));            }        } else {            $this->meta_title = '新增用户';            $this->display();        }    }    /**     * 获取用户注册错误信息     * @param  integer $code 错误编码     * @return string        错误信息     */    private function showRegError($code = 0){        switch ($code) {            case -1:  $error = '用户名长度必须在16个字符以内！'; break;            case -2:  $error = '用户名被禁止注册！'; break;            case -3:  $error = '用户名被占用！'; break;            case -4:  $error = '密码长度必须在6-30个字符之间！'; break;            case -5:  $error = '邮箱格式不正确！'; break;            case -6:  $error = '邮箱长度必须在1-32个字符之间！'; break;            case -7:  $error = '邮箱被禁止注册！'; break;            case -8:  $error = '邮箱被占用！'; break;            case -9:  $error = '手机格式不正确！'; break;            case -10: $error = '手机被禁止注册！'; break;            case -11: $error = '手机号被占用！'; break;            default:  $error = '未知错误';        }        return $error;    }}?>