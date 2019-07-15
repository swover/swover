<?php

namespace Swover\Tests\Utils;

use PHPUnit\Framework\TestCase;
use Swover\Contracts\Events\MasterStart;
use Swover\Contracts\Events\TaskStart;
use Swover\Contracts\Events\WorkerStart;
use Swover\Utils\Event;

class EventTest extends TestCase
{

    public function instanceProvider()
    {
        Event::setInstance(null);
        $instance = Event::getInstance();
        $instance->clear();
        $this->assertEmpty($instance);
        return [[$instance]];
    }

    /**
     * @dataProvider instanceProvider
     * @param Event $instance
     */
    public function testRegister($instance)
    {
        $events = [
            '\Swover\Tests\Utils\TestMasterStart',
            [
                '\Swover\Tests\Utils\TestWorkerStartA',
                new TestWorkerStartB()
            ],
            new TestTaskStart()
        ];
        $bounds = $instance->register($events);
        $this->assertEquals(4, $bounds);
    }

    /**
     * @dataProvider instanceProvider
     * @param Event $instance
     */
    public function testRegisterNoArray($instance)
    {
        $events = '\Swover\Tests\Utils\TestMasterStart';
        $bounds = $instance->register($events);
        $this->assertEquals(1, $bounds);
    }

    /**
     * @dataProvider instanceProvider
     * @param Event $instance
     */
    public function testTrigger($instance)
    {
        $events = [
            new TestTaskStart()
        ];

        $instance->register($events);
        $instance->trigger(TaskStart::EVENT_TYPE, null, 300, 0, 'data');
        $this->expectOutputString('300:data');
    }

    /**
     * @dataProvider instanceProvider
     * @param Event $instance
     */
    public function testBind($instance)
    {
        $events = [
            new TestWorkerStartA()
        ];

        $instance->register($events);
        $instance->bind(new TestWorkerStartB());
        $instance->trigger(WorkerStart::EVENT_TYPE, null, 100);
        $this->expectOutputString('a100b100');
    }

    /**
     * @dataProvider instanceProvider
     * @param Event $instance
     */
    public function testBindInstance($instance)
    {
        $instance->bindInstance('special_event', 'special_alias', new TestWorkerStartB());
        $instance->trigger('special_event', null, 100);
        $this->expectOutputString('b100');
    }

    /**
     * @dataProvider instanceProvider
     * @param Event $instance
     */
    public function testBefore($instance)
    {
        $events = [
            new TestWorkerStartA()
        ];

        $instance->register($events);
        $instance->before(new TestWorkerStartB());
        $instance->trigger(WorkerStart::EVENT_TYPE, null, 200);
        $this->expectOutputString('b200a200');
    }

    /**
     * @dataProvider instanceProvider
     * @param Event $instance
     */
    public function testRemove($instance)
    {
        $events = [
            new TestWorkerStartA(),
            new TestWorkerStartB()
        ];

        $instance->register($events);
        $instance->remove(new TestWorkerStartA());
        $instance->trigger(WorkerStart::EVENT_TYPE, null, 400);
        $this->expectOutputString('b400');
    }

    /**
     * @dataProvider instanceProvider
     * @param Event $instance
     */
    public function testRemoveAlias($instance)
    {
        $events = [
            new TestWorkerStartA(),
            new TestWorkerStartB()
        ];

        $instance->register($events);
        $instance->removeAlias(WorkerStart::EVENT_TYPE, TestWorkerStartA::class);
        $instance->trigger(WorkerStart::EVENT_TYPE, null, 400);
        $this->expectOutputString('b400');
    }

    /**
     * @dataProvider instanceProvider
     * @param Event $instance
     */
    public function testClear($instance)
    {
        $events = [
            '\Swover\Tests\Utils\TestMasterStart',
            [
                '\Swover\Tests\Utils\TestWorkerStartA',
                new TestWorkerStartB()
            ],
            new TestTaskStart()
        ];

        $instance->register($events);
        $this->assertEquals(3, count($instance->instances));
        $instance->clear();
        $this->assertEquals(0, count($instance->instances));
    }

    /**
     * @dataProvider instanceProvider
     * @param Event $instance
     */
    public function testClosure($instance)
    {
        $instance->bindInstance(WorkerStart::EVENT_TYPE, 'aliasA', function ($worker_id) {
            echo 'closureA' . $worker_id;
        });
        $instance->bindInstance(WorkerStart::EVENT_TYPE, 'aliasB', function ($worker_id) {
            echo 'closureB' . $worker_id;
        });

        $instance->trigger(WorkerStart::EVENT_TYPE, 500);

        $this->expectOutputString('closureA500closureB500');
    }

    /**
     * @dataProvider instanceProvider
     * @param Event $instance
     */
    public function testRemoveClosure($instance)
    {
        $instance->bindInstance(WorkerStart::EVENT_TYPE, 'aliasA', function ($worker_id) {
            echo 'closureA' . $worker_id;
        });
        $instance->bindInstance(WorkerStart::EVENT_TYPE, 'aliasB', function ($worker_id) {
            echo 'closureB' . $worker_id;
        });

        $instance->removeAlias(WorkerStart::EVENT_TYPE, 'aliasB');
        $instance->trigger(WorkerStart::EVENT_TYPE, 600);
        $this->expectOutputString('closureA600');
    }

    /**
     * @dataProvider instanceProvider
     * @param Event $instance
     */
    public function testNoneClass($instance)
    {
        $count = $instance->bind('NoneClass');
        $this->assertEquals(0, $count);
    }

    /**
     * @dataProvider instanceProvider
     * @param Event $instance
     */
    public function testCanNotConstruct($instance)
    {
        $count = $instance->bind('\Swover\Tests\Utils\CanNotConstruct');
        $this->assertEquals(0, $count);
    }

    /**
     * @dataProvider instanceProvider
     * @param Event $instance
     */
    public function testHasParamConstruct($instance)
    {
        $count = $instance->bind('\Swover\Tests\Utils\HasParamConstruct');
        $this->assertEquals(0, $count);
    }
}

class CanNotConstruct
{
    private function __construct()
    {
    }
}

class HasParamConstruct
{
    public function __construct($name, $type)
    {
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
    public function trigger($server, $worker_id)
    {
        echo 'a' . $worker_id;
    }
}

class TestWorkerStartB implements WorkerStart
{
    public function trigger($server, $worker_id)
    {
        echo 'b' . $worker_id;
    }
}

class TestTaskStart implements TaskStart
{
    public function trigger($server, $task_id, $worker_id, $data)
    {
        echo $task_id . ':' . $data;
    }
}