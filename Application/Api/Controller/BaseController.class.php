<?php
namespace Api\Controller;
use Think\Controller;
class BaseController extends Controller{
    protected $ukey;
    protected $uid;
    protected $server;
    public function __construct(){        
        parent::__construct();
        /* 读取数据库中的配置 */
//        $this->apiError(0,'系统维护');
        $config =   S('DB_CONFIG_DATA');
        if(!$config){
            $config =   api('Config/lists');
            S('DB_CONFIG_DATA',$config);
        }
        C($config);
        /*添加配置*/

        $extend=array('payMoney','getUploadUrl','cityList','uploadHeadimg','changepwd1','server_callback',
            'paypal_callback','on_time_refreezing','push_test','r_msg','vgetUser','company','knows','payMoney_paypal',
        'success1','cancel1','testmail');
		if(!in_array(ACTION_NAME,$extend)){
            $this->checkLogin();
        }
        $this->server='http://'.$_SERVER['HTTP_HOST'];
    }
    /**
     * 找不到接口时调用该函数
     */
    public function _empty() {
        $this->apiError(404, "找不到该接口");
    }

    /*城市*/
    public function getCity($city_id){
        $map['no']=$city_id;
        $name=M('city_area')->where($map)->getField('areaname');
        return $name;
    }
    protected function getProvince($city_id){
        $map['no']=$city_id;
        $topno=M('city_area')->where($map)->getField('topno');
        if(!$topno){
            return '';
        }
        $map['no']=$topno;
        $name=M('city_area')->where($map)->getField('areaname');
        return $name;
    }
    /*上传地址*/
    protected function getUpUrl(){
        $url=C('WEB_UPLOAD_IP');
        $url=$url?$url:'http://'.$_SERVER['HTTP_HOST'].__ROOT__;
        if(strpos($url,'http://')===false){
            $url='http://'.$url;
        }
        if(strrpos($url,'/')==strlen($url)-1){
            $url=substr($url,0,strlen($url));
        }
        return $url;
    }
    /*检测登录*/
    protected function checkLogin(){
        $ukey=I('get.ukey');
        if(!$ukey){
            $this->apiError(-1,'请登录');
        }
        $this->uid=$this->checkKey($ukey);
        if(!$this->uid){
            $this->apiError(-1,'未知的用户');
        }
    }
    /*检测登录2*/
    protected function checkLogin1(){
        $ukey=I('ukey');
        if(!$ukey){
            return false;
        }
        $this->uid=$this->checkKey($ukey);
        if(!$this->uid){
            return false;
        }
        return $this->uid;
    }
    /*返回成功信息*/
    protected function apiSuccess($message, $extra=null) {
        if($message=="success"){
            $message="操作成功";
        }
        return $this->apiReturn(true, 0, $message, $extra);
    }
    /*返回失败信息*/
    protected function apiError($error_code, $message, $extra=null) {
        return $this->apiReturn(false, $error_code, $message, $extra);
    }
    protected function apiReturn($success, $error_code=0, $message=null, $extra=null) {
    /*生成返回信息*/
        $result = array();
        $result['success'] = $success;
        $result['error_code'] = $error_code;
        if($message !== null) {
            $result['message'] = $message;
        }
        foreach($extra as $key=>$value) {
            $result[$key] = $value;
        }
        /*将返回信息进行编码*/
        $format = $_REQUEST['format'] ? $_REQUEST['format'] : 'json';
        /*返回值格式，默认json*/
        $format = strtolower($format);
        if(in_array($format, array('json','xml','jsonp','eval'))){
            $this->ajaxReturn($result,$format);
        }else{
            $_GET['format'] = 'json';
            $_REQUEST['format'] = 'json';
            return $this->apiError(400, "format参数错误");
        }
    }
    /*检测ukey*/
    protected function checkKey($key){
        $uid=M('ukey')->where(array('ukey'=>$key,'status'=>1))->getField('uid');
        if($uid){
            $isOk=M('member')->where(array('uid'=>$uid,'status'=>1))->find();
            if(!$isOk){
                return false;
            }
        }
        return $uid;
    }
    /*获取ukey*/
    protected function getKey($uid){
        $key=M('ukey')->where(array('uid'=>$uid,'status'=>1))->getField('ukey');
        return $key;
    }
    /*生成ukey*/
    protected function newKey($uid){
        if($key=$this->getKey($uid)){
            return $key;
        }
        $key=getkey();
        if($this->checkKey($key)){
            $this->newKey($uid);
        }
        $ukey=array(
            'uid'=>$uid,
            'ukey'=>$key,
            'createtime'=>time(),
            'updatetime'=>time(),
            'status'=>1        );
        $res=M('ukey')->add($ukey);
        if(!$res){
            $this->newKey($uid);
        }
        return $key;
    }
    /*获取容联账号*/
    protected function getRonglianCount($uid){
        $field=array('subaccountsid','voipaccount');
        $info=M('ucenter_member')->field($field)->find($uid);
        return $info;
    }
    /*获取用户信息*/
    protected function getUserInfo($uid){
        S('userinfo_'.$uid,null);
        $info=S('userinfo_'.$uid);
        if(empty($info)){
            $field=array('uid','nickname','sex','age','headimg','vip','city','video','height','weight','online','phoneout','sound');
            $info=M('member')->field($field)->find($uid);
            if($info){
                $info['headimg']=$info['headimg']?$this->server.$info['headimg']:'';
                $info['cityname']=$this->getCity($info['city']);
                S('userinfo_'.$uid,$info);
            }
        }
        if($info){
            $info1=$this->getUserInfo1($uid);
            $info=array_merge($info,$info1,$this->getRonglianCount($uid));
        }
        return $info;
    }
    /*获取用户名*/
    protected function getUserName($uid){
        return M('ucenter_member')->where(array('id'=>$uid))->getField('username');
    }
    /*获取用实时信息*/
    protected function getUserInfo1($uid){

        $field=array('last_login_time','lng','lat','nowcity','coin');
        $info=M('member')->field($field)->find($uid);
        $info['last_login_time']=date('Y-m-d H:i',$info['last_login_time']);
        
        return $info;
    }
    /*获取用户信息*/
    protected function getUserInfo2($uid){
        $field=array('uid','nickname','headimg','phone');
        $info=M('member')->field($field)->find($uid);
        return $info;
    }
    /*上传图片*/
    protected function upload(){
    /* 上传配置 */
        $setting = C('PICTURE_UPLOAD');
        $info=D('Picture')->upload($_FILES,$setting);
        if($info){
            $info['path']=$info['image']['path'];
        }else{
            $this->apiError(0,'上传失败');
        }
        return $info;
    }
    /*上传文件*/
    protected function uploadFile(){
    /* 上传配置 */
        $setting = C('DOWNLOAD_UPLOAD');
        $info=D('File')->upload($_FILES,$setting);
        if($info){
            $url = $setting['rootPath'].$info['file']['savepath'].$info['file']['savename'];
            $url = str_replace('./', '/', $url);
            $info['path'] = $url;
        }else{
            $this->apiError(0,'上传失败');
        }
        return $info;
    }
    protected function thumbImg($path,$width=0,$height=0){
        $first=substr($path,0,1);
        if($first=='/'){
            $path=substr($path,1);
        }
        $extos=strrpos($path,'.');
        $ext=substr($path,$extos);
        $pos=strrpos($path,'/');
        if(!$width && !$height){
            $size=C('THUMB_PIC');
            $width=$size['width'];
            $height=$size['height'];
        }
        $thumb=substr($path,0,$pos+1).md5_file($path).'_thumb_'.$width.'_'.$height.$ext;
        if(is_file($thumb)){
            return '/'.$thumb;
        }
        $image=new \Think\Image();
        $image->open($path);
        $image->thumb($width, $height)->save($thumb);
        return '/'.$thumb;
    }
    protected function thumbImgById($id,$width=0,$height=0){
        $path=M('picture')->where(array('id'=>$id))->getField('path');
        return $this->thumbImg($path,$width,$height);
    }
    /*加密验证*/
    protected function checkEncrypt($token,$str,$username){
        $token1=sha1(md5($username).$str);
        if($token==$token1){
            $result=true;
        }else{
            $result=false;
        }
        return $result;
    }
    /*获取会员等级*/
    protected function getVipInfo($levelid){
        $map['id']=$levelid;
        $map['status']=array('neq',-1);
        $level=M('user_vip')->field(array('id','level','pic','title'))->where($map)->find();
        $level['pic']=$level['pic']?$this->server.$level['pic']:'';
        return $level;
    }
    /*获取会员等级*/
    protected function getVipLevel($levelid){
        $map['id']=$levelid;
        $map['status']=array('neq',-1);
        $level=M('user_vip')->where($map)->getField('level');
        return $level;
    }
    /*比较两人等级*/
    protected function compareVip($me,$uid){
        $user=$this->getUserInfo($uid);
        $myinfo=$this->getUserInfo($me);
        if($myinfo['vip']==0 && $user['vip']==0){
            return false;
        }
        if($myinfo['vip']==0 && $user['vip']!=0){
        /*对方是vip，自身不是*/
            return false;
        }
        if($user['vip']==0 && $myinfo['vip']!=0){
        /*对方不是vip,自身是*/
            return true;
        }
        $user['level']=$this->getVipLevel($user['vip']);
        $myinfo['level']=$this->getVipLevel($myinfo['vip']);
        if($user['level']>$myinfo['level']){
            return false;
        }else{
            return true;
        }
    }
    /*检测已付费*/
    protected function checkPay($type,$content_id){
        if($type=='give' || $type=='getmoney'){
            return false;
        }
        $map['uid']=$this->uid;
        $map['type']=$type;
        $map['content_id']=$content_id;
        $map['status']=1;
        $isPay=M('user_order_coin')->where($map)->getField('id');
        if($isPay){
            return true;
        }else{
            return false;
        }
    }
    /*检测支付手机*/
    protected function checkPayPhone($uid){
        $map['uid']=$this->uid;
        $map['type']='phone';
        $map['foruid']=$uid;
        $map['status']=1;
        $isPay=M('user_order_coin')->where($map)->getField('id');
        if($isPay){
            return true;
        }else{
            return false;
        }
    }
    /*检测约会*/
    protected function checkDate($uid){
        $map['uid']=array($this->uid,$uid,'or');
        $map['onuid']=array($this->uid,$uid,'or');
        $map['status']=3;
        $sql="select id from messenger_user_date where (uid={$this->uid} and onuid={$uid}) or (uid={$uid} and onuid={$this->uid}) and status=3;";
        $isDate=M()->query($sql);
//        $isDate=M('user_date')->where($map)->getField('id');
        if($isDate){
            return true;
        }else{
            return false;
        }

    }
    /*检测约会状态*/
    protected function checkOnDateStatus($date_id,$uid){
        $info=M('user_date')->find($date_id);
        if(!$info){
            return false;
        }
        if($info['type']==1){
            /*报名*/
            if($info['uid']!=$uid){
                /*不是我发起的*/
                $map['uid']=$uid;
                $map['date_id']=$date_id;
                $map['status']=1;
                $sign=M('user_date_sign')->where($map)->find();
                if(!$sign){
                    return false;
                }else{
                    if($info['ondate']==1 || $info['ondate']==3){
                        /*1:接受，2:拒绝,3:完成*/
                        return true;
                    }else{
                        return false;
                    }
                }
            }else{
                /*我发起的*/
                if($info['status']>1){
                    return true;
                }else{
                    return false;
                }
            }
        }elseif($info['type']==2){
            /*邀请*/
            if($info['ondate']==1 || $info['ondate']==3){
                /*1:接受，2:拒绝,3:完成*/
                return true;
            }else{
                return false;
            }
        }
    }
    /*扣除用户金币*/
    protected function deCoin($num,$order_id,$foruid,$style=''){
        $map['uid']=$this->uid;
        $res=M('member')->where($map)->setDec('coin',$num);
        if($res){
            $arr['uid']=$this->uid;
            $arr['order_id']=$order_id;
            $arr['foruid']=$foruid;
            $arr['style']=$style;
            $arr['type']=2;
            $arr['status']=1;
            $arr['coin']=$num;
            $arr['create_time']=time();
            $arr['update_time']=time();
            $this->coinLog($arr);
            return true;
        }else{
            return false;
        }
    }
    /*冻结用户金币*/
    protected function freezeCoin($num,$date_id,$uid,$type,$foruid=0){
        $map['uid']=$uid;
        $res=M('member')->where($map)->setDec('coin',$num);
        if($res){
            $arr['uid']=$uid;
            $arr['date_id']=$date_id;
            $arr['type']=$type;
            $arr['status']=1;
            $arr['coin']=$num;
            $arr['create_time']=time();
            $arr['update_time']=time();
            M('coin_freezing')->add($arr);
            $arr1['uid']=$uid;
            $arr1['order_id']=$date_id;
            $arr1['foruid']=$foruid?$foruid:$uid;
            $arr1['type']=2;
            $arr1['status']=1;
            $arr1['style']=$type==1?'date':'dategivef';
            $arr1['coin']=$num;
            $arr1['create_time']=time();
            $arr1['update_time']=time();
            $this->coinLog($arr1);
            return true;
        }else{
            return false;
        }
    }
    /*用户添加金币*/
    protected function addCoin($num,$uid,$order_id,$foruid,$style=''){
        $map['uid']=$uid;
        $res=M('member')->where($map)->setInc('coin',$num);
        if($res){
            $arr['uid']=$uid;
            $arr['order_id']=$order_id;
            $arr['foruid']=$foruid;
            $arr['type']=1;
            $arr['status']=1;
            $arr['coin']=$num;
            $arr['style']=$style;
            $arr['create_time']=time();
            $arr['update_time']=time();
            $this->coinLog($arr);
            return true;
        }else{
            return false;
        }
    }
    /*用户收入支出日志*/
    protected function coinLog($arr){
        $res=M('user_coin_log')->add($arr);
        return $res;
    }
    /*获取jid*/
    protected function getJid($uid){
        $map['status']=1;
        $map['id']=$uid;
        $jid=M('ucenter_member')->where($map)->getField('jid');
        return $jid;
    }
    /*检测jid*/
    protected function checkJid($jid){
        $map['jid']=$jid;
        $uid=M('ucenter_member')->where($map)->getField('id');
        return $uid;
    }
    /*新增jid*/
    protected function newJid($uid){
        $jid=getkey(32);
        if($this->checkJid($jid)){
            $this->newJid($uid);
        }
        $arr['id']=$uid;
        $arr['jid']=$jid;
        $arr['update_time']=time();
        $res=M('ucenter_member')->add($arr);
        if(!$res){
            $this->newJid($uid);
        }
        return $jid;
    }
    /*检测最高级*/
    protected function checkBigLevel($uid){
        $info=$this->getUserInfo($uid);
        if(!$info['vip']){
            return false;
        }
        $map['status']=1;
        $bigVip=M('user_vip')->where($map)->order('level desc')->getField('id');
        if($bigVip==$info['vip']){
            return true;
        }else{
            return false;
        }
    }
    /*获取最高级*/
    protected function getBigLevel(){
        $map['status']=1;
        $bigVip=M('user_vip')->where($map)->order('level desc')->getField('id');
        return $bigVip;
    }
    /*半径经纬度*/
    protected function getXY($len,$lon1, $lat1){
        $r1=6377830;//赤道半径
        $r2=6356908.8;//极半径
        $lon=abs($len*180/(pi()*$r1*cos($lon1)));//经度差值
        $lat=$len*180/(pi()*$r2);//纬度差值
        $arr['lon_min']=$lon1-$lon;
        $arr['lon_max']=$lon1+$lon;
        if($arr['lon_max']>180){
            $arr['lon_max']=$arr['lon_max']-360;
        }
        if($arr['lon_min']<-180){
            $arr['lon_min']=-$arr['lon_min']-180;
        }
        $arr['lat_min']=$lat1-$lat;
        $arr['lat_max']=$lat1+$lat;
        return $arr;
    }
    /*检测报名*/
    protected function checkSign($uid,$date_id){
        $map['date_id']=$date_id;
        $map['uid']=$uid;
        $map['status']=1;
        $isSign=M('user_date_sign')->where($map)->getField('id');
        if($isSign){
            return true;
        }else{
            return false;
        }
    }
    /*排序小到大*/
    protected function min2max($arr){
        $count = count($arr);
        for($i=0; $i<$count; $i++){
            for($j=$i+1; $j<$count; $j++){
                if ($arr[$i]['len'] > $arr[$j]['len']){
                    $tmp = $arr[$i];
                    $arr[$i] = $arr[$j];
                    $arr[$j] = $tmp;
                }elseif($arr[$i]['len'] == $arr[$j]['len'] && $arr[$i]['create_time'] > $arr[$j]['create_time']){
                    $tmp = $arr[$i];
                    $arr[$i] = $arr[$j];
                    $arr[$j] = $tmp;
                }
            }
        }
        return $arr;
    }
    /*经纬求距离*/
    protected function rad($d){
        return $d * pi() / 180.0;
    }
    protected function GetDistance($lat1, $lng1, $lat2, $lng2){
        $EARTH_RADIUS = 6371004;
        $radLat1 = $this->rad($lat1);

        $radLat2 = $this->rad($lat2);
        $a = $radLat1 - $radLat2;
        $b = $this->rad($lng1) - $this->rad($lng2);
        $s = 2 * asin(sqrt(pow(sin($a/2),2) +
                cos($radLat1)*cos($radLat2)*pow(sin($b/2),2)));
        $s = $s *$EARTH_RADIUS;
        $s = round($s);
        return $s;
    }
    /*检测提现金额*/
    protected function checkCoinNum($num){
        $min=C('USER_GET_MONEY');
        if($num<$min){
        /*金额太少*/
            return false;
        }
        return true;
    }
    /*检测用户金币*/
    protected function checkUserCoin($num){
        $info=$this->getUserInfo1($this->uid);
        if($info['coin']<$num){
        /*用户的金币不够了*/
            return false;
        }else{
            return true;
        }
    }
    /*检测昵称*/
    protected function checkNickName($nickname){
        $map['uid']=array('neq',$this->uid);
        $map['nickname']=$nickname;
        $isExists=M('member')->where($map)->getField('uid');
        return $isExists;
    }
    /*计算时间段*/
    protected function getTimeNum($time){
        $times=time()-$time;
        if($times>3600*24){
            $day=floor($times/(3600*24));
            $day=$day<=7?$day.'天':'7天前';
            return $day;
        }elseif($times>3600){
            return floor($times/3600).'小时';
        }elseif($times>60){
            return floor($times/60).'分钟';
        }else{
            return $times.'秒';
        }
    }
    /*约会类型私约或者发布*/
    protected function getDateType($date_id){
        $type=M('user_date')->where(array('id'=>$date_id))->getField('type');
        return $type;
    }
    /*推送*/
    protected function push2user($uid,$msg,$type=0,$content_id=0,$style=0){
        if(!$msg){
            return false;
        }
        $arr['title']=$msg;
        $arr['description']=$msg;
        $arr['aps']['alert']=$msg;
        $arr['custom_content']['style']=$type;
        $arr['custom_content']['date_id']=$content_id;
        $arr['custom_content']['uid']=$uid;
        $arr['custom_content']['success']=true;
        $arr['style']=$type;
        $arr['date_id']=$content_id;
        $arr['uid']=$uid;
        $arr['success']=true;
        if($content_id){
            $date_type=$this->getDateType($content_id);
            $arr['custom_content']['date_type']=$date_type;
            $arr['date_type']=$date_type;
        }
//        $info=$this->getUserInfo($uid);

        $users=$this->get_push_user($uid);
        $id=$this->push_log($uid,$arr['title'],2,$content_id,$style);
        $arr['custom_content']['id']=$id?$id:0;
        $arr['id']=$id?$id:0;
        if(!empty($users)){
            foreach($users as $key=>$v){
                if(intval($v['token'])>0){
                    $push=A('Addons://Baidupush/push');
                    $push->__construct('','',$v['type']);
                    if($v['type']==1){
//                        if($info['sound']==0){
//                            $arr['notification_builder_id']=0;
//                            $arr['notification_basic_style']=0x01;
//                        }elseif($info['sound']==2){
//                            $arr['notification_builder_id']=0;
//                            $arr['notification_basic_style']=0x02;
//                        }
                        $msg=json_encode($arr);
                        $push->pushMessage_android($msg,0,$v['token']);
                    }elseif($v['type']==2){
//                        if($info['sound']!=1){
//                            $arr['aps']['sound']="";
//                        }
                        $msg=json_encode($arr);
                        $push->pushMessage_ios($msg,1,$v['token']);
                    }
                }
            }
        }

    }
    protected function push_log($uid,$msg,$type=1,$content_id=0,$style=0){
        $data=array(
            'uid'=>$uid,
            'msg'=>$msg,
            'type'=>$type,
            'content_id'=>$content_id,
            'status'=>1,
            'style'=>$style,
            'create_time'=>time()
        );
        $res=M('baidu_msg')->add($data);
        return $res;
    }
    public function push_test($msg='测试消息'){
        $type=I('get.type');
        $type=$type?$type:1;
        $token=I('get.token');
        $token=$token?$token:'3737105755583432978';
        $style=I('get.style');
        $style=$style?$style:0;
        $date_id=I('get.date_id');
        $date_id=$date_id?$date_id:0;
        $sound=I('get.sound');
        $sound=isset($_GET['sound'])?$sound:1;
        $uid=I('get.uid');
        $uid=$uid?$uid:16;

        $arr['title']=$msg;
        $arr['description']=$msg;
        $arr['aps']['alert']=$msg;
        $arr['custom_content']['style']=$style;
        $arr['custom_content']['date_id']=$date_id;
        $arr['custom_content']['uid']=$uid;
        $arr['custom_content']['success']=true;
        $arr['style']=$style;
        $arr['date_id']=$date_id;
        $arr['uid']=$uid;
        $arr['custom_content']['id']=0;
        $arr['id']=0;
        $arr['success']=true;
        if($date_id){
            $date_type=$this->getDateType($date_id);
//            echo $date_type;die;
            $arr['custom_content']['date_type']=$date_type;
            $arr['date_type']=$date_type;
        }
//        if($type==1){
//            if($sound==0){
//                $arr['notification_builder_id']=0;
//                $arr['notification_basic_style']=0x01;
//            }elseif($sound==2){
//                $arr['notification_builder_id']=0;
//                $arr['notification_basic_style']=0x02;
//            }
//        }elseif($type==2){
//            if($sound!=1){
//                $arr['aps']['sound']="";
//            }
//        }
        $arr['style']=$style;
        $msg=json_encode($arr);
        $push=A('Addons://Baidupush/push');
        $push->__construct('','',$type);
        if($type==1){
            $res=$push->pushMessage_android($msg,0,$token);
        }else{
            $res=$push->pushMessage_ios($msg,1,$token);
        }

        print_r($res);die;
    }
    /*获取推送用户3718875005761379707*/
    protected function get_push_user($uid){
        $map['uid']=$uid;
        $map['status']=1;
        $users=M('baidu_user')->where($map)->group('token')->select();
        return $users;
    }
    /*获取约会类型*/
    protected function getCateInfo($id){
        $info=S('date_cate_'.$id);
        if(empty($info)){
            $info=M('user_date_cate')->find($id);
            S('date_cate_'.$id,$info);
        }
        return $info;
    }
    /*id生成编号*/
    protected function id2no($id){
        $len=5;
        $idlen=strlen($id);
        $no="2".str_repeat("0",$len-$idlen).$id;
        return $no;
    }
    /*检测绑定*/
    protected function checkBind($token){
        $map['token']=$token;
        $map['uid']=$this->uid;
        $map['status']=1;
        $isExists=M('baidu_user')->where($map)->getField('id');
        return $isExists;
    }
    /*约会说明*/
    protected function getAboutDate(){
        $path='Data/dateinfo.config';
        $fp=fopen($path,'r');
        $str=fread($fp,filesize($path));
        fclose($fp);
        if($str) {
            $info = json_decode($str, true);
        }
        return $info;
    }
    /*注册容联*/
    protected function newRonglian($username){
        $res=A('Addons://Ronglian/Ronglian')->createSubAccount($username);
        return $res;
    }
    /*获取通讯录*/
    protected function getFriends(){
        $list=M('adlist')->where(array('status'=>1,'uid'=>$this->uid))->getField('phone',true);
        if($list){
            $str="";
            foreach($list as $v){
                $str.="'".$v."',";
            }
            $str=substr($str,0,strlen($str)-1);
        }
        return $str;
    }
    /*检测关注*/
    protected function checkFollow($uid){
        $map['uid']=$this->uid;
        $map['fuid']=$uid;
        $isExists=M('follow')->where($map)->getField('id');
        if($isExists){
            return true;
        }else{
            return false;
        }
    }
}
