<?php
/**
 * TODO
 */
require_once __DIR__ . '/../src/UnionPayDirectDebit.php';
use zhangv\unionpay\UnionPayDirectDebit;
use PHPUnit\Framework\TestCase;

class UnionPayDirectDebitTest extends TestCase{
	/** @var  UnionPayDirectDebit */
	private $unionPay;
	private $config;
	public function setUp(){
		list($mode,$this->config) = include_once __DIR__ .'/../demo/config-direct.php';
		$this->unionPay = new UnionPayDirectDebit($this->config,$mode);
	}


}
