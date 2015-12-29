<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/5/18
 * Time: 20:09
 */

namespace Api\Controller;


class DateController extends BaseController{
    /*最新约会列表*/
    public function Lists1(){
        $num=I('get.num');
        $num=$num?$num:10;
        $place=I('place');
        $min_age=I('min_age');
        $max_age=I('max_age');
        $min_height=I('min_height');
        $max_height=I('max_height');
        $min_weight=I('min_weight');
        $max_weight=I('max_weight');
        $headimg=I('headimg');
        $video=I('video');
        $lng=I('lng');
        $lat=I('lat');
        $p=I('page');
        $p=$p?$p:1;

        $user=$this->getUserInfo($this->uid);

        $map['m.uid']=array('neq',1);
        $map['m.sex']=array('neq',$user['sex']);
        $map['d.status']=array('in','1,2');
        $map['d.date_time']=array('gt',date('Y-m-d H:i:s'));
//        $map['d.pic']=array('exp','is not null');
        $map['d.type']=1;
        $search='';
        $search1='';
        if($place){
            /*城市*/
            $map['d.place']=array('like',"%$place%");
//            $search1.=" and d.place like '%".$place."%'";
        }
        if($min_age){
            /*最小年龄*/
//            $time=date("Y-m-d",strtotime("-".$min_age." Years"));
//            echo $time;die;
            $map['m.age']=array('egt',$min_age);
            $search.=" and age >=".$min_age;
            $search1.=" and m.age>=".$min_age;
        }
        if($max_age){
            /*最大年龄*/
            $map['m.age']=array('elt',$max_age);
            $search.=" and age <=".$max_age;
            $search1.=" and m.age<=".$max_age;
        }
        if($min_height){
            /*最小身高*/
            $map['m.height']=array('egt',$min_height);
            $search.=" and height>=".$min_height;
            $search1.=" and m.height>=".$min_height;
        }
        if($max_height){
            /*最大身高*/
            $map['m.height']=array('elt',$max_height);
            $search.=" and height<=".$max_height;
            $search1.=" and m.height<=".$max_height;
        }
        if($min_weight){
            /*最小体重*/
            $map['m.weight']=array('egt',$min_weight);
            $search.=" and weight>=".$min_weight;
            $search1.=" and m.weight>=".$min_weight;
        }
        if($max_weight){
            /*最大体重*/
            $map['m.weight']=array('elt',$max_weight);
            $search.=" and weight<=".$max_weight;
            $search1.=" and m.weight<=".$max_weight;
        }
        if($headimg==1){
            /*有头像*/
            $map['m.headimg']=array('EXP','IS NOT NULL');
            $search.=" and headimg is not null";
            $search1.=" and m.headimg is not null";
        }elseif($headimg==2){
            /*无头像*/
            $map['m.headimg']=array('EXP','IS NULL');
            $search.=" and headimg is null";
            $search1.=" and m.headimg is null";
        }
        if($video==1){
            /*视频认证*/
            $map['m.video']=1;
            $search.=" and video=1";
            $search1.=" and m.video=1";
        }elseif($video==2){
            /*无视频认证*/
            $map['m.video']=0;
            $search.=" and video=0";
            $search1.=" and m.video=0";
        }

        $field=array('d.id','d.pic','d.uid','d.cid','d.place','d.redbag_type','d.redbag'
        ,'m.headimg','m.nickname','m.birthday','m.vip','m.video','m.city'
        ,'m.height','m.weight','d.create_time','d.lng','d.lat','m.phoneout','m.age');

        $sql="select uid,headimg,nickname,birthday,vip,video,city,phoneout,age
        ,height,weight,last_login_time as create_time,lng,lat from messenger_member m
        where (SELECT count(*) from messenger_user_date where m.uid=uid and status in(1,2))<=0
        and (SELECT count(*) from messenger_user_album where m.uid=uid and status=1)>0
        and sex!=".$user['sex']." and uid !=1".$search."
         order by last_login_time desc limit ".($p-1)*$num.",".$num;
        /*屏蔽好友*/
        if($user['online']==0){
            $friends=$this->getFriends();
            if($friends){
                $sql="select m.uid,m.headimg,m.nickname,m.birthday,m.vip,m.video,m.city,m.phoneout,m.age
        ,m.height,m.weight,m.last_login_time as create_time,m.lng,m.lat from messenger_member m
         left join messenger_ucenter_member u on m.uid=u.id
        where (SELECT count(*) from messenger_user_date where m.uid=uid and status in(1,2))<=0
        and (SELECT count(*) from messenger_user_album where m.uid=uid and status=1)>0
        and m.sex!=".$user['sex']." and m.uid !=1 and u.username not in(".$friends.")".$search1."
         order by m.last_login_time desc limit ".($p-1)*$num.",".$num;
//echo $sql;
            }
        }

        $member=M()->query($sql);
//        print_r($member);
//        echo M()->getLastSql();
//        echo M()->getDbError();die;
        $prefix=C('DB_PREFIX');
        if($friends){
            $map['u.username']=array('not in',$friends);
            $count=M()->field($field)
                ->table($prefix.'member m')
                ->join('LEFT JOIN '.$prefix.'user_date d on d.uid=m.uid')
                ->join('LEFT JOIN '.$prefix.'ucenter_member u on u.id=m.uid')
                ->where($map)
                ->count();
//                        echo M()->getDbError();
//            echo M()->getLastSql();die;
            $page=new \Think\Page($count,$num);
            $list=M()->field($field)
                ->table($prefix.'member m')
                ->join('LEFT JOIN '.$prefix.'user_date d on d.uid=m.uid')
                ->join('LEFT JOIN '.$prefix.'ucenter_member u on u.id=m.uid')
                ->where($map)
                ->order('d.create_time desc')
                ->limit($page->firstRow,$page->listRows)
                ->select();
        }else{
            $count=M()->field($field)
                ->table($prefix.'member m')
                ->join('LEFT JOIN '.$prefix.'user_date d on d.uid=m.uid')
//            ->join('LEFT JOIN '.$prefix.'user_date_cate c on d.cid=c.id')
                ->where($map)
                ->count();

            $page=new \Think\Page($count,$num);
            $list=M()->field($field)
                ->table($prefix.'member m')
                ->join('LEFT JOIN '.$prefix.'user_date d on d.uid=m.uid')
//            ->join('LEFT JOIN '.$prefix.'user_date_cate c on d.cid=c.id')
                ->where($map)
                ->order('d.create_time desc')
                ->limit($page->firstRow,$page->listRows)
                ->select();
        }
        if(!empty($member) && !empty($list)){
            $list=array_merge($member,$list);
        }else{
            $list=$member?$member:$list;
        }
        if(empty($list)){
            $this->apiError(0,'未查找到数据');
        }else{
            shuffle($list);
            foreach($list as $key=>$v){
                if($lng && $lat && $v['lat'] && $v['lng']){
                    $list[$key]['len']=$this->GetDistance($lat,$lng,$v['lat'],$v['lng'])."公里";
                }else{
                    $list[$key]['len']='未知';
                }
                if(isset($v['redbag_type']) && $v['redbag_type']){
                    $cate=$this->getCateInfo($v['cid']);
                    if($v['redbag_type']==1){
                        $str="愿付酬金".$v['redbag']."元";
                        $list[$key]['title']=$str.",需要".$cate['title'];
                    }else{
                        $str="需要酬金".$v['redbag']."元";
                        $list[$key]['title']=$str.",提供".$cate['title'];
                    }
                    $list[$key]['style']=1;

                    /*报名*/
                    $list[$key]['sign']=$this->checkSign($this->uid,$v['id']);

                    unset($list[$key]['cid']);
//                    $list[$key]['times']=$this->getTimeNum($v['create_time']);
                }else{
//                    echo 1;die;
                    $list[$key]['id']='0';
                    //$list[$key]['place']=$this->getCity($v['city']);
                    $list[$key]['redbag_type']='0';
                    $list[$key]['redbag']='0';
                    $list[$key]['style']=2;
                    $list[$key]['title']="该用户处于空闲状态";

                }
                $list[$key]['place']=$this->getProvince($v['city'])." ".$this->getCity($v['city']);
                $list[$key]['is_follow']=$this->checkFollow($v['uid']);
                /*查看手机*/
                if($v['phoneout']==1){
                    $list[$key]['phone']=true;
                }else{
                    if($this->compareVip($this->uid,$v['uid']) && ($this->checkDate($v['uid']) || $this->checkPayPhone($v['uid']))){
                        $list[$key]['phone']=true;
                    }else{
                        $list[$key]['phone']=false;
                    }
                }
                unset($list[$key]['phoneout']);

                $list[$key]['times']=$this->getTimeNum($v['create_time']);
//                if($v['birthday']!='0000-00-00' && $v['birthday']){
////                    $list[$key]['age']=date('Y-m-d',time())-$v['birthday'];
//                }else{
//                    $list[$key]['age']='未知';
//                }
                if($v['vip']){
                    $list[$key]['vipInfo']=$this->getVipInfo($v['vip']);
                }
//                $list[$key]['last_login_time']=date('Y-m-d H:i',$v['last_login_time']);
//                $list[$key]['user']=$this->getUserInfo($v['uid']);

                /*图片*/
                $field=array('type','pic','firsturl','thumb_720','thumb_360','secret_pic');
                if($v['pic']){
                    $pic=M('user_album')->field($field)->find($v['pic']);
                }else{
                    $pic=M('user_album')->field($field)->where(array('uid'=>$v['uid'],'status'=>1))
                        ->order('is_top desc,create_time desc')->find();
                }
                $list[$key]['pic_count']=M('user_album')->field($field)->where(array('uid'=>$v['uid'],'status'=>1))->count();

                /*比较等级*/
                $comparevip=$this->compareVip($this->uid,$v['uid']);
                if(!$comparevip && $pic['type']==2){
                    /*自身等级比对方低*/
                    /*私密相册*/
                    $pic['see']=0;
                    $pic['pic']=$pic['secret_pic']?$pic['firsturl'].$pic['secret_pic']:'';
                    $pic['thumb_720']=$pic['pic'];
                    $pic['thumb_360']=$pic['pic'];
                }else{
                    $pic['see']=1;
                    $pic['pic']=$pic['pic']?$pic['firsturl'].$pic['thumb_360']:'';
                    $pic['thumb_720']=$pic['firsturl'].$pic['thumb_720'];
                    $pic['thumb_360']=$pic['firsturl'].$pic['thumb_360'];
                }


                $list[$key]['pic']=$pic;
            }
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }
    }
    /*最新约会列表*/
    public function Lists2(){
        $num=I('get.num');
        $num=$num?$num:10;
        $place=I('place');
        $min_age=I('min_age');
        $max_age=I('max_age');
        $min_height=I('min_height');
        $max_height=I('max_height');
        $min_weight=I('min_weight');
        $max_weight=I('max_weight');
        $headimg=I('headimg');
        $video=I('video');
        $lng=I('lon');
        $lat=I('lat');
        $p=I('page');
        $p=$p?$p:1;
        $near=I('near');

        if($lat && $lng){
            $datas=[
                'lng'=>$lng,
                'lat'=>$lat,
                'last_login_time'=>time()
            ];
        }else{
            $datas=['last_login_time'=>time()];
        }
        M('member')->where(array('uid'=>$this->uid))->save($datas);
        $user=$this->getUserInfo($this->uid);
        $search='';
        $search1='';
        if($near && $lat && $lng){
            $arr=$this->getXY(100000,$lng,$lat);
            /*附近*/
            $search.=" and lat>=".$arr['lat_min']." and lat <=".$arr['lat_max'];
            $search1.=" and m.lat>=".$arr['lat_min']." and m.lat <=".$arr['lat_max'];

            if($arr['lon_max']*$arr['lon_min']<0 && $arr['lon_min']>170){
                //180度附近,$arr['lon_min']>170
                $search.=" and (lng>=".$arr['lon_min']." and lng <=180) or (lng>=-180 and lng <=".$arr['lon_max'].")";
                $search1.=" and (m.lng>=".$arr['lon_min']." and m.lng<=180) or (m.lng>=-180 and m.lng<=".$arr['lon_max'].")";

            }else{
                $search.=" and lng>=".$arr['lon_min']." and lng <=".$arr['lon_max'];
                $search1.=" and m.lng>=".$arr['lon_min']." and m.lng <=".$arr['lon_max'];
            }
            $num=50;
        }
        if($place){
            /*城市*/
            $search.=" and city=$place";
            $search1.=" and m.city=$place";
        }
        if($min_age){
            /*最小年龄*/
            $search.=" and age >=".$min_age;
            $search1.=" and m.age>=".$min_age;
        }
        if($max_age){
            /*最大年龄*/
            $search.=" and age <=".$max_age;
            $search1.=" and m.age<=".$max_age;
        }
        if($min_height){
            /*最小身高*/
            $search.=" and height>=".$min_height;
            $search1.=" and m.height>=".$min_height;
        }
        if($max_height){
            /*最大身高*/
            $search.=" and height<=".$max_height;
            $search1.=" and m.height<=".$max_height;
        }
        if($min_weight){
            /*最小体重*/
            $search.=" and weight>=".$min_weight;
            $search1.=" and m.weight>=".$min_weight;
        }
        if($max_weight){
            /*最大体重*/
            $search.=" and weight<=".$max_weight;
            $search1.=" and m.weight<=".$max_weight;
        }
        if($headimg==1){
            /*有头像*/
            $search.=" and headimg is not null";
            $search1.=" and m.headimg is not null";
        }elseif($headimg==2){
            /*无头像*/
            $search.=" and headimg is null";
            $search1.=" and m.headimg is null";
        }
        if($video==1){
            /*视频认证*/
            $search.=" and video=1";
            $search1.=" and m.video=1";
        }elseif($video==2){
            /*无视频认证*/
            $search.=" and video=0";
            $search1.=" and m.video=0";
        }

        $sql="select uid,headimg,nickname,birthday,vip,video,city,phoneout,age
        ,height,weight,last_login_time as create_time,lng,lat from messenger_member m
        where (SELECT count(*) from messenger_user_album where m.uid=uid and status=1)>0
        and status=1 and sex!=".$user['sex']." and uid !=1".$search."
         order by last_login_time desc limit ".($p-1)*$num.",".$num;
        /*屏蔽好友*/
        if($user['online']==0){
            $friends=$this->getFriends();
            if($friends){
                $sql="select m.uid,m.headimg,m.nickname,m.birthday,m.vip,m.video,m.city,m.phoneout,m.age
        ,m.height,m.weight,m.last_login_time as create_time,m.lng,m.lat from messenger_member m
         left join messenger_ucenter_member u on m.uid=u.id
        where (SELECT count(*) from messenger_user_album where m.uid=uid and status=1)>0
        and m.status=1 and m.sex!=".$user['sex']." and m.uid !=1 and u.username not in(".$friends.")".$search1."
         order by m.last_login_time desc limit ".($p-1)*$num.",".$num;
            }
        }

        $list=M()->query($sql);
        if(empty($list)){
            $this->apiError(0,'未查找到数据');
        }else{
            foreach($list as $key=>$v){
                $date=$this->getUserDate1($v['uid']);
                if($date){
                    $arr=array_merge($v,$date);
                    $list[$key]=$arr;
                    $cate=$this->getCateInfo($date['cid']);
                    if($date['redbag_type']==1){
                        $str="愿付酬金".$date['redbag']."元";
                        $list[$key]['title']=$str.",需要".$cate['title'];
                    }else{
                        $str="需要酬金".$date['redbag']."元";
                        $list[$key]['title']=$str.",提供".$cate['title'];
                    }
                    $list[$key]['style']=1;


                    /*报名*/
                    $list[$key]['sign']=$this->checkSign($this->uid,$date['id']);
                }else{
                    $list[$key]['id']='0';
                    $list[$key]['redbag_type']='0';
                    $list[$key]['redbag']='0';
                    $list[$key]['style']=2;
                    $list[$key]['title']="该用户处于空闲状态";
                }
                /*容联*/
                $ronglian=$this->getRonglianCount($v['uid']);
                $list[$key]['subAccountSid']=$ronglian['subaccountsid'];
                $list[$key]['voipAccount']=$ronglian['voipaccount'];
                if($lng && $lat && $v['lat']!=0 && $v['lng']!=0){
                    $list[$key]['len']=$this->GetDistance($lat,$lng,$v['lat'],$v['lng']);
                }else{
                    $list[$key]['len']=0;
                }
                $list[$key]['place']=$this->getProvince($v['city'])." ".$this->getCity($v['city']);
                $list[$key]['is_follow']=$this->checkFollow($v['uid']);
                /*查看手机*/
                if($v['phoneout']==1){
                    $list[$key]['phone']=true;
                }else{
                    if($this->compareVip($this->uid,$v['uid']) && ($this->checkDate($v['uid']) || $this->checkPayPhone($v['uid']))){
                        $list[$key]['phone']=true;
                    }else{
                        $list[$key]['phone']=false;
                    }
                }
                unset($list[$key]['phoneout']);

                $list[$key]['times']=$this->getTimeNum($list[$key]['create_time']);
                if($v['vip']){
                    $list[$key]['vipInfo']=$this->getVipInfo($v['vip']);
                }
                /*图片*/
                $field=array('type','pic','firsturl','thumb_720','thumb_360','secret_pic');
                $pic=M('user_album')->field($field)->where(array('uid'=>$v['uid'],'status'=>1))
                    ->order('is_top desc,create_time desc')->find();
                $album=M('user_album')->field($field)->where(array('uid'=>$v['uid'],'status'=>1))->count();
                $video=M('user_video')->field($field)->where(array('uid'=>$v['uid'],'status'=>1))->count();
                $list[$key]['pic_count']=$album+$video;
                /*比较等级*/
                $comparevip=$this->compareVip($this->uid,$v['uid']);
                if(!$comparevip && $pic['type']==2){
                    /*自身等级比对方低*/
                    /*私密相册*/
                    $pic['see']=0;
                    $pic['pic']=$pic['secret_pic']?$pic['firsturl'].$pic['secret_pic']:'';
                    $pic['thumb_720']=$pic['pic'];
                    $pic['thumb_360']=$pic['pic'];
                }else{
                    $pic['see']=1;
                    $pic['pic']=$pic['pic']?$pic['firsturl'].$pic['thumb_360']:'';
                    $pic['thumb_720']=$pic['firsturl'].$pic['thumb_720'];
                    $pic['thumb_360']=$pic['firsturl'].$pic['thumb_360'];
                }


                $list[$key]['pic']=$pic;
            }
            if($near && $lng && $lat){
                $list=$this->min2max($list);
            }
            foreach($list as $key=>$v){
                if($v['len']<1000){
                    $list[$key]['len'].="米";
                }else{
                    $list[$key]['len']=round($v['len']/1000)."公里";
                }
            }
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }
    }
    public function Lists(){
        $num=I('get.num');
        $num=$num?$num:10;
        $place=I('place');
        $min_age=I('min_age');
        $max_age=I('max_age');
        $min_height=I('min_height');
        $max_height=I('max_height');
        $min_weight=I('min_weight');
        $max_weight=I('max_weight');
        $headimg=I('headimg');
        $video=I('video');
        $lng=I('lon');
        $lat=I('lat');
        $p=I('page');
        $p=$p?$p:1;
        $near=I('near');
        $nowcity=I('nowcity');

        if($p==1){
            if($lat && $lng){
                $datas=[
                    'lng'=>$lng,
                    'lat'=>$lat,
                    'nowcity'=>$nowcity,
                    'last_login_time'=>time(),
                    'last_login_ip'=>get_client_ip(1,true)
                ];
            }else{
                $datas=['last_login_time'=>time(),'last_login_ip'=>get_client_ip(1,true)];
            }
        }

        M('member')->where(array('uid'=>$this->uid))->save($datas);
        $user=$this->getUserInfo($this->uid);
        $username=$this->getUserName($this->uid);
        $search='';
        $search1='';
        if($near && $lat && $lng){
            $arr=$this->getXY(100000,$lng,$lat);
            /*附近*/
            $search.=" and lat>=".$arr['lat_min']." and lat <=".$arr['lat_max'];
            $search1.=" and m.lat>=".$arr['lat_min']." and m.lat <=".$arr['lat_max'];

            if($arr['lon_max']*$arr['lon_min']<0 && $arr['lon_min']>170){
                //180度附近,$arr['lon_min']>170
                $search.=" and (lng>=".$arr['lon_min']." and lng <=180) or (lng>=-180 and lng <=".$arr['lon_max'].")";
                $search1.=" and (m.lng>=".$arr['lon_min']." and m.lng<=180) or (m.lng>=-180 and m.lng<=".$arr['lon_max'].")";

            }else{
                $search.=" and lng>=".$arr['lon_min']." and lng <=".$arr['lon_max'];
                $search1.=" and m.lng>=".$arr['lon_min']." and m.lng <=".$arr['lon_max'];
            }
            $num=50;
        }
        if($place){
            /*城市*/
            $search.=" and city=$place";
            $search1.=" and m.city=$place";
        }
        if($min_age){
            /*最小年龄*/
            $search.=" and age >=".$min_age;
            $search1.=" and m.age>=".$min_age;
        }
        if($max_age){
            /*最大年龄*/
            $search.=" and age <=".$max_age;
            $search1.=" and m.age<=".$max_age;
        }
        if($min_height){
            /*最小身高*/
            $search.=" and height>=".$min_height;
            $search1.=" and m.height>=".$min_height;
        }
        if($max_height){
            /*最大身高*/
            $search.=" and height<=".$max_height;
            $search1.=" and m.height<=".$max_height;
        }
        if($min_weight){
            /*最小体重*/
            $search.=" and weight>=".$min_weight;
            $search1.=" and m.weight>=".$min_weight;
        }
        if($max_weight){
            /*最大体重*/
            $search.=" and weight<=".$max_weight;
            $search1.=" and m.weight<=".$max_weight;
        }
        if($headimg==1){
            /*有头像*/
            $search.=" and headimg is not null";
            $search1.=" and m.headimg is not null";
        }elseif($headimg==2){
            /*无头像*/
            $search.=" and headimg is null";
            $search1.=" and m.headimg is null";
        }
        if($video==1){
            /*视频认证*/
            $search.=" and video=1";
            $search1.=" and m.video=1";
        }elseif($video==2){
            /*无视频认证*/
            $search.=" and video=0";
            $search1.=" and m.video=0";
        }
        /*屏蔽好友*/
        $sql="select m.uid,m.headimg,m.nickname,m.birthday,m.vip,m.video,m.city,m.phoneout,m.age";
        $sql.=",m.height,m.weight,m.last_login_time as create_time,m.lng,m.lat from messenger_member m";
        $sql.=" left join messenger_ucenter_member u on m.uid=u.id";
        $sql.=" where (SELECT count(*) from messenger_user_album where m.uid=uid and status=1)>0";
        $sql.=" and m.status=1 and m.sex!=".$user['sex']." and m.uid!=1 and ";
        $sql.=" (m.online=1 or (m.online=0 and (SELECT count(*) from messenger_adlist where m.uid=uid and status=1 and phone='".$username."')=0)) ".$search1;
        $sql.=" order by m.last_login_time desc limit ".($p-1)*$num.",".$num;

        $list=M()->query($sql);
//        echo M()->getLastSql();
//        echo M()->getDbError();die;
//print_r($list);
        if(empty($list)){
            $this->apiError(0,$p==1?"未查找到数据":'已经到底啦!');
        }else{
            foreach($list as $key=>$v){
                $date=$this->getUserDate1($v['uid']);
                if($date){
                    $arr=array_merge($v,$date);
                    $list[$key]=$arr;
                    $cate=$this->getCateInfo($date['cid']);
                    if($date['redbag_type']==1){
                        $str="愿付酬金".$date['redbag']."元";
                        $list[$key]['title']=$str.",需要".$cate['title'];
                    }else{
                        $str="需要酬金".$date['redbag']."元";
                        $list[$key]['title']=$str.",提供".$cate['title'];
                    }
                    $list[$key]['style']=1;


                    /*报名*/
                    $list[$key]['sign']=$this->checkSign($this->uid,$date['id']);
                }else{
                    $list[$key]['id']='0';
                    $list[$key]['redbag_type']='0';
                    $list[$key]['redbag']='0';
                    $list[$key]['style']=2;
                    $list[$key]['title']="该用户处于空闲状态";
                }
                /*容联*/
                $ronglian=$this->getRonglianCount($v['uid']);
                $list[$key]['subAccountSid']=$ronglian['subaccountsid'];
                $list[$key]['voipAccount']=$ronglian['voipaccount'];
                if($lng && $lat && $v['lat']!=0 && $v['lng']!=0){
                    $list[$key]['len']=$this->GetDistance($lat,$lng,$v['lat'],$v['lng']);
                }else{
                    $list[$key]['len']=0;
                }
                $list[$key]['place']=$this->getProvince($v['city'])." ".$this->getCity($v['city']);
                $list[$key]['is_follow']=$this->checkFollow($v['uid']);
                /*查看手机*/
                if($v['phoneout']==1){
                    $list[$key]['phone']=true;
                }else{
                    if($this->compareVip($this->uid,$v['uid']) && ($this->checkDate($v['uid']) || $this->checkPayPhone($v['uid']))){
                        $list[$key]['phone']=true;
                    }else{
                        $list[$key]['phone']=false;
                    }
                }
//                unset($list[$key]['phoneout']);

                $list[$key]['times']=$this->getTimeNum($list[$key]['create_time']);
                if($v['vip']){
                    $list[$key]['vipInfo']=$this->getVipInfo($v['vip']);
                }
                /*图片*/
                $field=array('type','pic','firsturl','thumb_720','thumb_360','secret_pic');
                $pic=M('user_album')->field($field)->where(array('uid'=>$v['uid'],'status'=>1))
                    ->order('is_top desc,create_time desc')->find();
                $album=M('user_album')->field($field)->where(array('uid'=>$v['uid'],'status'=>1))->count();
                $video=M('user_video')->field($field)->where(array('uid'=>$v['uid'],'status'=>1,'is_sign'=>array('neq',1)))->count();
                $list[$key]['pic_count']=$album+$video;
                /*比较等级*/
                $comparevip=$this->compareVip($this->uid,$v['uid']);
                if(!$comparevip && $pic['type']==2){
                    /*自身等级比对方低*/
                    /*私密相册*/
                    $pic['see']=0;
                    $pic['pic']=$pic['secret_pic']?$pic['firsturl'].$pic['secret_pic']:'';
                    $pic['thumb_720']=$pic['pic'];
                    $pic['thumb_360']=$pic['pic'];
                }else{
                    $pic['see']=1;
                    $pic['pic']=$pic['pic']?$pic['firsturl'].$pic['thumb_360']:'';
                    $pic['thumb_720']=$pic['firsturl'].$pic['thumb_720'];
                    $pic['thumb_360']=$pic['firsturl'].$pic['thumb_360'];
                }


                $list[$key]['pic']=$pic;
            }
            if($near && $lng && $lat){
                $list=$this->min2max($list);
            }
            foreach($list as $key=>$v){
                if($v['len']<1000){
                    $list[$key]['len'].="米";
                }else{
                    $list[$key]['len']=round($v['len']/1000)."公里";
                }
            }
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }
    }
    /*用户查询约会*/
    protected function getUserDate1($uid){
        $map['uid']=$uid;
        $map['status']=array('in','1,2');
        $map['date_time']=array('gt',date('Y-m-d H:i:s'));
        $map['type']=1;
        $field=array('id','pic','uid','cid','place'=>"location",'redbag_type','redbag');
        $info=M('user_date')->field($field)->where($map)->order('create_time desc')->find();
        return $info;
    }
    /*约会详情*/
    public function detail(){
        $id=I('get.id');
        if(!$id){
            $this->apiError(0,'非法请求');
        }
        $info=S('date_'.$id);
        if(empty($info)){
//            $field=array('d.id','d.pic','d.uid','d.cid','d.type','d.place','lng'
//            ,'m.headimg','m.nickname','m.birthday','m.vip','m.video','m.city'
//            ,'m.height','m.weight','m.last_login_time','m.lng','m.lat');
//            $map['d.status']=array('neq','-1');
//            $map['d.id']=$id;
//
//            $prefix=C('DB_PREFIX');
//            $info=M()->field($field)
//                ->table($prefix.'user_date d')
//                ->join($prefix.'member m on d.uid=m.uid')
//                ->join($prefix.'user_date_cate c on d.cid=c.id')
//                ->where($map)
//                ->find();
        }
    }

    /*约会类型列表*/
    public function cate(){
        $map['status']=1;
        $field=array('id','title','pic');
        $list=M('user_date_cate')->field($field)->where($map)->order('level')->select();
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['pic']=$v['pic']?$this->server.$v['pic']:'';
            }
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }else{
            $this->apiError(0,'未查找到数据，请联系管理员');
        }
    }
    /*发布约会*/
    public function newDate(){
        $arr=I('post.');
        $user=$this->getUserInfo($this->uid);
        if(!$user['vip']){
            $this->apiError(0,'vip才能发布意向');
        }
        $pic=M('user_album')->where(array('uid'=>$this->uid,'status'=>1))->find();
        if(!$pic){
            $this->apiError(0,'您的相册空空如也，请先上传相册');
        }
        if(!$arr['cid'] || !$arr['redbag_type'] || !$arr['place'] || intval($arr['days'])<=0){
//            $str='';
//            foreach($arr as $key=>$v){
//                $str.=$key.$v;
//            }
            $this->apiError(0,'参数错误');
        }
        $arr['date_time']=date("Y-m-d H:i:s",time()+intval($arr['days'])*3600);
        $arr['type']=$arr['type']?$arr['type']:1;
        if($arr['type']==2 && !$arr['inviteuid']){
            $this->apiError(0,'请选择邀请的会员');
        }
        if($arr['type']==2 && $arr['inviteuid']){
            if(!$this->compareVip($this->uid,$arr['inviteuid'])){
                $this->apiError(0,'对方的等级较高，请升级后再来邀请TA吧！');
            }
        }
        /*检测金币*/
        $coin=0;
        $cate=$this->getCateInfo($arr['cid']);
        $coin=C("USER_DATE_COIN");
//        if($arr['redbag_type']==1){
//            /*赠金*/
//            $coin+=$arr['redbag'];
//        }
        if(!$this->checkUserCoin($coin)){
            $this->apiError(0,'余额不够了，请充值');
        }
        $arr['status']=1;
        $arr['uid']=$this->uid;
        $arr['create_time']=time();
        $arr['update_time']=time();
        $res=M('user_date')->add($arr);
        if($res){
            /*检测金币*/
//            if($arr['type']==1){
                /*冻结金币*/
                $this->freezeCoin(C("USER_DATE_COIN"),$res,$this->uid,1,$arr['type']==2 ? $arr['inviteuid']:$this->uid);
//            }
//            if($arr['redbag_type']==1){
//                /*冻结赠金*/
//                $this->freezeCoin($arr['redbag'],$res,$this->uid,2,$arr['type']==2 ? $arr['inviteuid']:$this->uid);
//            }
            if($arr['type']==2 && $arr['inviteuid']){
                $this->push2user($arr['inviteuid'],"有人向你发起意向邀请",2,$res);
            }
            $this->apiSuccess('success');
        }else{
            $this->apiError(0,'发布失败');
        }
    }
    /*邀请*/
//    public function invite(){
//        $uid=I('get.uid');
//        if(!$uid){
//            $this->apiError(0,'请选择邀请的会员');
//        }
//        $inUserInfo=$this->getUserInfo($uid);
//        if(!$inUserInfo){
//            $this->apiError(0,'请选择邀请的会员');
//        }
//        if(!$this->compareVip($this->uid,$uid)){
//            $this->apiError(0,'对方的等级较高，请升级后再来邀请TA吧！');
//        }
//
//    }
    /*报名约会*/
    public function dateSign(){
        $id=I('get.id');
        if(!$id){
            $this->apiError(0,'未知的意向');
        }
        /*约会信息*/
        $info=$this->getDateInfo($id);
        if(!$info){
            $this->apiError(0,'未知的意向');
        }
        if($this->uid==$info['uid']){
            $this->apiError(0,'自己不能报名');
        }
        if($info['date_time']<=date("Y-m-d H:i:s")){
            $this->apiError(0,'已过期');
        }
        if(!$this->compareVip($this->uid,$info['uid'])){
            $this->apiError(0,'对方的等级较高，请升级后再来报名吧！');
        }
        /*检测报名*/
        if($this->checkSign($this->uid,$id)){
            $this->apiError(0,'已报名');
        }
        /*检测金币*/
        $coin=C("USER_DATE_COIN");
//        if($info['redbag_type']==2){
//            $coin+=$info['redbag'];
//        }
        if(!$this->checkUserCoin($coin)){
            $this->apiError(0,'余额不够了');
        }
        /*冻结金币类型（1：约会冻结，2：约会赠金冻结）*/
        $this->freezeCoin(C("USER_DATE_COIN"),$id,$this->uid,1,$info['uid']);
//        if($info['redbag_type']==2 && $info['redbag']>0){
//            $this->freezeCoin($info['redbag'],$id,$this->uid,2,$info['uid']);
//        }
        $arr['uid']=$this->uid;
        $arr['date_id']=$id;
        $arr['status']=1;
        $arr['create_time']=time();
        $arr['update_time']=time();
        $res=M('user_date_sign')->add($arr);
        if($res){
            /*通知发布约会人*/
            $this->push2user($info['uid'],'有会员报名你的意向,请查看',1,$id);
            $this->apiSuccess('success');
        }else{
            $coin=$this->reCoinFreezing($this->uid,$id);
            $this->apiError(0,'报名失败,'.$coin.'信用豆已自动返还，请注意查收');
        }

    }
    /*恢复金币，解除冻结*/
    protected function reCoinFreezing($uid,$date_id){
        $where['uid']=$uid;
        $where['date_id']=$date_id;
        $where['status']=1;
        $list=M('coin_freezing')->where($where)->select();
        $coin=0;
        if($list){
            $freezing_id='';
            foreach($list as $key=>$v){
                $freezing_id.=$v['id'].',';
                $coin+=$v['coin'];
            }
            M('coin_freezing')->where($where)->setField('status',-1);
            $this->addCoin($coin,$uid,$date_id,$uid,'refreezing');
        }
        return $coin;
    }

    /*响应约会*/
    public function onDate(){
        $id=I('get.id');
        $uid=I('get.uid');
        $ondate=I('get.ondate');
        if(!$id){
            $this->apiError(0,'未知的意向');
        }
        if(!$uid){
            $this->apiError(0,'对方用户不存在');
        }
        S('date_'.$id,null);
        $info=$this->getDateInfo($id);
        if(!$info){
            $this->apiError(0,'未知的意向');
        }
        if($info['date_time']<=date("Y-m-d H:i:s")){
            $this->apiError(0,'已过期');
        }
        if($ondate==2){
            if($info['type']==1){
                /*修改报名字段*/
                M('user_date_sign')->where(array('status'=>1,'date_id'=>$id,'uid'=>$uid))->setField('ondate',2);
                /*解冻金币*/
                $freezing=M('coin_freezing')->where(array('uid'=>$uid,'date_id'=>$id,'status'=>1))->select();
                if($freezing){
                    foreach($freezing as $key=>$v){
                        $fz['id']=$v['id'];
                        $fz['status']=-1;
                        M('coin_freezing')->save($fz);
                        $this->addCoin($v['coin'],$v['uid'],$v['date_id'],$info['uid'],'refreezing');
                    }
                }

            }else{
                M('user_date')->where(array('id'=>$id))->setField('ondate',2);
            }

            /*发送通知*/
            $this->push2user($uid,'会员拒绝了你的意向请求，同时解冻了你的信用豆,请查看',3,$id);
            S('date_'.$id,null);
            $this->apiSuccess('success');
        }

        /*发起约会，别人报名,我选择响应某人*/
        if($info['type']==1 && !$this->checkSign($uid,$id)){
            $this->apiError(0,'此人未报名');
        }
        /*别人发起邀请，我来响应*/
        if($info['type']==2){
            $coin=C("USER_DATE_COIN");
//            if($info['redbag_type']==2){
//                $coin+=$info['redbag'];
//            }
            if(!$this->checkUserCoin($coin)){
                /*我的金币不够了*/
                $this->apiError(-8,'余额不够了,请充值');
            }
            /*冻结约会金币*/
            $this->freezeCoin(C("USER_DATE_COIN"),$id,$this->uid,1,$info['uid']);
//            if($info['redbag_type']==2 && $info['redbag']>0){
//                /*冻结赠金*/
//                $this->freezeCoin($info['redbag'],$id,$this->uid,2,$info['uid']);
//            }
        }else{
            /*别人报名*/
            $signCount=$this->getSignOndateCount($id);
            /*除了第一个，其他都要再次冻结*/
            if($signCount>1){
                $coin=C("USER_DATE_COIN");
//                if($info['redbag_type']==1){
//                    /*我赠送金币*/
//                    $coin+=$info['redbag'];
//                }
                if(!$this->checkUserCoin($coin)){
                    /*我的金币不够了*/
                    $this->apiError(-8,'余额不够了,请充值');
                }
                /*冻结约会金币*/
                $this->freezeCoin(C("USER_DATE_COIN"),$id,$this->uid,1,$uid);
//                if($info['redbag_type']==1 && $info['redbag']>0){
//                    /*冻结赠金*/
//                    $this->freezeCoin($info['redbag'],$id,$this->uid,2,$uid);
//                }
            }
        }

        $arr['id']=$id;
        $arr['ontime']=time();
        if($info['type']==1){
            $arr['onuid']=$info['onuid']?$info['onuid'].','.$uid:$uid;
        }elseif($info['type']==2){
            $arr['onuid']=$this->uid;
        }
        $arr['onusernum']=$info['onusernum']+1;
        $arr['status']=2;
        $res=M('user_date')->save($arr);
        if($res){
            /*生成密码*/
            $this->newDatePwd($id,$uid,$this->uid);
            $this->newDatePwd($id,$this->uid,$uid);
            if($info['type']==1){
                /*修改报名字段*/
                M('user_date_sign')->where(array('status'=>1,'date_id'=>$id,'uid'=>$uid))->setField('ondate',1);
            }else{
                M('user_date')->where(array('id'=>$id))->setField('ondate',1);
            }
            /*发送通知*/
            $this->push2user($uid,'有人接受了你的意向，请查看',3,$id);
            S('date_'.$id,null);
            $this->apiSuccess('success');
        }else{
            if($info['type']==2){
                /*解冻*/
                $coin=$this->reCoinFreezing($this->uid,$id);
            }
            $this->apiError(0,'响应失败');
        }

    }
    /*约会信息*/
    protected function getDateInfo($id){
        S('date_'.$id,null);
        $info=S('date_'.$id);
        if(empty($info)){
            $info=M('user_date')->find($id);
            if($info){
                $cate=$this->getCateInfo($info['cid']);
                unset($cate['id']);
            }
            S('date_'.$id,$info);
        }
        if($info){
            $info['signList']=$this->getSignUserList($id);
//            $info['signNum']=count($info['signList']);
        }
        return $info;
    }
    /*报名人数*/
    protected function getSignCount($date_id){
        $map['date_id']=$date_id;
        $map['status']=1;
        $count=M('user_date_sign')->where($map)->count();
        return $count;
    }
    /*报名接受人数*/
    protected function getSignOndateCount($date_id){
        $map['date_id']=$date_id;
        $map['status']=1;
        $map['ondate']=array('in','1,3');
        $count=M('user_date_sign')->where($map)->count();
        return $count;
    }
    /*报名人列表*/
    protected function getSignUserList($date_id){
        $map['date_id']=$date_id;
        $map['status']=1;
        $field=array('uid','create_time','ondate');
        $list=M('user_date_sign')->field($field)->where($map)->select();
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['create_time']=date('Y-m-d H:i',$v['create_time']);
                $user=$this->getUserInfo2($v['uid']);
                if($v['ondate']==0 || $v['ondate']==2){
                   unset($user['phone']);
                }else{
                   $list[$key]['pwd']=$this->checkOnDate($date_id,$this->uid,$v['uid']);;
                }
                $list[$key]['tousu']=$this->checkTousu($v['uid'],$date_id);
                $list[$key]['signUser']=$user;
            }
        }
        return $list;
    }
    /*检测响应*/
    protected function checkOnDate($date_id,$uid,$uid1){
        $arr['uid']=$uid;
        $arr['uid1']=$uid1;
        $arr['date_id']=$date_id;
        $arr['status']=array(array('eq',1),array('eq',2),'or');
        $is_on=M('user_date_pwd')->where($arr)->getField('pwd');
        if($is_on){
            return $is_on;
        }else{
            return false;
        }
    }
    /*响应报名人数*/
    protected function getOnDateCount($date_id,$uid){
        $arr['uid']=$uid;
        $arr['date_id']=$date_id;
        $arr['status']=1;
        $count=M('user_date_pwd')->where($arr)->count();
        return $count;
    }
    /*生成约会密码*/
    protected function newDatePwd($date_id,$uid,$uid1){
        if($this->checkUserDatePwd($uid,$uid1,$date_id)){
            return true;
        }
        $arr['uid']=$uid;
        $arr['uid1']=$uid1;
        $arr['date_id']=$date_id;
        $arr['pwd']=mt_rand(100000,999999);
        $arr['create_time']=time();
        $arr['status']=1;
        if($this->checkPwdUnique($arr['pwd'])){
            $this->newDatePwd($date_id,$uid,$uid1);
        }
        $res=M('user_date_pwd')->add($arr);
        if($res){
            return true;
        }else{
            $this->newDatePwd($date_id,$uid,$uid1);
        }
    }
    /*检测密码唯一性*/
    protected function checkPwdUnique($pwd){
        $map['status']=1;
        $map['pwd']=$pwd;
        $isExists=M('user_date_pwd')->where($map)->getField('id');
        if($isExists){
            return true;
        }else{
            return false;
        }
    }
    /*检测用户密码存在*/
    protected function checkUserDatePwd($uid,$uid1,$date_id){
        $arr['uid']=$uid;
        $arr['uid1']=$uid1;
        $arr['date_id']=$date_id;
        $arr['status']=1;
        $isExists=M('user_date_pwd')->where($arr)->getField('id');
        if($isExists){
            return true;
        }else{
            return false;
        }
    }
    /*输入约会密码*/
    public function datePwd(){
//        $date_id=I('date_id');
        $pwd=I('post.pwd');
        if(!$pwd){
            $this->apiError(0,'参数不能为空');
        }
//        $date=$this->getDateInfo($date_id);
//        if(!$date){
//            $this->apiError(0,'未知的约会');
//        }

//        $arr['date_id']=$date_id;
        $arr['pwd']=$pwd;
        $arr['uid1']=$this->uid;
        $arr['status']=1;
        $arr['res']=0;
        $check=M('user_date_pwd')->where($arr)->find();
        $date=$this->getDateInfo($check['date_id']);
        if(!$date){
            $this->apiError(0,'未知的意向');
        }
        if($check){
            /*解冻金币，扣除手续费,赠币转让*/
            $this->reDateCoin($date,$check['uid'],$check['uid1']);
            /*更新状态*/
            if($date['type']==1){
                /*报名*/
                $map['date_id']=$check['date_id'];
                $map['uid']=array('in',$check['uid1'].','.$check['uid']);
                $map['status']=1;
                M('user_date_sign')->where($map)->setField('ondate',3);
            }elseif($date['type']==2){
                /*私约*/
                M('user_date')->where(array('id'=>$check['date_id']))->setField('ondate',3);
            }
            M('user_date')->where(array('id'=>$check['date_id']))->setField('status',3);

            $arr1['uid1|uid']=$this->uid;
            $arr1['status']=1;
            $arr1['date_id']=$check['date_id'];
            M('user_date_pwd')->where($arr1)->setField('status',2);
            $this->apiSuccess('解冻成功');
        }else{
            $this->apiError(0,'密码错误');
        }
    }
    /*查看约会密码*/
    public function datePwdList(){
        $date_id=I('date_id');
        if(!$date_id){
            $this->apiError(0,'未知的意向');
        }
        $where['date_id']=$date_id;
        $where['uid']=$this->uid;
        $where['status']=1;
        $pwd=M('user_date_pwd')->where($where)->getField('pwd');
        $data['pwd']=$pwd;
        if($pwd){
            $this->apiSuccess('success',$data);
        }else{
            $this->apiError(0,'未查找到密码');
        }
    }
    /*约会金币解冻并扣取手续费,赠金转让*/
    protected function reDateCoin($dateInfo,$uid,$uid1){

        $arr['date_id']=$dateInfo['id'];
        $arr['uid']=array('in',$uid.','.$uid1);
        $arr['status']=1;
        $list=M('coin_freezing')->where($arr)->select();
        $this->coinDoing($list,$dateInfo,$uid);

    }
    /*约会金币操作*/
    protected function coinDoing($list,$dateInfo,$uid){
        $per=C('USER_DATE_SXF');
        if(!empty($list)){
            foreach($list as $key=>$v){
                if($v['type']==1){
                    $foruid=$v['uid']==$dateInfo['uid']?$uid:$dateInfo['uid'];
                    /*约会金币*/
                    $coin=$v['coin']*(100-$per)/100;
                    if($coin>0){
                        /*扣除手续费并返还*/
                        $this->addCoin($coin,$v['uid'],$dateInfo['id'],$foruid,'datefree');
                    }
                    /*手续费日志*/
                    $sxf=$v['coin']*$per/100;
                    $arr1['uid']=$v['uid'];
                    $arr1['order_id']=$dateInfo['id'];
                    $arr1['foruid']=$foruid;
                    $arr1['type']=2;
                    $arr1['status']=1;
					$arr1['style']='datesys';
                    $arr1['coin']=$sxf;
                    $arr1['create_time']=time();
                    $arr1['update_time']=time();
                    $this->coinLog($arr1);
                }elseif($v['type']==2){
                    /*赠金*/
//                    $foruid=$v['uid']==$dateInfo['uid']?$uid:$dateInfo['uid'];
//                    $this->addCoin($v['coin'],$foruid,$dateInfo['id'],$v['uid'],'dategive');
                }
                $freezing['id']=$v['id'];
                $freezing['status']=-1;
                M('coin_freezing')->save($freezing);
            }
        }
    }
    /*我的发布*/
    public function myDateList(){
        $map['uid']=$this->uid;
        $map['type']=1;
//        $map['status']=array('in','1,2');
        $field=array('id','cid','place','redbag_type','redbag','date_time','status','create_time','lng','lat');
        $count=M('user_date')->field($field)->where($map)->count();
        $page=new \Think\Page($count,10);
        $list=M('user_date')->field($field)->where($map)
            ->order('create_time desc')
            ->limit($page->firstRow,$page->listRows)
            ->select();
        if(empty($list)){
            $this->apiError(0,'未查找到数据');
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
                if($v['status']!=3 && strtotime($v['date_time'])<time()){
                    $list[$key]['status']='4';
                }
                $list[$key]['style']=1;

                $list[$key]['signCount']=$this->getSignCount($v['id']);
                $list[$key]['onDateCount']=$this->getOnDateCount($v['id'],$this->uid);
                $list[$key]['create_time']=date('m月d日 H:i',$v['create_time']);
            }
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }
    }
    /*我的私约*/
    public function mySingleDateList(){
        $map['inviteuid|uid']=$this->uid;
        $map['type']=2;
//        $map['status']=array('in','1,2');
        $field=array('id','cid','place','redbag_type','redbag','date_time','inviteuid','uid','ondate','lng','lat','status');
        $count=M('user_date')->field($field)->where($map)->count();
        $page=new \Think\Page($count,10);
        $list=M('user_date')->field($field)->where($map)
            ->order('create_time desc')
            ->limit($page->firstRow,$page->listRows)
            ->select();
        $sign=$this->mySignList();
        if($list && $sign){
            $list=array_merge($list,$sign);
        }else{
            $list=$list?$list:$sign;
        }
        if(empty($list)){
            $this->apiError(0,'未查找到数据');
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
                if($v['status']!=3 && strtotime($v['date_time'])<time()){
                    $list[$key]['status']='4';
                }
                $list[$key]['style']=1;

                if($v['type']){
                    $list[$key]['user']=$this->getUserInfo2($v['uid']);
                    if($v['ondate']==1){
                        $list[$key]['pwd']=$this->checkOnDate($v['id'],$this->uid,$v['uid']);
                    }
                    $list[$key]['tousu']=$this->checkTousu($v['uid'],$v['id']);
                }else{
                    if($v['uid']==$this->uid){
                        $list[$key]['type']="我邀请";
                        $list[$key]['user']=$this->getUserInfo2($v['inviteuid']);
                        if($v['ondate']==1){
                            $list[$key]['pwd']=$this->checkOnDate($v['id'],$this->uid,$v['inviteuid']);
                        }
                        $list[$key]['tousu']=$this->checkTousu($v['inviteuid'],$v['id']);
                    }else{
                        $list[$key]['type']="邀请我";
                        $list[$key]['user']=$this->getUserInfo2($v['uid']);
                        if($v['ondate']==1){
                            $list[$key]['pwd']=$this->checkOnDate($v['id'],$this->uid,$v['uid']);
                        }
                        $list[$key]['tousu']=$this->checkTousu($v['uid'],$v['id']);
                    }
                }
            }
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }
    }
    /*我报名的*/
    protected function mySignList(){
        $map['s.uid']=$this->uid;
        $map['s.status']=1;
        $field=array('d.id','d.cid','d.place','d.redbag_type','d.redbag','d.date_time','d.inviteuid','d.uid','s.ondate','d.lng','d.lat','d.status');
        $pre=C('DB_PREFIX');
        $count=M()->field($field)
            ->table($pre."user_date d")
            ->join($pre."user_date_sign s on s.date_id=d.id")
            ->where($map)
            ->order('s.create_time')
            ->count();
        $page=new \Think\Page($count,10);
        $list=M()->field($field)
            ->table($pre."user_date d")
            ->join($pre."user_date_sign s on s.date_id=d.id")
            ->where($map)
            ->order('s.create_time desc')
            ->select();
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['type']="报名";
                if($v['status']!=3 && strtotime($v['date_time'])<time()){
                    $list[$key]['status']='4';
                }
            }
        }
        return $list;
    }
    /*停止报名*/
    public function stopDate(){
        $id=I('get.id');
        if(!$id){
            $this->apiError(0,'未知的意向');
        }
        $map['uid']=$this->uid;
        $map['id']=$id;
        $map['status']=array('gt',0);
        $info=M('user_date')->where($map)->find();
        if(!$info){
            $this->apiError(0,'未知的意向');
        }
        $now=date('Y-m-d H:i:s',time()-5);
        if($info['date_time']<$now){
            $this->apiError(0,'意向已过期');
        }
        if($this->checkOnDateStatus($id,$this->uid)){
            $this->apiError(0,'意向已经达成,不可取消');
        }
        M('user_date')->where($map)->setField("date_time",$now);
        S('date_'.$id,null);
        $this->apiSuccess("操作成功");
    }
    /*报名人员*/
    public function dateSignList(){
        $id=I('get.id');
        if(!$id){
            $this->apiError(0,'未知的意向');
        }
        $info=$this->getDateInfo($id);
        if($info){
            if($info['type']==1){
                $info['signCount']=$this->getSignCount($id);
                $info['onDateCount']=$this->getOnDateCount($id,$this->uid);
                $info['create_time']=date('m月d日 H:i',$info['create_time']);
                unset($info['title']);
                unset($info['des']);
            }else{
                $arr['uid']=$info['inviteuid'];
                $arr['create_time']=date('m月d日 H:i',$info['create_time']);
                $arr['ondate']=$info['ondate'];
                $user=$this->getUserInfo2($arr['uid']);
                if($arr['ondate']!=1){
                    unset($user['phone']);
                }else{
                    if($this->uid==$info['uid']){
                        $arr['pwd']=$this->checkOnDate($id,$this->uid,$info['inviteuid']);
                        $arr['tousu']=$this->checkTousu($info['inviteuid'],$id);
                    }else{
                        $arr['pwd']=$this->checkOnDate($id,$this->uid,$info['uid']);
                        $arr['tousu']=$this->checkTousu($info['uid'],$id);
                    }

                }
                $arr['signUser']=$user;
                $brr[0]=$arr;
                $info['signList']=$brr;
            }
            $cate=$this->getCateInfo($info['cid']);
            if($info['redbag_type']==1){
                $str="愿付酬金".$info['redbag']."元";
                $info['title']=$str.",需要".$cate['title'];
            }else{
                $str="需要酬金".$info['redbag']."元";
                $info['title']=$str.",提供".$cate['title'];
            }
            if(strtotime($info['date_time'])<time()){
                $info['status']='4';
            }
            $data['info']=$info;
            $this->apiSuccess('success',$data);
        }else{
            $this->apiError(0,'未知的意向');
        }
    }
    
    /*约会说明*/
    public function aboutDate(){
        $info=$this->getAboutDate();
        $data['info']=$info;
        $this->apiSuccess('success',$data);
    }
    /*投诉*/
    public function tousu(){
        $date_id=I('date_id');
        $foruid=I('foruid');
        $reason=I('reason');
        if(!$date_id || !$foruid || !$reason){
            $this->apiError(0,'参数错误');
        }
        if($foruid==$this->uid){
            $this->apiError(0,'自己不能投诉自己');
        }
        $date=$this->getDateInfo($date_id);
        if(!$date){
            $this->apiError(0,'未知的意向');
        }
        if($date['type']==1){
            if($date['uid']!=$this->uid && !$this->checkSign($this->uid,$date_id)){
                $this->apiError(0,'不能投诉');
            }
            if($date['uid']!=$foruid && !$this->checkSign($foruid,$date_id)){
                $this->apiError(0,'投诉对象错误');
            }
        }else{
            if($this->uid!=$date['uid'] && $this->uid!=$date['inviteuid']){
                $this->apiError(0,'不能投诉');
            }
            if($foruid!=$date['uid'] && $foruid!=$date['inviteuid']){
                $this->apiError(0,'不能投诉');
            }
        }
        if($date['status']==3){
            $this->apiError(0,'不能投诉');
        }
        if($this->checkTousu($foruid,$date_id)){
            $this->apiError(0,'已经投诉了');
        }
        M('member')->where(array('uid'=>$foruid))->setInc("tousu");
        $map['foruid']=$foruid;
        $map['uid']=$this->uid;
        $map['date_id']=$date_id;
        $map['treason']=$reason;
        $map['create_time']=time();
        M('tousu_log')->add($map);
        $this->apiSuccess('success');
    }
    /*取消投诉*/
    public function delTousu(){
        $date_id=I('date_id');
        $foruid=I('foruid');
        $reason=I('reason');
        if(!$date_id || !$foruid || !$reason){
            $this->apiError(0,'参数错误');
        }
        $date=$this->getDateInfo($date_id);
        if(!$date){
            $this->apiError(0,'未知的意向');
        }
        if(!$this->checkTousu($foruid,$date_id)){
            $this->apiError(0,'未投诉');
        }
        M('member')->where(array('uid'=>$foruid))->setDec("tousu");
        $map['foruid']=$foruid;
        $map['uid']=$this->uid;
        $map['date_id']=$date_id;
        $arr['status']=-1;
        $arr['qreason']=$reason;
        $res=M('tousu_log')->where($map)->save($arr);
        if($res){
            $this->apiSuccess('success');
        }else{
            $this->apiError(0,'取消失败');
        }
    }
    /*检测投诉*/
    protected function checkTousu($foruid,$date_id){
        $map['foruid']=$foruid;
        $map['uid']=$this->uid;
        $map['date_id']=$date_id;
        $map['status']=1;
        $isE=M('tousu_log')->where($map)->find();
        if($isE){
            return true;
        }else{
            return false;
        }
    }
    /*投诉图片*/
    public function tousuPic(){
        $arr['pic_id']=I('pic_id');
        $arr['msg']=I('msg');
        $arr['uid']=$this->uid;
        $res=D('TousuPic')->addTousu($arr);
        if(!$res){
            $this->apiError(0,D('TousuPic')->getError());
        }else{
            $this->apiSuccess('投诉成功');
        }
    }
}
?>