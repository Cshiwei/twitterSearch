<?php
/**
 * Created by PhpStorm.
 * User: Cshiwei
 * Date: 2016/8/30
 * Time: 9:22
 */
class TwitterOauth {

    /**curl设置数组
     * @var array
     */
    private $opt_array=array();

    /**密钥参数
     * @var array
     */
    private $composite_param=array(
        'oauth_consumer_secret' => '',
        'oauth_token_secret'    => '',
    );

    /**基本的url地址
     * @var string
     */
    private $url = '';

    /**
     * @var string
     */
    private $getfield;

    /**
     * @var string
     */
    private $postfields;

    /**
     * @var mixed
     */
    protected $oauth;

    /**get或者post方法
     * @var string
     */
    public $requestMethod;

    public function __construct()
    {
        if (!in_array('curl', get_loaded_extensions()))
        {
            throw new Exception('You need to install cURL, see: http://curl.haxx.se/docs/install.html');
        }
    }

    /**
     * Set postfields array, example: array('screen_name' => 'J7mbo')
     *
     * @param array $array Array of parameters to send to API
     *
     * @throws \Exception When you are trying to set both get and post fields
     *
     * @return TwitterAPIExchange Instance of self for method chaining
     */
    public function setPostfields(array $array)
    {
        if (!is_null($this->getGetfield()))
        {
            throw new Exception('You can only choose get OR post fields.');
        }

        if (isset($array['status']) && substr($array['status'], 0, 1) === '@')
        {
            $array['status'] = sprintf("\0%s", $array['status']);
        }

        foreach ($array as $key => &$value)
        {
            if (is_bool($value))
            {
                $value = ($value === true) ? 'true' : 'false';
            }
        }

        $this->postfields = $array;

        // rebuild oAuth
        if (isset($this->oauth['oauth_signature'])) {
            $this->buildOauth($this->url, $this->requestMethod);
        }

        return $this;
    }

    /**
     * Set getfield string, example: '?screen_name=J7mbo'
     *
     * @param $query  array or string
     * @return TwitterAPIExchange
     * @throws Exception
     * @internal param string $string Get key and value pairs as string
     *
     */
    public function setGetfield($query)
    {
        if (!is_null($this->getPostfields()))
        {
            throw new Exception('You can only choose get OR post fields.');
        }

        $params = array();
        if(is_array($query))
        {
            $params = $query;
        }
        else
        {
            $getfields = preg_replace('/^\?/', '', explode('&', $query));

            foreach ($getfields as $field)
            {
                if ($field !== '')
                {
                    list($key, $value) = explode('=', $field);
                    $params[$key] = $value;
                }
            }
        }
        $this->getfield = '?' . http_build_query($params);
        return $this;
    }

    /**添加curl设置
     * @param $optArray
     * @return $this
     */
    public function setOptArray($optArray)
    {
        $this->opt_array = $optArray;
        return $this;
    }


    /**设置密钥参数
     * @param $consumer_secret
     * @param $oauth_access_token
     * @return $this
     */
    public function setComposite($consumer_secret,$oauth_access_token)
    {
        $this->composite_param['oauth_consumer_secret'] = $consumer_secret;
        $this->composite_param['oauth_token_secret'] = $oauth_access_token;

        return $this;
    }

    /**
     * Get getfield string (simple getter)
     *
     * @return string $this->getfields
     */
    public function getGetfield()
    {
        return $this->getfield;
    }

    /**
     * Get postfields array (simple getter)
     *
     * @return array $this->postfields
     */
    public function getPostfields()
    {
        return $this->postfields;
    }

    /**
     * Build the Oauth object using params set in construct and additionals
     * passed to this method. For v1.1, see: https://dev.twitter.com/docs/api/1.1
     *
     * @param string $url The API url to use. Example: https://api.twitter.com/1.1/search/tweets.json
     * @param string $requestMethod Either POST or GET
     *
     * @param $oauth
     * @return TwitterAPIExchange
     * @throws Exception
     * @internal param $param
     */
    public function buildOauth($url, $requestMethod,$oauth=array())
    {
        if (!in_array(strtolower($requestMethod), array('post', 'get')))
        {
            throw new Exception('Request method must be either POST or GET');
        }

        @$consumer_secret = $this->composite_param['oauth_consumer_secret'];
        @$oauth_access_token_secret = $this->composite_param['oauth_token_secret'];
        $getfield = $this->getGetfield();

        if (!is_null($getfield))
        {
            $getfields = str_replace('?', '', explode('&', $getfield));

            foreach ($getfields as $g)
            {
                $split = explode('=', $g);

                /** In case a null is passed through **/
                if (isset($split[1]))
                {
                    $oauth[$split[0]] = urldecode($split[1]);
                }
            }
        }

        $postfields = $this->getPostfields();

        if (!is_null($postfields)) {
            foreach ($postfields as $key => $value) {
                $oauth[$key] = $value;
            }
        }

        $requestMethod = strtoupper($requestMethod);
        $base_info = $this->buildBaseString($url, $requestMethod, $oauth);

        $composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
        $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
        $oauth['oauth_signature'] = $oauth_signature;

        $this->url = $url;
        $this->requestMethod = $requestMethod;
        $this->oauth = $oauth;
    }

    /**
     * Perform the actual data retrieval from the API
     *
     * @param boolean $return      If true, returns data. This is left in for backward compatibility reasons
     * @param array   $curlOptions Additional Curl options for this request
     *
     * @throws \Exception
     *
     * @return string json If $return param is true, returns json data.
     */
    public function performRequest($return = true, $curlOptions = array())
    {
        $curlOptions = ! empty($curlOptions) ? $curlOptions : $this->opt_array;

        if (!is_bool($return))
        {
            throw new Exception('performRequest parameter must be true or false');
        }

        $header =  array($this->buildAuthorizationHeader($this->oauth), 'Expect:');

        $getfield = $this->getGetfield();
        $postfields = $this->getPostfields();

        $options = array(
            CURLOPT_HTTPHEADER      => $header,
            CURLOPT_HEADER          => false,
            CURLOPT_URL             => $this->url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_PROXY           => '192.168.1.25:1081',
            CURLOPT_TIMEOUT         => 100,
           // CURLOPT_COOKIE          =>  'guest_id=v1%3A147264010134710156; _twitter_sess=BAh7CSIKZmxhc2hJQzonQWN0aW9uQ29udHJvbGxlcjo6Rmxhc2g6OkZsYXNo%250ASGFzaHsABjoKQHVzZWR7ADoPY3JlYXRlZF9hdGwrCOWrMOBWAToMY3NyZl9p%250AZCIlYmU3OGNhMTUwNzFhMGZjYWMyMGExODBhY2JmZjhmYWE6B2lkIiU4ZjBj%250AZjQxM2FiMGIyZDIxNDMwZjgxYTMzMTU2YWM2OA%253D%253D--62a8b934d28e40578b0e53c246cb24f63a504b52;',
        );

        if (!is_null($postfields))
        {
            $options[CURLOPT_POSTFIELDS] = http_build_query($postfields);
        }
        else
        {
            if ($getfield !== '')
            {
                $options[CURLOPT_URL] .= $getfield;
            }
        }

        $feed = curl_init();
        curl_setopt_array($feed, $options);

        if( ! empty($curlOptions))                             //如果存在自定义设置，则再次设置参数，覆盖原来的参数
            curl_setopt_array($feed, $curlOptions);

        $json = curl_exec($feed);

        if (($error = curl_error($feed)) !== '')
        {
            curl_close($feed);

            throw new \Exception($error);
        }

        curl_close($feed);
        $this->reset();
        return $json;
    }

    /**
     * Private method to generate the base string used by cURL
     *
     * @param string $baseURI
     * @param string $method
     * @param array  $params
     *
     * @return string Built base string
     */
    private function buildBaseString($baseURI, $method, $params)
    {
        $return = array();
        ksort($params);

        foreach($params as $key => $value)
        {
            $return[] = rawurlencode($key) . '=' . rawurlencode($value);
        }

        return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $return));
    }

    /**
     * Private method to generate authorization header used by cURL
     *
     * @param array $oauth Array of oauth data generated by buildOauth()
     *
     * @return string $return Header used by cURL for request
     */
    private function buildAuthorizationHeader(array $oauth)
    {
        $return = 'Authorization: OAuth ';
        $values = array();

        foreach($oauth as $key => $value)
        {
            if (in_array($key, array('oauth_consumer_key', 'oauth_nonce', 'oauth_signature',
                'oauth_signature_method', 'oauth_timestamp', 'oauth_token', 'oauth_version'))) {
                $values[] = "$key=\"" . rawurlencode($value) . "\"";
            }
        }

        $return .= implode(', ', $values);
        return $return;
    }

    /**重置变量
     *
     */
    private function reset()
    {
        $this->opt_array = array();
        $this->composite_param = array();
        $this->url = '';
        $this->getfield = null;
        $this->postfields = null;
        $this->oauth = '';
        $this->requestMethod = '';
    }

}