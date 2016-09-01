<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/8/19
 * Time: 13:49
 */
class Twitter extends CI_Controller {

    /**表示已经产生并领取日志文件的进程
     * 新产生的子进程的日志记录在上一个回收的日志文件下
     * 以免文件写入错乱
     * @var
     */
    private $regist_p=array();

    /**twitter应用信息
     * @var
     */
    private $app;

    /**所有已经授权的token信息
     * @var
     */
    private $tokens;

    /**
     * @var int
     */
    private $max_pid_num = 10;     //同时存在的 最大的子进程数量

    /**
     * @var int
     */
    private $now_pid_num = 0;

    /**
     * @var int
     */
    private $debug = DEBUG_DETAIL; //定义日志的记录级别（从不，简单，详细记录）

    /**
     * @var string
     */
    private $log_path = 'twitter/';

    /**
     * @var string
     */
    private $p_path_pre ='twitter/process_';

    /**
     * @var int
     */
    private $dic_num = 72;        //sys_dictionary 里的推特网id

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('log');                 //加载自定义日志函数
        $this->load->config('service');             //加载配置文件
        $this->load->model('twitter_model','tw');   //加载模型
        $this->set_app();                           //设置应用信息
    }

    /**
     * @return bool
     */
    public function start()
    {
        $this->debug_msg('',$this->log_path,'twitter服务开启!',DEBUG_SIMPLE,true);
        $this->benchmark->mark('server_start');

        $dic = $this->get_dic();                   			//获取关键词
        shuffle($dic);									//打乱关键词顺序防止某进程任务持续过重

        if( ! $dic)
        {
            $this->debug_msg('',$this->log_path,'关键字为空，程序终止运行！',DEBUG_SIMPLE);
            return false;
        }

        $count_dic = count($dic);                            //总的关键词的数量

        $l = 0;

        for ($i = 0; $i < $count_dic; $i++)
        {
            if ($this->now_pid_num >= $this->max_pid_num)
            {
                $this->debug_msg('',$this->log_path,"当前进程数量{$this->now_pid_num}已超过最大数量限制{$this->max_pid_num},主进程挂起...");
                $id = pcntl_wait($status);
                $path = $this->regist_p[$id];
                $this->now_pid_num --;
                $l ++;
                $this->debug_msg('',$this->log_path,"{$l}:子进程{$id}释放资源，顺利回收。",DEBUG_SIMPLE);
                $this->debug_msg('',$this->log_path,"日志 {$path} 文件被释放",DEBUG_DETAIL);
            }
            else
            {
                $path = $this->get_p_path($i);
            }

            $pid = pcntl_fork();

            if ($pid)   //父进程走这里
            {
                $id = getmypid();
                $this->regist_p[$pid] = $path;
                $this->now_pid_num ++;
                $this->debug_msg('',$this->log_path,"父进程ID:{$id},创建子进程ID:{$pid}",DEBUG_SIMPLE);
                $this->debug_msg('',$this->log_path,"子进程{$i} 日志路径:{$path}");
            }
            else        //子进程走这里
            {
                $app = $this->get_token($i);                                    //获取app信息以及access_token
                $current_dic = $dic[$i];                                        //当前的关键字数组
                $since = $current_dic[0] ? $current_dic[0] : strtotime('-1 day');
                $condition = $current_dic[2];                                   //关键字
                $until = time();                                                //抓取截止日期
                $str = remove_n(serialize($current_dic));                       //记录当前进程操作的关键字数组

                $this->debug_msg('',$path,"子进程".($i+1)."开始工作...",DEBUG_SIMPLE);
                $this->debug_msg('',$path,"分配的关键词为\n{$str}",DEBUG_DETAIL);
//                $this->debug_msg('',$path,"APP信息:\n".var_export($app,true));

            //    sleep(rand(1,3));
                $this->tw   ->init($app)
                            ->set_p_path($path)
                            ->set_since($since)
                            ->set_until($until)
                            ->set_condition($condition);

                $this->tw->exe();
                exit();
            }
        }

        do
        {
            $id = (int) pcntl_wait($status);
            $this->now_pid_num -- ;
            $l++;
            $this->debug_msg('',$this->log_path,"{$l}:子进程{$id}释放资源，顺利回收。",DEBUG_SIMPLE);
        }while($id > 0);

        $this->benchmark->mark('server_end');
        $total_time = $this->benchmark->elapsed_time('server_start', 'server_end');
        $this->debug_msg('',$this->log_path,"所有子进程释放资源,twitter服务终止!用时{$total_time}",DEBUG_SIMPLE);
    }

    /**
     * @return array
     */
    private function get_dic()
    {
        $file = APPPATH.'overwords_tw.txt';
        if(file_exists($file))
        {
            $content = file($file);
            $item = array();
            foreach($content as $key=>$val)
            {
                $item[$key] = explode('||',$val);
            }

            return $item;
        }

        return array();
    }

    /**
     * @param $file
     * @param $path
     * @param $msg
     * @param int $grade
     * @param bool|false $cover
     */
    private function debug_msg($file,$path,$msg,$grade=DEBUG_SIMPLE,$cover=false)
    {
        if($grade <= $this->debug)
        {
            log_msg($file,$path,$msg,$cover);
        }

    }

    /**
     * @param $i
     * @return string
     */
    private function get_p_path($i)
    {
        $p = ($i + 1) % $this->max_pid_num;
        $p = ($p==0) ? $this->max_pid_num : $p;

        $path = $this->p_path_pre.$p.'/';
        return $path;
    }

    /**
     * @return array|bool
     */
    private function get_dectionary()
    {
        $this->db_103->select('id,fname,fparentId,fparent');
        $res = $this->db_103->get_where('sys_dictionary',array('fparentId'=>$this->dic_num));
        if($res)
        {
            $res = $res->result_array();
            $item=array();
            foreach($res as $key=>$val)
            {
                $item[$val['fname']]=$val;
            }
            return $item;
        }
        return false;
    }

    /**设置应用信息
     *
     */
    private function set_app()
    {
        $this->app = $this->config->item('twitter_app');
        $this->tokens = $this->config->item('twitter_tokens');
    }

    /**获取应用信息和access_token
     * @param $i
     * @return array
     */
    private function get_token($i)
    {
        $count = count($this->tokens);
        $l = $i % $count;
        $token = $this->tokens[$l];
        return array_merge($this->app,$token);
    }

    /**通过传入用户名密码，系统自动以PIN——AUTH方式授权
     * 获取的access_token 原则上说是不会过期的，除非用户主动取消授权
     * @param string $username
     * @param string $pwd
     * @return bool
     */
    public function auth($username='',$pwd='')
    {
        if(empty($username) || empty($pwd))
            return false;

        $this->load->model('TwitterOauth_model','tw_oauth');
        $this->tw_oauth ->setUser($username,$pwd)
                        ->exe();

        $res = $this->tw_oauth->getAccessToken();

        var_dump($res);
    }

}
