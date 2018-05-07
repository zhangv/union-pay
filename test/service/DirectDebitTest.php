<?php
/**
 * TODO
 */
require_once __DIR__ . "/../../demo/autoload.php";
use zhangv\unionpay\UnionPay;
use PHPUnit\Framework\TestCase;

class DirectDebitTest extends TestCase{
	/** @var  UnionPayDirectDebit */
	private $unionPay;
	private $config;
	public function setUp(){
		list($mode,$this->config) = include __DIR__ .'/../demo/config-direct.php';
		$this->unionPay = UnionPay::DirectDebit($this->config,$mode);
	}


}
