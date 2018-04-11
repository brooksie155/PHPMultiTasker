<?php 

class Task 
{
    /**
     * @var int $taskId
     */
    protected $taskId;

    /**
     * @var Generator $coroutine
     */
    protected $coroutine;
    
    /**
     * @var mixed $sendValue
     */
    protected $sendValue = null;
    
    /**
     * @var boolean $beforeFirstYield
     */
    protected $beforeFirstYield = true;

    /**
     * 
     * @param int $taskId
     * @param Generator $coroutine
     */
    public function __construct(int $taskId, Generator $coroutine) 
    {
        $this->taskId = $taskId;
        $this->coroutine = $coroutine;
    }

    /**
     * 
     * @return int
     */
    public function getTaskId() : int
    {
        return $this->taskId;
    }

    /**
     * @var mixed $sendValue
     */
    public function setSendValue($sendValue) 
    {
        $this->sendValue = $sendValue;
    }

    /**
     * Run Task
     * 
     * Use boolean to keep track of whether this is the first request, and 
     * return current() from coroutine if true so as to return the first yield response. 
     * This is necessary as send() will invoke rewind() which gets us to the first yield
     * however the value will not be yielded
     * 
     * @return mixed
     */
    public function run()
    {
        if ($this->beforeFirstYield) {
            $this->beforeFirstYield = false;
            return $this->coroutine->current(); 
        } else {
            $retval = $this->coroutine->send($this->sendValue); 
            $this->sendValue = null;
            return $retval;
        }
    }

    /**
     * True if coroutine is complete
     * 
     * @return boolean
     */
    public function isFinished() : bool
    {
        return !$this->coroutine->valid();
    }
}

