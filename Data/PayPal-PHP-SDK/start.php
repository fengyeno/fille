<?php
require_once("autoload.php");
define('SITE_URL', 'http://fille.wbteam.cn');
$apiContext = new \Paypal\Rest\ApiContext(
    new \Paypal\Auth\OAuthTokenCredential(
        "AU07d4sI-p6TsCBUcotsOflMrV8D1190RUvv5l5l2DPtE2SF9TSd5WMWZwFsYhoXCU3sncI_Fa4R7IW-",
        "EMDfrV0P8wzLd1x7n1Ajr3VYoQeeGBRCz9VOZXMTCWSff6wIn-uW4IoWncnE1wMufzr9-ToTd-qDE7Cp"
    )
);