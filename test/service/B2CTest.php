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
	/** @var  \zhangv\unionpay\service\B2C */
	private $unionPay;

	public function setUp(){
		list($mode,$config) = include __DIR__ .'/../../demo/config.php';
		$this->unionPay = UnionPay::B2C($config,$mode);
	}

	private static $outTradeNoOffset = 0;
	private function genOutTradeNo(){
		return time().(self::$outTradeNoOffset++);
	}

	/** @test */
	public function pay(){
		$orderId = $this->genOutTradeNo();
		$f = $this->unionPay->pay($orderId,1);
		$this->assertNotNull($f);
	}

	/**
	 * @test
	 * @group tmp
	 * @expectedException Exception
	 */
	public function query(){
		$r = $this->unionPay->query(20180204092701,date('YmdHis'));
		$this->assertEquals(34,$this->unionPay->respCode);
	}

	/** @test */
	public function preAuth(){
		$orderId = $this->genOutTradeNo();
		$f = $this->unionPay->preAuth($orderId,1,'test');
		$this->assertNotNull($f);
	}

	/** @test */
	public function fileDownload(){
		$this->unionPay->fileDownload('0119');
		$this->assertEquals(UnionPay::RESPCODE_SUCCESS,$this->unionPay->respCode);
	}
}
