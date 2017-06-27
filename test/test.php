<?php
/**
 * Created by PhpStorm.
 * User: xiangdong
 * Date: 2017/6/22
 * Time: 下午3:51
 */
require_once "A.php";
require_once "B.php";
require_once "../lib/MultiProcess.php";
require_once "../lib/MultiProcessLockTable.php";
$setting = [
    'maxProcess' => 3
];
$multi = new MultiProcessLockTable($setting);
$data[1]['obj'] = new A();
$data[1]['method'] = 'printA';
$data[1]['params'] = ['X'];
$data[2]['obj'] = new B();
$data[2]['method'] = 'printB';
$data[2]['params'] = ['Y'];
$data[3]['obj'] = new B();
$data[3]['method'] = 'printNull';
$data[3]['params'] = [];
$multi->setJobs($data);
$multi->start();