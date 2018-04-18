<?php
/**
 * Created by PhpStorm.
 * User: derekzhangv
 * Date: 2018/4/10
 * Time: 10:52
 */
require_once __DIR__ . '/../src/UnionPayToken.php';
use zhangv\unionpay\UnionPayToken;
use PHPUnit\Framework\TestCase;

class UnionPayTokenTest extends TestCase{
	/** @var  UnionPayToken */
	private $unionPay;
	private $config;
	public function setUp(){
		list($mode,$this->config) = include_once __DIR__ .'/../demo/config-direct.php';
		$this->unionPay = new UnionPayToken($this->config,$mode);
	}

	/**
	 * @test
	 */
	public function applyToken(){
		$orderId = date('YmdHis');//'20180418024955';//开通时获取
		$txnTime  = $orderId;//开通时获取
		$testAcc = $this->config['testAcc'][2];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'], //手机号
			'cvn2' => $testAcc['cvn2'], //cvn2
			'expired' => $testAcc['expired'], //有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
			'smsCode' => '111111', //短信验证码
		);
		$tokenPayData = "{trId=62000000001&tokenType=01}";

		$r = $this->unionPay->applyToken($orderId,$txnTime,$tokenPayData);
		$tokenPayData = $r['tokenPayData'];
		$tokenPayData = substr($tokenPayData,1,-1);
		$tokenPayData = explode('&',$tokenPayData);
		$token = null;
		foreach($tokenPayData as $v){
			$tmp = explode('=',$v);
			if($tmp[0] == 'token'){
				$token = $tmp[1];
				break;
			}
		}
		var_dump($token);

//		$r = $this->updateToken($orderId,$txnTime,$customerInfo,$token); //FIXME 重复交易
		$r = $this->deleteToken($orderId,$txnTime,$token);
	}

	public function updateToken($orderId,$txnTime,$customerInfo,$token){
		$tokenPayData = "{trId=62000000001&token={$token}&tokenType=01}";
		$r = $this->unionPay->updateToken($orderId,$txnTime,$customerInfo,$tokenPayData);
		var_dump($r);
	}

	public function deleteToken($orderId,$txnTime,$token){
		$tokenPayData = "{trId=62000000001&token={$token}&tokenType=01}";
		$r = $this->unionPay->deleteToken($orderId,$txnTime,$tokenPayData);
		$this->assertEquals('74',$r['txnType']);
		$this->assertEquals('00',$r['respCode']);
	}

	/**
	 * @test
	 */
	public function payByToken(){
		$orderId = date('YmdHis');
		$txnTime = date('YmdHis');
		$customerInfo = array(
			'smsCode' => '111111',
		);
		$token = '6235240000020837064'; //maybe you have to query this token
		$tokenPayData = "{trId=62000000001&token={$token}}";
		$ext['customerInfo'] = $customerInfo;
		$r = $this->unionPay->payByToken($orderId,1,$txnTime,$tokenPayData,$ext);
		$this->assertEquals('00',$r['respCode']);
	}

	/**
	 * 测试环境：无此交易权限
	 * @test
	 */
	public function backOpen(){
		$orderId = date('YmdHis');
		$testAcc = $this->config['testAcc'][2];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'], //手机号
			'cvn2' => $testAcc['cvn2'], //cvn2
			'expired' => $testAcc['expired'], //有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
			'smsCode' => '111111', //短信验证码
		);
		$ext['tokenPayData'] = "{trId=62000000001&tokenType=01}";
		$r = $this->unionPay->backOpen($orderId,$accNo,$customerInfo,$ext);
		$this->assertEquals('00',$r['respCode']);
	}

}
