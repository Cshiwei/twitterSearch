<?php
/**
 * Created by PhpStorm.
 * User: csw
 * Date: 2016/8/12
 * Time: 9:31
 * 回调函数库
 */
//筛选开放的小组
if(! function_exists('group_filter'))
{
    function group_filter($val)
    {
       if($val['privacy']=='OPEN')
           return true;
    }
}
//去除event中不需要的键值对
if(! function_exists('event_intersect'))
{
    function event_intersect($val)
    {
        $intersect = array(
          'id' => 'item',
          'name'=>'item',
        );

        return array_intersect_key($val,$intersect);
    }
}

if(! function_exists('twitter_intersect'))
{
    function twitter_intersect($val)
    {
        $intersect = array(
            'id'		=>'',
            'text'		=>'',
            'created_at'=>'',
        );

        return array_intersect_key($val,$intersect);
    }
}