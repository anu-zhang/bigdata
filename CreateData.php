<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Model\EclientStatistics;
use Log;
use App\Model\CompanyUserBind;

class CreateData extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'create-data';

    /**
     * 命令描述
     * @var string
     */
    protected $description = '生成随机数';


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
//        $this->beginCreate();
        $this->beginCreateBigData();

    }

    public static $max = 1000 * 10000;//单线程最大一千万 16min大约

    public function beginCreate()
    {
        $timebegin = time();
        $i = 1;
        while ($i <= self::$max) {
            $rand = md5(mt_rand(0, 10000000000));//100亿
            Log::info($rand, ['rand number ' . str_pad($i, strlen(self::$max), "0", STR_PAD_LEFT)]);
            if ($i % 10000 === 0) echo ($i / 10000) . "\n";
            $i++;

        }
        $timeend = time();
        $timeall = $timeend - $timebegin;

        echo 'time:'.$timeall;
    }

    public static $maxBig = 10000 * 10000;//多线程最小1亿数据
    public static $processNum = 3;

    public function beginCreateBigData()
    {
        $timebegin = time();

        for ($i = 1; $i <= self::$processNum; $i++) {
            $ppid = posix_getpid();
            $pid = pcntl_fork();
            if ($pid == -1) {
                die(' pid == -1');
            } elseif ($pid > 0) { // master进程
                pcntl_wait($status, WNOHANG);
            } else {
                $cpid = posix_getpid();
                $num = 1;
                while ($num <= (self::$max / self::$processNum)) {
                    $rand = md5(mt_rand(0, 10000000000));//100亿
                    Log::info($rand, ['rand number ' . str_pad($num, strlen(self::$max), "0", STR_PAD_LEFT)]);
                    if ($num % 10000 === 0) echo 'i:'.$i.' ppid:'.$ppid .' pid: '.$cpid.' num:'. ($num / 10000) . "\n";
                    $num++;

                }
                $timeend = time();
                $timeall = $timeend - $timebegin;
                echo 'time:'.$timeall."\n";
                exit;
            }
        }

        //在这里我们等待10秒，不然子进程还没执行完，主进程就退出了，看不出效果
        sleep(10000);
    }

    public function beginCreateBigData2()
    {
//        多进程
//
//目的：一个master fork多个worker
//现象：所有worker的ppid父进程ID为当前master的pid
//
//
//master控制worker
//
//目的：master通知worker，worker接收来自master的消息
//
//
//master接收信号
//
//目的：master接收并自定义处理来自终端的信号

//            $pid = pcntl_fork(3); // pcntl_fork 的返回值是一个int值

//        var_dump($pid);//pid > 0 -> master ->这个 pid66614 fork 的worker的pid,pid = 0,-> workder,
//        $pid2 = pcntl_fork(); // pcntl_fork 的返回值是一个int值
//        var_dump($pid2);

        // 如果$pid=-1 fork进程失败
        // 如果$pid=0 当前的上下文环境为worker
        // 如果$pid>0 当前的上下文环境为master，这个pid就是fork的worker的pid
//        switch ($pid) {
//            case -1:
//                // fatal error 致命错误 所有进程crash掉
//                break;
//
//            case 0:
////                sleep(50);
//                // worker context
//                exit; // 这里exit掉，避免worker继续执行下面的代码而造成一些问题
//                break;
//
//            default:
//                // master context 防止worker成为僵尸进程
//                pcntl_wait($status); // pcntl_wait会阻塞，例如直到一个子进程exit
//                // 或者 pcntl_waitpid($pid, $status, WNOHANG); // WNOHANG:即使没有子进程exit，也会立即返回
//                break;
//        }

        $processes = 5;

        $num = range(1, 10);
        $blocks = array();

        foreach ($num as $i) {
            $blocks [($i % $processes)] [] = $i;
        }
//        var_dump($blocks);die;
        foreach ($blocks as $blockNum => $block) {//$blockNum 组名 0 1 2 3 4,$block 每个块
            $pid = pcntl_fork();

            if ($pid == -1) {
                // 错误处理：创建子进程失败时返回-1.
                die ('could not fork');
            } else if ($pid) {
                // 父进程逻辑
                // 等待子进程中断，防止子进程成为僵尸进程。
                // WNOHANG 为非阻塞进程，具体请查阅pcntl_wait PHP官方文档
                var_dump($pid);
                pcntl_wait($status, WNOHANG);
            } else {
                // 子进程逻辑
                foreach ($block as $i) {
                    echo "父进程ID: ", posix_getppid(), " 进程ID : ", posix_getpid(), "  \r\n";
                    echo "I'm blockNum {$blockNum},I'm  printing:{$i}\n";
                    sleep(1);
                }

                // 为避免僵尸进程，当子进程结束后，手动杀死进程
//                if (function_exists ( "posix_kill" )) {
//                    posix_kill ( getmypid (), SIGTERM );
//                } else {
//                    system ( 'kill -9' . getmypid () );
//                }
                exit ();
            }
        }
        //在这里我们等待10秒，不然子进程还没执行完，主进程就退出了，看不出效果
        sleep(10);
    }
}
