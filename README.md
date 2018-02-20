# union-pay
simplest union pay

Step 1: config.php - 配置

```php
return ['test',[
		'version' => '5.1.0',
		'encoding' => 'UTF-8',
		'merId' => '700000000000001',
		'returnUrl' => 'http://Yoursites.com/union-pay/demo/payreturn.php', //前台返回
		'notifyUrl' => 'https://Yoursites.com/demo/paynotify.php', //后台通知
		'frontFailUrl'   => 'http://Yoursites.com/union-pay/demo/payfail.php',
		'refundnotifyUrl' => 'https://Yoursites.com/demo/refundnotify.php',
		'signCertPath' => dirname(__FILE__).'/cert/acp_test_sign.pfx',
		'signCertPwd' => '000000', //签名证书密码
		'verifyCertPath' => dirname(__FILE__).'/cert/5.0.0/acp_test_verify_sign.cer',  //v5.0.0 required NOTE:该测试环境证书已失效，推荐使用5.1.0
		'verifyRootCertPath' => dirname(__FILE__).'/cert/5.1.0/acp_test_root.cer', //v5.1.0 required
		'verifyMiddleCertPath' => dirname(__FILE__).'/cert/5.1.0/acp_test_middle.cer', //v5.1.0 required
		'encryptCertPath' => dirname(__FILE__).'/cert/acp_test_enc.cer',
		'ifValidateCNName' => false, //正式环境设置为true
	]
];
```


Step 2: pay.php - 支付

```php
require_once __DIR__ . "/../src/UnionPay.php";
require_once __DIR__ . "/../src/HttpClient.php";
use zhangv\unionPay\UnionPay;

list($mode,$config) = include './config.php';
$unionPay = new UnionPay($config,$mode);

$payOrderNo = date('YmdHis');
$sum = 1;
$desc = 'desc';

$html = $unionPay->pay($payOrderNo,$sum,$desc,'');
echo $html;

```

Step 3: payreturn.php - 支付完成前台返回

```php
require_once __DIR__ . "/../src/UnionPay.php";
require_once __DIR__ . "/../src/HttpClient.php";
use zhangv\unionPay\UnionPay;

list($mode,$config) = include './config.php';
$unionPay = new UnionPay($config,$mode);

$postdata = $_REQUEST;
$unionPay->onPayNotify($postdata,function($notifydata){
	echo 'SUCCESS';
	var_dump($notifydata);
});
```

Step 4: paynotifyn.php - 支付完成后台通知

```php
require_once __DIR__ . "/../src/UnionPay.php";
require_once __DIR__ . "/../src/HttpClient.php";
use zhangv\unionPay\UnionPay;
list($mode,$config) = include './config.php';
$unionPay = new UnionPay($config,$mode);

$notifyData = $_POST;
$respCode = $notifyData['respCode'];
if($respCode == '00'){
	$txnType = $notifyData['txnType'];
	if($txnType == UnionPay::TXNTYPE_CONSUME){
		$unionPay->onPayNotify($notifyData,'demoCallback');
	}elseif($txnType == UnionPay::TXNTYPE_CONSUMEUNDO){
		$unionPay->onPayUndoNotify($notifyData,'demoCallback');
	}elseif($txnType == UnionPay::TXNTYPE_REFUND){
		$unionPay->onRefundNotify($notifyData,'demoCallback');
	}else echo 'fail';
}elseif(in_array($respCode,['03','04','05'])){
	//后续需发起交易状态查询交易确定交易状态
}else{
	echo 'fail';
}


function demoCallback($notifyData){
	var_dump($notifyData);
	print('ok');
}
```