<?php
/**
 * Created by PhpStorm.
 * User: derekzhangv
 * Date: 2018/5/7
 * Time: 11:51
 */

require_once __DIR__ . "/../demo/autoload.php";

use zhangv\unionpay\UnionPay;
use PHPUnit\Framework\TestCase;

class UnionPayTest extends TestCase{
	/** @var  UnionPay */
	private $unionPay;
	/** @var  array */
	private $config;

	public function setUp(){
		list($mode,$this->config) = include __DIR__ .'/../demo/config.php';
		$this->unionPay = new UnionPay($this->config,$mode);
	}

	/**
	 * @test
	 */
	public function encryptData(){
		$accNo = '6226388000000095';
		$r1 = $this->unionPay->encryptData($accNo);
		$r2 = $this->unionPay->encryptData($accNo);
		$this->assertNotEquals($r1,$r2);

//		$r3 = $this->unionPay->decryptData($r2);
//		$this->assertEquals($r1,$r3);
	}

	/**
	 * @test
	 */
	public function updatePublicKey(){
		$orderId = date('YmdHis');
		$f = $this->unionPay->updatePublicKey($orderId);
		$this->assertEquals('00',$f['respCode']);
	}

	/**
	 * @test
	 */
	public function verifySignature(){
		$vstr = "accNo=cjsqgmTALZk1Rcb/l0GL+WKoExXkdZPv+kEezrB0+qpw0qQNNXjCDnV65peho8RyqPchKR3uX22Ov9A5mkUsoUtQD8Z9p1dBxv/s0C+fZOLHJz3LkJJL8xDgfAS7OGghS7gKRJt05S5WDnC5SBoIvb5+PFCB9gjOEJrOBYE3YgwBqQ/UQbPpVsk5FnOKlYQyHC5Z/BBz5YhUbarjAKwBN8aY3aLpD+PN0ii535XuMV2ZTnnkKvVtiWNHHZf5HOD5qgUOR83QSAQSEw6/5inRqI6miWCbAVeidk0JbOIqbElXUeiPDwFvGx6DmBWsydqKI4iQsfYBIrdScevzZnGvHg==&accessType=0&bizType=000301&currencyCode=156&encoding=utf-8&merId=777290058158470&orderId=20180414025538&queryId=121804140255385818028&respCode=00&respMsg=成功[0000000]&signMethod=01&signPubKeyCert=-----BEGIN CERTIFICATE-----
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
-----END CERTIFICATE-----&txnAmt=1000&txnSubType=01&txnTime=20180414025538&txnType=01&version=5.1.0";
		//line separator!!!!
		$vstr = preg_replace("/\r\n|\r|\n/m", "\r\n", $vstr);
		$sha256 = hash('sha256', $vstr);
		$this->assertEquals("ba3adf0b7276ac823d063aefa672d045a0647ba2f248ddfbe3c4054e5d6d95d5", $sha256);
		$sig = "f5Gz5srn7RvdF2qtAHcakoiwVbSO8cOf9CVX9AJ3oCyjxsdTTXQmx+JQZ8Aw1y2ON+dvFxWC5Z4X/lOmQRSXs3fUZWaErWkgTqBO9Wrl5x3f6FgnB3sGuCXSPs/fm/mXhzv3LVrsmx2EmAxgsuDc7U+eRej/kfwSqI3E2wgHdteQW9jVhG8hxllO7yu9OTfcoPlo87quisMtggeXrfprpuBWKRTPRqsWUypP3+cskVZmc65XL7AGsz74HhS5kwZ9Sc2LejrQKC73Q4wzREdwKUwiPAnoL96ryDqca5+RT1WYq9u3YtxjQUzFTTXypMtZlH92P++MK+rppE9ck5rpyg==";

		$params = $this->unionPay->convertQueryStringToArray($vstr);
		$params['signature'] = $sig;

		$r = $this->unionPay->validateSign($params);
		$this->assertEquals(1,$r);
	}

	/**
	 * @test
	 */
	function onNotify(){
		$notify = ['a'=>'b'];
		$r = $this->unionPay->onNotify($notify,function($data){
			return $data;
		},false);
		$this->assertEquals('b',$r['a']);

		$notify = ['a' => 'b', 'signMethod' => UnionPay::SIGNMETHOD_RSA,
			'version' => UnionPay::VERSION_500];
		$notify['certId'] = $this->unionPay->getSignCertId();
		$sig = $this->unionPay->sign($notify);
		$notify['signature'] = $sig;
		$r = $this->unionPay->onNotify($notify,function($data){
			return $data;
		},true);
		$this->assertEquals('b',$r['a']);
	}

	/**
	 * @test
	 * @expectedException Exception
	 */
	function onNotify_notcallable(){
		$notify = ['a'=>'b'];
		$r = $this->unionPay->onNotify($notify,'',false);
	}
}
