<?php
/**
 * Created by PhpStorm.
 * User: derekzhangv
 * Date: 01/02/2018
 * Time: 22:29
 */

use zhangv\unionpay\UnionPay;

class UnionPayTest extends PHPUnit\Framework\TestCase{
	/** @var  UnionPay */
	private $unionPay;

	public function setUp(){
		$config = [
			'merId' => '700000000000001',
			'returnUrl' => 'https://Yoursites.com/demo/unionpayreturn.php', //前台返回
			'notifyUrl' => 'https://Yoursites.com/demo/unionpaynotify.php', //后台通知
			'failUrl'   => 'https://Yoursites.com/unionpayfail/',
			'signCertPath' => dirname(__FILE__).'/cert/acp_test_sign.pfx',
			'signCertPwd' => '000000', //签名证书密码
			'verifyCertPath' => dirname(__FILE__).'/cert/acp_test_root.cer',
		];
		$this->unionPay = new UnionPay($config);
		$this->unionPay->frontTransUrl = 'https://gateway.test.95516.com/gateway/api/frontTransReq.do';
		$this->unionPay->backTransUrl = 'https://gateway.test.95516.com/gateway/api/backTransReq.do';
		$this->unionPay->singleQueryUrl = 'https://gateway.test.95516.com/gateway/api/queryTrans.do';
		$this->unionPay->fileDownloadUrl = 'https://filedownload.test.95516.com/';
	}

	private static $outTradeNoOffset = 0;
	private function genOutTradeNo(){
		return time().(self::$outTradeNoOffset++);
	}

	public function testPay(){
		$orderId = $this->genOutTradeNo();
		$f = $this->unionPay->pay($orderId,1);
		var_dump($f);
	}

	public function testFileDownload(){
		$f = $this->unionPay->fileDownload('0119');
		$r = explode('&',$f);
		$rr = [];
		foreach($r as $v){
			$tmp = explode('=',$v);
			$rr[$tmp[0]] = $tmp[1];
		}
		$this->assertEquals('00',$rr['respCode']);
	}
}
