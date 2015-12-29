<?php
include_once("paypal.class.php");
class paypal_pay{
    private $paypalmode;
    private $PayPalMode 			= 'live'; // sandbox or live
    private $PayPalApiUsername 		= 'alberteo772_api1.slidnet.com'; //PayPal API Username
    private $PayPalApiPassword 		= 'UL2LME4MCAWJDXDX'; //Paypal API password
    private $PayPalApiSignature 	= 'AH7fEFZRfxcqsa-fhuu4CeQIhKHNAT5mGSk0vgsgQoeUfOQbDtGhMOgv'; //Paypal API Signature
    private $PayPalCurrencyCode 	= 'USD'; //Paypal Currency Code
    private $PayPalReturnURL 		= 'http://fille.wbteam.cn/index.php/Api/Pay/success1'; //Point to process.php page
    private $PayPalCancelURL 		= 'http://fille.wbteam.cn/index.php/Api/Pay/cancel1'; //Cancel URL if user clicks cancel
    public function __construct($param){
        if(empty($param)){
            return false;
        }
        $this->paypalmode= ($this->PayPalMode=='sandbox') ? '.sandbox' : '';
        $this->PayPalApiUsername=$param['username'];
        $this->PayPalApiPassword=$param['pwd'];
        $this->PayPalApiSignature=$param['sign'];
    }
    public function sendData($data){
        $ItemName 		= $data["itemname"]; //Item Name
        $ItemPrice 		= $data["itemprice"]; //Item Price
        $ItemNumber 	= $data["itemnumber"]; //Item Number
        $ItemDesc 		= $data["itemdesc"]; //Item Number
        $ItemQty 		= $data["itemQty"]; // Item Quantity
        $orderNo        = $data['order_no'];
        $ItemTotalPrice = ($ItemPrice*$ItemQty); //(Item Price x Quantity = Total) Get total amount of product;

        //Other important variables like tax, shipping cost
        $TotalTaxAmount 	= 0.00;  //Sum of tax for all items in this order.
        $HandalingCost 		= 0.00;  //Handling cost for this order.
        $InsuranceCost 		= 0.00;  //shipping insurance cost for this order.
        $ShippinDiscount 	= 0.00; //Shipping discount for this order. Specify this as negative number.
        $ShippinCost 		= 0.00; //Although you may change the value later, try to pass in a shipping amount that is reasonably accurate.

        //Grand total including all tax, insurance, shipping cost and discount
        $GrandTotal = ($ItemTotalPrice);

        //Parameters for SetExpressCheckout, which will be sent to PayPal
        $padata = 	'&METHOD=SetExpressCheckout'.
            '&RETURNURL='.urlencode($this->PayPalReturnURL ).
            '&CANCELURL='.urlencode($this->PayPalCancelURL).
            '&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE").

            '&L_PAYMENTREQUEST_0_NAME0='.urlencode($ItemName).
            '&L_PAYMENTREQUEST_0_NUMBER0='.urlencode($ItemNumber).
            '&L_PAYMENTREQUEST_0_DESC0='.urlencode($ItemDesc).
            '&L_PAYMENTREQUEST_0_AMT0='.urlencode($ItemPrice).
            '&L_PAYMENTREQUEST_0_QTY0='. urlencode($ItemQty).


            '&NOSHIPPING=0'. //set 1 to hide buyer's shipping address, in-case products that does not require shipping

            '&PAYMENTREQUEST_0_ITEMAMT='.urlencode($ItemTotalPrice).
            '&PAYMENTREQUEST_0_TAXAMT='.urlencode($TotalTaxAmount).
            '&PAYMENTREQUEST_0_SHIPPINGAMT='.urlencode($ShippinCost).
            '&PAYMENTREQUEST_0_HANDLINGAMT='.urlencode($HandalingCost).
            '&PAYMENTREQUEST_0_SHIPDISCAMT='.urlencode($ShippinDiscount).
            '&PAYMENTREQUEST_0_INSURANCEAMT='.urlencode($InsuranceCost).
            '&PAYMENTREQUEST_0_AMT='.urlencode($GrandTotal).
            '&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($this->PayPalCurrencyCode).
            '&LOCALECODE=GB'. //PayPal pages to match the language on your website.
            '&LOGOIMG=http://www.sanwebe.com/wp-content/themes/sanwebe/img/logo.png'. //site logo
            '&CARTBORDERCOLOR=FFFFFF'. //border color of cart
            '&PAYMENTREQUEST_0_CUSTOM='.$orderNo.
            '&ALLOWNOTE=1';

        ############# set session variable we need later for "DoExpressCheckoutPayment" #######
        $_SESSION['ItemName'] 			=  $ItemName; //Item Name
        $_SESSION['ItemPrice'] 			=  $ItemPrice; //Item Price
        $_SESSION['ItemNumber'] 		=  $ItemNumber; //Item Number
        $_SESSION['ItemDesc'] 			=  $ItemDesc; //Item Number
        $_SESSION['ItemQty'] 			=  $ItemQty; // Item Quantity
        $_SESSION['ItemTotalPrice'] 	=  $ItemTotalPrice; //(Item Price x Quantity = Total) Get total amount of product;
        $_SESSION['TotalTaxAmount'] 	=  $TotalTaxAmount;  //Sum of tax for all items in this order.
        $_SESSION['HandalingCost'] 		=  $HandalingCost;  //Handling cost for this order.
        $_SESSION['InsuranceCost'] 		=  $InsuranceCost;  //shipping insurance cost for this order.
        $_SESSION['ShippinDiscount'] 	=  $ShippinDiscount; //Shipping discount for this order. Specify this as negative number.
        $_SESSION['ShippinCost'] 		=   $ShippinCost; //Although you may change the value later, try to pass in a shipping amount that is reasonably accurate.
        $_SESSION['GrandTotal'] 		=  $GrandTotal;


        //We need to execute the "SetExpressCheckOut" method to obtain paypal token
        $paypal= new MyPayPal();
        $httpParsedResponseAr = $paypal->PPHttpPost('SetExpressCheckout', $padata, $this->PayPalApiUsername, $this->PayPalApiPassword, $this->PayPalApiSignature, $this->PayPalMode);

        //Respond according to message we receive from Paypal
        if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
        {
            //Redirect user to PayPal store with Token received.
            $paypalurl ='https://www'.$this->paypalmode.'.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='.$httpParsedResponseAr["TOKEN"].'';
            header('Location: '.$paypalurl);
            die;
        }else{
            return $httpParsedResponseAr;
        }
    }
    public function getData($param){

    $token = $param["token"];
    $payer_id = $param["PayerID"];

    //get session variables
    $ItemName 			= $_SESSION['ItemName']; //Item Name
    $ItemPrice 			= $_SESSION['ItemPrice'] ; //Item Price
    $ItemNumber 		= $_SESSION['ItemNumber']; //Item Number
    $ItemDesc 			= $_SESSION['ItemDesc']; //Item Number
    $ItemQty 			= $_SESSION['ItemQty']; // Item Quantity
    $ItemTotalPrice 	= $_SESSION['ItemTotalPrice']; //(Item Price x Quantity = Total) Get total amount of product;
    $TotalTaxAmount 	= $_SESSION['TotalTaxAmount'] ;  //Sum of tax for all items in this order.
    $HandalingCost 		= $_SESSION['HandalingCost'];  //Handling cost for this order.
    $InsuranceCost 		= $_SESSION['InsuranceCost'];  //shipping insurance cost for this order.
    $ShippinDiscount 	= $_SESSION['ShippinDiscount']; //Shipping discount for this order. Specify this as negative number.
    $ShippinCost 		= $_SESSION['ShippinCost']; //Although you may change the value later, try to pass in a shipping amount that is reasonably accurate.
    $GrandTotal 		= $_SESSION['GrandTotal'];

    $padata = 	'&TOKEN='.urlencode($token).
        '&PAYERID='.urlencode($payer_id).
        '&PAYMENTREQUEST_0_PAYMENTACTION='.urlencode("SALE").

        //set item info here, otherwise we won't see product details later
        '&L_PAYMENTREQUEST_0_NAME0='.urlencode($ItemName).
        '&L_PAYMENTREQUEST_0_NUMBER0='.urlencode($ItemNumber).
        '&L_PAYMENTREQUEST_0_DESC0='.urlencode($ItemDesc).
        '&L_PAYMENTREQUEST_0_AMT0='.urlencode($ItemPrice).
        '&L_PAYMENTREQUEST_0_QTY0='. urlencode($ItemQty).


        '&PAYMENTREQUEST_0_ITEMAMT='.urlencode($ItemTotalPrice).
        '&PAYMENTREQUEST_0_TAXAMT='.urlencode($TotalTaxAmount).
        '&PAYMENTREQUEST_0_SHIPPINGAMT='.urlencode($ShippinCost).
        '&PAYMENTREQUEST_0_HANDLINGAMT='.urlencode($HandalingCost).
        '&PAYMENTREQUEST_0_SHIPDISCAMT='.urlencode($ShippinDiscount).
        '&PAYMENTREQUEST_0_INSURANCEAMT='.urlencode($InsuranceCost).
        '&PAYMENTREQUEST_0_AMT='.urlencode($GrandTotal).
        '&PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($this->PayPalCurrencyCode);

    //We need to execute the "DoExpressCheckoutPayment" at this point to Receive payment from user.
    $paypal= new MyPayPal();
    $httpParsedResponseAr = $paypal->PPHttpPost('DoExpressCheckoutPayment', $padata, $this->PayPalApiUsername, $this->PayPalApiPassword, $this->PayPalApiSignature, $this->PayPalMode);

    //Check if everything went ok..
    if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
    {

//        echo '<h2>Success</h2>';
//        echo 'Your Transaction ID : '.urldecode($httpParsedResponseAr["PAYMENTINFO_0_TRANSACTIONID"]);

        /*
        //Sometimes Payment are kept pending even when transaction is complete.
        //hence we need to notify user about it and ask him manually approve the transiction
        */

        if('Completed' == $httpParsedResponseAr["PAYMENTINFO_0_PAYMENTSTATUS"])
        {
//            header("Content-type:text/html;charset=utf-8");
            echo '<div style="color:green">Payment Received! Your product will be sent to you very soon!</div>';
        }
        elseif('Pending' == $httpParsedResponseAr["PAYMENTINFO_0_PAYMENTSTATUS"])
        {
            echo '<div style="color:red">Transaction Complete, but payment is still pending! '.
                'You need to manually authorize this payment in your <a target="_new" href="http://www.paypal.com">Paypal Account</a></div>';
        }

        // we can retrive transection details using either GetTransactionDetails or GetExpressCheckoutDetails
        // GetTransactionDetails requires a Transaction ID, and GetExpressCheckoutDetails requires Token returned by SetExpressCheckOut
        $padata = 	'&TOKEN='.urlencode($token);
        $paypal= new MyPayPal();
        $httpParsedResponseAr = $paypal->PPHttpPost('GetExpressCheckoutDetails', $padata, $this->PayPalApiUsername, $this->PayPalApiPassword, $this->PayPalApiSignature, $this->PayPalMode);
        die;
        if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
        {
            echo '<br /><b>Stuff to store in database :</b><br /><pre>';

            echo '<pre>';
            print_r($httpParsedResponseAr);
            echo '</pre>';
        } else  {
            echo '<div style="color:red"><b>GetTransactionDetails failed:</b>'.urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]).'</div>';
            echo '<pre>';
            print_r($httpParsedResponseAr);
            echo '</pre>';

        }

    }else{
        echo '<div style="color:red">支付失败<b>Error : </b>'.urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]).'</div>';
        echo '<pre>';
//        print_r($httpParsedResponseAr);
        echo '</pre>';
    }
    }
}