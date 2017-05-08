<?php

return [
	'version'   => '5.0.0',
	'encoding'  => 'utf-8',
	'returnUrl' => 'https://Yoursites.com/unionpayreturn/', //前台返回
	'notifyUrl' => 'https://Yoursites.com/unionpaynotify/', //后台通知
	'failUrl'   => 'https://Yoursites.com/unionpayfail/',
	'frontUrl' => 'https://gateway.95516.com/gateway/api/frontTransReq.do', //前台交易请求地址
	'singleQueryUrl' => 'https://gateway.95516.com/gateway/api/queryTrans.do', //单笔查询请求地址
	'merId' => 'XXXXXX', //商户号
	'signCertPath' => '/YOURPATHTO/signcert.pfx', //签名证书路径
	'signCertPwd' => 'XXXX', //签名证书密码
	'verifyCertPath' => '/YOURPATHTO/acp_prod_verify_sign.cer', //验签证书路径

];