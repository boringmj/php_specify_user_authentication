<?php

class Main
{

    public $return="";
    public $domain="";

    function __construct()
    {
        if($this->check())
        {
            $this->verify();
        }
    }

    function check()
    {
        if(!function_exists('session_start'))
        {
            $this->return="系统错误 -1<br>当前环境不支持SESSION";
            return 0;
        }
        if(!class_exists('PDO'))
        {
            $this->return="系统错误 -1<br>当前环境不支持PDO";
            return 0;
        }
        if(!is_writable('data')||!is_dir('data'))
        {
            $this->return='系统错误 -1<br>未知的错误101';
            return 0;
        }
        session_start();
        return 1;
    }

    function verify()
    {
        //这里涉及到一个安全问题,请自行屏蔽通过web访问data目录
        if(is_file('data/token.data')&&is_file('data/ip.data'))
        {
            //已经存在用户完成鉴权
            if(!empty($_SESSION['token'])&&$_SESSION['token']==file_get_contents('data/token.data'))
            {
                if($_SERVER['REMOTE_ADDR']==file_get_contents('data/ip.data'))
                {
                    echo '很高兴您可以看见此内容,您看见此内容代表您已经通过验证';
                }
                else
                {
                    $this->return='访问被拒绝 403<br>检测到疑似<a href="">CSRF攻击</a>';
                    return 0;
                }
            }
            else
            {
                $this->return='访问被拒绝 403 <br>您无法继续访问该页';
                return 0;
            }
        }
        else
        {
            //还没有用户完成鉴权
            if(empty($_SESSION['verify'])||empty($_GET['verify']))
            {
                //返回鉴权页
                $token=$this->randString(36);
                $_SESSION['verify']=$token;
                $this->return="<script>".$this->randScript($token)."</script>";
            }
            else
            {
                //用户尝试鉴权
                if($_SESSION['verify']==$_GET['verify'])
                {
                    $_SESSION['token']=$_SESSION['verify'];
                    file_put_contents('data/token.data',$_SESSION['verify']);
                    file_put_contents('data/ip.data',$_SERVER['REMOTE_ADDR']);
                    header("Location:http://{$this->domain}");
                }
                else
                {
                    $this->return='访问被拒绝 403 <br>用户鉴权失败';
                    return 0;
                }
            }
        }
    }

    function randScript($string)
    {
        $string_array_string=array();
        for($len=0;$len<mb_strlen($string);$len++)
        {
            $string_array_string[]=array(rand(0,2),$string[$len]);
        }
        $string_variable=$this->randString(6);  //为javascript定义一个随机的存储变量
        $string_count=0;  //用来计次,主要目的是防止函数或变量名重复
        $string_return="";  //为最终的javascript程序提供一个变量进行存储
        $string_array_return=array();  //用来存储遗留
        foreach($string_array_string as $value_array)
        {
            ++$string_count;  //我>>听说<< ++i比i++效率高一些
            $temp_rand=mt_rand(0,1);  //mt_rand()效率远高于rand()
            $variable_name=$this->randString(6).$string_count;  //因为下面会用到,直接放这里了
            if($temp_rand)
            {
                //处理之前遗留和这的次字符组
                foreach($string_array_return as $value_temp_array)
                {
                    $temp_string="";
                    if($value_temp_array[0]==1)
                        $temp_string.="+{$value_temp_array[1]}";
                    else if($value_temp_array[0]==2)
                        $temp_string.="+{$value_temp_array[1]}()";
                    else
                        $temp_string.="+\"{$value_temp_array[1]}\"";
                    if(!empty($temp_string))
                    {
                        $string_return.="{$string_variable}={$string_variable}{$temp_string};";
                        $string_array_return=array();
                    }
                }
                if($value_array[0]==1)
                    $string_return.="var {$variable_name}=\"{$value_array[1]}\";{$string_variable}+={$variable_name};";
                else if($value_array[0]==2)
                    $string_return.="var {$variable_name}=function(){return \"{$value_array[1]}\";};{$string_variable}+={$variable_name}();";
                else
                    $string_return.="{$string_variable}+=\"{$value_array[1]}\";";
            }
            else
            {
                //遗留至以后处理
                if($value_array[0]==1)
                    $string_return.="var {$variable_name}=\"{$value_array[1]}\";";
                else if($value_array[0]==2)
                    $string_return.="var {$variable_name}=function(){return \"{$value_array[1]}\";};";
                else
                    $variable_name=$value_array[1];
                //上面您可以自定义多种函数定义,变量甚至是常量的方法
                $string_array_return[]=array($value_array[0],$variable_name);
            }
        }
        //因为最后还可能存在遗留,所以也需要处理遗留
        foreach($string_array_return as $value_temp_array)
        {
            $temp_string="";
            if($value_temp_array[0]==1)
                $temp_string.="+{$value_temp_array[1]}";
            else if($value_temp_array[0]==2)
                $temp_string.="+{$value_temp_array[1]}()";
            else
                $temp_string.="+\"{$value_temp_array[1]}\"";
            if(!empty($temp_string))
            {
                $string_return.="{$string_variable}={$string_variable}{$temp_string};";
                $string_array_return=array();
            }
        }
        $string_return="var {$string_variable}=\"\";{$string_return}window.location.href=\"http://{$this->domain}?verify=\"+{$string_variable};";
        return $string_return;
    }

    function randString($length)
    {
        $chars="LilIoOuUVvQPqpbd";
        mt_srand(10000000*(double)microtime());
        for ($i=0,$str='',$lc=strlen($chars)-1;$i<$length;$i++)
        {
            $str.=$chars[mt_rand(0,$lc)];
        }
        return $str;
    }
}

?>