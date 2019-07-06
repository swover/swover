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

        $instance = Event::getInstance();
        $instance->clear();
        $instance->register($events);

        $instance->bind(new TestWorkerStartB());
        $instance->trigger(WorkerStart::EVENT_TYPE, 100);
        $this->expectOutputString('a100b100');
    }

    public function testBefore()
    {
        $events = [
            'worker_start' => new TestWorkerStartA()
        ];

        $instance = Event::getInstance();
        $instance->clear();
        $instance->register($events);

        $instance->before(new TestWorkerStartB());
        $instance->trigger(WorkerStart::EVENT_TYPE, 200);
        $this->expectOutputString('b200a200');
    }

    public function testTrigger()
    {
        $events = [
            'task_start' => new TestTaskStart()
        ];

        $instance = Event::getInstance();
        $instance->register($events);
        $instance->trigger(TaskStart::EVENT_TYPE, 300,'data');
        $this->expectOutputString('300:data');
    }

    public function testRemove()
    {
        $events = [
            'worker_start' => [
                new TestWorkerStartA(),
                new TestWorkerStartB()
            ]
        ];
        $instance = Event::getInstance();
        $instance->register($events);

        $instance->remove(new TestWorkerStartA());

        $instance->trigger(WorkerStart::EVENT_TYPE, 400);
        $this->expectOutputString('b400');
    }

    public function testClear()
    {
        $events = [
            'master_start' => '\Swover\Tests\Utils\TestMasterStart',
            'worker_start' => [
                '\Swover\Tests\Utils\TestWorkerStartA',
                new TestWorkerStartB()
            ],
            'task_start' => new TestTaskStart()
        ];
        $instance = Event::getInstance();
        $instance->register($events);
        $this->assertEquals(3, count($instance->instances));
        $instance->clear();
        $this->assertEquals(0, count($instance->instances));
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