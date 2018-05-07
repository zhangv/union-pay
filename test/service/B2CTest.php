<?php
/**
 * Created by PhpStorm.
 * User: derekzhangv
 * Date: 01/02/2018
 * Time: 22:29
 */
require_once __DIR__ . "/../../demo/autoload.php";
use zhangv\unionpay\UnionPay;

class B2CTest extends PHPUnit\Framework\TestCase{
	/** @var  UnionPay */
	private $unionPay;

	public function setUp(){
		list($mode,$config) = include __DIR__ .'/../../demo/config.php';
		$this->unionPay = UnionPay::B2C($config,$mode);
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

	public function testQuery(){
		$r = $this->unionPay->query(20180204092701);
		$this->assertEquals(UnionPay::RESPCODE_SUCCESS,$this->unionPay->respCode);
	}

	public function testPreAuth(){
		$orderId = $this->genOutTradeNo();
		$f = $this->unionPay->preAuth($orderId,1,'test');
		var_dump($f);
	}

	public function testFileDownload(){
		$this->unionPay->fileDownload('0119');
		$this->assertEquals(UnionPay::RESPCODE_SUCCESS,$this->unionPay->respCode);
	}
}
