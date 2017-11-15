<?php

/**
 * Push通知栏消息Demo
 * 本示例程序中的appId,appSecret,deviceTokens以及appPkgName需要用户自行替换为有效值
 */
require_once ('HuaweiPush.php');

$redis = new redis();
$redis->connect('127.0.0.1', '6379');

$HuaweiPush = new HuaweiPush('12345678', 'appSecret'); // 用户在华为开发者联盟申请的appId和appSecret（会员中心->我的产品，点击产品对应的Push服务，点击“移动应用详情”获取）

if ($redis->get('tokenExpiredTime') <= time()) {
    $response = $HuaweiPush->RefreshToken();
    $redis->set('accessToken', $response['access_token']); // 下发通知消息的认证Token
    $redis->set('tokenExpiredTime', time() + $response['expires_in']); // accessToken的过期时间
}

// PushManager.requestToken为客户端申请token的方法，可以调用多次以防止申请token失败
// PushToken不支持手动编写，需使用客户端的onToken方法获取
$deviceTokens = array(); // 目标设备Token
$deviceTokens[] = '12345678901234561234567890123456';
$deviceTokens[] = '22345678901234561234567890123456';
$deviceTokens[] = '32345678901234561234567890123456';

$body = $param = $action = $msg = $ext = $hps = $payload = array();

// 仅通知栏消息需要设置标题和内容，透传消息key和value为用户自定义
$body['title'] = 'Push message title'; // 消息标题
$body['content'] = 'Push message content'; // 消息内容体
$param['appPkgName'] = 'com.huawei.hms.hmsdemo'; // 定义需要打开的appPkgName
$action['type'] = 3; // 类型3为打开APP，其他行为请参考接口文档设置
$action['param'] = $param; // 消息点击动作参数
$msg['type'] = 3; // 3: 通知栏消息，异步透传消息请根据接口文档设置
$msg['action'] = $action; // 消息点击动作
$msg['body'] = $body; // 通知栏消息body内容
                      
// 扩展信息，含BI消息统计，特定展示风格，消息折叠。
$ext['biTag'] = 'Trump'; // 设置消息标签，如果带了这个标签，会在回执中推送给CP用于检测某种类型消息的到达率和状态
$ext['icon'] = 'http://pic.qiantucdn.com/58pic/12/38/18/13758PIC4GV.jpg'; // 自定义推送消息在通知栏的图标,value为一个公网可以访问的URL
                                                                          
// 华为PUSH消息总结构体
$hps['msg'] = $msg;
$hps['ext'] = $ext;
$payload['hps'] = $hps;

var_dump($HuaweiPush->SendPushMessage($redis->get('accessToken'), $deviceTokens, $payload));