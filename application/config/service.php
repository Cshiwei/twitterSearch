<?php

$config['twitter_app']=array(
		/* 推特的APP应用信息 */
	    'consumer_key' 				=> "{YOUR CONSUMER_KEY}",
	    'consumer_secret' 			=> "{YOUR CONSUMER_SECRET}"
	);
/**
 * 用户授权后的access_token信息
 * 由于twitter api 有调用限制，当请求过于频繁时，适当增加access_token是可行的
 */
$config['twitter_tokens'] = array(
	array(
		'oauth_access_token'		=> '{AN USER ACCESS_TOKEN}',
		'oauth_access_token_secret'	=> '{AN USER ACCESS_TOKEN_SECRET}',
	),
	array(
		'oauth_access_token'		=> '{AN USER ACCESS_TOKEN}',
		'oauth_access_token_secret'	=> '{AN USER ACCESS_TOKEN_SECRET}',
	),
	array(
		'oauth_access_token'		=> '{AN USER ACCESS_TOKEN}',
		'oauth_access_token_secret'	=> '{AN USER ACCESS_TOKEN_SECRET}',
	),
	array(
		'oauth_access_token'		=> '{AN USER ACCESS_TOKEN}',
		'oauth_access_token_secret'	=> '{AN USER ACCESS_TOKEN_SECRET}',
	),
);
