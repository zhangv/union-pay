<?php
require_once __DIR__ . "/../../demo/autoload.php";
use zhangv\unionpay\UnionPay;
use zhangv\unionpay\service\DirectDebit;
use PHPUnit\Framework\TestCase;

class DirectDebitTest extends TestCase{
	/** @var  DirectDebit */
	private $unionPay;
	private $config;
	public function setUp(){
		list($mode,$this->config) = include __DIR__ .'/../../demo/config-direct.php';
		$this->unionPay = UnionPay::DirectDebit($this->config,$mode);
	}

	/**
	 * @test
	 */
	public function authorize(){
		$orderId = date('YmdHis');
		$testAcc = $this->config['testAcc'][1];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'], //手机号
			'cvn2' => $testAcc['cvn2'], //cvn2
			'expired' => $testAcc['expired'], //有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
			'smsCode' => '111111', //短信验证码
		);
		$r = $this->unionPay->authorize($orderId,$accNo,$customerInfo);
		$this->assertNotNull($r);
	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionMessage 6151050
	 */
	public function backAuthorize(){
		$orderId = date('YmdHis');
		$testAcc = $this->config['testAcc'][1];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'], //手机号
			'cvn2' => $testAcc['cvn2'], //cvn2
			'expired' => $testAcc['expired'], //有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
			'smsCode' => '111111', //短信验证码
		);
		//无此交易权限
		$r = $this->unionPay->backAuthorize($orderId,$accNo,$customerInfo);
	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionMessage 6151050
	 */
	public function unauthorize(){
		$orderId = date('YmdHis');
		$testAcc = $this->config['testAcc'][1];
		$accNo = $testAcc['accNo'];
		//无此交易权限
		$r = $this->unionPay->unauthorize($orderId,$accNo);
	}

	/**
	 * @test
	 */
	public function debit(){
		$orderId = 'testdebitorder'.date('YmdHis');
		$testAcc = $this->config['testAcc'][1];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'], //手机号
			'cvn2' => $testAcc['cvn2'], //cvn2
			'expired' => $testAcc['expired'], //有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
			'smsCode' => '111111', //短信验证码
		);

		$r = $this->unionPay->debit($orderId,1,$accNo,$customerInfo);
		$this->assertEquals('00',$r['respCode']);
	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionMessage 绑定关系检查失败[6151200]
	 */
	public function debitByBindId(){
		$orderId = 'testdebitbindorder'.date('YmdHis');
		$r = $this->unionPay->debitByBindId($orderId,1,'1');
	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionMessage [9100003]Invalid field[origQryId]
	 */
	public function payUndo(){
		$orderId = 'testdebitbindorder'.date('YmdHis');
		$r = $this->unionPay->payUndo($orderId,1,1);
	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionMessage [9100003]Invalid field[origQryId]
	 */
	public function refund(){
		$orderId = 'testdebitbindorder'.date('YmdHis');
		$r = $this->unionPay->refund($orderId,1,1);
	}

	/**
	 * @test
	 */
	public function backBind(){
		$orderId = 'testbackbindorder'.date('YmdHis');
		$testAcc = $this->config['testAcc'][1];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'], //手机号
			'cvn2' => $testAcc['cvn2'], //cvn2
			'expired' => $testAcc['expired'], //有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
			'smsCode' => '111111', //短信验证码
		);

		$r = $this->unionPay->backBind($orderId,$accNo,$customerInfo,$orderId);
		$this->assertEquals('00',$r['respCode']);
	}

	/**
	 * @test
	 */
	public function frontBind(){
		$orderId = 'testfrontbindorder'.date('YmdHis');
		$testAcc = $this->config['testAcc'][1];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'], //手机号
			'cvn2' => $testAcc['cvn2'], //cvn2
			'expired' => $testAcc['expired'], //有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
			'smsCode' => '111111', //短信验证码
		);

		$r = $this->unionPay->frontBind($orderId,$accNo,$customerInfo,$orderId);
		$this->assertNotNull($r);
	}

	/**
	 * @test
	 * @expectedException Exception
	 */
	public function removeBind(){
		$orderId = 'testdebitorder'.date('YmdHis');
		$r = $this->unionPay->removeBind($orderId,$orderId);
		$this->assertEquals('00',$r['respCode']);
	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionMessage 绑定关系检查失败[6151230]
	 */
	public function queryBind(){
		$orderId = 'testdebitorder'.date('YmdHis');
		$r = $this->unionPay->queryBind($orderId,$orderId);
		$this->assertEquals('00',$r['respCode']);
	}

	/**
	 * @test
	 */
	public function frontAuthenticate(){
		$orderId = 'testfrontbindorder'.date('YmdHis');
		$testAcc = $this->config['testAcc'][1];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'], //手机号
			'cvn2' => $testAcc['cvn2'], //cvn2
			'expired' => $testAcc['expired'], //有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
			'smsCode' => '111111', //短信验证码
		);

		$r = $this->unionPay->frontAuthenticate($orderId,$accNo,$customerInfo);
		$this->assertNotNull($r);
	}

	/**
	 * @test
	 */
	public function backAuthenticate(){
		$orderId = 'testfrontbindorder'.date('YmdHis');
		$testAcc = $this->config['testAcc'][1];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'], //手机号
			'cvn2' => $testAcc['cvn2'], //cvn2
			'expired' => $testAcc['expired'], //有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
			'smsCode' => '111111', //短信验证码
		);

		$r = $this->unionPay->backAuthenticate($orderId,$accNo,$customerInfo);
		$this->assertEquals('00',$r['respCode']);
	}

	/**
	 * @test
	 */
	public function smsAuthenticate(){
		$orderId = 'testfrontbindorder'.date('YmdHis');
		$testAcc = $this->config['testAcc'][1];
		$accNo = $testAcc['accNo'];
		$customerInfo = array(
			'phoneNo' => $testAcc['phoneNo'], //手机号
			'cvn2' => $testAcc['cvn2'], //cvn2
			'expired' => $testAcc['expired'], //有效期，YYMM格式，持卡人卡面印的是MMYY的，请注意代码设置倒一下
			'smsCode' => '111111', //短信验证码
		);

		$r = $this->unionPay->smsAuthenticate($orderId,$accNo,$customerInfo);
		$this->assertEquals('00',$r['respCode']);
	}

	/**
	 * @test
	 */
	public function batchDebit(){
		$filecontent = "5.0.0|1000|5||
orderId|currencyCode|txnAmt|accType|accNo|customerNm|bizType|certifTp|certifId|phoneNo|postscript
0823000300003371|156|100|01|6221558812340000|互联网|000501|01|341126197709218366|13552535506|样例展示
0823000300003872|156|200|01|6221558812340000|互联网|000501|01|341126197709218366|13552535506|样例展示
0823000300003873|156|300|01|6221558812340000|互联网|000501|01|341126197709218366|13552535506|样例展示
0823000300003874|156|200|01|6221558812340000|互联网|000501|01|341126197709218366|13552535506|样例展示
0823000300003875|156|200|01|6221558812340000|互联网|000501|01|341126197709218366|13552535506|样例展示";
		$batchno = str_pad(rand(1,9999),4,'0',STR_PAD_LEFT);
		$r = $this->unionPay->batchDebit($batchno,5,1000,$filecontent);
		$this->assertEquals('00',$r['respCode']);
	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionMessage [34]批次（1000）不存在[7103401]
	 */
	public function queryBatchDebit(){
		$r = $this->unionPay->queryBatchDebit('1000');
	}
}
