<?php
//以下URL为sanbox，亦即沙盒，表明属于测试环境，真实环境为https://api-3t.paypal.com/nvp和https://www.paypal.com/cgi-bin/webscr&cmd=_express-checkout&useraction=commit&token=
define('API_ENDPOINT', 'https://api-3t.sandbox.paypal.com/nvp');
define('PAYPAL_URL', 'https://www.sandbox.paypal.com/cgi-bin/webscr&cmd=_express-checkout&useraction=commit&token=');
$API_Endpoint =API_ENDPOINT;
class paypal {
    public $errMsg = array();
    function __construct() {
    }
    //获取token的函数
    function SetExpressCheckout($params) {
        $token = '';
        $serverName = $_SERVER['SERVER_NAME'];
        $serverPort = $_SERVER['SERVER_PORT'];
        $url = dirname('https://'.$serverName.':'.$serverPort.$_SERVER['REQUEST_URI']);
        $payAmount = $params['amount'];
        $currency = $params['currency'];
        $payType = $params['payType'];
        $desc = $params['DESC'];
        $returnURL = urlencode($url.'/'.$params['returnPage'].'?cmd=paypal&currency='.$currency.'&payType='.$payType.'&payAmount='.$payAmount);
        $cancelURL = urlencode($url.'/'.$params['cancelPage'].'?cmd=cancel');
        $nvpstr = "&AMT=".$payAmount."&PAYMENTACTION=".$payType."&RETURNURL=".$returnURL."&CANCELURL=".$cancelURL."&CURRENCYCODE=".$currency."&DESC=".$desc;
        $resArray=self::makeCall("SetExpressCheckout", $nvpstr);
        if(!$resArray) {
            return false;
        }
        if(array_key_exists('ACK', $resArray) AND strtoupper($resArray['ACK']) == 'SUCCESS') {
            if (array_key_exists("TOKEN",$resArray)) {
                $token = urldecode($resArray["TOKEN"]);
            }
            $payPalURL = PAYPAL_URL.$token;
            echo $payPalURL;
            return $payPalURL;
        }
        //插入你的异常处理
    }
    //
    function GetExpressCheckoutDetails($params) {
        $token = urlencode($params['token']);
        $nvpstr = "&TOKEN=".$token;
        $resArray = self::makeCall("GetExpressCheckoutDetails",$nvpstr);
        if(!$resArray) {
            return false;
        }
        if(array_key_exists('ACK', $resArray) AND strtoupper($resArray['ACK']) == 'SUCCESS') {
            return $resArray;
        } else {
            //插入你的异常处理
        }
    }
    //确定执行交易
    function DoExpressCheckoutPayment($params) {
        $token = urlencode( $params['token']);
        $payAmount = urlencode ($params['payAmount']);
        $payType = urlencode($params['payType']);
        $payerID = urlencode($params['PayerID']);
        $nvpstr = '&TOKEN='.$token.'&PAYERID='.$payerID.'&PAYMENTACTION='.$payType.'&AMT='.$payAmount ;
        $resArray = self::makeCall("DoExpressCheckoutPayment",$nvpstr);
        if(!$resArray) {
            return false;
        }
        if(array_key_exists('ACK', $resArray) AND strtoupper($resArray['ACK']) == 'SUCCESS') {
            return $resArray;
        } else {
            //插入你的异常处理
        }
    }

    function RefundTransaction($params) {
        $type = $params['type'];
        $transactionId = $params['transactionId'];
        $amount = urlencode($params['amount']);
        $nvpstr = '&TRANSACTIONID='.$transactionId.'&REFUNDTYPE='.$type;
        if($type == 'Full')
            $nvpstr .= '&AMT='.$amount;
        $resArray = self::makeCall("RefundTransaction", $nvpstr);
        if(!$resArray){
            return false;
        }
        if(array_key_exists('ACK', $resArray) AND strtoupper($resArray['ACK']) == 'SUCCESS') {
            return $resArray;
        } else {
            //插入你的异常处理
        }
    }
//通过curl库来发送请求，被以上的函数调用
    function makeCall($methodName,$nvpStr) {
        global $API_Endpoint;
        $version = '82.0';
        //获取商家，亦即卖家的账户名，密码和签名，我将这些放在一个xml文件中读取，读者可自行决定如何取这些
//        $xml = new DOMDocument ( );
//        $xml->load("xml/xmlforpaypal.xml");
//        $item = $xml->getElementsByTagName("root")->item(0);
//        $item = $xml->getElementsByTagName("paypal")->item(0);
//        $username = $item->getElementsByTagName("username")->item(0)->textContent;
//        $password = $item->getElementsByTagName("password")->item(0)->textContent;
//        $signature = $item->getElementsByTagName("signature")->item(0)->textContent;
        $API_UserName = C('PAYPAL_USERNAME');
        $API_Password = C('PAYPAL_PWD');
        $API_Signature = C('PAYPAL_SIGN');
        IF(!$API_UserName || !$API_Password || !$API_Signature){
            return false;
        }

        //  $nvp_Header;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$API_Endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        /*  if(USE_PROXY)//如果使用代理
         {
         curl_setopt ($ch, CURLOPT_PROXY, PROXY_HOST.":".PROXY_PORT);
         }*/
        $nvpreq = "METHOD=".urlencode($methodName)."&VERSION=".urlencode($version)."&PWD=".urlencode($API_Password)."&USER=".urlencode($API_UserName)."&SIGNATURE=".urlencode($API_Signature).$nvpStr;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
        $response = curl_exec($ch);
        $nvpResArray=self::deformatNVP($response);
        if (!$response) {
            //插入你的异常处理函数
            return false;
        } else {
            curl_close($ch);
        }
        return $nvpResArray;
    }
    //关于字符串的你懂的
    function deformatNVP($nvpstr) {
        $intial = 0;
        $nvpArray = array();
        while(strlen($nvpstr)) {
            $keypos = strpos($nvpstr, '=');
            $valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&') : strlen($nvpstr);
            $keyval = substr($nvpstr, $intial, $keypos);
            $valval = substr($nvpstr, $keypos+1, $valuepos-$keypos-1);
            $nvpArray[urldecode($keyval)] = urldecode($valval);
            $nvpstr = substr($nvpstr, $valuepos+1, strlen($nvpstr));
        }
        return $nvpArray;
    }
}