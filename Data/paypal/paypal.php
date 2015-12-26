<?php
class Paypal_IPN extends IPN_Handler{
    public function process($post_data)
    {
        $data = parent::process($post_data);
        return $data;
    }
}
/**
 * IPN Handler.
 */
abstract class IPN_Handler{
    const paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
    const paypal_sandbox_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    const charset = 'utf-8';
    /**
     * Validates and santizes IPN data from PayPal.
     *
     * @return  mixed        returns the processed data or FALSE if validation failed.
     */
    public function process($post_data)
    {
        // Validate
        $valid = self::validate($post_data);
        if($valid !== TRUE)
            return FALSE;
        // Sanitize
        return self::sanitize($post_data);
    }
    /**
     * Validates IPN data.
     *
     * [!!] Verification will fail if the data has been alterend in *any* way.
     *
     * @param   array    raw ipn post data from paypal
     * @return  mixed        returns the reply on error; otherwise `TRUE`
     */
    protected static function validate($ipn_post_data)
    {
        // Choose url
//        if(array_key_exists('test_ipn', $ipn_post_data) && 1 === (int) $ipn_post_data['test_ipn'])
            $url = self::paypal_sandbox_url;
//        else
//            $url = self::paypal_url;
        // Set up request to PayPal
        $request = curl_init();
        curl_setopt_array($request, array
        (
            CURLOPT_URL => $url,
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => "cmd=_notify-validate&".$ipn_post_data,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_HEADER => FALSE,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CAINFO => "cacert.pem",
        ));
        // Execute request and get response and status code
        $response = curl_exec($request);
        $status   = curl_getinfo($request, CURLINFO_HTTP_CODE);
        // Close connection
        curl_close($request);
        if($status == 200 && $response == 'VERIFIED')
            return TRUE;
        return $response;
    }
    protected static function sanitize($ipn_data)
    {
        // Just return empty array if empty
        if( ! $ipn_data)
            return array();
        $msg=explode('&',$ipn_data);
        $arr=array();
        foreach($msg as $v){
            $tmp=explode("=",$v);
            $arr[$tmp[0]]=urlencode(stripslashes($tmp[1]));
        }
        // Fix encoding
        self::fix_encoding($arr);
        // Sort keys (easier to debug)
        ksort($arr);
        return $arr;
    }
    protected static function fix_encoding( & $ipn_data)
    {
        // If charset is specified
        if(array_key_exists('charset', $ipn_data) && ($charset = $ipn_data['charset']))
        {
            // Ignore if same as our default
            if($charset == self::charset)
                return;
            // Otherwise convert all the values
            foreach($ipn_data as $key => &$value)
            {
                $value = mb_convert_encoding($value, self::charset, $charset);
            }
            // And store the charset values for future reference
            $ipn_data['charset'] = self::charset;
            $ipn_data['charset_original'] = $charset;
        }
    }
    /**
     * Get PayPal Payment Data
     * Read more at http://ethanblog.com/tech/webdev/php-for-paypal-payment-data-transfer.html
     * @param   $tx Transaction ID
     * @return      PayPal Payment Data or FALSE
     */
    public function get_payment_data($payment_id)
    {
        $token=$this->get_token();
        print_r($token);die;
        $url = "https://api.paypal.com/v1/payments/payment/PAY-5YK922393D847794YKER7MUI";
//        else
//            $url = self::paypal_url;
        // Set up request to PayPal
        $request = curl_init();
        curl_setopt_array($request, array
        (
            CURLOPT_URL => $url,
            CURLOPT_SSLVERSION => 1,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 60,    // maximum number of seconds to allow cURL functions to execute
            CURLOPT_USERAGENT => 'PayPal-PHP-SDK',
            CURLOPT_HTTPHEADER => array(),
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => 1,
//            CURLOPT_SSL_CIPHER_LIST => 'TLSv1',

            CURLOPT_HEADER=>true,
            CURLINFO_HEADER_OUT=>true,
            CURLOPT_HTTPHEADER=> array(
                'Content-Type' => 'application/json',
                'Authorization'=>  "Bearer "
            ),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CAINFO => "cacert.pem",
        ));
        // Execute request and get response and status code
        $response = curl_exec($request);
        $status   = curl_getinfo($request, CURLINFO_HTTP_CODE);
        // Close connection
        curl_close($request);
        if($status == 200){}
//            return TRUE;
        return $response;
    }
    public function get_token(){
        $url = "https://api.sandbox.paypal.com/v1/oauth2/token";
//        else
//            $url = self::paypal_url;
        // Set up request to PayPal
        $request = curl_init();
        curl_setopt_array($request, array
        (
            CURLOPT_URL => $url,
            CURLOPT_SSLVERSION => 1,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 60,    // maximum number of seconds to allow cURL functions to execute
            CURLOPT_USERAGENT => 'PayPal-PHP-SDK',
//            CURLOPT_SSL_CIPHER_LIST => 'TLSv1',

            CURLOPT_HEADER=>true,
//            CURLINFO_HEADER_OUT=>true,
            CURLOPT_HTTPHEADER=> array(
                'Accept' => 'application/json',
                'Accept-Language'=>  "en_US",
                'username'=>'AU07d4sI-p6TsCBUcotsOflMrV8D1190RUvv5l5l2DPtE2SF9TSd5WMWZwFsYhoXCU3sncI_Fa4R7IW-:EMDfrV0P8wzLd1x7n1Ajr3VYoQeeGBRCz9VOZXMTCWSff6wIn-uW4IoWncnE1wMufzr9-ToTd-qDE7Cp',
            ),
            CURLOPT_POST=>true,
            CURLOPT_POSTFIELDS=>array(

                'grant_type'=>'client_credentials',
            ),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CAINFO => "cacert.pem",
        ));
        // Execute request and get response and status code
        $response = curl_exec($request);
        $status   = curl_getinfo($request, CURLINFO_HTTP_CODE);
        // Close connection
        curl_close($request);
        if($status == 200){}
//            return TRUE;
        return $response;
    }
}
?>