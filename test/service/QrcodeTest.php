<?php
/**
 * Created by PhpStorm.
 * User: derekzhangv
 * Date: 01/02/2018
 * Time: 22:29
 */

use zhangv\unionpay\UnionPay;

class QrcodeTest extends PHPUnit\Framework\TestCase{
	/** @var  \zhangv\unionpay\service\Qrcode */
	private $unionPay;

	public function setUp(){
		list($mode,$config) = include __DIR__ .'/../../demo/config.php';
		$this->unionPay = UnionPay::Qrcode($config,$mode);
	}

	private static $outTradeNoOffset = 0;
	private function genOutTradeNo(){
		return time().(self::$outTradeNoOffset++);
	}
	/** @test */
	public function pay(){
		$orderId = $this->genOutTradeNo();
		$c2b = $this->getC2BCode();
		$qrno = $c2b['qrNo'];
		$ext = [
			'qrNo' => $qrno
		];
		$r = $this->unionPay->pay($orderId,1,$ext);
		$this->assertEquals('00',$r['respCode']);
	}

	/** @test */
	public function apply(){
		$orderId = $this->genOutTradeNo();
		$r = $this->unionPay->apply($orderId,1);
		$this->assertEquals('00',$r['respCode']);
		$this->assertNotNull($r['qrCode']); // https://qr.95516.com/00010001/62112028173283416953321113029241
	}

	public function getC2BCode(){
		$applyc2b = "https://open.unionpay.com/ajweb/help/qrcodeFormPage/sendOk?puid=34&requestType=coverSweepReceiverApp&sendtype=C2B%E7%A0%81%E7%94%B3%E8%AF%B7&sendData=%5B%7B%22fid%22%3A523%2C%22keyword%22%3A%22issCode%22%2C%22value%22%3A%2290880019%22%7D%2C%7B%22fid%22%3A525%2C%22keyword%22%3A%22backUrl%22%2C%22value%22%3A%22http%3A%2F%2F101.231.204.84%3A8091%2Fsim%2Fnotify_url2.jsp%22%7D%2C%7B%22fid%22%3A526%2C%22keyword%22%3A%22qrType%22%2C%22value%22%3A%22%22%7D%2C%7B%22fid%22%3A527%2C%22keyword%22%3A%22reqAddnData%22%2C%22value%22%3A%22%22%7D%2C%7B%22fid%22%3A646%2C%22keyword%22%3A%22emvCodeIn%22%2C%22value%22%3A%22%22%7D%2C%7B%22fid%22%3A528%2C%22keyword%22%3A%22accNo%22%2C%22value%22%3A%226216261000000002485%22%7D%2C%7B%22fid%22%3A529%2C%22keyword%22%3A%22name%22%2C%22value%22%3A%22%E5%AE%8B%E5%B0%8F%22%7D%5D";
		$hc = new \zhangv\unionpay\util\HttpClient(1);
		$r = $hc->get($applyc2b);
		$r = substr($r,1,strlen($r)-2);
		$t = explode(',',$r);
		$rr = [];
		foreach($t as $tt){
			$tmp  = explode('=',$tt,2);
			$rr[trim($tmp[0])] = trim($tmp[1]);
		}
		return $rr;
	}

	/**
	 * @test
	 * @expectedException Exception
	 */
	public function payUndo(){
		$orderId = $this->genOutTradeNo();
		$f = $this->unionPay->payUndo($orderId,1,1);
	}

	/**
	 * @test
	 * @expectedException Exception
	 */
	public function refund(){
		$orderId = $this->genOutTradeNo();
		$f = $this->unionPay->refund($orderId,1,1);
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

}
