<?php
//start session in all pages
if (session_status() == PHP_SESSION_NONE) { session_start(); } //PHP >= 5.4.0
//if(session_id() == '') { session_start(); } //uncomment this line if PHP < 5.4.0 and comment out line above

$PayPalMode 			= 'live'; // sandbox or live
$PayPalApiUsername 		= 'alberteo772_api1.slidnet.com'; //PayPal API Username
$PayPalApiPassword 		= 'UL2LME4MCAWJDXDX'; //Paypal API password
$PayPalApiSignature 	= 'AH7fEFZRfxcqsa-fhuu4CeQIhKHNAT5mGSk0vgsgQoeUfOQbDtGhMOgv'; //Paypal API Signature
$PayPalCurrencyCode 	= 'USD'; //Paypal Currency Code
$PayPalReturnURL 		= ''; //Point to process.php page
$PayPalCancelURL 		= ''; //Cancel URL if user clicks cancel
?>