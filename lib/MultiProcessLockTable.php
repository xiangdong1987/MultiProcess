<?php
/**
 * Created by PhpStorm.
 * User: xiangdong
 * Date: 2017/6/26
 * Time: 下午6:09
 */

$son_work = [];
$table = new swoole_table(1024);
$lock = new swoole_lock(SWOOLE_MUTEX);
$table->column('list', swoole_table::TYPE_STRING, 64);
$table->create();
$table->set('free_job', ['list' => '1,2,3,4,6,7,8,9,10']);
for ($i = 0; $i < 3; $i++) {
    $process = new swoole_process('sonWorker', false, false);
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
function getFreeJob()
{
    global $table;
    global $lock;
    $lock->lock();
    $list = $table->get('free_job');
    if ($list['list']) {
        return explode(',', $list['list']);
    } else {
        return [];
    }
}

function sonWorker(swoole_process $worker)
{
    global $lock;
    global $table;
    while (1) {
        $ids = getFreeJob();
        if (!$ids) {
            $lock->unlock();
            break;
        }
        $id = array_pop($ids);
        $table->set('free_job', ['list' => implode(',', $ids)]);
        $lock->unlock();
        //解锁执行任务
        var_dump('worker ' . $worker->pid . ' run task ' . $id);
    }
    $worker->exit(0);
}

