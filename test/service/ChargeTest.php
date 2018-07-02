<?php
/**
 * Created by PhpStorm.
 * User: derekzhangv
 * Date: 01/02/2018
 * Time: 22:29
 */

use zhangv\unionpay\UnionPay;

class ChargeTest extends PHPUnit\Framework\TestCase{
	/** @var  \zhangv\unionpay\service\Charge */
	private $unionPay;
	private $config;

	public function setUp(){
		list($mode,$this->config) = include __DIR__ .'/../../demo/config-direct.php';
		$this->unionPay = UnionPay::Charge($this->config,$mode);
	}

	private static $outTradeNoOffset = 0;
	private function genOutTradeNo(){
		return time().(self::$outTradeNoOffset++);
	}

	/** @test */
	public function frontRepay(){
		$orderId = 'testpaybill'.date('YmdHis');
		$creditCard = $this->config['testAcc'][2];
		$usr_num = $creditCard['accNo'];
		$usr_nm = $creditCard['customerNm'];

		$r = $this->unionPay->frontRepay($orderId,1,$usr_num,$usr_nm);
		$this->assertNotFalse(strpos($r,'https://cashier.test.95516.com/b2c/api/Fee.action'));
		$this->assertNotFalse(strpos($r,$orderId));
		$this->assertNotFalse(strpos($r,$orderId));
//		输入号码错误或暂未开通此项业务
	}

	/**
	 * @test
	 */
	public function backRepay(){
		$orderId = 'testpaybill'.date('YmdHis');

		$creditCard = $this->config['testAcc'][2];
		$usr_num = $creditCard['accNo'];
		$usr_nm = $creditCard['customerNm'];

		$debitCard = $this->config['testAcc'][3];
		$accNo = $debitCard['accNo'];
		$customerInfo = array(
			'phoneNo' => $debitCard['phoneNo'], //手机号
			'certifTp' => $debitCard['certifTp'], //证件类型
			'certifId' => $debitCard['certifId'], //证件ID
			'customerNm' => $debitCard['customerNm'], //姓名
		);

		try{
			$r = $this->unionPay->backRepay($orderId,1,$usr_num,$usr_nm,$accNo,$customerInfo);
		}catch (Exception $e){
			$this->assertEquals('44',$e->getCode());//输入号码错误或暂未开通此项业务
		}
	}
	/**
	 * @test
	 */
	public function appRepay(){
		$orderId = 'testpaybill'.date('YmdHis');

		$creditCard = $this->config['testAcc'][2];
		$usr_num = $creditCard['accNo'];
		$usr_nm = $creditCard['customerNm'];

		try{
			$r = $this->unionPay->appRepay($orderId,1,$usr_num,$usr_nm);
		}catch (Exception $e){
			$this->assertEquals('44',$e->getCode());//输入号码错误或暂未开通此项业务
		}
	}

	/**
	 * @test
	 */
	public function queryRepay(){
		$orderId = 'testpaybill'.date('YmdHis');

		$creditCard = $this->config['testAcc'][2];
		$usr_num = $creditCard['accNo'];
		$usr_nm = $creditCard['customerNm'];

		try{
			$r = $this->unionPay->queryRepay($orderId,$usr_num,date('Ym'),$usr_nm);
		}catch (Exception $e){
			$this->assertEquals('01',$e->getCode());//交易失败。详情请咨询95516[2123912]
		}
	}

	/** @test */
	public function areas(){
		$r = $this->unionPay->areas();
		// string(11893) "[{"code":"00","name":"全国"},{"code":"34","name":"安徽","areas":[{"code":"3401","name":"合肥"},{"code":"3402","name":"芜湖"},{"code":"3403","name":"蚌埠"},{"code":"3404","name":"淮南"},{"code":"3405","name":"马鞍山"},{"code":"3406","name":"淮北"},{"code":"3407","name":"铜陵"},{"code":"3408","name":"安庆"},{"code":"3410","name":"黄山"},{"code":"3411","name":"滁州"},{"code":"3412","name":"阜阳"},{"code":"3413","name":"宿州"},{"code":"3414","name":"巢湖"},{"code":"3415","name":"六安"},{"code":"3416","name":"亳州"},{"code":"3417","name":"池州"},{"code":"3418","name":"宣城"},{"code":"3474","name":"宁国"}]},{"code":"11","name":"北京"},{"code":"50","name":"重庆"},{"code":"35","name":"福建","areas":[{"code":"3501","name":"福州"},{"code":"3502","name":"厦门"},{"code":"3503","name":"莆田"},{"code":"3504","name":"三明"},{"code":"3505","name":"泉州"},{"code":"3506","name":"漳州"},{"code":"3507","name":"南平"},{"code":"3508","name":"龙岩"},{"code":"3509","name":"宁德"}]},{"code":"62","name":"甘肃","areas":[{"code":"6201","name":"兰州"},{"code":"6202","name":"嘉峪关"},{"code":"6203","name":"金昌"},{"code":"6204","name":"白银"},{"code":"6205","name":"天水"},{"code":"6206","name":"武威"},{"code":"6221","name":"酒泉"},{"code":"6222","name":"张掖"},{"code":"6224","name":"定西"},{"code":"6226","name":"陇南"},{"code":"6227","name":"平凉"},{"code":"6228","name":"庆阳"},{"code":"6229","name":"临夏回族自治州"},{"code":"6230","name":"甘南藏族自治州"}]},{"code":"44","name":"广东","areas":[{"code":"4401","name":"广州"},{"code":"4402","name":"韶关"},{"code":"4403","name":"深圳"},{"code":"4404","name":"珠海"},{"code":"4405","name":"汕头"},{"code":"4406","name":"佛山"},{"code":"4407","name":"江门"},{"code":"4408","name":"湛江"},{"code":"4409","name":"茂名"},{"code":"4412","name":"肇庆"},{"code":"4413","name":"惠州"},{"code":"4414","name":"梅州"},{"code":"4415","name":"汕尾"},{"code":"4416","name":"河源"},{"code":"4417","name":"阳江"},{"code":"4418","name":"清远"},{"code":"4419","name":"东莞"},{"code":"4420","name":"中山"},{"code":"4451","name":"潮州"},{"code":"4452","name":"揭阳"},{"code":"4453","name":"云浮"}]},{"code":"45","name":"广西","areas":[{"code":"4501","name":"南宁"},{"code":"4502","name":"柳州"},{"code":"4503","name":"桂林"},{"code":"4504","name":"梧州"},{"code":"4505","name":"北海"},{"code":"4506","name":"防城港"},{"code":"4507","name":"钦州"},{"code":"4508","name":"贵港"},{"code":"4509","name":"玉林"},{"code":"4521","name":"南宁地区"},{"code":"4522","name":"柳州地区"},{"code":"4524","name":"贺州地区"},{"code":"4526","name":"百色地区"},{"code":"4527","name":"河池地区"}]},{"code":"52","name":"贵州","areas":[{"code":"5201","name":"贵阳"},{"code":"5202","name":"六盘水"},{"code":"5203","name":"遵义"},{"code":"5204","name":"安顺"},{"code":"5222","name":"铜仁"},{"code":"5223","name":"兴义"},{"code":"5224","name":"毕节"},{"code":"5226","name":"凯里"},{"code":"5227","name":"都匀"}]},{"code":"46","name":"海南","areas":[{"code":"4601","name":"海口"},{"code":"4602","name":"三亚"},{"code":"4690","name":"省直辖县级行政单位"}]},{"code":"13","name":"河北","areas":[{"code":"1301","name":"石家庄"},{"code":"1302","name":"唐山"},{"code":"1303","name":"秦皇岛"},{"code":"1304","name":"邯郸"},{"code":"1305","name":"邢台"},{"code":"1306","name":"保定"},{"code":"1307","name":"张家口"},{"code":"1308","name":"承德"},{"code":"1309","name":"沧州"},{"code":"1310","name":"廊坊"},{"code":"1311","name":"衡水"}]},{"code":"23","name":"黑龙江","areas":[{"code":"2301","name":"哈尔滨"},{"code":"2302","name":"齐齐哈尔"},{"code":"2303","name":"鸡西"},{"code":"2304","name":"鹤岗"},{"code":"2305","name":"双鸭山"},{"code":"2306","name":"大庆"},{"code":"2307","name":"伊春"},{"code":"2308","name":"佳木斯"},{"code":"2309","name":"七台河"},{"code":"2310","name":"牡丹江"},{"code":"2311","name":"黑河"},{"code":"2312","name":"绥化"},{"code":"2327","name":"大兴安岭"}]},{"code":"41","name":"河南","areas":[{"code":"4101","name":"郑州"},{"code":"4102","name":"开封"},{"code":"4103","name":"洛阳"},{"code":"4104","name":"平顶山"},{"code":"4105","name":"安阳"},{"code":"4106","name":"鹤壁"},{"code":"4107","name":"新乡"},{"code":"4108","name":"焦作"},{"code":"4109","name":"濮阳"},{"code":"4110","name":"许昌"},{"code":"4111","name":"漯河"},{"code":"4112","name":"三门峡"},{"code":"4113","name":"南阳"},{"code":"4114","name":"商丘"},{"code":"4115","name":"信阳"},{"code":"4116","name":"周口"},{"code":"4117","name":"驻马店"}]},{"code":"42","name":"湖北","areas":[{"code":"4201","name":"武汉"},{"code":"4202","name":"黄石"},{"code":"4203","name":"十堰"},{"code":"4205","name":"宜昌"},{"code":"4206","name":"襄樊"},{"code":"4207","name":"鄂州"},{"code":"4208","name":"荆门"},{"code":"4209","name":"孝感"},{"code":"4210","name":"荆州"},{"code":"4211","name":"黄冈"},{"code":"4212","name":"咸宁"},{"code":"4213","name":"随州"},{"code":"4228","name":"恩施"},{"code":"4290","name":"省直辖县级行政单位"}]},{"code":"43","name":"湖南","areas":[{"code":"4301","name":"长沙"},{"code":"4302","name":"株洲"},{"code":"4303","name":"湘潭"},{"code":"4304","name":"衡阳"},{"code":"4305","name":"邵阳"},{"code":"4306","name":"岳阳"},{"code":"4307","name":"常德"},{"code":"4308","name":"张家界"},{"code":"4309","name":"益阳"},{"code":"4310","name":"郴州"},{"code":"4311","name":"永州"},{"code":"4312","name":"怀化"},{"code":"4313","name":"娄底"},{"code":"4331","name":"湘西土家族苗族自治州"}]},{"code":"32","name":"江苏","areas":[{"code":"3201","name":"南京"},{"code":"3202","name":"无锡"},{"code":"3203","name":"徐州"},{"code":"3204","name":"常州"},{"code":"3205","name":"苏州"},{"code":"3206","name":"南通"},{"code":"3207","name":"连云港"},{"code":"3208","name":"淮安"},{"code":"3209","name":"盐城"},{"code":"3210","name":"扬州"},{"code":"3211","name":"镇江"},{"code":"3212","name":"泰州"},{"code":"3213","name":"宿迁"}]},{"code":"36","name":"江西","areas":[{"code":"3601","name":"南昌"},{"code":"3602","name":"景德镇"},{"code":"3603","name":"萍乡"},{"code":"3604","name":"九江"},{"code":"3605","name":"新余"},{"code":"3606","name":"鹰潭"},{"code":"3607","name":"赣州"},{"code":"3608","name":"吉安"},{"code":"3609","name":"宜春"},{"code":"3610","name":"抚州"},{"code":"3611","name":"上饶"}]},{"code":"22","name":"吉林","areas":[{"code":"2201","name":"长春"},{"code":"2202","name":"吉林"},{"code":"2203","name":"四平"},{"code":"2204","name":"辽源"},{"code":"2205","name":"通化"},{"code":"2206","name":"白山"},{"code":"2207","name":"松原"},{"code":"2208","name":"白城"},{"code":"2224","name":"延边"}]},{"code":"21","name":"辽宁","areas":[{"code":"2101","name":"沈阳"},{"code":"2102","name":"大连"},{"code":"2103","name":"鞍山"},{"code":"2104","name":"抚顺"},{"code":"2105","name":"本溪"},{"code":"2106","name":"丹东"},{"code":"2107","name":"锦州"},{"code":"2108","name":"营口"},{"code":"2109","name":"阜新"},{"code":"2110","name":"辽阳"},{"code":"2111","name":"盘锦"},{"code":"2112","name":"铁岭"},{"code":"2113","name":"朝阳"},{"code":"2114","name":"葫芦岛"}]},{"code":"15","name":"内蒙古","areas":[{"code":"1501","name":"呼和浩特市"},{"code":"1502","name":"包头市"},{"code":"1503","name":"乌海市"},{"code":"1504","name":"赤峰市"},{"code":"1505","name":"通辽市"},{"code":"1506","name":"鄂尔多斯市"},{"code":"1507","name":"呼伦贝尔市"},{"code":"1522","name":"兴安盟"},{"code":"1525","name":"锡林郭勒盟"},{"code":"1526","name":"乌兰察布盟"},{"code":"1528","name":"巴彦淖尔盟"},{"code":"1529","name":"阿拉善盟"}]},{"code":"64","name":"宁夏","areas":[{"code":"6401","name":"银川"},{"code":"6402","name":"石嘴山"},{"code":"6403","name":"吴忠"},{"code":"6404","name":"固原"},{"code":"6405","name":"中卫"}]},{"code":"37","name":"山东","areas":[{"code":"3701","name":"济南"},{"code":"3702","name":"青岛"},{"code":"3703","name":"淄博"},{"code":"3704","name":"枣庄"},{"code":"3705","name":"东营"},{"code":"3706","name":"烟台"},{"code":"3707","name":"潍坊"},{"code":"3708","name":"济宁"},{"code":"3709","name":"泰安"},{"code":"3710","name":"威海"},{"code":"3711","name":"日照"},{"code":"3712","name":"莱芜"},{"code":"3713","name":"临沂"},{"code":"3714","name":"德州"},{"code":"3715","name":"聊城"},{"code":"3716","name":"滨州"},{"code":"3717","name":"菏泽"}]},{"code":"31","name":"上海"},{"code":"14","name":"山西","areas":[{"code":"1401","name":"太原"},{"code":"1402","name":"大同"},{"code":"1403","name":"阳泉"},{"code":"1404","name":"长治"},{"code":"1405","name":"晋城"},{"code":"1406","name":"朔州"},{"code":"1407","name":"晋中"},{"code":"1408","name":"运城"},{"code":"1409","name":"忻州"},{"code":"1410","name":"临汾"},{"code":"1423","name":"吕梁"}]},{"code":"61","name":"陕西","areas":[{"code":"6101","name":"西安"},{"code":"6102","name":"铜川"},{"code":"6103","name":"宝鸡"},{"code":"6104","name":"咸阳"},{"code":"6105","name":"渭南"},{"code":"6106","name":"延安"},{"code":"6107","name":"汉中"},{"code":"6108","name":"榆林"},{"code":"6109","name":"安康"},{"code":"6110","name":"商洛"}]},{"code":"51","name":"四川","areas":[{"code":"5101","name":"成都"},{"code":"5103","name":"自贡"},{"code":"5104","name":"攀枝花"},{"code":"5105","name":"泸州"},{"code":"5106","name":"德阳"},{"code":"5107","name":"绵阳"},{"code":"5108","name":"广元"},{"code":"5109","name":"遂宁"},{"code":"5110","name":"内江"},{"code":"5111","name":"乐山"},{"code":"5113","name":"南充"},{"code":"5114","name":"眉山"},{"code":"5115","name":"宜宾"},{"code":"5116","name":"广安"},{"code":"5117","name":"达州"},{"code":"5118","name":"雅安"},{"code":"5119","name":"巴中"},{"code":"5120","name":"资阳"},{"code":"5132","name":"阿坝"},{"code":"5133","name":"甘孜"},{"code":"5134","name":"凉山"}]},{"code":"12","name":"天津"},{"code":"65","name":"新疆","areas":[{"code":"6501","name":"乌鲁木齐"},{"code":"6502","name":"克拉玛依"},{"code":"6521","name":"吐鲁番"},{"code":"6522","name":"哈密"},{"code":"6523","name":"昌吉回族自治州"},{"code":"6527","name":"博尔塔拉蒙古自治州"},{"code":"6528","name":"巴音郭楞蒙古自治州"},{"code":"6529","name":"阿克苏"},{"code":"6530","name":"克孜勒苏柯尔克孜自治州"},{"code":"6531","name":"喀什"},{"code":"6532","name":"和田"},{"code":"6540","name":"伊犁哈萨克自治州"},{"code":"6542","name":"塔城"},{"code":"6543","name":"阿勒泰"},{"code":"6590","name":"自治县直辖县级行政单位"}]},{"code":"53","name":"云南","areas":[{"code":"5301","name":"昆明"},{"code":"5303","name":"曲靖"},{"code":"5304","name":"玉溪"},{"code":"5305","name":"保山"},{"code":"5306","name":"昭通"},{"code":"5323","name":"楚雄"},{"code":"5325","name":"红河"},{"code":"5326","name":"文山"},{"code":"5327","name":"思茅"},{"code":"5328","name":"西双版纳"},{"code":"5329","name":"大理"},{"code":"5331","name":"德宏"},{"code":"5332","name":"丽江"},{"code":"5333","name":"怒江"},{"code":"5334","name":"迪庆"},{"code":"5335","name":"临沧"}]},{"code":"33","name":"浙江","areas":[{"code":"3301","name":"杭州"},{"code":"3302","name":"宁波"},{"code":"3303","name":"温州"},{"code":"3304","name":"嘉兴"},{"code":"3305","name":"湖州"},{"code":"3306","name":"绍兴"},{"code":"3307","name":"金华"},{"code":"3308","name":"衢州"},{"code":"3309","name":"舟山"},{"code":"3310","name":"台州"},{"code":"3311","name":"丽水"}]}]"
		$r = json_decode($r);
		$this->assertNotNull($r);
	}

	/** @test */
	public function categories(){
		$r = $this->unionPay->categories('3401');
		//string(5670) "[{"code":"D1_3600","name":"电费缴纳","type":"2","categories":[{"code":"D1_3600_8401","name":"合肥供电公司"},{"code":"D1_3600_0101","name":"肥东供电公司"},{"code":"D1_3600_0102","name":"肥西供电公司"},{"code":"D1_3600_0103","name":"长丰供电公司"},{"code":"D1_3600_8101","name":"安徽电力省公司"}]},{"code":"D3_3600","name":"燃气费缴纳","type":"3","categories":[{"code":"D3_3600_00AH","name":"合肥燃气"}]},{"code":"I1_3600","name":"联通缴费","type":"5","categories":[{"code":"I1_3600_1305","name":"安徽联通"}]},{"code":"J0_3600","name":"彩票投注","type":"34","categories":[{"code":"J0_3600_00AH","name":"安徽福彩"},{"code":"J0_3600_00ZS","name":"招商福彩"},{"code":"J0_3600_00NH","name":"农行福彩"}]},{"code":"D4_3600","name":"水费缴纳","type":"1","categories":[{"code":"D4_3600_00HF","name":"合肥供水"},{"code":"D4_3600_G114","name":"安徽-合肥-长丰双墩水厂"}]},{"code":"I1_3600","name":"移动缴费","type":"4","categories":[{"code":"I1_3600_000A","name":"安徽移动"}]},{"code":"G1_3600","name":"交通罚款","type":"26","categories":[{"code":"G1_3600_3602","name":"交警罚款交罚(安徽非税)(条码)"},{"code":"G1_3600_0000","name":"违章代办(第三方处理)"},{"code":"G1_3600_0001","name":"安徽非税交警规费"}]},{"code":"I1_3600","name":"有线电视缴费","type":"19","categories":[{"code":"I1_3600_3610","name":"合肥市有线电视"},{"code":"I1_3600_200A","name":"合肥市有线电视"}]},{"code":"S2_3600","name":"医疗充值","type":"47","categories":[{"code":"S2_3600_0000","name":"医疗-健康之路"}]},{"code":"I1_3600","name":"电信缴费","type":"6","categories":[{"code":"I1_3600_2550","name":"安徽电信固话缴费(滁州)"},{"code":"I1_3600_2551","name":"安徽电信固话缴费(合肥)"},{"code":"I1_3600_2552","name":"安徽电信固话缴费(蚌埠)"},{"code":"I1_3600_2553","name":"安徽电信固话缴费(芜湖)"},{"code":"I1_3600_2554","name":"安徽电信固话缴费(淮南)"},{"code":"I1_3600_2555","name":"安徽电信固话缴费(马鞍山)"},{"code":"I1_3600_2556","name":"安徽电信固话缴费(安庆)"},{"code":"I1_3600_2557","name":"安徽电信固话缴费(宿州)"},{"code":"I1_3600_2558","name":"安徽电信固话缴费(阜阳)"},{"code":"I1_3600_2559","name":"安徽电信固话缴费(黄山)"},{"code":"I1_3600_2560","name":"安徽电信固话缴费(亳州)"},{"code":"I1_3600_2561","name":"安徽电信固话缴费(淮北)"},{"code":"I1_3600_2562","name":"安徽电信固话缴费(铜陵)"},{"code":"I1_3600_2563","name":"安徽电信固话缴费(宣城)"},{"code":"I1_3600_2564","name":"安徽电信固话缴费(六安)"},{"code":"I1_3600_2566","name":"安徽电信固话缴费(池州)"},{"code":"I1_3600_5550","name":"安徽电信宽带缴费(滁州)"},{"code":"I1_3600_5551","name":"安徽电信宽带缴费(合肥)"},{"code":"I1_3600_5552","name":"安徽电信宽带缴费(蚌埠)"},{"code":"I1_3600_5553","name":"安徽电信宽带缴费(芜湖)"},{"code":"I1_3600_5554","name":"安徽电信宽带缴费(淮南)"},{"code":"I1_3600_5555","name":"安徽电信宽带缴费(马鞍山)"},{"code":"I1_3600_5556","name":"安徽电信宽带缴费(安庆)"},{"code":"I1_3600_5557","name":"安徽电信宽带缴费(宿州)"},{"code":"I1_3600_5558","name":"安徽电信宽带缴费(阜阳)"},{"code":"I1_3600_5559","name":"安徽电信宽带缴费(黄山)"},{"code":"I1_3600_5560","name":"安徽电信宽带缴费(亳州)"},{"code":"I1_3600_5561","name":"安徽电信宽带缴费(淮北)"},{"code":"I1_3600_5562","name":"安徽电信宽带缴费(铜陵)"},{"code":"I1_3600_5563","name":"安徽电信宽带缴费(宣城)"},{"code":"I1_3600_5564","name":"安徽电信宽带缴费(六安)"},{"code":"I1_3600_5566","name":"安徽电信宽带缴费(池州)"},{"code":"I1_3600_4550","name":"安徽电信手机缴费(滁州)"},{"code":"I1_3600_4551","name":"安徽电信手机缴费(合肥)"},{"code":"I1_3600_4552","name":"安徽电信手机缴费(蚌埠)"},{"code":"I1_3600_4553","name":"安徽电信手机缴费(芜湖)"},{"code":"I1_3600_4554","name":"安徽电信手机缴费(淮南)"},{"code":"I1_3600_4555","name":"安徽电信手机缴费(马鞍山)"},{"code":"I1_3600_4556","name":"安徽电信手机缴费(安庆)"},{"code":"I1_3600_4557","name":"安徽电信手机缴费(宿州)"},{"code":"I1_3600_4558","name":"安徽电信手机缴费(阜阳)"},{"code":"I1_3600_4559","name":"安徽电信手机缴费(黄山)"},{"code":"I1_3600_4560","name":"安徽电信手机缴费(亳州)"},{"code":"I1_3600_4561","name":"安徽电信手机缴费(淮北)"},{"code":"I1_3600_4562","name":"安徽电信手机缴费(铜陵)"},{"code":"I1_3600_4563","name":"安徽电信手机缴费(宣城)"},{"code":"I1_3600_4564","name":"安徽电信手机缴费(六安)"},{"code":"I1_3600_4566","name":"安徽电信手机缴费(池州)"}]},{"code":"S0_3600","name":"税费","type":"20","categories":[{"code":"S0_3600_ACP01","name":"安徽国税"},{"code":"S0_9800_0003_1","name":"安徽-安徽-财政非税(直缴)"},{"code":"S0_9800_0003","name":"安徽-安徽-财政非税"}]},{"code":"S0_3600","name":"非税缴费","type":"42","categories":[{"code":"S0_3600_0000","name":"安徽非税一般缴款书信息查询"},{"code":"S0_3600_0001","name":"安徽非税电子票据号查询"},{"code":"S0_3600_0003","name":"安徽非税法院诉讼费业务"}]},{"code":"P1_3600","name":"教育缴费","type":"47","categories":[{"code":"P1_3600_0001","name":"安徽教育费"}]},{"code":"S3_3600","name":"工会缴费","type":"49","categories":[{"code":"S3_3600_0001","name":"安徽-安徽-中国银联股份有限公司安徽分公司(党费)"}]}]"
		$r = json_decode($r);
		$this->assertNotNull($r);
	}

	/** @test */
	public function biz(){
		$r = $this->unionPay->biz('D1_3600_8401');
		//{"code":"D1_3600_8401","action":"prequery","title":"账单查询 - 合肥供电公司","form":[{"type":"string","label":"友情提示","value":"受理时间为01:00-23:00"},{"type":"text","label":"用户号码","name":"usr_num"}]}
		$this->assertNotNull($r);
	}
}
