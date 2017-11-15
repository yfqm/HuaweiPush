<?php

/**
 * Push透传消息Demo
 * 本示例程序中的appId,appSecret以及deviceTokens需要用户自行替换为有效值
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

$body = $msg = $hps = $payload = array();

$body['key1'] = 'value1'; // 透传消息自定义body内容
$body['key2'] = 'value2'; // 透传消息自定义body内容
$body['key3'] = 'value3'; // 透传消息自定义body内容
$msg['type'] = 1; // 1: 透传异步消息，通知栏消息请根据接口文档设置
$msg['body'] = json_encode($body); // body内容不一定是JSON，可以是String，若为JSON需要转化为String发送
                                   
// 华为PUSH消息总结构体
$hps['msg'] = $msg;
$payload['hps'] = $hps;

var_dump($HuaweiPush->SendPushMessage($redis->get('accessToken'), $deviceTokens, $payload));