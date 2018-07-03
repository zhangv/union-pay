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
//			'termId' => '12',
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

	/** @test */
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

}
