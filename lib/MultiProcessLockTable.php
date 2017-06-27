<?php

/**
 * Created by PhpStorm.
 * User: xiangdong
 * Date: 2017/6/22
 * Time: 下午2:49
 */
class MultiProcessLockTable
{
    private $jobs;
    private $maxProcess;
    public $table;
    public $lock;

    function __construct($setting)
    {
        if (!isset($setting['maxProcess'])) {
            exit('Please set maxProcess!');
        }
        $this->maxProcess = $setting['maxProcess'];
        $this->table = new swoole_table(1024);
        $this->lock = new swoole_lock(SWOOLE_MUTEX);
        $this->table->column('list', swoole_table::TYPE_STRING, 64);
        $this->table->create();

    }

    public function setJobs($jobs)
    {
        $this->jobs = $jobs;
        foreach ($jobs as $key => $job) {
            $list[] = $key;
        }
        $this->table->set('free_job', ['list' => implode(',', $list)]);
    }

    public function start()
    {
        for ($i = 0; $i < $this->maxProcess; $i++) {
            $process = new swoole_process([$this, 'sonWorker'], false, false);
            $pid = $process->start();
            $son_work[$pid] = $process;
        }
        while ($son_work) {
            if ($ret = swoole_process::wait(false)) {
                $pid = $ret['pid'];
                unset($son_work[$pid]);
                echo "sonWorker Exit, PID=" . $pid . PHP_EOL;
            }
        }
    }

    function getFreeJob()
    {
        $this->lock->lock();
        $list = $this->table->get('free_job');
        if ($list['list']) {
            return explode(',', $list['list']);
        } else {
            return [];
        }
    }

    function sonWorker(swoole_process $worker)
    {
        while (1) {
            $ids = $this->getFreeJob();
            if (!$ids) {
                $this->lock->unlock();
                break;
            }
            $job_id = array_pop($ids);
            $this->table->set('free_job', ['list' => implode(',', $ids)]);
            $this->lock->unlock();
            //解锁执行任务
            $obj = $this->jobs[$job_id]['obj'];
            $method = $this->jobs[$job_id]['method'];
            $params = $this->jobs[$job_id]['params'];
            call_user_func_array([$obj, $method], $params);
            var_dump('worker ' . $worker->pid . ' run task ' . $job_id);
        }
        $worker->exit(0);
    }
}