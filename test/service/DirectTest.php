<?php
/**
 * Created by PhpStorm.
 * User: derekzhangv
 * Date: 2018/4/10
 * Time: 10:52
 */
require_once __DIR__ . "/../../demo/autoload.php";
use zhangv\unionpay\UnionPay;
use PHPUnit\Framework\TestCase;

class DirectTest extends TestCase{
	/** @var  \zhangv\unionpay\service\Direct */
	private $unionPay;
	private $config;
	public function setUp(){
		list($mode,$this->config) = include __DIR__ .'/../../demo/config-direct.php';
		$this->unionPay = UnionPay::Direct($this->config,$mode);
	}

	/**
	 * 测试商户号仅支持前台开通，后台开通：无此交易权限。（需要用真实商户号测试）
	 * @test
	 * @expectedException Exception
	 */
	public function backOpen(){
		$orderId = date('YmdHis');
		$testAcc = $this->config['testAcc'][1];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'], //手机号
			'cvn2' => $testAcc['cvn2'], //cvn2
			'expired' => $testAcc['expired'], //有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
			'smsCode' => '111111', //短信验证码
		);
		$r = $this->unionPay->backOpen($orderId,$accNo,$customerInfo);
	}

	/**
	 * @test
	 */
	public function queryOpen(){
		$orderId = date('YmdHis');
		$testAcc = $this->config['testAcc'][0];
		$accNo = $testAcc['accNo'];
		$r = $this->unionPay->queryOpen($orderId,$accNo);
		$this->assertEquals('1',$r['activateStatus']);
	}
	/**
	 * @test
	 */
	public function sms(){
		$orderId = 'testorder';//date('YmdHis'); //static order id if using mockery
		$testAcc = $this->config['testAcc'][0];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'],
		);

		$mock = Mockery::mock('HttpClient');
		$mock->shouldReceive('post')->andReturn("accessType=0&bizType=000301&encoding=UTF-8&merId=777290058158470&orderId=20180624171625&respCode=00&respMsg=成功[0000000]&signMethod=01&txnSubType=00&txnTime=20180624171625&txnType=77&version=5.1.0&signPubKeyCert=-----BEGIN CERTIFICATE-----
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
-----END CERTIFICATE-----&signature=PF/QCf/LgEXFAN/MyBbH2jGsoP62luL5xG5NDQFGDw1R4uUdCLoP0liiT+aIkcE5cBbZrxesjA+zQOFvbqhet3bGh0j/62s5omBncrSP1BtoEB2ij8V/SKE9rmgNIv6lKPRTF3kAoo0V0GxS5iLi5rStlPOYMS5P+YkbE+vY/GG4ZTkecpXk5qDi2Phy0LccAKNq4/rcHhdw/xISO1OaxpMRZBikqKMyk8h/xZNppSnvgFxIg+Qy9vEK9wmVNTPhei/7+qoSNRap3ffC+Sq8ItmHNXGcY44O1jSeAAlwmt8ax1rqtPXD3FaaMVhwcV5PxOaC++Pc1dFbh8J7SV828A==
");
//		$this->unionPay->setHttpClient($mock);

		try{
			$r = $this->unionPay->sms($orderId,$accNo,$customerInfo);
			$this->assertEquals('00',$r['respCode']);
		}catch (Exception $e){
			$this->assertEquals('37',$e->getCode()); //已超过最大查询次数或操作过于频繁[6100087]
		}

	}

	/**
	 * @test
	 */
	public function pay(){
		$orderId = date('YmdHis');
		$testAcc = $this->config['testAcc'][2];
		$accNo = $testAcc['accNo'];
		$ext = [];
		$customeerInfo =  ['smsCode' => '111111'];
		$r = $this->unionPay->pay($orderId,1000,$accNo,$customeerInfo,$ext);
		$this->assertEquals('00',$r['respCode']);
	}

	/**
	 * 无此交易权限
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionMessageRegExp  /无此交易权限/
	 */
	public function payByInstallment(){
		$orderId = date('YmdHis');
		$testAcc = $this->config['testAcc'][1];
		$accNo = $testAcc['accNo'];
		$customerInfo = ['smsCode' => '111111'];
		$this->unionPay->payByInstallment($orderId,100,$accNo,$customerInfo,$testAcc['instalTransInfo']);
	}

}
