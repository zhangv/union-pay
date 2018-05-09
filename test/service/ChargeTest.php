<?php
/**
 * Created by PhpStorm.
 * User: derekzhangv
 * Date: 01/02/2018
 * Time: 22:29
 */
require_once __DIR__ . "/../../demo/autoload.php";

use zhangv\unionpay\UnionPay;

class ChargeTest extends PHPUnit\Framework\TestCase{
	/** @var  \zhangv\unionpay\service\Charge */
	private $unionPay;

	public function setUp(){
		list($mode,$config) = include __DIR__ .'/../../demo/config.php';
		$this->unionPay = UnionPay::Charge($config,$mode);
	}

	private static $outTradeNoOffset = 0;
	private function genOutTradeNo(){
		return time().(self::$outTradeNoOffset++);
	}

	/** @test */
	public function backPayBill(){
		$accNo = '6226090000000048';
		$customerInfo = array (
			'phoneNo' => '18100000000', // 手机号
			'certifTp' => '01', // 证件类型，01-身份证
			'certifId' => '510265790128303', // 证件号，15位身份证不校验尾号，18位会校验尾号，请务必在前端写好校验代码
			'customerNm' => '张三' // 姓名
			// 'cvn2' => '248',　//cvn2
			// 'expired' => '1912',　//有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
		);
		$this->unionPay->backPayBill();

	}

	/** @test */
	public function queryBill(){
		$accNo = '6226090000000048';
		$customerInfo = array (
			'phoneNo' => '18100000000', // 手机号
			'certifTp' => '01', // 证件类型，01-身份证
			'certifId' => '510265790128303', // 证件号，15位身份证不校验尾号，18位会校验尾号，请务必在前端写好校验代码
			'customerNm' => '张三' // 姓名
			// 'cvn2' => '248',　//cvn2
			// 'expired' => '1912',　//有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
		);
		//todo

	}

	/** @test */
	public function areas(){
		$r = $this->unionPay->areas();
		$r = json_decode($r);
		$this->assertNotNull($r);
	}

	/** @test */
	public function categories(){
		$r = $this->unionPay->categories(null);
		var_dump($r);
		$r = json_decode($r);
		$this->assertNotNull($r);
	}
}
