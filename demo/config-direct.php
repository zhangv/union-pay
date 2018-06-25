<?php
/**
 * 无跳转测试配置
 * 测试卡信息：https://open.unionpay.com/ajweb/help/faq/list?id=4&level=0&from=0
 */
//if(file_exists(__DIR__ .'/config-prod.php')) return ['prod',$config = include './config-prod.php'];
return ['test',
	[
		'version' => '5.1.0',
		'signMethod'=> '01', //RSA
		'encoding' => 'UTF-8',
		'merId' => '777290058158470', //注意这个测试商户号
		'returnUrl' => 'http://dev.git.com/union-pay/demo/payreturn.php', //前台网关支付返回
		'notifyUrl' => 'http://dev.git.com/union-pay/demo/paynotify.php', //后台通知
		'frontFailUrl'   => 'http://dev.git.com/union-pay/demo/payfail.php',
		'refundnotifyUrl' => 'http://dev.git.com.com/union-pay/demo/refundnotify.php',
		'signCertPath' => dirname(__FILE__) . '/../cert/acp_test_sign.pfx',
		'signCertPwd' => '000000', //签名证书密码
		'verifyCertPath' => dirname(__FILE__) . '/../cert/acp_test_verify_sign.cer', //v5.0.0 required NOTE:该测试环境证书已失效，推荐使用5.1.0
		'verifyRootCertPath' => dirname(__FILE__) . '/../cert/acp_test_root.cer', //v5.1.0 required
		'verifyMiddleCertPath' => dirname(__FILE__) . '/../cert/acp_test_middle.cer', //v5.1.0 required
		'encryptCertPath' => dirname(__FILE__) . '/../cert/acp_test_enc.cer',
		'ifValidateCNName' => false, //正式环境设置为true
		//以下无跳转
		'openReturnUrl' => 'http://dev.git.com/union-pay/demo/openreturn.php', //前台开通返回
		'openNotifyUrl' => 'http://dev.git.com/union-pay/demo/opennotify.php', //前台开通通知
		'testAcc' => [
			/**
			 * 网关、WAP短信验证码 111111 控件短信验证码 123456
			 * B2B企业网银测试账号:账号：123456789001   密码：789001
			 */
			[//借记卡 平安银行 - 用于无跳转开通
				'accNo' => '6216261000000000018',
				'phoneNo' => '13552535506',
				'certifTp' => '01',
				'certifId' => '341126197709218366',
				'customerNm' => '全渠道',
				'password' => '123456'
			],
			[// 贷记卡 平安银行 - 用于无跳转分期付款
				'accNo' => '6221558812340000',
				'phoneNo' => '13552535506',
				'certifTp' => '01',
				'certifId' => '341126197709218366',
				'customerNm' => '互联网',
				'password' => '123456',
				'cvn2' => '123',
				'expired' => '2311',
				'instalTransInfo' => '{numberOfInstallments=06}'
			],
			[//华夏银行贷记卡 测试支付、前台类交易
				'accNo' => '6226388000000095',
				'phoneNo' => '18100000000', //手机号
				'certifTp' => '01', //证件类型，01-身份证
				'certifId' => '510265790128303', //证件号，15位身份证不校验尾号，18位会校验尾号，请务必在前端写好校验代码
				'customerNm' => '张三', //姓名
				'smsCode' => '123456', //123456（手机）/111111（PC）
				'cvn2' => '248',
				'expired' => '1912',
			],
			[//招商银行借记卡 前台类交易
				'accNo' => '6226090000000048',
				'phoneNo' => '18100000000', //手机号
				'certifTp' => '01', //证件类型，01-身份证
				'certifId' => '510265790128303', //证件号，15位身份证不校验尾号，18位会校验尾号，请务必在前端写好校验代码
				'customerNm' => '张三', //姓名
				'smsCode' => '123456', //123456（手机）/111111（PC）
				'password' => '111101',
			]
		],
	]
];
