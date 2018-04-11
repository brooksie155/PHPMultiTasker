<?php

require('Task.php');
require('Scheduler.php');
require('SystemCall.php');
require('WebServer.php');
require('CoroutineReturnValue.php');
require('CoSocket.php');

function stackedCoroutine(Generator $gen) 
{
    $stack = new SplStack;

    for (;;) {
        
        $value = $gen->current();

        if ($value instanceof Generator) {
            $stack->push($gen);
            $gen = $value;
            continue;
        }

        $isReturnValue = $value instanceof CoroutineReturnValue;
        
        if (!$gen->valid() || $isReturnValue) {
            if ($stack->isEmpty()) {
                return;
            }

            $gen = $stack->pop();
            $gen->send($isReturnValue ? $value->getValue() : NULL);
            continue;
        }

        $gen->send((yield $gen->key() => $value));
    }
}

function newTask(Generator $coroutine) 
{
    return new SystemCall(
        function(Task $task, Scheduler $scheduler) use ($coroutine) {
            $task->setSendValue($scheduler->newTask($coroutine));
            $scheduler->schedule($task);
        }
    );
}

function waitForRead($socket) 
{
    return new SystemCall(
        function(Task $task, WebServerScheduler $scheduler) use ($socket) {
            $scheduler->waitForRead($socket, $task);
        }
    );
}

function waitForWrite($socket) 
{
    return new SystemCall(
        function(Task $task, WebServerScheduler $scheduler) use ($socket) {
            $scheduler->waitForWrite($socket, $task);
        }
    );
}

function server($port) 
{
    echo "Starting server at port $port...\n";

    $socket = @stream_socket_server("tcp://localhost:$port", $errNo, $errStr);
    if (!$socket) throw new Exception($errStr, $errNo);

    stream_set_blocking($socket, 0);

    $socket = new CoSocket($socket);
    while (true) {
        yield newTask(
            handleClient((yield $socket->accept()))
        );
    }
}

function handleClient($socket) 
{
    $data = (yield $socket->read(8192));

    $msg = "Received following request:\n\n$data";
    $msgLength = strlen($msg);

    $response = <<<RES
HTTP/1.1 200 OK\r
Content-Type: text/plain\r
Content-Length: $msgLength\r
Connection: close\r
\r
$msg
RES;

    yield $socket->write($response);
    yield $socket->close();
}

$scheduler = new WebServerScheduler;
$scheduler->newTask(server(8000));
$scheduler->run();