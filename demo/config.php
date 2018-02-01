<?php

return [
	'version'   => '5.1.0',
	'encoding'  => 'utf-8',
	'returnUrl' => 'https://Yoursites.com/demo/unionpayreturn.php', //前台返回
	'notifyUrl' => 'https://Yoursites.com/demo/unionpaynotify.php', //后台通知
	'failUrl'   => 'https://Yoursites.com/unionpayfail/',
	'merId' => 'XXXXXX', //商户号
	'signCertPath' => '/YOURPATHTO/signcert.pfx', //签名证书路径
	'signCertPwd' => 'XXXX', //签名证书密码
	'verifyCertPath' => '/YOURPATHTO/acp_prod_verify_sign.cer', //验签证书路径
	'encryptCertPath' => '/PATHTO/acp_prod_enc.cer',//加密证书路径
];