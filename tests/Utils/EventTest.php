<?php

namespace Swover\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Swover\Contracts\Events\MasterStart;
use Swover\Contracts\Events\TaskStart;
use Swover\Contracts\Events\WorkerStart;
use Swover\Utils\Event;

class EventTest extends TestCase
{
    public function testRegister()
    {
        $events = [
            'master_start' => '\Swover\Tests\Utils\TestMasterStart',
            'worker_start' => [
                '\Swover\Tests\Utils\TestWorkerStartA',
                new TestWorkerStartB()
            ],
            'task_start' => new TestTaskStart()
        ];
        $bounds = Event::getInstance()->register($events);
        $this->assertEquals(4, $bounds);
    }

    public function testBind()
    {
        $events = [
            'worker_start' => new TestWorkerStartA()
        ];

        Event::getInstance()->remove('worker_start');

        Event::getInstance()->register($events);

        Event::getInstance()->bind('worker_start', new TestWorkerStartB());
        Event::getInstance()->trigger('worker_start', 100);
        $this->expectOutputString('a100b100');
    }

    public function testBefore()
    {
        $events = [
            'worker_start' => new TestWorkerStartA()
        ];

        Event::getInstance()->remove('worker_start');

        Event::getInstance()->register($events);

        Event::getInstance()->before('worker_start', new TestWorkerStartB());
        Event::getInstance()->trigger('worker_start', 100);
        $this->expectOutputString('b100a100');
    }

    public function testTrigger()
    {
        $events = [
            'task_start' => new TestTaskStart()
        ];

        Event::getInstance()->remove('task_start');

        Event::getInstance()->register($events);
        Event::getInstance()->trigger('task_start', 100,'data');
        $this->expectOutputString('100:data');
    }
}

class TestMasterStart implements MasterStart
{
    public function trigger($master_id)
    {
        echo $master_id;
    }
}

class TestWorkerStartA implements WorkerStart
{
    public function trigger($worker_id)
    {
        echo 'a'.$worker_id;
    }
}

class TestWorkerStartB implements WorkerStart
{
    public function trigger($worker_id)
    {
        echo 'b'.$worker_id;
    }
}

class TestTaskStart implements TaskStart
{
    public function trigger($task_id, $data)
    {
        echo $task_id.':'.$data;
    }
}