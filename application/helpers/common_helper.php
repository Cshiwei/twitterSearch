<?php
/*@author csw   2016年4月27日
*@param 去除指定字符串中的所有空白占位符包括回车，制表符，空格
*@param l 定界符，默认为# ,
*@param	decorate 修饰符 默认为 is，
*@param subject 需要修改的字符串
 *   */
if (! function_exists ( 'format_str' )) 
{
	function format_str($l='#',$decorate='is',$subject ) 
	{
		return $subject;
		//return str_replace(array('\r\n','\n','\t'), '', $subject);
		//return preg_replace($l.'[\f\n\r\t\v]'.$l.$decorate,'', $subject);
	}
}

if (! function_exists ( 'admin_log' )) {
	function admin_log($name, $msg, $user_name='',$to_user=0) {
		$CI = & get_instance ();
		//`id`, `name`, `msg`, `username`, `ip`, `created`,`to_user`
		$username = !empty($user_name) ? $user_name : $CI->session->userdata ( 'username' );
		$CI->db->set ( 'name', $name );
		$CI->db->set ( 'msg', $msg );
		$CI->db->set ( 'username', $username);
		$CI->db->set ( 'ip', $CI->input->ip_address () );
		$CI->db->set ( 'created', date ( "Y-m-d H:i:s" ) );
		$CI->db->set ( 'to_user', intval($to_user) );
		$CI->db->insert ( 'adminlogs' );
	}
}

if (! function_exists ( 'login_log' )) {
	function login_log($user_id=0, $type) {
		$CI = & get_instance ();
		$time = date('Y-m-d',time());
		$query = $CI->db->query("select recent_time from loginlogs where user_id='{$user_id}' and recent_time='{$time}' limit 1");
		if($query->num_rows() && $row = $query->row_array()){
			switch ($type){
					case 'ukey':
						$sql = "UPDATE loginlogs set ukey=ukey+1,count=count+1 where user_id='{$user_id}' AND recent_time='{$time}'";
					break;  
					case 'client_login':
						$sql = "UPDATE loginlogs set client_login=client_login+1,count=count+1 where user_id='{$user_id}' AND recent_time='{$time}'";
					break;
					case 'service_login':
						$sql = "UPDATE loginlogs set service_login=service_login+1,count=count+1 where user_id='{$user_id}' AND recent_time='{$time}'";
					break;
					default:
						$sql = "UPDATE loginlogs set ukey=ukey+1,count=count+1 where user_id='{$user_id}' AND recent_time='{$time}'";
					break;
			}
		}else{
			switch ($type){
				case 'ukey':
					$sql = "INSERT INTO loginlogs(user_id, ukey,recent_time,count) VALUES('{$user_id}', 1, '{$time}',1)";
				break;  
				case 'client_login':
					$sql = "INSERT INTO loginlogs(user_id, client_login,recent_time,count) VALUES('{$user_id}', 1, '{$time}',1)";
				break;
				case 'service_login':
					$sql = "INSERT INTO loginlogs(user_id, service_login,recent_time,count) VALUES('{$user_id}', 1, '{$time}',1)";
				break;
				default:
					$sql = "INSERT INTO loginlogs(user_id, ukey,recent_time,count) VALUES('{$user_id}', 1, '{$time}',1)";
				break;
			}
		}
		$CI->db->query($sql);
	}
}


if (! function_exists ( 'sys_log' )) 
{
	function sys_log($name, $msg, $site_id = 0, $level = 0, $creator = '') 
	{
		if ($level == 0) 
		{
			return;
		}
		$sqlbody = "('". addslashes($name) ."', '". addslashes($msg) ."', $site_id, $level, '{$creator}', '".date ( "Y-m-d H:i:s" )."')";
		$logsqlfile = ROOTPATH."logs/syslog.sql";

		if (!file_exists($logsqlfile)) 
		{
			$sqlheader = "INSERT INTO `syslogs` (`name`, `msg`, `site_id`, `level`, `creator`, `created`) VALUES \r\n";
			@file_put_contents($logsqlfile, $sqlheader.$sqlbody.",\r\n");
			return ;
		}
		file_put_contents($logsqlfile, $sqlbody.",\r\n", FILE_APPEND);
	}
}

function go($uri='', $notice = '') {
	if (! empty ( $notice )) {
		$CI = & get_instance ();
		$CI->session->set_userdata ( 'notice', $notice );
	}
	redirect ( $uri );

}

function save_cache_by_txtid($txtid, $content, $ext = 'html') {
	$url = CACHE_SAVE_API;

	$data = array(
		'ext'	=> $ext,
		'txtid'	=> $txtid,
		'content'	=> $content,
		'key'=> substr(md5(md5('unotice save cache file')), 10, 16)
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, 1 );
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0");
	curl_setopt($ch, CURLOPT_HEADER, 1);
	$result = curl_exec($ch);
	$error = curl_error($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);

	return $error ? $error : $result;
}

function path_by_txtid($txtid, $create_dir = false, $ext = 'html', $base_dir = CACHE_PAGE_PATH2) {

	if (strlen($txtid) == 32 ) {
		if ($create_dir) {
			$path = $base_dir.substr($txtid, 0, 4);
			if(! is_dir($path)) {
				mkdir($path);
				chmod($path, 0777);
			}

			$path .= "/".substr($txtid, 4, 2);
			if(! is_dir($path)) {
				mkdir($path);
				chmod($path, 0777);
			}

			$path .= "/".substr($txtid, 6, 2);
			if(! is_dir($path)) {
				mkdir($path);
				chmod($path, 0777);
			}
		}
		else
		{
			$path = $base_dir.substr($txtid, 0, 4)."/".substr($txtid, 4, 2)."/".substr($txtid, 6, 2);
		}

		return $path.'/'.$txtid.'.'.$ext;
	}
	else
	{
		$txtid = trim($txtid);
		list($status, $id) = explode("_", $txtid);
		return path_by_id($id, $status, $create_dir, $ext);
	}
}

function path_by_txtid2($txtid, $create_dir = false, $ext = 'html', $base_dir = HTMLPAGE_PATH) 
{
	if ($create_dir) 
	{
		$path = $base_dir.substr($txtid, 0, 4);
		if(! is_dir($path)) 
		{
			mkdir($path);
			chmod($path, 0777);
		}

		$path .= "/".substr($txtid, 4, 2);
		if(! is_dir($path)) 
		{
			mkdir($path);
			chmod($path, 0777);
		}

		$path .= "/".substr($txtid, 6, 2);
		if(! is_dir($path)) 
		{
			mkdir($path);
			chmod($path, 0777);
		}

		$path .= "/".substr($txtid, 8, 2);
		if(! is_dir($path)) 
		{
			mkdir($path);
			chmod($path, 0777);
		}
	}
	else
	{
		$path = $base_dir.substr($txtid, 0, 4)."/".substr($txtid, 4, 2)."/".substr($txtid, 6, 2)."/".substr($txtid, 8, 2);
	}
	return $path.'/'.$txtid.'.'.$ext;
}

function path_by_id($id, $status = 0, $create_dir = false, $ext = 'html') 
{
	if ($status == 0) 
	{
		$path = '';
	} 
	else 
	{
		$path = 'dir' . strval ( $status ) . '/';
	}
	if ($create_dir) {
		$path .= floor ( $id / 10000 );
		if (! is_dir ( CACHE_PAGE_PATH . $path )) {
			mkdir ( CACHE_PAGE_PATH . $path );
			@chmod ( CACHE_PAGE_PATH . $path, 0777 );
		}
		$path .= '/' . floor ( floor ( $id % 10000 ) / 100 );
		if (! is_dir ( CACHE_PAGE_PATH . $path )) {
			mkdir ( CACHE_PAGE_PATH . $path );
			@chmod ( CACHE_PAGE_PATH . $path, 0777 );
		}
		return CACHE_PAGE_PATH."{$path}/{$id}.{$ext}";
	}
	else
	{
		$path .= floor ( $id / 10000 ).'/' . floor ( floor ( $id % 10000 ) / 100 );
		return CACHE_PAGE_URL2."{$path}/{$id}.{$ext}";
	}

}

function cachepage_move($from_id, $from_status, $to_id, $to_status) {
	$path_1 = path_by_id ( $from_id, $from_status, false );
	$path_1_txt = path_by_id ( $from_id, $from_status, false, 'txt');
	if (false == $path_1) {
		return false;
	}
	$path_2 = path_by_id ( $to_id, $to_status, true );
	$path_2_txt = path_by_id ( $to_id, $to_status, true, 'txt');
	rename ( CACHE_PAGE_PATH . $path_1, CACHE_PAGE_PATH . $path_2 );

	rename ( CACHE_PAGE_PATH . $path_1_txt, CACHE_PAGE_PATH . $path_2_txt );
}

function msubstr($str, $start, $len, $dot='') {
	$tmpstr = "";
	$strlen = $start + $len;
	if (strlen($str) < $start + $len) {
		return $str;
	}
	for($i = 0; $i < $strlen; $i ++) {
		if (ord ( substr ( $str, $i, 1 ) ) > 0xa0) {
			$tmpstr .= substr ( $str, $i, 2 );
			$i ++;
		} else {
			$tmpstr .= substr ( $str, $i, 1 );
		}
	}
	return $tmpstr.$dot;
}

function convertrule_old($rule) {
	$rule = preg_quote ( $rule, "|" );
	$rule = str_replace ( "\[\*\]", ".*?", $rule );
	$rule = str_replace ( "\[d\]", "[\d]+", $rule );
	return $rule;
}

function convert_date($rule) {

	if( strpos($rule, '[date]') === FALSE) {
		return $rule;
	}
	//根据匹配规则获取匹配项，将匹配项作为参数传到回调函数里  convert_date_exec
	return preg_replace_callback("|\[date\](.*?)\[/date\]|ims", "convert_date_exec", $rule);
}

//回调函数，对匹配项进行处理
function convert_date_exec($matches) {
	$dateformat = trim($matches[1]);
	$times = NULL;
	if( strpos($matches[1], ',') !== false ) {
		$arr = explode(",", $matches[1]);
		if(count($arr) == 2) {
			$dateformat = trim($arr[0]);
			$times = strtotime(trim($arr[1]));
		}
	}
	if (empty($times)) {
		return date($dateformat);
	} else {
		return date($dateformat, $times);
	}
}

/*对规则进行修饰
 *   */
 function convertrule($rule) {
	if (empty($rule)) 
	{
		return FALSE;
	}
	$arr = array ();
	if (preg_match_all ( "|\[r=?(\w*)\](.*?)\[\/r\]|is", $rule, $out ))
	{
		foreach ( $out [0] as $k => $val ) 
		{
			if (empty ( $out [1] [$k] ))
			{
				$rule = str_replace ( $out [0] [$k], "---" . $k . "---", $rule );
				$arr ["---" . $k . "---"] = $out [2] [$k];
			} 
			else 
			{
				$rule = str_replace ( $out [0] [$k], "---" . $out [1] [$k] . "-" . $k . "---", $rule );
				$arr ["---" . $out [1] [$k] . "-" . $k . "---"] = $out [0] [$k];
			}
		}
	}

	$rule = preg_quote ( $rule, "|" );

	foreach ( $arr as $k => $val ) 
	{
		$rule = str_replace ( $k, $val, $rule );
		$k = str_replace('-', '\-', $k);
		$rule = str_replace ( $k, $val, $rule );
	}

	$rule = str_replace ( array ("\[\*\]", "\[d\]", "\[d\+\]", "\[w\]", "\[w\+\]", "\[s\]", "\[s\+\]" ), array (".*?", "\d*", "\d+", "\w*", "\w+", "\s*", "\s+" ), $rule );
	$rule = str_replace ( "\[\*\]", ".*?", $rule );
	$rule = str_replace ( "\[d\]", "\d+", $rule );
	$rule = str_replace ( "\[d\+\]", "\d+", $rule );
	$rule = str_replace ( "\[w\]", "\w+", $rule );
	$rule = str_replace ( "\[w\+\]", "\w+", $rule );
	//$rule = quot_replace($rule);
	return $rule;
} 
/* csw
 * 替换规则中的单双引号
 * 匹配时单引号双引号无差别匹配
 *  */
function quot_replace($rule)
{
	if ($rule=='')
		return false;
	
	return preg_replace('#[\'"]#is', '[\'"]', $rule);  //将匹配到的单引号或者双引号替换为匹配规则['"]
}
/*对规则进行修饰
 *   */
/* function convertrule($rule) {
	if (empty($rule)) {
		return FALSE;
	}
} */
function sdate($dateformat, $timestamp = '', $title = true) {
	if (empty ( $timestamp )) {
		return '从未';
	}
	$time = time () - $timestamp;
	if ($time > 12 * 3600) {
		$result = date ( $dateformat, $timestamp );
	} elseif ($time > 3600) {
		$result = intval ( $time / 3600 ) . '小时前';
	} elseif ($time > 60) {
		$result = intval ( $time / 60 ) . '分钟前';
	} elseif ($time > 0) {
		$result = $time . '秒前';
	} else {
		$result = '刚刚';
	}
	if ($title) {
		$result = "<span title='" . date ( $dateformat, $timestamp ) . "'>{$result}</span>";
	}
	return $result;
}
/*
 * 获取缓存文件
 *   */
function cache_get($cache_key, $expireTime = 0, $group = 'default') {
	if (empty ( $cache_key )) {
		return false;
	}
	$cache_key = md5 ( $cache_key );

	$CI = & get_instance ();
	$cache_path = $CI->config->config ['cache_path'];

	$filepath = $cache_path . 'file/' . $group . '/' . $cache_key;

	if (! file_exists ( $filepath )) {
		return false;
	}

	if (! empty ( $expireTime ) && filemtime ( $filepath ) < time () - $expireTime) {
		unlink ( $filepath );
		return false;
	}
	return unserialize ( file_get_contents ( $filepath ) );
}
/*
 * 缓存设置
 * 添加缓存
 *   */
function cache_set($cache_key, $content = '', $group = 'default') {
	if (empty ( $cache_key )  || empty ( $content )) {
		return false;
	}
	$cache_key = md5 ( $cache_key );

	$CI = & get_instance ();
	$cache_path = $CI->config->config ['cache_path'];

	if (! is_dir ( $cache_path . 'file' )) {
		mkdir ( $cache_path . 'file' );
		@chmod ( $cache_path . 'file', 0777 );
	}

	$path = $cache_path . 'file/' . $group;
	if (! is_dir ( $path )) {
		mkdir ( $path );
		@chmod ( $path, 0777 );
	}
	
	$filepath = $path . "/" . $cache_key;
	if (@file_put_contents ( $filepath, serialize ( $content ) )) {
		return false;
	}
	return true;
}
/*
 * 删除缓存
 *   */
function cache_del($cache_key, $group = 'default') {
	if (empty ( $cache_key ) || empty ( $content )) {
		return false;
	}
	$cache_key = md5 ( $cache_key );

	$CI = & get_instance ();
	$cache_path = $CI->config->config ['cache_path'];

	$filepath = $cache_path . 'file/' . $group . '/' . $cache_key;
	if (! file_exists ( $filepath )) {
		return false;
	}
	unlink ( $filepath );
	return true;
}
/*清空缓存
 *   */
function cache_clean($group = 'default') {
	$CI = & get_instance ();
	$cache_path = $CI->config->config ['cache_path'];

	$CI->load->helper ( 'file' );
	if (strtolower ( $group ) == 'all') {
		$filepath = $cache_path . 'file/';
		delete_files ( $filepath, true );
	} else {
		$filepath = $cache_path . 'file/' . $group;
		delete_files ( $filepath, true );
		@rmdir ( $filepath );
	}
	return true;
}
/* 
 * 转换编码
 *  */
function convert_encoding($str, $in_charset = 'UTF-8', $out_charset = 'GBK') {
	if (empty ( $str )) {
		return "";
	}
	if ($in_charset == $out_charset) {
		return $str;
	}
	return mb_convert_encoding ( $str, $out_charset, $in_charset );
}

function parse_rss($xml, $parser = 'magpierss') {
	$CI = & get_instance ();

	$rss = false;

	if ($parser == 'magpierss') {
		$CI->load->plugin ( 'magpierss' );
		$rss = parserss ( $xml, $err );
	}
	
	if (false != $rss) {
		$arr = array ();
		$arr ['channel'] = array_map ( 'convert_encoding', $rss->channel );
		foreach ( $rss->items as $item ) {

			$content = $item ["description"];
			if (strlen ( $item ["content"] ["encoded"] ) > strlen ( $content )) {
				$content = $item ["content"] ["encoded"];
			}
			if (strlen ( $item ["atom_content"] ) > strlen ( $content )) {
				$content = $item ["atom_content"];
			}
			if (strlen ( $item ["summary"] ) > strlen ( $content )) {
				$content = $item ["summary"];
			}
			if (strlen ( $item ["content:encoded"] ) > strlen ( $content )) {
				$content = $item ["content:encoded"];
			}
			$item ['description'] = $content;
			if (empty ( $item ['author'] ) && ! empty ( $item ['dc'] ['creator'] )) {
				$item ['author'] = $item ['dc'] ['creator'];
				unset ( $item ['dc'] );
			}
			$t = strtotime ( str_replace ( ' GMT+8', ' +0800', $item ['pubdate'] ) );
			if ($t > 0) {
				$item ['pubdate'] = date ( "Y-m-d H:i:s", $t );
			} else {
				$item ['pubdate'] = '';
			}
			$arr ['items'] [] = array_map ( 'convert_encoding', $item );
		}
		$arr ['encoding'] = $rss->encoding;
		$arr ['parser'] = 'magpierss';
	} else {
		$CI->load->plugin ( 'lastrss' );
		$lastrss = new lastRSS ( );
		$lastrss->cp = "GBK";
		$rss = $lastrss->Parse ( $xml );

		$arr = & $rss;
		foreach ( $arr ['items'] as $key => $item ) {
			
			foreach ($item as $k => $v) {
				if ($k !== strtolower($k) ) {
					$arr['items'][$key][strtolower($k)] = $item[strtolower($k)] = $v;
					unset($arr['items'][$key][$k]);
				}
			}
		
			$t = strtotime ( str_replace ( ' GMT+8', ' +0800', $item ['pubdate'] ) );
			if ($t > 0) {
				$arr ['items'] [$key] ['pubdate'] = date ( "Y-m-d H:i:s", $t );
			} else {
				$arr ['items'] [$key] ['pubdate'] = '';
			}
			if (empty($arr ['items'] [$key] ['description']) && !empty($arr ['items'] [$key] ['summary'])) {
				$arr ['items'] [$key] ['description'] = $arr ['items'] [$key] ['summary'];

			}
			$arr ['items'] [$key] ['description'] = htmlspecialchars_decode ( $item ['description'] );
		}
		$arr ['parser'] = 'lastRSS';
	}
	return $arr;
}

function get_remotepate($url, $retry = 0, & $http_info='') {
	$old_url = $url;
	$CI = & get_instance ();
	
	$CI->load->config('proxy');

	$url = trim ( $url );
	if (strtolower ( substr ( $url, 0, 7 ) ) != 'http://' || strlen($url) > 500 ) {
		return;
	}

	$ch = curl_init ();
	$proxy_domains = $CI->config->config['proxy']['domains'];
	foreach ($proxy_domains as $domain) {
		if (strpos($url,$domain) !== false) {
			$url = 'http://www.teamwiki.cn/proxy.php';
			$post_data = "url=".urlencode($old_url)."&p=unotice#";
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
			break;
		}
	}
	//add end

	curl_setopt ( $ch, CURLOPT_URL, $url );
	curl_setopt ( $ch, CURLOPT_ENCODING, "gzip, deflate" );
	curl_setopt ( $ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; CIBA; InfoPath.1; .NET CLR 2.0.50727)" );
	curl_setopt ( $ch, CURLOPT_MAXREDIRS, 5 );
	curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );


	$is_use_proxy = FALSE;
	$arr = parse_url($url);
	$host = $arr['host'];
	unset($arr);

	foreach ( (array)$CI->config->config['enmus']['use_proxy_host'] as $val ) {
		if ( preg_match("|{$val}|i", $host) ) {
			$is_use_proxy = true;
			break;
		}
	}

	if ($retry > 0 || $is_use_proxy == TRUE ) {
		$arr = (array)$CI->config->config['enums']['proxy'];
		$proxy = $arr[array_rand($arr)];
		if (!empty($proxy)) {
		
			curl_setopt($ch, CURLOPT_PROXY, "{$proxy['ip']}:{$proxy['port']}");
		}
		unset($arr);
	}

	curl_setopt ( $ch, CURLOPT_TIMEOUT, 15 );
	curl_setopt ( $ch, CURLOPT_HEADER, 0 );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );

	$contents = curl_exec ( $ch );

	$http_info = curl_getinfo($ch);
	if ( $http_info['http_code'] >= 400 ) {
		$contents = false;
	}

	curl_close ( $ch );

	if (empty($contents) && $retry == 0) {
		return get_remotepate($url, 1);
	}
	if (!empty($contents) && $retry < 5) {
		if(preg_match("'<meta[\s]*http-equiv[^>]*?content[\s]*=[\s]*[\"\']?\d+;[\s]*URL[\s]*=[\s]*([^\"\']*?)[\"\']?[/]*>'i",$contents,$match))
		{
			$redirct_url = trim($match[1]);
			if(!empty($redirct_url))
			{
				$redirct_url = expandlinks($redirct_url, $url);
				$retry ++ ;
				return get_remotepate($redirct_url, $retry);
			}
		}
	}
	return $contents;
}

function get_page($url,$cookie='') {
	if (empty($url) ) {
		return FALSE;
	}
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_URL, $url );

	$URI_PARTS = parse_url($url);
	if(strtolower($URI_PARTS["scheme"]) == 'https'){
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	}
	curl_setopt ( $ch, CURLOPT_ENCODING, "gzip, deflate" );
	curl_setopt ( $ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; CIBA; InfoPath.1; .NET CLR 2.0.50727)" );
	curl_setopt ( $ch, CURLOPT_MAXREDIRS, 5 );
	curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
	curl_setopt ( $ch, CURLOPT_TIMEOUT, 20 ); 
	curl_setopt ( $ch, CURLOPT_HEADER, 0 );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	if($cookie){
		curl_setopt($ch, CURLOPT_COOKIE, $cookie);
	}
	$contents = curl_exec ( $ch );
	curl_close ( $ch );

	return $contents;
}

/*获取重定向地址
 *   */
function get_redirect_url($url) {

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate");
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; CIBA; InfoPath.1; .NET CLR 2.0.50727)");
	curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); //自动跟踪location
	curl_setopt($ch, CURLOPT_TIMEOUT, 20); //Timeout
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_NOBODY, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$contents = curl_exec($ch);
	curl_close($ch);

	$urlnew = '';
	$i = 0;
	do
	{
		$contents = trim($contents);
		if (strtoupper(substr($contents, 0, 5)) == 'HTTP/' ) {
			$pos = strpos($contents, "\r\n\r\n");
			if ($pos === FALSE) {
				break;
			}
			$header = substr($contents, 0, $pos);
			preg_match("/Location: (.+)\s/i", $header, $out);
			$urlnew = trim($out[1]);
			$contents = substr($contents, $pos);
		} else {
			break;
		}
		$i ++;
	} while ( $i<5 );

	return empty($urlnew) ? $url : $urlnew;
}
/*获取编码方式
 *   */
function get_charset($html, $content_type='') 
{
	if (empty ( $html )) 
	{
		return false;
	}
	if (mb_check_encoding($html, 'utf-8'))
	{
		return 'UTF-8';
	}
	elseif (mb_check_encoding($html, 'gbk'))
	{
		return 'GBK';
	}
	preg_match_all ( '|<meta[^>]+>|is', $html, $out );

	foreach ( $out [0] as $val ) 
	{
		if (strpos ( strtolower ( $val ), 'content-type' ) !== false) 
		{
			preg_match ( "|charset=([a-z0-9\-]+)\b|i", $val, $code );
			$encode = strtoupper ( trim ( $code [1] ) );
			return $encode;
		}
		elseif (preg_match ( "|charset=\"([a-z0-9\-]+)\"|i", $val, $code )) 
		{
			$encode = strtoupper ( trim ( $code [1] ) );
			return $encode;
		}
	}

	if (!empty($content_type) ) 
	{
		if(preg_match("|charset=([a-z0-9\-]+)\b|i", $content_type, $code ) ) 
		{
			$encode = strtoupper ( trim ( $code [1] ) );
			return $encode;
		}
	}


	if (empty($encode) ) 
	{

		$arr = array('EUC-CN'=>'GBK', 'CP936'=>'GBK');
		$encode = mb_detect_encoding($str, "UTF-8,ASCII,EUC-CN,BIG5,CP936,EUC-JP,EUC-TW");
		if (!empty($arr[$encode]) ) 
		{
			$encode = $arr[$encode];
		}
		return $encode;
	}
	return FALSE;
}

function in_top_snatchlist($host) {
	$CI = &get_instance ();
	$CI->load->library('memcache');
	$hostlist = $CI->memcache->get ( 'host_list' );
	if (false == $hostlist) {
		$hostlist = array();
	}
	if ( in_array($host, $hostlist) ) {
		return TRUE;
	}
	array_unshift ( $hostlist, $host);
	if (count($hostlist) > 30) {
		array_pop ( $hostlist );
	}
	$CI->memcache->set('host_list', $hostlist);
	return FALSE;
}


function expandlinks($links, $baseURL)
{
	preg_match("/^[^\?]+/",$baseURL,$match);

	$match = preg_replace("|/[^\/\.]+\.[^\/\.]+$|","",$match[0]);
	$match = preg_replace("|/$|","",$match);
	$match_part = parse_url($match);
	$match_root =
	$match_part["scheme"]."://".$match_part["host"];

	$search = array( 	"|^http://".preg_quote($match_part['host'])."|i",
	"|^(\/)|i",
	"|^(?!http://)(?!mailto:)|i",
	"|/\./|",
	"|/[^\/]+/\.\./|"
	);

	$replace = array(	"",
	$match_root."/",
	$match."/",
	"/",
	"/"
	);

	$expandedLinks = preg_replace($search,$replace,$links);

	return $expandedLinks;
}

function composite_keywords(array $words)
{
	$str = "";
	$expr = "/\s+(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/";
	if ( trim($words["as_and"])!='' ) {
		$str .= preg_replace($expr, ' ', trim($words["as_and"]));
	}

	if ( trim($words["as_or"]) != '' ) {
		$str .= " ((" . preg_replace($expr,' | ',trim($words["as_or"])) . "))";
	}
	if ( trim($words["as_not"]) != '' ) {
		$str .= " -" . preg_replace($expr,' -',trim($words["as_not"]));
	}
	return trim($str);
}

function split_condition( $str )
{
	if (trim($str)=="") {
		return array();
	}
	$expr = "/\s+(?=(?:[^\"]*\"[^\"]*\")*(?![^\"]*\"))/";
	$words = array();
	$str_andor = $str;
	if ( strpos($str, "((") !== false ) { 
		$start = strpos($str, "((");
		$end = strpos($str, "))");
		$words["as_or"] = trim( str_replace(" | ", " ", substr($str, $start+2, $end-$start-2)) );
		$str_andor = substr( $str, 0, $start-1 ) . substr($str, $end+2);
	}

	$arr = preg_split($expr, $str_andor);
	foreach ($arr as $val) {
		$val = trim($val);
		if ( !empty($val) ) {
			if ( substr($val,0,1) == "-" ) {
				$words["as_not"] .= ( substr($val,1) . " " );
			}
			else
			{
				$words["as_and"] .= ( $val . " " );
			}
		}
	}
	if ( isset($words["as_not"]) ) {
		$words["as_not"] = trim($words["as_not"]);
	}
	if( isset($words["as_and"]) ){
		$words["as_and"] = trim($words["as_and"]);
	}
	return $words;
}

function split_condition2($word_arr)
{
	$words = array(
		'as_and' => '',
		'as_not' => '',
		'as_or' => '',
		'as_or2' => ''
		);

		if (empty($word_arr))
		{
			return $words;
		}

		if ($word_arr['and'])
		{
			foreach ($word_arr['and'] as $w => $n)
			{
				$words['as_and'] .= $w . ' ';
			}
			$words['as_and'] = trim($words['as_and']);
		}

		if ($word_arr['or'])
		{
			foreach ($word_arr['or'] as $w => $n)
			{
				$words['as_or'] .= $w . ' ';
			}
			$words['as_or'] = trim($words['as_or']);
		}
		
		if ($word_arr['or2'])
		{
			foreach ($word_arr['or2'] as $w => $n)
			{
				$words['as_or2'] .= $w . ' ';
			}
			$words['as_or2'] = trim($words['as_or2']);
		}

		if ($word_arr['not'])
		{
			foreach ($word_arr['not'] as $w => $n)
			{
				$words['as_not'] .= $w . ' ';
			}
			$words['as_not'] = trim($words['as_not']);
		}

		return $words;
}

/*抓取水平
 * 级别越高，每小时抓取的数量越高则，snatch_level越小，抓取频率越大
 *   */
function get_snatch_level($grade, $perh=-1) {
	
	if ($grade == 'AA') 
	{	
		if ($perh == -1) 
		{
			return 2;
		}
		if( $perh >= 5 )
		 {
			return 1;
		} 
		else 
		{
			return 2;
		}
	} 
	elseif ($grade == 'A+') 
	{	
		if ($perh == -1)
		 {
			return 2;
		}
		if( $perh >= 5 ) 
		{
			return 1;
		} 
		else 
		{
			return 2;
		}
	} 
	elseif ($grade == 'A') 
	{	
		if ($perh == -1) 
		{
			return 3;
		}
		if( $perh >= 4 ) 
		{
			return 1;
		} 
		elseif ( $perh >= 2 ) 
		{
			return 2;
		} 
		elseif ( $perh> 0 ) 
		{
			return 3;
		} 
		else 
		{
			return 4;
		}
	} 
	else 
	{
		if ($perh == -1)
		 {
			return 6;
		}
		if( $perh >= 3 )
		 {
			return 3;
		} 
		elseif( $perh >= 1.5 ) 
		{
			return 4;
		} 
		elseif ($perh >= 1.0 ) 
		{
			return 5;
		} 
		elseif ($perh > 0.8 ) 
		{
			return 6;
		}
		 elseif ($perh >= 0.5 ) 
		 {
			return 7;
		} 
		elseif ($perh > 0 )
		 {
			return 8;
		} 
		else 
		{
			return 9;
		}
	}
}

/* 
 * 根据字符串获取上传日期的时间戳
 * 原版
 *  */
function my_strtotime($str, $format='timestamp') {
	$arr = array("&nbsp;","\t","\r","\r\n","\n",'　');
	$str = str_replace($arr, '', $str);
	$str = trim( $str );
	if( empty($str) ) {
		return FALSE;
	}
	$time = 0;
	do
	{
		$time = strtotime($str);    //根据英文日期格式如果可以正确获取上传日期
		if ($time > 0 ) {
			break;
		}

		if (is_numeric($str) ) {
			$str = intval($str);
			if( $str > strtotime("2000-01-01") && $str < time()+3600*8 ) {
				$time = $str;
				break;
			}
		}

		if( ! preg_match("/^(((20)?[01]\d)|(19)?[98]\d)年(\d{1,2})月(\d{1,2})\D*((\d{1,2}):(\d{2})(:(\d{2}))?)?$/", $str, $out) ) 
		{
			if ( preg_match("/^(((20)?[01]\d)|(19)?[98]\d)\D(\d{2})\D(\d{2})\D*((\d{1,2}):(\d{2})(:(\d{2}))?(\s?pm)?)?$/i", $str, $out) ) 
			{

			}
		}
		if ( !empty($out) ) 
		{
			$w = trim($out[12]);
			if(!empty($w) && strtolower($w) == 'pm') 
			{
				$out[8] = $out[8] + 12;
			}
			$time = mktime($out[8], $out[9], $out[11], $out[5], $out[6], $out[1]);
			break;
		};
		if( preg_match("/^(\d{1,2})[\\\\\/\-\.](\d{1,2})\D?((\d{1,2}):(\d{2})(:(\d{2}))?(\s?pm)?)?$/i", $str, $out) )
		{
			if( intval($out[1]) >0 && intval($out[1]) <=12
			&& intval($out[2]) > 0 && intval($out[2]) <= 31
			) 
			{
				$year = intval($out[1]) <= date("m") ? date("Y") : date("Y", strtotime("-1 year"));
				$time = mktime(intval($out[4]), intval($out[5]), intval($out[7]), intval($out[1]), intval($out[2]), $year);
				break;
			}
		}

		if( preg_match("/^(\d{1,2}):(\d{2})(:(\d{2}))?(\s?pm)?$/i", $str, $out) )
		{
			if( intval($out[1]) >0 && intval($out[1]) <=24
			&& intval($out[2]) > 0 && intval($out[2]) < 60
			) 
			{
				$w = trim($out[5]);
				if(!empty($w) && strtolower($w) == 'pm') 
				{
					$out[1] = $out[1] + 12;
				}

				$time = mktime(intval($out[1]), intval($out[2]), intval($out[4]), date("m"), date("d"), date("Y") );
				break;
			}
		}

		if( preg_match("/^(\d+)\s?(小时|分钟|秒)(以前|前)/", $str, $out) ) 
		{
			$num = intval($out[1]);
			if( $num > 0 && $num < 60 ) 
			{
				if( $out[2] == '小时') 
				{
					$time = strtotime("-{$num} hours");
				} 
				elseif( $out[2] == '分钟') 
				{
					$time = strtotime("-{$num} minutes");
				} 
				elseif( $out[2] == '秒') 
				{
					$time = strtotime("-{$num} seconds");
				}
				break;
			}
		}

		if( preg_match("/^(\d+)\s?小时(\d+)分(以前|前)/", $str, $out) ) 
		{
			$hour = intval($out[1]);
			$min = intval($out[2]);
			if( $hour > 0 && $hour < 24 && $min > 0 && $min < 60 )
			 {
				$exp_time = 60 * $min + 3600 * $hour;
				$time = time() - $exp_time;
				break;
			}
		}

		if( preg_match("/^(昨天|前天)(\d+):(\d+)/", $str, $out) ) 
		{
			$hour = intval($out[2]);
			$min = intval($out[3]);
			if ($hour >= 0 && $hour < 24 && $min >= 0 && $min < 60) 
			{
				$exp_time = 60 * $min + 3600 * $hour;
				if( $out[1] == '昨天' ) 
				{
					$time = strtotime(date('Y-m-d' , strtotime('-1 day'))) + $exp_time;
				} 
				elseif ($out[1] == '前天' ) 
				{
					$time = strtotime(date('Y-m-d' , strtotime('-2 days'))) + $exp_time;
				}
				break;
			}
		}


		if( preg_match("/^(\d+)\s*天前\s*(\d{1,2}:\d{1,2}(:\d{1,2})?)?/", $str, $out) ) 
		{
			$day = $out[1];
			if ( ! empty($out[2]) ) 
			{
				$time = strtotime(date("Y-m-d", time()-3600*24*$day)." ".$out[2]);
			}
			else 
			{
				$time = strtotime(date("Y-m-d", time()-3600*24*$day)." ".date('H:i:s', time()));
			}
			break;
		}
		if( preg_match("/^(\d+)月(\d+)日\s*(\d{1,2}:\d{1,2}(:\d{1,2})?)/", $str, $out) ) 
		{
			$month = $out[1];
			$day = $out[2];
			if ($month <= date('m')) 
			{
				$time = strtotime(date("Y").'-'.$month.'-'.$day." ".$out[3]);
				break;
			}
		}


		if( preg_match("/^(\d+)\.(\d+)\.(\d+)(\s*(\d{1,2}:\d{1,2}(:\d{1,2})?)?)/", $str, $out) ) 
		{
			$year = $out[1];
			$month = $out[2];
			$day = $out[3];
			$time = strtotime($year.'-'.$month.'-'.$day." ".$out[5]);
			break;
		}


		if( preg_match("/^(\d+)年(\d+)月(\d+)日\s*(\d{1,2})(时|点)(\d{1,2})分((\d{1,2})秒?)?/", $str, $out) ) 
		{

			$year = $out[1];
			$month = $out[2];
			$day = $out[3];
			$hour = $out[4];
			$min = $out[6];
			$sec = '00';
			if(isset($out[8])) 
			{
				$sec = $out[8];
			}
			$time = strtotime($year.'-'.$month.'-'.$day." {$hour}:{$min}:{$sec}");
			break;
		}


		if( preg_match("/^(\d+)月(\d+)日\s*(\d{1,2})(时|点)(\d{1,2})分((\d{1,2})秒?)?/", $str, $out) ) 
		{
			$month = $out[1];
			$day = $out[2];
			$hour = $out[3];
			$min = $out[5];
			$sec = '00';
			if(isset($out[7])) 
			{
				$sec = $out[7];
			}
			$time = strtotime(date("Y").'-'.$month.'-'.$day." {$hour}:{$min}:{$sec}");
			break;
		}
	} while (FALSE);

	if ($time > 0) 
	{
		return 'timestamp'== $format ? $time : date($format, $time) ;
	} 
	else 
	{
		return FALSE;
	}
}

/*用来将不同格式的日期格式化
 * $str 原始字符串
 * $format 日期格式
 * $time_diff 时差  与北京时间的时差，可正可负 默认选用美国东时区夏令时
 *   */
if (! function_exists('sea_strtotime'))
{
	function sea_strtotime($str,$time_diff=-12)
	{
		$timestamp=0;
		do 
		{	$arr=array('ET','HKT','Updated');
			$str=str_replace($arr, '', $str);
			
			$timestamp=strtotime($str);
			if ($timestamp !== false)
				break;
			
		}
		while (false);
	
		return $timestamp + $time_diff *= 3600;
	}
}

if (! function_exists('get_domain')) {
	function get_domain($url){
		if (substr($url, 0, 7) != 'http://') {
			$url = 'http://'.$url;
		}
		$pattern = "/[\w\-]+\.(com|net|org|gov|cc|biz|info|cn|mobi|asia|name|me|tv)(\.(cn|hk))?/";
		$rs = parse_url($url);
		$main_url = $rs["host"];
		if(!strcmp(long2ip(sprintf("%u", ip2long($main_url))), $main_url)) {
			return $main_url;
		}else {
			$arr = explode(".", $main_url);
			$count = count($arr);
			$endArr = array("com", "net", "org", 'gov', "3322");
			if (in_array($arr[$count-2], $endArr)){
				$domain = $arr[$count-3].".".$arr[$count-2].".".$arr[$count-1];
			}else{
				$domain =  $arr[$count-2].".".$arr[$count-1];
			}
			return $domain;
		}
	}
}

function get_hostname($url) {
	$rs = parse_url($url);
	$host = $rs["host"];
	if (preg_match("|\w+\.blog\.sohu\.com|i", $host) ) {
		$host = 'blog.sohu.com';
	} elseif (preg_match("|\w+\.blog\.163\.com|i", $host) ) {
		$host = 'blog.163.com';
	}
	return $host;
}

/*
 * 获取站点名称
 *   */
function get_sitename($url, $site_cls)
{

	$domain_2 = get_hostname($url);

	$CI = & get_instance();
	$CI->load->library('memcache');

	$sitename = $CI->memcache->get('get_sitename_'.$domain_2);
	if (!empty($sitename)) 
	{
		return $sitename;
	}

	$CI->db->select('name');
	$CI->db->where('domain_2', $domain_2);
	$query = $CI->db->get('websites');
	if ($query->num_rows() == 1) 
	{
		$sitename = $query->row()->name;
	}
	elseif($query->num_rows() > 1) 
	{	
		$n = $query->row()->name;
		$sitename = current(explode("-", $n));
	}


	if (empty($sitename)) 
	{
		$domain_1 = get_domain($url);
		$CI->db->select('name');
		$CI->db->where('domain_1', $domain_1);
		$query = $CI->db->get('websites');
		if ($query->num_rows() == 1) 
		{
			$sitename = $query->row()->name;
		}
		elseif($query->num_rows() > 1) 
		{
			$n = $query->row()->name;
			$sitename = current(explode("-", $n));
		}
	}

	if (empty($sitename)) 
	{
		$html = get_remotepate("http://".$domain_2);
		$charset = get_charset($html);
		if ( $charset != '' && $charset != 'GBK' && $charset != 'GB2312' ) 
		{
			$html = convert_encoding($html, $charset, 'GBK');
		}
		if( preg_match("|<title>(.*?)</title>|ims", $html, $out) ) 
		{
			$title = trim($out[1]);
			$arr_title = preg_split("/[-_ ]/", str_replace('—', '-', $title) );
			$title = $arr_title[0];

			$sitename = strlen($title) > 20 ? mb_substr($title, 0, 12) : $title;
		}
		if (empty($sitename) ) 
		{
			$sitename = $domain_1;
		}
		file_put_contents(ROOTPATH."logs/no_doamin.log", $site_cls."\t".$url."\t".$sitename."\t".time()."\r\n", FILE_APPEND);
	}

	if (!empty($sitename)) 
	{
		$CI->memcache->set('get_sitename_'.$domain_2, $sitename, 3600*5);
		return $sitename;
	}

	file_put_contents(ROOTPATH."logs/no_doamin_site.log", $url."\r\n", FILE_APPEND);

	return FALSE;
}

function get_word_data($arr) 
{
	$str_arr = array();
	foreach ($arr as $bid => $arr_p) 
	{
		$arr_p = array_unique($arr_p);
		$str_arr[] = $bid.":".implode(",", $arr_p);
	}
	return implode(";", $str_arr);
}

function get_report_tbl_by_status($user_id, $status=0) 
{
	$CI = & get_instance();
	$report_tbl_arr = $CI->config->config['enums']['report_tbl'];
	$report_word_tbl_arr = $CI->config->config['enums']['report_words_tbl'];
	if (!in_array($status, array_keys($report_tbl_arr) )) 
	{
		return FALSE;
	}
	if ($status < 0) 
	{
		return array($report_tbl_arr[$status], $report_word_tbl_arr[$status]);
	} 
	else 
	{
		return array($report_tbl_arr[$status]."_".$user_id, $report_word_tbl_arr[$status]."_".$user_id );
	}
}
/*
 * 根据名称获取报表模板
 *   */
function get_report_tbl_by_name($user_id, $tbl='') {
	$arr = array('weight', 'other', 'del', '', 'report');
	if (!in_array($tbl, $arr)) 
	{
		return ;
	}
	if ('weight' == $tbl) 
	{
		return array('reports_weight', 'report_word_weight', -2);
	} elseif ('other' == $tbl) {
		return array('reports_other', 'report_word_other', -3);
	} elseif ('del' == $tbl) {
		return array('reports_del', 'report_word_del', -1);
	} else {
		return array('reports_'.$user_id, 'report_word_'.$user_id,  0);
	}
}

function get_report_tbl_by_name_1($user_id, $tbl='') {
	$arr = array('weight', 'other', 'del', '', 'report');
	if (!in_array($tbl, $arr)) {
		return ;
	}
	if ('weight' == $tbl) {
		return array('reports_weight', 'report_word_weight', -2);
	} elseif ('other' == $tbl) {
		return array('reports_other', 'report_word_other', -3);
	} elseif ('del' == $tbl) {
		return array('reports_del', 'report_word_del', -1);
	} else {
		return array('reports_'.$user_id, 'report_word_'.$user_id,'reports_similar_'.$user_id,  0);
	}
}


function my_array_multisort($arr, $col_1, $direction_1='desc', $col_2 = NULL, $direction_2='desc') {

	foreach ($arr as $key => $row) {
		$volume[$key]  = $row[$col_1];
		$edition[$key] = $row[$col_2];
	}

	$direction_1 = ('desc'==$direction_1) ? SORT_DESC : SORT_ASC;
	$direction_2 = ('desc'==$direction_2) ? SORT_DESC : SORT_ASC;
	array_multisort($volume, $direction_1, $edition, $direction_2, $arr);
	return $arr;
}

function has_substr($str, $substr, $delimiter=' ') {
	$substr = trim($substr);
	$arr = explode($delimiter, $substr);
	foreach ($arr as $val) {
		$val = trim($val);
		if (empty($val)) {
			continue;
		}
		if (strpos($str, $val) !== FALSE) {
			return TRUE;
		}
	}
	return FALSE;
}

function is_english($str, $charset = 'gbk') {
	$x = mb_strlen($str, $charset);
	$y = strlen($str);

	if ($x == $y) {
		return 1;		
	}else{
		if ($y % $x == 0) {
			return 2;	
		}else {
			$pre = ord(mb_substr($str, 0, 1));
			$last = ord(mb_substr($str, -1, 1));
			if ( ($pre >= 48 && $pre <=57) || ($pre >= 65 && $pre <= 90) || ($pre >= 97 && $pre <= 122) ) {
				if ( ($last >= 48 && $last <=57) || ($last >= 65 && $last <= 90) || ($last >= 97 && $last <= 122) ) {
					return 5;	
				}else{
					return 3;	
				}
			}else {
				if ( ($last >= 48 && $last <=57) || ($last >= 65 && $last <= 90) || ($last >= 97 && $last <= 122) ) {
					return 4;	
				}else{
					return 6;	
				}
			}
		}
	}
}

function is_user_setting() 
{
	$domain = trim($_SERVER['HTTP_HOST']);
	if ('www.unotice.cn' == $domain || 'unotice.cn' == $domain) 
	{
		return FALSE;
	}
	$CI =& get_instance();
	$domain_info = $CI->config->config['enums']['domain_info'];
	if (isset($domain_info[$domain]) && ! empty($domain_info[$domain])) 
	{
		return $domain_info[$domain];
	}
	return false;
}

function helptips($tips, $txt='', $type='msg') 
{
	if ('msg' == $type && preg_match('|^[\w:/]+$|i', $tips)) 
	{
		$type = 'url';
	}
	$alttips = ($type == 'url') ? 'name="'.$tips.'"' : 'alt="'.$tips.'"';
	if (!empty($txt)) 
	{
		printf('<span %s class="helptips">%s</span>', $alttips, $txt);
	} 
	else 
	{
		printf('<span %s class="helptips"> &nbsp; &nbsp; </span>', $alttips, $txt);
	}
}

if (!function_exists('show_date')) 
{
	function show_date($pubdate='', $created=0) 
	{
		if (empty($pubdate)) 
		{
			$pubdate = date('Y-m-d H:i:s', $created);
		}
		if ('00:00:00' == substr($pubdate, -8)) 
		{
			$date = substr($pubdate, 5, 5);
		}
		else 
		{
			$date = substr($pubdate, 5, 11);
		}
		return $date;
	}
}
if (!function_exists('get_theme_by_id')) 
{
	function get_theme_by_id($user_id = 0)
	{
		$theme_name = 'client_new/';
		$theme_name_set = '';

		if (!empty($user_id)) 
		{
			$CI =& get_instance();

			$CI->db->select('theme_folder');
			$CI->db->where('id', $user_id);
			$theme_name_set = $CI->db->get('users')->row()->theme_folder . '/';
		}

		if (!empty($theme_name_set) && '/' != $theme_name_set && file_exists(APPPATH.'views/'.$theme_name_set.'index.html') && file_exists(APPPATH.'views/'.$theme_name_set.'info.php')) 
		{
			return $theme_name_set;
		}
		else 
		{
			return $theme_name;
		}
	}
}

if (! function_exists('get_index_by_domain')) 
{
	function get_index_by_domain($domain='')
	{
		if (empty($domain)) 
		{
			return false;
		}

		$CI =& get_instance();

		$domain_folders = $CI->config->config['enums']['domain_tpl'];
		if (isset($domain_folders[$domain]) && ! empty($domain_folders[$domain])) {
			return $domain_folders[$domain] . '/';
		}else {
			return false;
		}
	}
}
if (! function_exists('best_substr')) {
	function best_substr($string, $length = 80, $dot = '...')
	{
		if(strlen($string) <= $length) {
			return $string;
		}

		$string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;'), array('&', '"', '<', '>'), $string);

		$strcut = '';
		if(strtolower($charset) == 'utf-8') {

			$n = $tn = $noc = 0;
			while($n < strlen($string)) {
				$t = ord($string[$n]);
				if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
					$tn = 1; $n++; $noc++;
				} elseif(194 <= $t && $t <= 223) {
					$tn = 2; $n += 2; $noc += 2;
				} elseif(224 <= $t && $t < 239) {
					$tn = 3; $n += 3; $noc += 2;
				} elseif(240 <= $t && $t <= 247) {
					$tn = 4; $n += 4; $noc += 2;
				} elseif(248 <= $t && $t <= 251) {
					$tn = 5; $n += 5; $noc += 2;
				} elseif($t == 252 || $t == 253) {
					$tn = 6; $n += 6; $noc += 2;
				} else {
					$n++;
				}
				if($noc >= $length) {
					break;
				}
			}
			if($noc > $length) {
				$n -= $tn;
			}

			$strcut = substr($string, 0, $n);

		} else {
			for($i = 0; $i < $length; $i++) {
				$strcut .= ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
			}
		}

		$strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);

		return $strcut.$dot;
	}
}

function best_sitename($sitename, $len=10) {
	$arr = explode("-", $sitename);
	$sitename = $arr[0];
	return best_substr($sitename, $len, '');
}

function get_xhdata_brands($user_id){
	$CI =& get_instance();
	$sql = " select brand_name,id from brands where user_id = ".$user_id." and is_show=1 and status=0 order by id asc";
	$query = $CI->db->query($sql);
	return $query->result_array();
}

function get_brands_names_arr($user_id){
	$CI = &get_instance();

	$brands_arr = array();
	$brands_name_arr = array();
	$brands_arr = get_xhdata_brands($user_id);
	if(!empty($brands_arr)){
		foreach ($brands_arr as $val){
			$brands_name_arr[] = $val['brand_name'];
		}
	}
	return $brands_name_arr;
}

function get_user_custom_model($user_id){
	if (empty($user_id)) {
		return ;
	}
	$CI =& get_instance();
	$sql = "select ibox_models from `users` where id = ".$user_id." limit 1 ";
	$query = $CI->db->query($sql);
	$user_custom_info = $query->row_array();
	if($user_custom_info){
		$user_custom_info = unserialize($user_custom_info['ibox_models']);
	}
	return $user_custom_info;
}

function get_brand_products($user_id = 0,$show_words=true){
	$CI =& get_instance();
	$CI->load->model('common_model');
	return $CI->common_model->client_product_words($user_id,$show_words);
}

function get_group_brand_products($user_id = 0,$is_add = false){
	$CI =& get_instance();
	$CI->load->model('common_model');
	return $CI->common_model->client_group_products($user_id,$is_add);
}


function write_id_to__file($user_id = 0,$id = '') {
	if($user_id <=0 || empty($id) ){
		return false;
	};
	$update_solr_ids_path = '';
	$content = '';
	$update_solr_ids_path = ROOTPATH . 'logs/update_solr_ids_'.$user_id.'.data';
	$content = $id."\r\n";
	file_put_contents($update_solr_ids_path,$content,FILE_APPEND);
}

function sendmail($to, $subject, $body,$email_from='') {
	$charset = "utf-8";
	$email_from = !empty($email_from) ? $email_from : 'service@infoclouds.net';
	$subject = "=?UTF-8?B?".base64_encode($subject)."?=";

	$headers  = "MIME-Version: 1.0" . "\r\n";
	$headers .= "From: $email_from " . "\r\n";
	$headers .= "Reply-To: $email_from" . "\r\n";
	$headers .= "Content-type: text/html; charset=$charset" . "\n";


	return mail($to, $subject, $body, $headers);
}
function get_replytime_tieba($url='') 
{
	if(empty($url))
	{
		return 0;
	}
	$url_c = get_remotepate($url);
	preg_match('|<li class="pagination">(.*?)</li>|ims',$url_c,$out1);
	if(!empty($out1))
	{
		preg_match('|下一页</a><a href=(.*?)>尾页</a>|ims',$out1[1],$out2);
		$last_page = file_get_contents('http://tieba.baidu.com'.$out2[1]);
		preg_match_all('|<li>(\d{4}\-\d{1,2}\-\d{1,2}\s\d{2}:\d{2})</li>|ims',$last_page,$out3);
		if(!empty($out3[1]))
		{
			$last_replytime = strtotime(max($out3[1]));
		}
		else
		{
			$last_replytime = 0;
		}

	}
	else
	{
		preg_match_all('|<li>(\d{4}\-\d{1,2}\-\d{1,2}\s\d{2}:\d{2})</li>|ims',$url_c,$out3);
		if(!empty($out3[1]))
		{
			$last_replytime = strtotime(max($out3[1]));
		}
		else
		{
			$last_replytime = 0;
		}

	}
	return $last_replytime;
}
function get_replytime_tianya($url = '')
{
	if(empty($url))
	{
		return 0;
	}
	$url_c = get_remotepate($url);
	$url_c = mb_convert_encoding($url_c,'gbk','utf-8');
	$out1 = array();
	preg_match('|下一页</a>\]&nbsp;\[<a style="text-decoration:underline;" href="(.*?)">末页</a>\]|ims',$url_c,$out1);
	if(!empty($out1))
	{
		$last_page = get_remotepate($out1[1]);
		$last_page = mb_convert_encoding($last_page,'gbk','utf-8');
		preg_match_all('|</a>　回复日期：(\d{4}\-\d{1,2}\-\d{1,2}\s\d{2}:\d{2}:\d{2})</font>|ims',$last_page,$out2);
		if(!empty($out2[1]))
		{
			$last_replytime = strtotime(max($out2[1]));
			return $last_replytime;
		}
		else
		{
			return 0;
		}
	}
	else
	{
		$url_c = get_remotepate($url);
		preg_match_all('|回复日期：(.*?)</font>|ims',$url_c,$out2);
		if(!empty($out2[1]))
		{
			$t = max($out2[1]);
			$t = str_replace('　',' ',$t);
			$last_replytime = strtotime($t);
			return $last_replytime;
		}
		else
		{
			return 0;
		}
	}
}

function my_strip_tags($string, $allowable_tags = '<img><table><tr><tbody><td><p>') {
	if (! empty($allowable_tags) && preg_match_all("/\<([a-z]+)([^>]*)\>/is", $allowable_tags, $tags)) {
		$normalize_tags = '<' . implode('><', $tags[1]) . '>';

		$searchcursory = array(
			'/<!--.*?-->/is',
			"/\\<(script|style|textarea|select|iframe)[^\\>]*?\\>.*?\\<\\/(\\1)\\>/si",
			"/\\<!*(--|doctype|html|head|meta|link|body)[^\\>]*?\\>/si",
			"/<\\/(html|head|meta|link|body)\\>/si",
			"/<\\/div\\>/si",
		);
		$replacecursory = array(
			' ',
			"",
			"",
			"",
			"<br />"
			);
			$string = preg_replace($searchcursory, $replacecursory, $string);



			$string = strip_tags($string, $normalize_tags);
			$attributes = array_map('trim', $tags[2]);

			$allowable_attributes = array();
			foreach ($attributes as $key => $val) {
				$allowable_attributes[$tags[1][$key]] = array();
				if (preg_match_all("/([a-z]+)\s*\=/is", $val, $vals)) {
					foreach ($vals[1] as $attribute) {
						$allowable_attributes[$tags[1][$key]][] = $attribute;
					}
				}
			}

			foreach ($tags[1] as $key => $val) {
				$match = "/\<{$val}(\s*[a-z]+\s*\=\s*[\"'][^\"']*[\"'])*\s*\>/is";

				if ( count( $allowable_attributes[$val] ) == 0) {
					continue;
				}

				if (preg_match_all($match, $string, $out)) {

					foreach ($out[0] as $start_tag) {

						if (preg_match_all("/([a-z]+)\s*\=\s*[\"'][^\"']*[\"']/is", $start_tag, $attributes_match)) {

							$replace = $start_tag;
							foreach ($attributes_match[1] as $attribute) {

								if (! in_array($attribute, $allowable_attributes[$val])) {
									$start_tag = preg_replace("/\s*{$attribute}\s*=\s*[\"'][^\"']*[\"']/is", '', $start_tag);
								}
							}

							$string = str_replace($replace, $start_tag, $string);
						}
					}
				}
			}
			return $string;
	} else {
		return strip_tags($string);
	}
}
/*
 * 高亮关键字
 *   */
function highlight_keywords($words = array(), $content='')
{
	foreach ($words as $val) 
	{
		$content = str_replace($val, '<span style="background-color:#FFFFCC;color:red">'.$val.'</span>', $content);
	}
	return $content;
}

function highlight_ikeywords($words = array(), $content='')
{
	foreach ($words as $val) 
	{
		$content = str_ireplace($val, '<span style="background-color:#FFFFCC;color:red">'.$val.'</span>', $content);
	}
	return $content;
}
/* 
 * 按格式打印数组
 *  */
if (! function_exists ( 'pr' )) 
{
	function pr($array = '') 
	{
		echo "<pre>";
		print_r($array);
		echo "</pre>";
	}
}
/*
 * 转为utf-8编码
 *   */
function to_utf8($str)
{
	if (is_array($str))
	{
		foreach ($str as $key => $value)
		{
			$str[$key] = iconv('GBK', 'UTF-8', $value);
		}

		return $str;
	}
	else
	{
		return iconv('GBK', 'UTF-8', $str);
	}
}
/*
 * 转为gbk编码
 *   */
function to_gbk($str)
{
	if (is_array($str))
	{
		foreach ($str as $key => $value)
		{
			$str[$key] = iconv('UTF-8', 'GBK', $value);
		}

		return $str;
	}
	else
	{
		return iconv('UTF-8', 'GBK', $str);
	}
}

function get_microblog_source($source)
{
	$ci =& get_instance();
	$ci->load->config('microblog');
	$arr = $ci->config->item('microblog_sites');

	return isset($arr[$source]) ? $arr[$source]['title'] : '(未知)';
}

function urlencode_rfc($str='')
{
	return str_replace(
		  '+',
		  ' ',
	str_replace('%7E', '~', rawurlencode($str))
	);
}

if (! function_exists ( 'update_record' )) 
{
	function update_record($source,$type,$time='') 
	{
		if(!$time)
		{
			$time = time();
		}
		if(!in_array($type,array('search','user','count','comments'))) return ;
		$record_folder = "logs/microblog_api/";
		$filename = date('Ymd_H',$time) .'_'.$source.'.log';

		if (!is_dir(ROOTPATH.$record_folder)) 
		{
			@mkdir(ROOTPATH.$record_folder, 0777);
		}
		if(!file_exists(ROOTPATH.$record_folder.$filename) ) 
		{
			@touch(ROOTPATH.$record_folder.$filename, 0777);
		}

		$file_content = file_get_contents(ROOTPATH.$record_folder.$filename);
		if($file_content)
		{
			$content_arr = explode("\n",$file_content);
		}
		else
		{
			$content = "search:0\nuser:0\ncount:0\ncomments:0";
			file_put_contents(ROOTPATH.$record_folder.$filename,$content);
			return ;
		}
		$content_arr = array_filter($content_arr);
		$new_content = '';
		foreach($content_arr as $val)
		{
			$val_arr = explode(':',$val);
			if($type == $val_arr[0])
			{
				$val_new = $val_arr[1] + 1;
				$new_content .= $val_arr[0] . ':' . $val_new. "\n";
			}
			else
			{
				$new_content .= $val_arr[0] . ':' . $val_arr[1]. "\n";
			}

			file_put_contents(ROOTPATH.$record_folder.$filename,$new_content);
		}
	}
}

if (! function_exists('my_len_sort')) {
	function my_len_sort($array, $order = 'DESC') {
		$arr = array();
		foreach($array as $val) {
			$arr[strlen($val)][] = $val;
		}
		if (strtolower($order) == 'desc') {
			krsort($arr);
		}else {
			ksort($arr);
		}

		$array = array();
		foreach($arr as $val) {
			$array = array_merge($array, $val);
		}

		return $array;
	}
}

function shorten_url($long_url){
	$apiKey='2897359379';
	$apiUrl='http://api.t.sina.com.cn/short_url/shorten.json?source='.$apiKey.'&url_long='.$long_url;
	$curlObj = curl_init();
	curl_setopt($curlObj, CURLOPT_URL, $apiUrl);
	curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curlObj, CURLOPT_HEADER, 0);
	curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
	$response = curl_exec($curlObj);
	curl_close($curlObj);
	$json = json_decode($response);
	return $json[0]->url_short;
}

function expand_url($short_url){
	$apiKey='2897359379';
	$apiUrl='http://api.t.sina.com.cn/short_url/expand.json?source='.$apiKey.'&url_short='.$short_url;
	$curlObj = curl_init();
	curl_setopt($curlObj, CURLOPT_URL, $apiUrl);
	curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curlObj, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($curlObj, CURLOPT_HEADER, 0);
	curl_setopt($curlObj, CURLOPT_HTTPHEADER, array('Content-type:application/json'));
	$response = curl_exec($curlObj);
	curl_close($curlObj);
	$json = json_decode($response);
	return $json[0]->url_long;
}
function sendsms($mobile_num='',$content='') {
	$this->load->library('sendwarning');
	$data = array(
		'mobile'=>$mobile_num,
		'content'=>$content,
		'ext'=>'',
		'stime'=>'',
		'rrid'=>''
	);
	
	$res = $this->sendwarning->send_soap($data);
	return $res;
}

function array_orderby() {
	$args = func_get_args();
	$data = array_shift($args);
	foreach ($args as $n => $field) {
		if (is_string($field)) {
			$tmp = array();
			foreach ($data as $key => $row) {
				$tmp[$key] = $row[$field];
			}
			$args[$n] = $tmp;
		}
	}
	$args[] = &$data;
	call_user_func_array('array_multisort', $args);
	return array_pop($args);
}

function new_access_process($arr){
	if(!empty($arr)){

		foreach($arr as $key){
			if(!is_numeric($key)){
				return false;
			}
		}
	}
	return serialize($arr);

}

function unescape($str) {
	$str = rawurldecode($str);
	preg_match_all("/%u.{4}|&#x.{4};|&#d+;|.+/U",$str,$r);
	$ar = $r[0];
	foreach($ar as $k=>$v) {
		if(substr($v,0,2) == "%u")
		$ar[$k] = mb_convert_encoding(pack("H4",substr($v,-4)),"gb2312","UCS-2");
		elseif(substr($v,0,3) == "&#x")
		$ar[$k] = mb_convert_encoding(pack("H4",substr($v,3,-1)),"gb2312","UCS-2");
		elseif(substr($v,0,2) == "&#") {
			$ar[$k] = mb_convert_encoding(pack("H4",substr($v,2,-1)),"gb2312","UCS-2");
		}
	}
	return join("",$ar);
}

//creat by csw
//2016 0810

if( ! function_exists('remove_n'))
{
	//去除字符串中的换行符
	function remove_n($str)
	{
		return str_replace("\n","",$str);
	}
}
