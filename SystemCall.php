<?php

/**
 * A callable wrapper
 */
class SystemCall 
{
    /**
     *
     * @var callable $callback
     */
    protected $callback;

    /**
     * 
     * @param callable $callback
     */
    public function __construct(callable $callback) 
    {
        $this->callback = $callback;
    }

    /**
     * 
     * @param Task $task
     * @param Scheduler $scheduler
     * @return type
     */
    public function __invoke(Task $task, Scheduler $scheduler) 
    {
        $callback = $this->callback; // Can't call it directly in PHP :/
        return $callback($task, $scheduler);
    }
}
