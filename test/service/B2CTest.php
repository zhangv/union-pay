<?php
/**
 * Created by PhpStorm.
 * User: derekzhangv
 * Date: 01/02/2018
 * Time: 22:29
 */
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
	 */
	public function query(){
		$mock = Mockery::mock('HttpClient');
		$mock->shouldReceive('post')->andReturn("accessType=0&bizType=000000&encoding=UTF-8&merId=700000000000001&orderId=20180614042747&origRespCode=05&origRespMsg=交易已受理，请稍后查询交易结果[6154037]&respCode=00&respMsg=成功[0000000]&settleAmt=0&signMethod=01&txnAmt=1&txnSubType=01&txnTime=20180614042747&txnType=01&version=5.1.0&signPubKeyCert=-----BEGIN CERTIFICATE-----
MIIEQzCCAyugAwIBAgIFEBJJZVgwDQYJKoZIhvcNAQEFBQAwWDELMAkGA1UEBhMC
Q04xMDAuBgNVBAoTJ0NoaW5hIEZpbmFuY2lhbCBDZXJ0aWZpY2F0aW9uIEF1dGhv
cml0eTEXMBUGA1UEAxMOQ0ZDQSBURVNUIE9DQTEwHhcNMTcxMTAxMDcyNDA4WhcN
MjAxMTAxMDcyNDA4WjB3MQswCQYDVQQGEwJjbjESMBAGA1UEChMJQ0ZDQSBPQ0Ex
MQ4wDAYDVQQLEwVDVVBSQTEUMBIGA1UECxMLRW50ZXJwcmlzZXMxLjAsBgNVBAMU
JTA0MUBaMjAxNy0xMS0xQDAwMDQwMDAwOlNJR05AMDAwMDAwMDEwggEiMA0GCSqG
SIb3DQEBAQUAA4IBDwAwggEKAoIBAQDDIWO6AESrg+34HgbU9mSpgef0sl6avr1d
bD/IjjZYM63SoQi3CZHZUyoyzBKodRzowJrwXmd+hCmdcIfavdvfwi6x+ptJNp9d
EtpfEAnJk+4quriQFj1dNiv6uP8ARgn07UMhgdYB7D8aA1j77Yk1ROx7+LFeo7rZ
Ddde2U1opPxjIqOPqiPno78JMXpFn7LiGPXu75bwY2rYIGEEImnypgiYuW1vo9UO
G47NMWTnsIdy68FquPSw5FKp5foL825GNX3oJSZui8d2UDkMLBasf06Jz0JKz5AV
blaI+s24/iCfo8r+6WaCs8e6BDkaijJkR/bvRCQeQpbX3V8WoTLVAgMBAAGjgfQw
gfEwHwYDVR0jBBgwFoAUz3CdYeudfC6498sCQPcJnf4zdIAwSAYDVR0gBEEwPzA9
BghggRyG7yoBATAxMC8GCCsGAQUFBwIBFiNodHRwOi8vd3d3LmNmY2EuY29tLmNu
L3VzL3VzLTE0Lmh0bTA5BgNVHR8EMjAwMC6gLKAqhihodHRwOi8vdWNybC5jZmNh
LmNvbS5jbi9SU0EvY3JsMjQ4NzIuY3JsMAsGA1UdDwQEAwID6DAdBgNVHQ4EFgQU
mQQLyuqYjES7qKO+zOkzEbvdFwgwHQYDVR0lBBYwFAYIKwYBBQUHAwIGCCsGAQUF
BwMEMA0GCSqGSIb3DQEBBQUAA4IBAQAujhBuOcuxA+VzoUH84uoFt5aaBM3vGlpW
KVMz6BUsLbIpp1ho5h+LaMnxMs6jdXXDh/du8X5SKMaIddiLw7ujZy1LibKy2jYi
YYfs3tbZ0ffCKQtv78vCgC+IxUUurALY4w58fRLLdu8u8p9jyRFHsQEwSq+W5+bP
MTh2w7cDd9h+6KoCN6AMI1Ly7MxRIhCbNBL9bzaxF9B5GK86ARY7ixkuDCEl4XCF
JGxeoye9R46NqZ6AA/k97mJun//gmUjStmb9PUXA59fR5suAB5o/5lBySZ8UXkrI
pp/iLT8vIl1hNgLh0Ghs7DBSx99I+S3VuUzjHNxL6fGRhlix7Rb8
-----END CERTIFICATE-----&signature=rBJGkD3bCEiiL1hUw5bZ1c/YH4GP9AGxw+ZacN+Ic4K3SJk0T9OGvN8iR+Iu7IiHd3LrAi2L7HxzMmY1KhlNZBwq5eo0MkDQQNXN0BBQ1XPNH8sadxwU9pT4lUUMA7829qqFsgsdobUrla1Yaqu3EKdZyT01kxROmlinYklYWqT3PKbHsbflNGO341sxiomgE1hNGUOP7G/Ps1ZPzPHg3jOMl5erP3sa20XWZHraSepLt2sVwWpD2/9joLQTv1w8zIaUYEeAOooxhckSnULjOs+fgZGSsdiR+qTs12u9bs4gkYgnKanF0A1+oB04GOjRyBxXrPhosp9u2NSIB04NBg==
");
		$this->unionPay->setHttpClient($mock);
		$orderId = '20180614042747';
		$r = $this->unionPay->query($orderId,date('YmdHis'));
		$this->assertEquals($orderId,$r['orderId']);
	}

	/**
	 * @test
	 * @group tmp
	 * @expectedException Exception
	 */
	public function query_nonexists(){
//		$r = $this->unionPay->query(20180204092701,date('YmdHis'));
//		$this->assertEquals(34,$this->unionPay->respCode);
		$mock = Mockery::mock('HttpClient');
		$mock->shouldReceive('post')->andReturn("accessType=0&bizType=000000&encoding=UTF-8&merId=700000000000001&orderId=20180204092701&respCode=34&respMsg=查无此交易[2600000]&signMethod=01&txnSubType=00&txnTime=20180614041856&txnType=00&version=5.1.0&signPubKeyCert=-----BEGIN CERTIFICATE-----
MIIEQzCCAyugAwIBAgIFEBJJZVgwDQYJKoZIhvcNAQEFBQAwWDELMAkGA1UEBhMC
Q04xMDAuBgNVBAoTJ0NoaW5hIEZpbmFuY2lhbCBDZXJ0aWZpY2F0aW9uIEF1dGhv
cml0eTEXMBUGA1UEAxMOQ0ZDQSBURVNUIE9DQTEwHhcNMTcxMTAxMDcyNDA4WhcN
MjAxMTAxMDcyNDA4WjB3MQswCQYDVQQGEwJjbjESMBAGA1UEChMJQ0ZDQSBPQ0Ex
MQ4wDAYDVQQLEwVDVVBSQTEUMBIGA1UECxMLRW50ZXJwcmlzZXMxLjAsBgNVBAMU
JTA0MUBaMjAxNy0xMS0xQDAwMDQwMDAwOlNJR05AMDAwMDAwMDEwggEiMA0GCSqG
SIb3DQEBAQUAA4IBDwAwggEKAoIBAQDDIWO6AESrg+34HgbU9mSpgef0sl6avr1d
bD/IjjZYM63SoQi3CZHZUyoyzBKodRzowJrwXmd+hCmdcIfavdvfwi6x+ptJNp9d
EtpfEAnJk+4quriQFj1dNiv6uP8ARgn07UMhgdYB7D8aA1j77Yk1ROx7+LFeo7rZ
Ddde2U1opPxjIqOPqiPno78JMXpFn7LiGPXu75bwY2rYIGEEImnypgiYuW1vo9UO
G47NMWTnsIdy68FquPSw5FKp5foL825GNX3oJSZui8d2UDkMLBasf06Jz0JKz5AV
blaI+s24/iCfo8r+6WaCs8e6BDkaijJkR/bvRCQeQpbX3V8WoTLVAgMBAAGjgfQw
gfEwHwYDVR0jBBgwFoAUz3CdYeudfC6498sCQPcJnf4zdIAwSAYDVR0gBEEwPzA9
BghggRyG7yoBATAxMC8GCCsGAQUFBwIBFiNodHRwOi8vd3d3LmNmY2EuY29tLmNu
L3VzL3VzLTE0Lmh0bTA5BgNVHR8EMjAwMC6gLKAqhihodHRwOi8vdWNybC5jZmNh
LmNvbS5jbi9SU0EvY3JsMjQ4NzIuY3JsMAsGA1UdDwQEAwID6DAdBgNVHQ4EFgQU
mQQLyuqYjES7qKO+zOkzEbvdFwgwHQYDVR0lBBYwFAYIKwYBBQUHAwIGCCsGAQUF
BwMEMA0GCSqGSIb3DQEBBQUAA4IBAQAujhBuOcuxA+VzoUH84uoFt5aaBM3vGlpW
KVMz6BUsLbIpp1ho5h+LaMnxMs6jdXXDh/du8X5SKMaIddiLw7ujZy1LibKy2jYi
YYfs3tbZ0ffCKQtv78vCgC+IxUUurALY4w58fRLLdu8u8p9jyRFHsQEwSq+W5+bP
MTh2w7cDd9h+6KoCN6AMI1Ly7MxRIhCbNBL9bzaxF9B5GK86ARY7ixkuDCEl4XCF
JGxeoye9R46NqZ6AA/k97mJun//gmUjStmb9PUXA59fR5suAB5o/5lBySZ8UXkrI
pp/iLT8vIl1hNgLh0Ghs7DBSx99I+S3VuUzjHNxL6fGRhlix7Rb8
-----END CERTIFICATE-----&signature=ZzYZeXyGaWLMkNHyzC9O1dVdwMvAbC9E3QH9Aqxn1TTIFYLqZ1aDTHRTTXSaHkRHl9p5iv7yooW++zRxta1V3ryAxVWgchMaZ3c2UY2lYlz8FBXHjTyOoL/SgctU0i5yRVk4UwB8ywQEuHsbaj0luh8D3WGoXFtv9MbFWIUvFA9oik70yMR48ljpmFMVDKJJ6kPXgRDgIufGkQ5AF+KQ/B/Hs2RaHL8rnfILXcbi7M45jmhqhDnXsPjXpt2d7C0CbBpL6ZpxF8XQ15ODYLaFSDtdWYVd0Xr0zGhcfV3E/20FXjYWZzJuCwxm5DJx45EGYAe6OgZ2V4xaSQkkXoADGg==
");
		$this->unionPay->setHttpClient($mock);
		$r = $this->unionPay->query(20180204092701,date('YmdHis'));
		var_dump($r);
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
