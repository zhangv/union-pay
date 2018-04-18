<?php
/**
 * Created by PhpStorm.
 * User: derekzhangv
 * Date: 01/02/2018
 * Time: 22:29
 */
require_once __DIR__ . '/../src/UnionPayApp.php';
use zhangv\unionpay\UnionPayApp;

class UnionPayAppTest extends PHPUnit\Framework\TestCase{
	/** @var  UnionPayApp */
	private $unionPay;

	public function setUp(){
		list($mode,$config) = include_once __DIR__ .'/../demo/config.php';
		$this->unionPay = new UnionPayApp($config,$mode);
	}

	private static $outTradeNoOffset = 0;
	private function genOutTradeNo(){
		return time().(self::$outTradeNoOffset++);
	}

	/** @test */
	public function verifyAppResponse(){
		$json = '{"sign" : "J6rPLClQ64szrdXCOtV1ccOMzUmpiOKllp9cseBuRqJ71pBKPPkZ1FallzW18gyP7CvKh1RxfNNJ66AyXNMFJi1OSOsteAAFjF5GZp0Xsfm3LeHaN3j/N7p86k3B1GrSPvSnSw1LqnYuIBmebBkC1OD0Qi7qaYUJosyA1E8Ld8oGRZT5RR2gLGBoiAVraDiz9sci5zwQcLtmfpT5KFk/eTy4+W9SsC0M/2sVj43R9ePENlEvF8UpmZBqakyg5FO8+JMBz3kZ4fwnutI5pWPdYIWdVrloBpOa+N4pzhVRKD4eWJ0CoiD+joMS7+C0aPIEymYFLBNYQCjM0KV7N726LA==",  "data" : "pay_result=success&tn=201602141008032671528&cert_id=68759585097"}';
		$r = $this->unionPay->verifyAppResponse($json);
		$this->assertEquals(1,$r);
	}

}
