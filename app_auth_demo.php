<?php
define('APP_ID','xxxxxxx');
define('SECRET','xxxxxxx');
//认证入口函数，当前入口的url与developer portal的App Website保持一致
function auth()
{
    if (!isset($_GET['code'])) {
        //接收speadmin iframe app之后的认证参数
        $customer_id = $_GET['customer_id'];
        $xspe = $_GET['xspe'];
        // 获取 code地址
        $urlObj["app_id"] = APP_ID;
        $urlObj["xspe"] = $xspe;
        $urlObj["customer_id"] = $customer_id;
        //任意值，用于app和oauth服务的校验
        $urlObj["state"] = "xxx";
        $urlObj["scope"] = "install_login";
        $urlObj["response_type"] = "code";
        //填写一个当前域名下的url，用于oauth认证成功后接收code参数
        $urlObj["redirect_uri"] = "xxxxx";
        $url = 'https://api.nexusguard.com/oauth/connect?'.urlParams($urlObj);
        $_SESSION['customer_id'] = $customer_id;
        $_SESSION['state'] = $urlObj["state"];
        // 跳转到oauth认证授权页面
        Header("Location: $url");
    } else {
        //通过code获得access_token
        $code = $_GET['code'];
        $state = $_GET['state'];
        //验证state，防窜改
        if($state != $_SESSION['state']){
            return false;
        }
        $urlObj["app_id"] = APP_ID;
        $urlObj["secret"] = SECRET;
        $urlObj["code"] = $code;
        $urlObj["grant_type"] = "authorization_code";
        $oauth_url = "https://api.nexusguard.com/oauth/credit/token";
        $oauthResponse = httpRequest('post',$oauth_url,$urlObj);
        $access_token = $oauthResponse['result']['access_token'];
        //通过access_token获取open api的资源
        $open_api_url = 'https://api.nexusguard.com/api/customer/'.$_SESSION['customer_id'].'?access_token='.$access_token;
        $resource = httpRequest('get',$open_api_url);
        return $resource;
    }
}

/**
 *
 * url参数字符串
 * @param array $urlObj
 * @return 返回已经拼接好的字符串
 */
function urlParams($urlObj)
{
    $buff = "";
    foreach ($urlObj as $k => $v)
    {
        if($k != "sign"){
            $buff .= $k . "=" . $v . "&";
        }
    }
    $buff = trim($buff, "&");
    return $buff;
}

/**
 * 发送http请求
 * @param $method
 * @param $url
 * @param string $data
 * @return mixed|string
 */
function httpRequest($method,$url,$data=''){
    $ci=curl_init($url);
    curl_setopt($ci, CURLOPT_TIMEOUT, 300);
    curl_setopt($ci, CURLOPT_HEADER, FALSE);
    curl_setopt($ci,CURLOPT_RETURNTRANSFER,true);
    if($method == 'post'){
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ci, CURLOPT_POSTFIELDS,$data);
    }
    $res = curl_exec($ci);//运行curl，结果以jason形式返回
    $data = json_decode($res,true);
    curl_close($ci);
    return $data;
}