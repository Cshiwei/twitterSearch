<?php

/**
 * Class twitter_model
 * @property  benchmark
 */
class Twitter_Model extends CI_Model{
	
	private $data;
	private $debug = DEBUG_DETAIL;              //日志记录级别
	private $base_url = 'https://api.twitter.com/1.1/search/tweets.json';  //api基础url
	//private $base_url ='https://api.twitter.com/oauth2/token' ;
	private $twitter;
	private $query_builder;
	private $max_list_num = 10;					//列表页每页获取条数

	private $condition;                         //检索关键词
	private $dictionary;						//数据字典
	private $p_path;							//日志路径
	private $since;								//抓取开始时间
	private $until;								//抓取结束时间
	private $access_token;						//用户授权的access_token

	private $total=0;							//本次抓取总条数
	
	public function __construct()
	{
		parent::__construct();

		$this->load->helper('callback');

		$this->load->library('TwitterQueryBuilder');
		$this->query_builder = & $this->twitterquerybuilder;
	}

	/**
	 * @param string $condition
	 * @param string $since
	 * @param string $until
	 */
	public function exe($condition='',$since='',$until='')
	{
		$this->benchmark->mark('code_start');

		$condition = $condition ? $condition : $this->condition;
		$condition = trim($condition);
		$since = $since ? $since : $this->since;
		$until = $until ? $until : $this->until;

		$this->debug_msg('',$this->p_path,"开始抓取关键词{$condition}!",DEBUG_SIMPLE);

		$this->get_search($condition,$since,$until);

		$this->benchmark->mark('code_end');
		$total_time = $this->benchmark->elapsed_time('code_start','code_end');
		$count = $this->total;
		$this->debug_msg('',$this->p_path,"抓取关键词{$condition}完毕!用时{$total_time}秒,共抓取推文{$count}条",DEBUG_SIMPLE);
	}

	/**
	 * @param string $condition
	 * @param string $since
	 * @param string $until
	 * @return bool
	 */
	public function get_search($condition='',$since='',$until='')
	{
		if(! $condition)
		{
			$this->debug_msg('',$this->p_path,"未指定关键词进程终止！",DEBUG_SIMPLE);
			return true;
		}
		$res = $this->get_index($condition,$since,$until);

    	if($res)
    	{
			$data = $res['statuses'];
			$count = count($data);
			$this->total += $count;
			$this->debug_msg('',$this->p_path,"抓取到首页数据{$count}条;",DEBUG_SIMPLE);
			$data = array_map('twitter_intersect',$data);
			$this->debug_msg('',$this->p_path,"\n".var_export($data,true),DEBUG_DETAIL);
			$meta = $res['search_metadata'];
			$i = 1;
			while (array_key_exists('next_results', $meta))
			{
				$i++;
				$this->debug_msg('',$this->p_path,"开始抓取第{$i}页数据->".$meta['next_results'],DEBUG_SIMPLE);
				$res = $this->get_next($meta['next_results']);

				$meta = array();
				if($res)
				{
					$meta = $res['search_metadata'];
					$data = $res['statuses'];
					$count = count($data);
					$this->total += $count;
					$this->debug_msg('',$this->p_path,"抓取到第{$i}页数据{$count}条:",DEBUG_SIMPLE);
					$data = array_map('twitter_intersect',$data);
					$this->debug_msg('',$this->p_path,"\n".var_export($data,true),DEBUG_DETAIL);
				}
			}
    	} 
    	 
	}

	/**获取首页数据
	 * @param $condition
	 * @param $since
	 * @param $until
	 * @return bool|mixed
	 */
	private function get_index($condition,$since,$until)
	{
		$res = $this->do_index($condition,$since,$until);

		$i = 1;
		$max = 3;
		while( ! $res && $i<=$max)
		{
			$this->debug_msg('',$this->p_path,"开始第{$i}次重试",DEBUG_SIMPLE);
			$res = $this->do_index($condition,$since,$until);
			$i++;
		}

		return $res;
	}

	/**
	 * @param $condition
	 * @param $since
	 * @param $until
	 * @return bool|mixed
	 */
	private function do_index($condition,$since,$until)
	{
		$this->query_builder->setExactWord($condition)
							->setSince($since)
							->setUntil($until)
							->setNeedQ(false);

		$condition = $this->query_builder->getQuery();
		$count = $this->max_list_num;
		$fields = array(
				'q' 				=> $condition,
				'count'				=> $count,
				'include_entities'	=> 'false',
				//'result_type'		=> 'recent'
		);

		$requestMethod	= 'GET';
		try
		{
			$result = $this->twitter->setGetfield($fields)
					->buildOauth($this->base_url, $requestMethod)
					->performRequest();
		}
		catch(Exception $e)
		{
			$this->debug_msg('',$this->p_path,"twitterAPi抛出异常{$e->getCode()}:{$e->getMessage()}",DEBUG_SIMPLE);
			return false;
		}

		$res=json_decode($result,true);

		if($res)
		{
			if(array_key_exists('errors', $res))
			{
				$error = $res['errors'];
				$this->debug_msg('',$this->p_path,"接口返回错误,终止抓取:".var_export($error,true),DEBUG_SIMPLE);
				return false;
			}
			return $res;
		}
	}


	/**
	 * @param $query
	 * @return mixed
	 */
	private function get_next($query)
	{
		$res = $this->do_next($query);

		$i = 1;
		$max = 3;
		while(! $res && $i <= $max)
		{
			$this->debug_msg('',$this->p_path,"开始第{$i}次重试",DEBUG_SIMPLE);
			$res = $this->do_next($query);
			$i ++;
		}

		return $res;
	}
	/**
	 * @param $query
	 * @return mixed
	 * @internal param $auery
	 * @internal param 下一页的连接地址 $url
	 */
	private function do_next ($query)
	{
		try
		{
			$res = $this->twitter->setNext($query)
					->buildOauth($this->base_url, 'GET')
					->performRequest();
		}
		catch(Exception $e)
		{
			$this->debug_msg('',$this->p_path,"twitterAPi抛出异常{$e->getCode()}:{$e->getMessage()}",DEBUG_SIMPLE);
			return false;
		}

    	$res = json_decode($res,true);

		if($res && array_key_exists('errors',$res))
		{
			$error = $res['errors'];
			$this->debug_msg('',$this->p_path,"接口返回错误:\n".var_export($error,true),DEBUG_SIMPLE);
			return false;
		}

		return $res;
	}

	/**
	 * 将数组拼接为url字符串
	 * @param array $arr
	 * @return string
	 */
	private function create_query(array $arr=array())
	{
		$query = '?'.http_build_query($arr);
		return $query;
	}

	/**
	 * @param $file 文件名称
	 * @param $path	日志目录
	 * @param $msg	日志信息
	 * @param int $grade	日志级别
	 * @param bool|false $cover	 是否采用覆盖模式
	 */
	private function debug_msg($file,$path,$msg,$grade=DEBUG_SIMPLE,$cover=false)
	{
		if($grade <= $this->debug)
		{
			log_msg($file,$path,$msg,$cover);
		}

	}

	/**初始化twitterSDK
	 * @param $app
	 * @return $this
	 */
	public function init($app)
	{
		$this->load->library('TwitterAPIExchange',$app);
		$this->twitter = & $this->twitterapiexchange;
		$optArray = array(                  //当前设置会覆盖原api里的设置
				CURLOPT_PROXY	=>	'192.168.1.25:1081',
				CURLOPT_SSL_VERIFYPEER	=>	false,
				CURLOPT_TIMEOUT => 100,
		);
		$this->twitter->setOptArray($optArray);
		return $this;
	}

	//设置数据字典
	public function set_dictionary($dictionary)
	{
		$this->dictionary = $dictionary;
		return $this;
	}
	//设置检索条件
	public function set_condition($condition)
	{
		$this->condition = $condition;
		return $this;
	}

	/**
	 * @param mixed $p_path
	 * @return $this
	 */
	public function set_p_path($p_path)
	{
		$this->p_path = $p_path;
		return $this;
	}

	/**
	 * @param mixed $since
	 * @return $this
	 */
	public function set_since($since)
	{
		$this->since = $since;
		return $this;
	}

	/**
	 * @param mixed $until
	 * @return $this
	 */
	public function set_until($until)
	{
		$this->until = $until;
		return $this;
	}

}