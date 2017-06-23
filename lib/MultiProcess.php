<?php

/**
 * Created by PhpStorm.
 * User: xiangdong
 * Date: 2017/6/22
 * Time: 下午2:49
 */
class MultiProcess
{
    private $master;
    private $jobs;
    private $un_do_jobs;
    private $son_workers;
    private $maxProcess;
    private $masterPid;

    function __construct($setting)
    {
        if (!isset($setting['maxProcess'])) {
            exit('Please set maxProcess!');
        }
        $this->maxProcess = $setting['maxProcess'];
        $this->master = new swoole_process([$this, 'masterStart'], false, false);
        $this->master->useQueue();

    }

    public function setJobs($jobs)
    {
        $this->jobs = $jobs;
    }

    public function start()
    {
        $this->masterPid = $this->master->start();
        while (1) {
            if ($ret = swoole_process::wait(false)) {
                $pid = $ret['pid'];
                echo "Master Worker Exit, PID=" . $pid . PHP_EOL;
                exit;
            }
        }
    }

    function masterStart(swoole_process $worker)
    {
        while (1) {
            if (empty($this->jobs) && count($this->son_workers) == 0) {
                echo "master done" . PHP_EOL;
                $worker->exit(0);
            }
            if (count($this->son_workers) < $this->maxProcess && $this->jobs) {
                foreach ($this->jobs as $job_id => $content) {
                    $this->un_do_jobs[$job_id] = $content;
                    unset($this->jobs[$job_id]);
                    break;
                }
                $process = new swoole_process([$this, 'sonWorker'], false, false);
                $process->useQueue();
                $pid = $process->start();
                $this->son_workers[$pid] = $process;
                //添加数据
                $worker->push("$job_id");
            } else {
                if ($ret = $worker::wait(false)) {
                    $pid = $ret['pid'];
                    unset($this->son_workers[$pid]);
                    echo "sonWorker Exit, PID=" . $pid . PHP_EOL;
                }
            }
        }
    }

    function sonWorker(swoole_process $worker)
    {
        //消费数据
        $job_id = $worker->pop();
        $obj = $this->un_do_jobs[$job_id]['obj'];
        $method = $this->un_do_jobs[$job_id]['method'];
        $params = $this->un_do_jobs[$job_id]['params'];
        call_user_func_array([$obj, $method], $params);
        echo "sonWorker is running,receive " . $job_id . " from master" . PHP_EOL;
        $worker->exit(0);
    }
}