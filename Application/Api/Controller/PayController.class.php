<?php

namespace Api\Controller;


class PayController extends BaseController{
    /*用户金币消费*/
    public function userPayForCoin(){
        $arr=I('post.');
        if(!$arr['type'] || !$arr['content_id']){
            $this->apiError(0,'非法请求');
        }
        if($arr['type']=='give' || $arr['type']=='getmoney' && !$arr['paycoin']){
            $this->apiError(0,'信用豆不能为零');
        }
        if($arr['type']=='give' || $arr['type']=='getmoney' && $arr['paycoin'] && !$this->checkUserCoin($arr['paycoin'])){
            $this->apiError(0,'您的余额不够了');
        }

        $type=$arr['type'];
        $content_id=$arr['content_id'];
        //video:观看视频，phone:查看手机号码，give:赠送金币，getmoney:提现
        switch($type){
            case 'video':
                if($this->checkPay($type,$content_id)){
                    /*已消费*/
                    $this->apiError(0,'已经付费');
                }
                $info=$this->checkVideo($content_id);
                if(!$info){
                    $this->apiError(0,'视频不存在或未通过审核');
                }
                if(!$this->checkUserCoin(C('USER_WATCH_VIDEO'))){
                    $this->apiError(0,'您的余额不够了');
                }
                $arr['foruid']=$info['uid'];
                $arr['coin']=$info['paycoin'];
                $arr['status']=1;
                break;
            case 'phone':
                if($this->checkPay($type,$content_id)){
                    /*已消费*/
                    $this->apiError(0,'已经付费');
                }
                $info=$this->checkPhoneUser($content_id);
                if(!$info){
                    /*用户不存在*/
                    $this->apiError(0,'用户不存在');
                }
                if(!$this->compareVip($this->uid,$arr['content_id'])){
                    /*对方比你高级*/
                    $this->apiError(0,'对方的VIP等级比你高呦');
                }
                if(!$this->checkUserCoin(C('USER_WATCH_PHONE'))){
                    $this->apiError(0,'您的余额不够了');
                }
                $arr['foruid']=$info['uid'];
                $arr['coin']=$info['paycoin'];
                $arr['status']=1;
                break;
            case 'give':
                $info=$this->checkPhoneUser($content_id);
                if(!$info){
                    /*被赠用户不存在*/
                    $this->apiError(0,'被赠用户不存在');
                }
                $arr['foruid']=$content_id;
                $arr['coin']=$arr['paycoin'];
                $arr['status']=1;
                break;
            case 'getmoney':
                /*提现*/
                $back=$this->checkCoinNum($arr['paycoin']);
                if(!$back){
                    $this->apiError(0,'提现最低限额为'.C('USER_GET_MONEY').'信用豆');
                }
                $arr['foruid']=$this->uid;
                $arr['status']=2;//待处理
                $arr['coin']=$arr['paycoin'];
                break;
            default:
                $this->apiError(0,'非法请求');
                break;
        }
        $arr['uid']=$this->uid;
        $arr['create_time']=time();
        $arr['update_time']=time();
        $res=M('user_order_coin')->add($arr);
        if($res){
            /*扣除金币*/
            $this->deCoin($arr['coin'],$res,$arr['foruid'],$type);
            if($arr['type']=='phone'){
                /*金币分成*/
                $coin=round(intval($arr['coin'])/C('USER_PHONE_SCALE'));
                $this->addCoin($coin,$arr['content_id'],$res,$arr['foruid'],$type);
            }
            $this->apiSuccess('success');
        }else{
            $this->apiError(0,'提交失败');
        }

    }
    public function giveCoin(){
        $uid=I('uid');
        $coin=I('coin');
        if(!$uid || !$coin){
            $this->apiError(0,'参数错误');
        }
        $user=$this->getUserInfo($uid);
        if(!$user){
            /*被赠用户不存在*/
            $this->apiError(0,'被赠用户不存在');
        }
        if(!$this->checkUserCoin($coin)){
            $this->apiError(0,'您的余额不够了');
        }
        $arr['foruid']=$uid;
        $arr['coin']=$arr['paycoin'];
        $arr['status']=1;
        $arr['uid']=$this->uid;
        $arr['type']='give';
        $arr['create_time']=time();
        $arr['update_time']=time();
        $res=M('user_order_coin')->add($arr);
        if($res){
            $this->deCoin($coin,$res,$uid,'give');
            $this->addCoin($coin,$uid,$res,$this->uid,'give');
            $info=$this->getUserInfo($this->uid);
            $this->push2user($uid,"会员:".$info['nickname']."赠送你".$coin."信用豆，请注意查收",3,$this->uid,3);
            $this->apiSuccess("success");
        }
        $this->apiError(0,'赠送失败');
    }
    /*提现申请*/
    public function getMoney(){
        $coin=I('post.coin');
        $account=I('post.account');
        if(!$coin || !$account){
            $this->apiError(0,'参数错误');
        }
        if(!$this->checkCoinNum($coin)){
            $this->apiError(0,'提现最低限额为'.C('USER_GET_MONEY').'信用豆');
        }
        if(!$this->checkUserCoin($coin)){
            $this->apiError(0,'您的余额不够了');
        }
        $map['status']=1;
        $map['type']='getmoney';
        $map['account']=$account;
        $map['coin']=$coin;
        $map['money']=ceil($coin*(100-C('GET_MONEY_SCALE'))/100);
        $map['scale']=C('GET_MONEY_SCALE')/100;
        $map['create_time']=time();
        $map['update_time']=time();
        $map['uid']=$this->uid;
        $res=M('user_order_money')->add($map);
        if($res){
            $this->deCoin($coin,$res,$this->uid,'getmoney');
            $this->apiSuccess('success');
        }
        $this->apiError(0,'申请失败');
    }
    /*提现记录*/
    public function getmoney_list(){
        $map['type']='getmoney';
        $map['uid']=$this->uid;
        $count=M('user_order_money')->where($map)->count();
        $page=new \Think\Page($count,10);
        $fields=array('type','money','coin','create_time','status','account');
        $list=M('user_order_money')->field($fields)->where($map)
            ->order('create_time desc')->limit($page->firstRow,$page->listRows)
            ->select();
        if($list){
            foreach($list as $key=>$v){
                $list[$key]['create_time']=date("Y-m-d H:i",$v['create_time']);
            }
            $data['list']=$list;
            $this->apiSuccess('success',$data);
        }
        $this->apiError(0,'未查找到数据');
    }
    /*检测视频存在*/
    protected function checkVideo($id){
        $map['status']=1;
        $map['id']=$id;
        $field=array('id','uid');
        $isExists=M('user_video')->field($field)->where($map)->find();
        if($isExists){
            $isExists['paycoin']=C('USER_WATCH_VIDEO');
        }
        return $isExists;
    }
    /*检测手机主人*/
    protected function checkPhoneUser($uid){
        $info=$this->getUserInfo($uid);
        if($info){
            $info['paycoin']=C('USER_WATCH_PHONE');
        }
        return $info;
    }

    /*金币购买*/
    public function payforvip(){
        /*充值*/
        $field=array('id','coin','money','more','des','pic','product_id');
        $map['status']=1;
        $map['type']=1;
        $coin=M('coin_vip')->field($field)->where($map)->order('coin')->select();
        /*vip*/
        $field=array('id','coin','pic','vip','more');
        $map['type']=2;
        $vip=M('coin_vip')->field($field)->where($map)->order('coin')->select();
        if($vip){
            $user=$this->getUserInfo($this->uid);
            if($user['vip']){
                $myVip=$this->getVipInfo($user['vip']);
//                print_r($myVip);
            }
            $userVip=$this->get_buyInfo($user['vip']);//
            foreach($vip as $key=>$v){
                if($v['vip']){
                    $info=$this->getVipInfo($v['vip']);
                    $vip[$key]['real_coin']=$v['coin'];
                    $vip[$key]['title']=$info['title'];
                    if(!$myVip){
                        $vip[$key]['is_click']=true;
                    }elseif($myVip['level']>=$info['level']){
                        $vip[$key]['is_click']=false;
                    }else{
                        $coin1=($v['coin']*1-$userVip['coin']*1)*1.2;
                        $vip[$key]['real_coin']=$coin1;
                        $vip[$key]['is_click']=true;
                    }
                }
            }
        }
        $info=$this->getAboutDate();
        $list['buyabout']=$info['buyabout'];
        $list['coin']=$coin;
        $list['vip']=$vip;
        $this->apiSuccess('success',$list);
    }
    /*购买*/
    public function buyone(){
        $id=I('get.id');
        $type=I('get.type');
        if(!$id || !$type){
            $this->apiError(0,'参数不能为空');
        }
        $map['id']=$id;
        $map['type']=$type;
        $map['status']=1;
        $info=M('coin_vip')->where($map)->find();
        if(!$info){
            $this->apiError(0,'非法请求');
        }
        if($type==1){
            /*充值*/
            $order['uid']=$this->uid;
            $order['type']="coin";
            $order['coin']=$info['coin']*1+$info['more'];
            $order['content_id']=$id;
            $order['money']=$info['money'];
            $order['status']=1;
            $order['create_time']=time();
            $order['update_time']=time();
            $order['order_no']=$this->newOrder();
            /*订单*/
            $res=M('user_order_money')->add($order);
            if($res){
                $info['order_no']=$order['order_no'];
                $info['money']=$order['money'];
                $info['coin']=$order['coin'];
                $data['info']=$info;
                $this->apiSuccess('success',$data);
            }
        }elseif($type==2){
            $coin=0;
            /*购买vip*/
            $user=$this->getUserInfo($this->uid);
            if($user['vip']){
                /*已为vip*/
                $userlevel=$this->getVipLevel($user['vip']);
                $buylevel=$this->getVipLevel($info['vip']);
                if($userlevel>=$buylevel){
                    $this->apiError(0,'您已经是'.$userlevel.'级会员了，无需再次购买');
                }
                $userVip=$this->get_buyInfo($user['vip']);
                $coin=($info['coin']*1-$userVip['coin']*1)*1.2;
                if($coin<=0){
                    $this->apiError(0,'系统错误');
                }
            }else{
                /*第一次购买*/
                if(!$this->checkUserCoin($info['coin'])){
                    $this->apiError(0,'您的余额不够了');
                }
                $coin=$info['coin'];
            }
            $order['uid']=$this->uid;
            $order['type']="vip";
            $order['coin']=$coin;
            $order['content_id']=$info['vip'];
            $order['status']=1;
            $order['create_time']=time();
            $order['update_time']=time();
            /*订单*/
            $res=M('user_order_coin')->add($order);
            /*减去金币*/
            $this->deCoin($coin,$res,$this->uid,'vip');
            $arr['uid']=$this->uid;
            $arr['vip']=$info['vip'];
            M('member')->save($arr);
            S('userinfo_'.$this->uid,null);
            $big=$this->getBigLevel();
            if($info['vip']==$big){
                $data['is_big']=1;
            }else{
                $data['is_big']=0;
            }
            $this->apiSuccess('success',$data);
        }

    }
    /*聊天付款*/
    public function payIM(){
        $uid=I('uid');
        if(!$uid){
            $this->apiError(0,'未知的用户');
        }
        if($this->checkPay('im',$uid)){
            $this->apiSuccess('已付款');
        }
        $coin=C('PAY_IM');

        $order['uid']=$this->uid;
        $order['type']="im";
        $order['coin']=$coin;
        $order['content_id']=$uid;
        $order['status']=1;
        $order['create_time']=time();
        $order['update_time']=time();
        /*订单*/
        $res=M('user_order_coin')->add($order);
        /*减去金币*/
        $this->deCoin($coin,$res,$this->uid,'im');

        $this->apiSuccess('success');
    }
    /*获取vip价格*/
    protected function get_buyInfo($vip){
        $map['status']=1;
        $map['vip']=$vip;
        $info=M('coin_vip')->where($map)->find();
        return $info;
    }
    /*生成订单号*/
    protected function newOrder(){
        $order_no=date("YmdHis").rand(10000,99999);
        $map['order_no']=$order_no;
        $isExists=M('user_order_money')->where($map)->find();
        if($isExists){
            $this->newOrder();
        }
        return $order_no;
    }
    /*支付宝支付*/
    public function payMoney(){
        $order_no=I('order_no');
        if(!$order_no){
            $this->apiError(0,"未知的订单号");
        }
        require_once("Data/alipay/pay/alipay.config.php");
        require_once("Data/alipay/pay/lib/alipay_submit.class.php");
        $ali_partner=C('ALIPAY_PARTNER');
        $ali_key=C('ALIPAY_KEY');
        if($ali_partner){
            $alipay_config['partner']=$ali_partner;
        }
        if($ali_key){
            $alipay_config['key']=$ali_key;
        }
        /**************************请求参数**************************/
        $map['order_no']=$order_no;
        $info=M('user_order_money')->where($map)->find();
        if(!$info || $info['type']!='coin'){
            $this->apiError(0,"未知的订单");
        }
        if($info['status']==2){
            $this->apiError(0,"已支付");
        }
        /************************************************************/

        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service" => "create_forex_trade_wap",
            "partner" => trim($alipay_config['partner']),
            "_input_charset"	=> trim(strtolower($alipay_config['input_charset'])),
            "notify_url"	=> 'http://fille.wbteam.cn/index.php/Api/Pay/server_callback',
            "out_trade_no"	=> $order_no,
            "currency"	=> 'USD',
            "subject"	=> "购买".$info['coin']."信用豆",
            "total_fee"	=> round($info['money'],2),
        );

        //建立请求
        $alipaySubmit = new \AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestHttp($parameter);
        //解析XML
        //注意：该功能PHP5环境及以上支持，需开通curl、SSL等PHP配置环境。建议本地调试时使用PHP开发软件
        $doc = new DOMDocument();
        $doc->loadXML($html_text);

        //请在这里加上商户的业务逻辑程序代码

        //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——

        //获取支付宝的通知返回参数，可参考技术文档中页面跳转同步通知参数列表

        //解析XML
        if( ! empty($doc->getElementsByTagName( "alipay" )->item(0)->nodeValue) ) {
            $alipay = $doc->getElementsByTagName( "alipay" )->item(0)->nodeValue;
            echo $alipay;
        }
        echo 1;die;
    }
    /*回调*/
    public function server_callback(){
        require_once("Data/alipay/pay/alipay.config.php");
        require_once("Data/alipay/pay/lib/alipay_notify.class.php");
        //计算得出通知验证结果
        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        if($verify_result) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            //商户订单号
            $out_trade_no = $_POST['out_trade_no'];
            //支付宝交易号
            $trade_no = $_POST['trade_no'];
            //交易状态
            $trade_status = $_POST['trade_status'];
            $total_fee = $_POST['total_fee'];

            /*订单状态*/
            $map['order_no']=$out_trade_no;
            $arr['trade_no']=$trade_no;
            $arr['status']=2;
            $arr['pay_money']=$total_fee;
            $arr['pay_type']=1;
            $info=M('user_order_money')->where($map)->find();
            if(!$info || $info['status']==2){
                die('fail');
            }
            M('user_order_money')->where($map)->save($arr);
            $coin=$info['coin']*1+$info['more']*1;
            $this->addCoin($coin,$info['uid'],$info['id'],$info['uid'],'coin');
            $this->push2user($info['uid'],'您购买了'.$coin.'信用豆，请注意查收');
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            $email=C('SYS_EMAIL');
            if($email){
                $user=$this->getUserInfo2($info['uid']);
                $msg="会员：".$user['nickname']."于".date("Y-m-d H:i:s",time())."购买了{$coin}信用豆";
                $this->send_mail($email,$msg);
            }
            echo "success";		//请不要修改或删除

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        }
        else {
            //验证失败
            echo "fail";
            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
    }
    /*paypal支付*/
    public function payMoney_paypal(){
        $order_no=I('order_no');
        if(!$order_no){
            $this->apiError(0,"未知的订单号");
        }
        $param['username']=C('PAYPAL_USERNAME');
        $param['pwd']=C('PAYPAL_PWD');
        $param['sign']=C('PAYPAL_SIGN');
        if(!$param['username'] || !$param['pwd'] || !$param['sign']){
            $this->apiError(0,"系统错误，请联系管理人员");
        }
        require_once("Data/Paypal-Express-Checkout/paypal.php");

        /**************************请求参数**************************/
        $map['order_no']=$order_no;
        $info=M('user_order_money')->where($map)->find();
        if(!$info || $info['type']!='coin'){
            $this->apiError(0,"未知的订单");
        }
        if($info['status']==2){
            $this->apiError(0,"已支付");
        }
        /************************************************************/
        $paypal=new \paypal_pay($param);
        if(!$paypal){
            $this->apiError(0,"系统错误，请联系管理人员");
        }
        $data["itemname"]="购买".$info['coin']."信用豆"; //Item Name
        $data["itemprice"]=round($info['money'],2); //Item Price
        $data["itemnumber"]=1; //Item Number
        $data["itemdesc"]="购买".$info['coin']."信用豆"; //Item Number
        $data["itemQty"]=1; // Item Quantity
        $data['order_no']=$order_no;
        $res=$paypal->sendData($data);
        if($res){
            $this->apiError(0,'系统错误',$res);
        }
    }
    /*paypal回调*/
    public function paypal_callback(){
        require_once("Data/paypal/paypal.php");
        $str=file_get_contents("php://input");
        if(!$str){
            die('fail');
        }
        $ipn=new \Paypal_IPN();
        $result=$ipn->process($str);
        if($result!==false) {//验证成功
            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            //请在这里加上商户的业务逻辑程序代
            //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
            //获取支付宝的通知返回参数，可参考技术文档中服务器异步通知参数列表
            $money=$result['payment_gross'];
            //商户订单号
            $out_trade_no = $result['custom'];
            //交易号
            $trade_no = $result['txn_id'];
            /*货币类型*/
            $mc_currency = $result['mc_currency'];
            $status=$result['payment_status'];
            if($status!='Completed' && $status!='Pending'){
                die('fail');
            }
            /*订单状态*/
            $map['order_no']=$out_trade_no;
            $arr['trade_no']=$trade_no;
            $arr['status']=2;
            $arr['pay_type']=2;
            $arr['pay_money']=$money;
            $info=M('user_order_money')->where($map)->find();
            if(!$info || $info['status']==2){
                die('fail');
            }
            $map1['trade_no']=$trade_no;
            $isExists=M('user_order_money')->where($map1)->find();
            if($isExists){
                die('fail');
            }
            M('user_order_money')->where($map)->save($arr);
            $coin=$info['coin']*1+$info['more']*1;
            $this->addCoin($coin,$info['uid'],$info['id'],$info['uid'],'coin');
            $this->push2user($info['uid'],'您购买了'.$coin.'信用豆，请注意查收');
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            $email=C('SYS_EMAIL');
            if($email){
                $user=$this->getUserInfo2($info['uid']);
                $msg="会员：".$user['nickname']."于".date("Y-m-d H:i:s",time())."购买了{$coin}信用豆";
                $this->send_mail($email,$msg);
            }
            echo "success";		//请不要修改或删除

            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        }
        else {
            //验证失败
            echo "fail";
            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
        }
        die;
    }
    /*检测苹果凭证*/
    protected function check_iap_sign($sign){
        $map['iap_sign']=$sign;
        return M('user_order_money')->where($map)->find();
    }
    /*检测苹果支付*/
    public function iap_pay(){
        $order_no=I('order_no');
        $iap_sign=I('iap_sign');
        if(!$order_no){
            $this->apiError(0,"未知的订单号");
        }
        if(!$iap_sign){
            $this->apiError(0,"未知的苹果凭证");
        }
        if($this->check_iap_sign($iap_sign)){
            $this->apiError(0,"非法请求");
        }
        $map['uid']=$this->uid;
        $map['order_no']=$order_no;
        $info=M('user_order_money')->where($map)->find();
        if(!$info){
            $this->apiError(0,'未知的订单');
        }
        if($info['status']==2){
            $this->apiError(0,"已支付");
        }
        M('user_order_money')->where($map)->setField('iap_sign',$iap_sign);
        require_once("Data/iosiap/iap_pay.php");
        $iap=new \iap_pay();
        $res=$iap->check($iap_sign);
        if($res['buy']==0){
            $this->apiError(0,$res['message']);
        }elseif($res['buy']==1){
            $arr['status']=2;
            $arr['pay_type']=3;
            M('user_order_money')->where($map)->save($arr);
            $coin=$info['coin']*1+$info['more']*1;
            $this->addCoin($coin,$info['uid'],$info['id'],$info['uid'],'coin');
            $this->push2user($info['uid'],'您购买了'.$coin.'信用豆，请注意查收');
            //——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
            $email=C('SYS_EMAIL');
            if($email){
                $user=$this->getUserInfo2($info['uid']);
                $msg="会员：".$user['nickname']."于".date("Y-m-d H:i:s",time())."购买了{$coin}信用豆";
                $this->send_mail($email,$msg);
            }
        }
        $this->apiSuccess('success');
    }
    public function success1(){
        $param['username']=C('PAYPAL_USERNAME');
        $param['pwd']=C('PAYPAL_PWD');
        $param['sign']=C('PAYPAL_SIGN');
        if(!$param['username'] || !$param['pwd'] || !$param['sign']){
            $this->apiError(0,"系统错误，请联系管理人员");
        }
        require_once("Data/Paypal-Express-Checkout/paypal.php");
        $paypal=new \paypal_pay($param);
        $arr=I('get.');
        $paypal->getData($arr);
    }
    public function cancel1(){
        echo "取消支付";
        die;
    }
    /*发送邮件*/
    protected function send_mail($email,$msg){
        if(!$email){
            return false;
        }
        /*发送邮件*/
        $subject = "会员购买了信用豆";

        $headers = "MIME-Version: 1.0" . "\n";
        $headers .= "Content-type:text/html;charset=utf-8" . "\n";
        $headers .= "From: ".$email."\n";
//        $res=send_mail($info['email'],$subject,$message);
        mail($email,$subject,$msg,$headers);
    }
    public function testmail($email="fengyeno@126.com",$msg='test'){
        $email1=I('email');
        if(!function_exists('mail')){
            echo 2;
        }
        echo mail($email1?$email1:$email,$msg,$msg);
    }

}