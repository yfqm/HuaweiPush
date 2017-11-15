<?php

/*
 * @author: yfqm <yfqm@outlook.com>
 */
class HuaweiPush
{

    public $appId = '';

    public $appSecret = '';

    const RESTAPI_TOKEN = 'https://login.vmall.com/oauth2/token';

    const RESTAPI_PUSHSEND = 'https://api.push.hicloud.com/pushsend.do';

    public function __construct($appId, $appSecret)
    {
        assert(isset($appId) && isset($appSecret));
        
        $this->appId = $appId;
        $this->appSecret = $appSecret;
    }

    public function __destruct()
    {}

    protected function json2Array($json)
    {
        $json = stripslashes($json);
        return json_decode($json, true);
    }

    protected function callRestful($url, $params)
    {
        $requestBase = new RequestBase();
        $extra_conf = array(
            CURLOPT_SSL_VERIFYPEER => true, // 只信任CA颁布的证书
            CURLOPT_CAINFO => getcwd() . '/root.pem', // CA根证书
            CURLOPT_SSL_VERIFYHOST => 2 // 检查证书中是否设置域名
        );
        $ret = $this->json2Array($requestBase->exec($url, $params, RequestBase::METHOD_POST, $extra_conf));
        
        return $ret;
    }

    /**
     * 获取下发通知消息的认证Token
     */
    public function RefreshToken()
    {
        $params = array();
        $params['grant_type'] = 'client_credentials';
        $params['client_secret'] = $this->appSecret;
        $params['client_id'] = $this->appId;
        
        return $this->callRestful(self::RESTAPI_TOKEN, $params);
    }

    /**
     * 发送Push消息
     */
    public function SendPushMessage($accessToken, $deviceTokenList = array(), $payload = array(), $expireTime = '')
    {
        $urlParams = array();
        $nsp_ctx = array();
        $nsp_ctx['ver'] = '1';
        $nsp_ctx['appId'] = $this->appId;
        $urlParams['nsp_ctx'] = json_encode($nsp_ctx);
        $apiUrl = self::RESTAPI_PUSHSEND . '?' . http_build_query($urlParams);
        
        $params = array();
        $params['access_token'] = $accessToken;
        $params['nsp_svc'] = 'openpush.message.api.send';
        $params['nsp_ts'] = time();
        $params['device_token_list'] = json_encode($deviceTokenList);
        $params['payload'] = json_encode($payload);
        if (! empty($expireTime)) {
            $params['expire_time'] = $expireTime;
        }
        
        return $this->callRestful($apiUrl, $params);
    }
}

class RequestBase
{

    // get请求方式
    const METHOD_GET = 'get';

    // post请求方式
    const METHOD_POST = 'post';

    /**
     * 发起一个get或post请求
     * 
     * @param $url 请求的url            
     * @param array $params
     *            请求参数
     * @param int $method
     *            请求方式
     * @param array $extra_conf
     *            curl配置, 高级需求可以用, 如
     *            $extra_conf = array(
     *            CURLOPT_HEADER => true,
     *            CURLOPT_RETURNTRANSFER = false
     *            )
     * @return bool|mixed 成功返回数据，失败返回false
     * @throws Exception
     */
    public static function exec($url, $params = array(), $method = self::METHOD_GET, $extra_conf = array())
    {
        $params = is_array($params) ? http_build_query($params) : $params;
        // 如果是get请求，直接将参数附在url后面
        if ($method == self::METHOD_GET) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . $params;
        }
        
        // 默认配置
        $curl_conf = array(
            CURLOPT_URL => $url, // 请求url
            CURLOPT_HEADER => false, // 不输出头信息
            CURLOPT_RETURNTRANSFER => true, // 不输出返回数据
            CURLOPT_CONNECTTIMEOUT => 3 // 连接超时时间
        );
        
        // 配置post请求额外需要的配置项
        if ($method == self::METHOD_POST) {
            // 使用post方式
            $curl_conf[CURLOPT_POST] = true;
            // post参数
            $curl_conf[CURLOPT_POSTFIELDS] = $params;
        }
        
        // 添加额外的配置
        foreach ($extra_conf as $k => $v) {
            $curl_conf[$k] = $v;
        }
        
        $data = false;
        try {
            // 初始化一个curl句柄
            $curl_handle = curl_init();
            // 设置curl的配置项
            curl_setopt_array($curl_handle, $curl_conf);
            // 发起请求
            $data = curl_exec($curl_handle);
            if ($data === false) {
                throw new Exception('CURL ERROR: ' . curl_error($curl_handle));
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        curl_close($curl_handle);
        
        return $data;
    }
}