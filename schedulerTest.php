<?php

require('Task.php');
require('Scheduler.php');
require('SystemCall.php');

function getTaskId() 
{
    
    return new SystemCall(
        function(Task $task, Scheduler $scheduler) 
        {
            $task->setSendValue($task->getTaskId());
            $scheduler->schedule($task);
        }
    );
}

function task($max) 
{
    $tid = (yield getTaskId()); // <-- here's the syscall!
    for ($i = 1; $i <= $max; ++$i) {
        echo "This is task $tid iteration $i.\n";
        yield;
    }
}

$scheduler = new Scheduler;

$scheduler->newTask(task(10));
$scheduler->newTask(task(5));

$scheduler->run();