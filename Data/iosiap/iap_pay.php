<?php
class iap_pay{
    /**
     * 检测凭证
     * @param string $receipt_data 购买凭证
     * @return array $result
    */
    public function check($receipt_data){
        //验证参数
//        if (strlen($receipt_data)<20){
//            return array('buy'=>'0','message'=>'非法参数');
//        }

        //请求验证
        $data['sandbox'] = 0;
        $html = $this->acurl($receipt_data);
        $data = json_decode($html,1);
        //如果是沙盒数据 则验证沙盒模式
        if($data['status']=='21007'){
            //请求验证
            $html = $this->acurl($receipt_data, $sandbox=1);
            $data = json_decode($html,1);
            $data['sandbox'] = 1;
        }
        if($data['status']==0){
            $result = array('buy'=>'1','message'=>'购买成功','sandbox'=>$data['sandbox'],'product_id'=>$data['receipt']['product_id']);
        }else{
            $result = array('buy'=>'0','message'=>'购买失败','status'=>$data['status']);
        }
        return $result;
    }
    /**
     * 21000 App Store不能读取你提供的JSON对象
     * 21002 receipt-data域的数据有问题
     * 21003 receipt无法通过验证
     * 21004 提供的shared secret不匹配你账号中的shared secret
     * 21005 receipt服务器当前不可用
     * 21006 receipt合法，但是订阅已过期。服务器接收到这个状态码时，receipt数据仍然会解码并一起发送
     * 21007 receipt是Sandbox receipt，但却发送至生产系统的验证服务
     * 21008 receipt是生产receipt，但却发送至Sandbox环境的验证服务
     * @param string $receipt_data 苹果凭证
     * @param int $sandbox 1：沙盒，0：正式
     * @return string $result
     */
    private function acurl($receipt_data, $sandbox=0){
        //小票信息
        $POSTFIELDS = array("receipt-data" => base64_encode($receipt_data));
        $POSTFIELDS = json_encode($POSTFIELDS);
        //正式购买地址 沙盒购买地址
        $url_buy     = "https://buy.itunes.apple.com/verifyReceipt";
        $url_sandbox = "https://sandbox.itunes.apple.com/verifyReceipt";
        $url = $sandbox ? $url_sandbox : $url_buy;

        //简单的curl
        $ch = curl_init($url);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1); //post到https
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//        curl_setopt($ch, CURLOPT_POST, true);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);//跟随页面的跳转
//        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $POSTFIELDS);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);  //这两行一定要加，不加会报SSL 错误
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $result = curl_exec($ch);
        curl_close($ch);
//        print_r($result);die;
        return $result;
    }
}