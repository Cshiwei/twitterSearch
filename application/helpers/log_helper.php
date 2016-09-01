<?php
/**
 * Created by PhpStorm.
 * User: Csw
 * Date: 2016/8/9
 * Time: 16:35
 */
//自定义函数 log_msg可以指定路径，文件名，以及是否每天产生新的日志文件
if( ! function_exists('log_msg'))
{
    function log_msg($file='',$path='',$msg,$cover=false)
    {
        $log_path = APPPATH.'logs/';               //指定记录日志的文件路径

        $config =& get_config();
        $file_ext =

            (isset($config['log_file_extension']) && $config['log_file_extension'] !== '')
            ? ltrim($config['log_file_extension'], '.')
            : 'php';

        if( ! $file)
        {
            $file = 'log-'.date('Y-m-d');
        }

        $file = $log_path.$path.$file.'.'.$file_ext;
        $message ='';

        if ( ! file_exists($file))
        {
            $newfile = TRUE;
            // Only add protection to php files
            if ($file_ext === 'php')
            {
                $message .= "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\n\n";
            }
        }

        if( ! $cover)
        {
            if ( ! $fp = @fopen($file, 'ab'))                    //新建或者打开文件(以附加内容的方式打开文件)
                return FALSE;
        }
        else
        {
            if ( ! $fp = @fopen($file, 'w'))                    //新建或者打开文件(以覆盖方式写入文件)
                return FALSE;
        }

        $date = date('Y-m-d H:i:s',time());

        $message .= $date.' --> '.$msg."\n";

        flock($fp, LOCK_EX);

        for ($written = 0, $length = strlen($message); $written < $length; $written += $result)
        {
            if (($result = fwrite($fp, substr($message, $written))) === FALSE)
            {
                break;
            }
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        if (isset($newfile) && $newfile === TRUE)
        {
            chmod($file, 0644);
        }

        return is_int($result);
    }
}


//删除日志文件
if( ! function_exists('del_log'))
{

}

