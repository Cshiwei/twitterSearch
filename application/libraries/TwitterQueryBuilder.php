<?php
/**
 * Created by PhpStorm.
 * User: Cshiwei
 * Date: 2016/8/24
 * Time: 16:57
 * 本系统模仿twitter的高级搜索功能拼接查询字符串
 * 参考地址 https://twitter.com/search-advanced
 */
class TwitterQueryBuilder
{
    /**最终生成的查询语句
     * @var string
     */
    private $query ='';

    /**
     * 当前已经注册的关键词处理方式
     * @var array
     */
    private $type = array();

    /**包含所有词，以空格分离，每个词在文章中可以不紧邻
     * @var string
     */
    private $allWord ='';

    /**确切的短语，将词作为整体，不可分割
     * @var string
     */
    private $exactWord ='';

    /**以空格分离，文章包含任意词语即可
     * @var string
     */
    private $orWord ='';

    /**以空格分割 文章中不可包含这些词语
     * @var string
     */
    private $outWord = '';

    /**多个话题以空格分离 以or连接
     * @var string
     */
    private $topicWord = '';
    /**文章上传的起始时间
     * @var string
     */
    private $since ='';

    /**文章上传的终止时间
     * @var string
     */
    private $until ='';

    /**语言 只能选一个
     * @var string
     */
    private $lang = '';

    /**来自这些账号 以空格分离 多个账号以or连接
     * @var string
     */
    private $fromWord = '';

    /**发往这些帐号的文章
     * @var string
     */
    private $toWord = '';

    /**文中提到这些帐号 以空格分离 OR连接
     * @var string
     */
    private $mentionWord = '';

    /**在该位置附近
     * @var string
     */
    private $placeWord = '';

    /**其他栏目下的选项
     * @var string
     */
    private $otherWord = '';


    /**resetWord方法里用来记录最终结果
     * @var string
     */
    private $resetRes = '';

    /**配置数组包含一些对应关系
     * @var array
     */
    private $config=array(
        'active'    =>':)',
        'negative'  =>':(',
        'question'  =>'?',
        'retweets'  =>'include:retweets',
    );

    /**是否需要 拼接 ?q=
     * @var bool
     */
    private $needQ = true;

    /**
     * @param mixed $allWord
     * @return $this
     */
    public function setAllWord($allWord)
    {
        $allWord = $this->restWord('','',$allWord);
        $this->allWord = $allWord;
        $this->type['allWord'] = $allWord;
        return $this;
    }

    /**
     * @param  mixed $exactWord
     * @return $this
     */
    public function setExactWord($exactWord)
    {
        $exactWord = trim($exactWord);
        $this->exactWord = '"'.$exactWord.'"';
        $this->type['exactWord'] = $this->exactWord;
        return $this;
    }

    /**
     * @param mixed $orWord
     * @return $this
     */
    public function setOrWord($orWord)
    {
        $orWord = $this->restWord('','OR',$orWord);
        $this->orWord = $orWord;
        $this->type['orWord'] = $orWord;
        return $this;
    }

    /**
     * 排除这些单词  以空格分离
     * @param string $outWord
     * @return $this
     */
    public function setOutWord($outWord)
    {
        $outWord = $this->restWord('-','',$outWord);
        $this->outWord = $outWord;
        $this->type['outWord'] = $outWord;
        return $this;
    }

    /**
     * @param string $topicWord
     * @return $this
     */
    public function setTopicWord($topicWord)
    {
        $res = $this->restWord('#','OR',$topicWord);
        $this->topicWord = $res;
        $this->type['topicWord'] = $res;
        return $this;
    }

    /**
     * @param string $since
     * @return $this
     */
    public function setSince($since)
    {
        $since = date('Y-m-d',$since);
        $this->since = "since:{$since}";
        $this->type['since'] = $this->since;
        return $this;
    }

    /**
     * @param string $until
     * @return $this
     */
    public function setUntil($until)
    {
        $until = date('Y-m-d',$until);
        $this->until = "until:{$until}";
        $this->type['until'] = $this->until;
        return $this;
    }

    /**
     * @param string $lang
     * @return $this
     */
    public function setLang($lang)
    {
        $lang = "lang:{$lang}";
        $this->lang = $lang;
        $this->type['lang'] = $lang;
        return $this;
    }

    /**
     * @param string $fromWord
     * @return $this
     */
    public function setFromWord($fromWord)
    {
        $fromWord = $this->restWord('from:','OR',$fromWord);
        $this->fromWord = $fromWord;
        $this->type['fromWord'] = $fromWord;
        return $this;
    }

    /**
     * @param string $toWord
     * @return $this
     */
    public function setToWord($toWord)
    {
        $toWord = $this->restWord('to:','OR',$toWord);
        $this->toWord = $toWord;
        $this->type['toWord'] = $toWord;
        return $this;
    }

    /**
     * @param string $mentionWord
     * @return $this
     */
    public function setMentionWord($mentionWord)
    {
        $mentionWord = $this->restWord('@','OR',$mentionWord);
        $this->mentionWord = $mentionWord;
        $this->type['mentionWord'] = $mentionWord;
        return $this;
    }

    /**
     * @param string $placeWord
     * @return $this
     */
    public function setPlaceWord($placeWord)
    {
        $placeWord='near"'.$placeWord.'"'.' within:15mi';
        $this->placeWord = $placeWord;
        $this->type['placeWord'] = $placeWord;
        return $this;
    }

    /**
     * @param array|string $otherWord
     * @return $this
     */
    public function setOtherWord(array $otherWord)
    {
        $item ='';
        foreach($otherWord as $key=>$val)
        {
            if(array_key_exists($val,$this->config))
            {
                $item .= ' '.$this->config[$val];
            }
        }
        $this->otherWord =trim($item);
        $this->type['otherWord'] = $this->otherWord;
        return $this;
    }

    /**
     * @param string $pre
     * @param string $link
     * @param $words
     * @return string
     */
    private function restWord($pre='',$link='',$words)
    {
        if(is_array($words))
        {
            $item = '';
            foreach($words as $key=>$val)
            {
                $val = trim($val);
                if($val)
                {
                    $item .=' '.$val;
                }
            }
            $this->restWord($pre,$link,$item);
        }
        else
        {
            $this->resetRes = '';
            $words = explode(' ',$words);
            foreach($words as $ke=>$va)
            {
                $va = trim($va);
                if($va)
                {
                    $this->resetRes .= ' '.$link.' '.$pre.$va;
                }
            }
            $this->resetRes = trim ($this->resetRes);
            $this->resetRes = trim($this->resetRes,$link);
            $this->resetRes = trim ($this->resetRes);
        }

        return $this->resetRes;
    }

    /**
     * 重置所有选项
     */
    public function init()
    {
        if(!empty($this->type))
        {
            foreach($this->type as $key=>$val)
            {
                $this->$key = '';
            }
        }

        $this->type = array();
    }

    /**可以自定义注册数组，
     * 如果传入注册数组，则按照参数对query进行拼接
     * 不建议如此，应按照twitter官方规定格式，否则会有难以预测的错误
     * @param $register
     */
    public function buildQuery(array $register=array())
    {
        $register =!empty($register) ? $register :$this->type;

        foreach($register as $key=>$val)
        {
            $this->query .= ' '.$val;
        }

        $this->query =trim($this->query);

        if($this->needQ)
        $this->query = '?q='.$this->query;
    }

    /**
     * @return array
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->query;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        if($this->query == '')
        {
            $this->buildQuery();
        }

        return $this->query;
    }

    /**
     * @param boolean $needQ
     */
    public function setNeedQ($needQ)
    {
        $this->needQ = $needQ;
    }
}