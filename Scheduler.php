<?php

class Scheduler 
{
    /**
     * @var int  $maxTaskId
     */
    protected $maxTaskId = 0;
    
    /**
     * @var array $taskMap
     */
    protected $taskMap = []; // taskId => task
    
    /**
     * @var SplQueue $taskQueue
     */
    protected $taskQueue;

    /**
     * 
     */
    public function __construct() 
    {
        $this->taskQueue = new SplQueue();
    }

    /**
     * 
     * @param Generator $coroutine
     * @return int
     */
    public function newTask(Generator $coroutine) : int 
    {
        $tid = ++$this->maxTaskId;
        $task = new Task($tid, $coroutine);
        $this->taskMap[$tid] = $task;
        $this->schedule($task);
        return $tid;
    }

    /**
     * 
     * @param int $tid
     * @return boolean
     */
    public function killTask(int $tid) : bool
    {
        if (!isset($this->taskMap[$tid])) {
            return false;
        }

        unset($this->taskMap[$tid]);

        // This is a bit ugly and could be optimized so it does not have to walk the queue,
        // but assuming that killing tasks is rather rare I won't bother with it now
        foreach ($this->taskQueue as $i => $task) {
            if ($task->getTaskId() === $tid) {
                unset($this->taskQueue[$i]);
                break;
            }
        }

        return true;
    }    
    
    /**
     * 
     * @param Task $task
     */
    public function schedule(Task $task) 
    {
        $this->taskQueue->enqueue($task);
    }

    /**
     * 
     */
    public function run() 
    {
        while (!$this->taskQueue->isEmpty()) {
            
            $task = $this->taskQueue->dequeue();
            $retval = $task->run();

            if ($retval instanceof SystemCall) {
                $retval($task, $this);
                continue;
            }            
            
            if ($task->isFinished()) {
                unset($this->taskMap[$task->getTaskId()]);
            } else {
                $this->schedule($task);
            }
        }
    }
}