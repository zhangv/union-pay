<?php

require_once __DIR__ . "/../src/UnionPay.php";
require_once __DIR__ . "/../src/UnionPayWAP.php";
require_once __DIR__ . "/../src/HttpClient.php";
use zhangv\unionPay\UnionPayWAP;

$config = include './config.php';
list($mode,$config) = include './config.php';
$unionPay = new UnionPayWAP($config,$mode);

$orderId = date('YmdHis');
$amt = 1;

$html = $unionPay->pay($orderId,$amt);
echo $html;

/**
招商银行借记卡：6226090000000048
手机号：18100000000
密码：111101
短信验证码：123456（手机）/111111（PC）（先点获取验证码之后再输入）
证件类型：01
证件号：510265790128303
姓名：张三

华夏银行贷记卡：6226388000000095
手机号：18100000000
cvn2：248
有效期：1219（后台接口注意格式YYMM需倒一下）
短信验证码：123456（手机）/111111（PC）（先点获取验证码之后再输入）
证件类型：01
证件号：510265790128303
姓名：张三
 */