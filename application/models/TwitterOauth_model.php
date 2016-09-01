<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/29
 * Time: 19:33
 */

class TwitterOauth_Model extends CI_Model{

    /**twitterOauth对象实例
     * @var
     */
    private $twitterOauth;

    /**推特注册应用信息
     * @var string
     */
    private $appMsg ='';

    /**保存cookie的文件
     * @var string
     */
    private $cookieFile ='';

    /**未授权的token 信息
     * @var array
     */
    private $unOauthToken;

    /**需要请求授权的用户名
     * @var
     */
    private $userName;

    /**用户密码
     * @var
     */
    private $pwd;

    /**授权时用到的隐藏域信息
     * @var
     */
    private $authenticityToken;

    /**隐藏域信息
     * @var
     */
    private $redirectAfterLogin='https://api.twitter.com/oauth/authorize?oauth_token=';

    /**隐藏域信息
     * @var
     */
    private $oauthToken;

    /**授权码
     * @var
     */
    private $code;

    /**最终的 accessToken
     * @var
     */
    private $accessToken = array();

    public function __construct()
    {
        parent::__construct();
        $this->appMsg = $this->config->item('twitter_app');
        $this->cookie_file();
        $this->load->library('TwitterOauth');
        $this->twitterOauth = & $this->twitteroauth;
        $this->load->library('ParserDom');
    }

    /**
     * @param mixed $userName
     * @return $this
     */
    public function setUser($userName,$pwd)
    {
        $this->userName = $userName;
        $this->pwd = $pwd;

        return $this;
    }

    public function exe()
    {
        if(empty($this->userName) || empty($this->pwd))
            return false;

        $this->getUnOauthToken();
        $this->authorize();
        $this->authcate();
        $this->access_token();
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**设置保存cookie的文件路径
     *
     */
    private function cookie_file()
    {
        $this->cookieFile = APPPATH.'temp/cookie.txt';
    }


    /**获取未授权的access_token
     *
     */
    private function getUnOauthToken()
    {
        $url = 'https://api.twitter.com/oauth/request_token';
        $requestMethod = 'POST';
        $consumer_secret = $this->appMsg['consumer_secret'];
        $oauth_access_token = '';

        $data = array(
            'oauth_callback'        =>  'oob',
        );

        $params = array(
            'oauth_consumer_key'    =>  $this->appMsg['consumer_key'],
            'oauth_signature_method'=> 'HMAC-SHA1',
            'oauth_timestamp'       =>  time(),
            'oauth_nonce'           =>  time(),
            'oauth_version'         =>  '1.0',
        );

        $this->twitterOauth ->setPostFields($data)
                            ->setComposite($consumer_secret,$oauth_access_token)
                            ->buildOauth($url,$requestMethod,$params);

        $res = $this->twitterOauth->performRequest(true);
        $res = explode('&',$res);
        $temp = array();
        foreach($res as $key => $val)
        {
            $val = explode('=',$val);
            $temp[$val[0]] = $val[1];
        }
        $this->unOauthToken = $temp;
    }

    /**对未授权的access_token进行确认
     *
     */
    private function authorize()
    {
        if(empty($this->unOauthToken))
            return false;

        $url = 'https://api.twitter.com/oauth/authorize';
        $requestMethod = 'GET';
        $oauth_access_token = $this->unOauthToken['oauth_token'];

        $optArray = array(
            CURLOPT_COOKIEJAR  => $this->cookieFile,
        );

        $data = array(
            'oauth_token'   => $oauth_access_token,
            'force_login'   => 'true',
        );

        $this->twitterOauth ->setGetField($data)
                            ->buildOauth($url,$requestMethod);

        $res = $this->twitterOauth->performRequest(true,$optArray);
        $this->getAuthMsg($res);
    }

    /**解析html内容获取授权时的表单信息
     * @param $html
     */
    private function getAuthMsg($html)
    {
        $this->parserdom->load($html);
        $res = $this->parserdom->find('input[name=authenticity_token]');
        foreach($res as $key=>$val)
        {
            $authenticity_token = $val->getAttr('value');
            if(! empty($authenticity_token))
                break;
        }
        $this->authenticityToken = $authenticity_token;
        $this->redirectAfterLogin .= $this->unOauthToken['oauth_token'];
        $this->oauthToken = $this->unOauthToken['oauth_token'];
    }

    /**模拟用户登陆授权
     *
     */
    private function authcate()
    {
        if(empty($this->authenticityToken))
            return false;

        $url = 'https://api.twitter.com/oauth/authorize';
        $requestMethod = 'POST';
       // $oauth_access_token = $this->unOauthToken['oauth_token'];
        $data = array(
            'authenticity_token'    => $this->authenticityToken,
            'redirect_after_login'  =>  $this->redirectAfterLogin,
            'oauth_token'           =>  $this->oauthToken,
            'session[username_or_email]' => $this->userName,
            'session[password]'     =>  $this->pwd,
        );

        $optArray =array(
            CURLOPT_COOKIEFILE => $this->cookieFile,
        );

        $this->twitterOauth ->setPostFields($data)
                            ->buildOauth($url,$requestMethod);

        $res = $this->twitterOauth->performRequest(true,$optArray);

        $this->getCode($res);
    }

    /**获取code 授权码
     *
     */
    private function getCode($html)
    {
        $this->parserdom->load($html);
        $res = $this->parserdom->find('code');
        foreach($res as $key=>$val)
        {
            $code = $val->getPlainText();
            if(! empty($code))
                break;
        }
        $this->code = $code;
    }

    /**根据pin码换取有效的access_token
     *
     */
    private function access_token()
    {
        if(empty($this->code))
            return false;

        $url = 'https://api.twitter.com/oauth/access_token';
        $requestMethod = 'POST';
        $oauth_access_token =$this->oauthToken;
        $data = array(
            'oauth_verifier' => $this->code,
        );

        $params = array(
            'oauth_consumer_key'    =>  $this->appMsg['consumer_key'],
            'oauth_token'           =>  $oauth_access_token,
            'oauth_signature_method'=> 'HMAC-SHA1',
            'oauth_timestamp'       =>  time(),
            'oauth_nonce'           =>  time(),
            'oauth_version'         =>  '1.0',
        );

        $this->twitterOauth ->setPostFields($data)
                            ->buildOauth($url,$requestMethod,$params);

        $res = $this->twitterOauth->performRequest();

        $res = explode('&',$res);
        $temp = array();
        foreach($res as $key => $val)
        {
            $val = explode('=',$val);
            $temp[$val[0]] = $val[1];
        }
        $this->accessToken = $temp;
    }

}