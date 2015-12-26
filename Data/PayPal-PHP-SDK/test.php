<?php
require_once("start.php");
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
$payment = Payment::get('PAY-5YK922393D847794YKER7MUI', $apiContext);
print_r($payment);