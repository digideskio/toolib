<?php
/*
 *  This file is part of PHPLibs <http://phplibs.kmfa.net/>.
 *
 *  Copyright (c) 2010 < squarious at gmail dot com > .
 *
 *  PHPLibs is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  PHPLibs is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with PHPLibs.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


use toolib\EventDispatcher;
use toolib\Event;

require_once __DIR__ .  '/path.inc.php';

class EventDispatcherTest extends PHPUnit_Framework_TestCase
{
	public $called_listener = array();

	public function consumer1($event)
	{
		$this->called_listener[] = array(
            'func' =>__FUNCTION__,
            'event' => $event
		);
	}

	public function consumer2($event)
	{
		$this->called_listener[] = array(
            'func' =>__FUNCTION__,
            'event' => $event
		);
	}

	public function consumer_final($event)
	{
		$this->called_listener[] = array(
            'func' =>__FUNCTION__,
            'event' => $event
		);
		return true;
	}

	public function appendDot($event)
	{
		$this->called_listener[] = array(
            'func' =>__FUNCTION__,
            'event' => $event
		);

		$event->filtered_value .= '.';
	}

	public function appendDash($event)
	{
		$this->called_listener[] = array(
            'func' =>__FUNCTION__,
            'event' => $event
		);

		$event->filtered_value .= '-';
	}

	public function testCreateDispatcher()
	{
		// Emptry constructor
		$d = new EventDispatcher();
		$this->assertEquals(array(), $d->getListeners(NULL));
		$this->assertNull($d->getListeners('unknown'));
		$this->assertEquals(array(), $d->getEvents());

		// Construct and declare
		$d = new EventDispatcher(array('event1', 'event2', 'event3'));
		$this->assertEquals(array(), $d->getListeners(NULL));
		$this->assertNull($d->getListeners('unknown'));
		$this->assertEquals(array(), $d->getListeners('event1'));
		$this->assertEquals(array('event1', 'event2', 'event3'), $d->getEvents());
	}

	/**
	 * @depends testCreateDispatcher
	 */
	public function testHasEvent()
	{
		$d = new EventDispatcher(array('event1', 'event2', 'event3'));
		$this->assertFalse($d->hasEvent('unknown'));
		$this->assertFalse($d->hasEvent(NULL));
		$this->assertTrue($d->hasEvent('event1'));
		$this->assertTrue($d->hasEvent('event2'));
		$this->assertTrue($d->hasEvent('event2'));
	}

	/**
	 * @depends testHasEvent
	 */
	public function testDeclareEvent()
	{
		$d = new EventDispatcher();
		$this->assertFalse($d->hasEvent('unknown'));
		$this->assertFalse($d->hasEvent(NULL));
		$this->assertEquals(array(), $d->getEvents());

		$d->declareEvent('event1');
		$this->assertEquals(array('event1'), $d->getEvents());
		$d->declareEvent('event2');
		$this->assertEquals(array('event1', 'event2'), $d->getEvents());
		$d->declareEvent('event3');
		$this->assertEquals(array('event1', 'event2', 'event3'), $d->getEvents());
	}

	/**
	 * @depends testCreateDispatcher
	 * @depends testHasEvent
	 */
	public function testConnectListeners()
	{
		$d = new EventDispatcher(
		array('event1', 'event2', 'group.event1', 'group.event2')
		);

		// Connect the first listener
		$this->assertTrue($d->connect('event1', array($this, 'consumer1')));
		$this->assertEquals(array(), $d->getListeners(NULL));
		$this->assertEquals(array(array($this, 'consumer1')), $d->getListeners('event1'));
		$this->assertEquals(array(), $d->getListeners('event2'));
		$this->assertNull($d->getListeners('unknown'));

		// Try to reconnect the same listener
		$this->assertFalse($d->connect('event1', array($this, 'consumer1')));
		$this->assertEquals($d->getListeners('event1'),
		array(array($this, 'consumer1')));

		// Connect another listener
		$this->assertTrue($d->connect('event1', array($this, 'consumer2')));
		$this->assertEquals(array(), $d->getListeners(NULL));
		$this->assertEquals($d->getListeners('event1'),
		array(array($this, 'consumer1'), array($this, 'consumer2')) );
		$this->assertEquals(array(), $d->getListeners('event2'));

		// Connect listener on all events (NULL)
		$this->assertTrue($d->connect(NULL, array($this, 'consumer2')));
		$this->assertEquals($d->getListeners(NULL),
		array(array($this, 'consumer2')) );
		$this->assertEquals($d->getListeners('event1'),
		array(array($this, 'consumer1'), array($this, 'consumer2')) );
		$this->assertEquals(array(), $d->getListeners('event2'));

		// Re-Connect listener on all events (NULL)
		$this->assertFalse($d->connect(NULL, array($this, 'consumer2')));
		$this->assertEquals($d->getListeners(NULL),
		array(array($this, 'consumer2')) );
	}

	/**
	 * @depends testConnectListeners
	 */
	public function testDisconnectListeners()
	{
		$d = new EventDispatcher(
		array('event1', 'event2', 'group.event1', 'group.event2')
		);
		$d->connect('event1', array($this, 'consumer1'));
		$d->connect('event1', array($this, 'consumer2'));
		$d->connect(NULL, array($this, 'consumer1'));

		// Check connections
		$this->assertEquals($d->getListeners('event1'),
		array(array($this, 'consumer1'), array($this, 'consumer2')) );
		$this->assertEquals($d->getListeners(NULL),
		array(array($this, 'consumer1')) );

		// Disconnect event1 listener1 (TRUE)
		$this->assertTrue($d->disconnect('event1', array($this, 'consumer1')));
		$this->assertEquals($d->getListeners('event1'),
		array(array($this, 'consumer2')) );
		$this->assertEquals($d->getListeners(NULL),
		array(array($this, 'consumer1')) );

		// Disconnect again event1 listener1 (FALSE)
		$this->assertFalse($d->disconnect('event1', array($this, 'consumer1')));
		$this->assertEquals($d->getListeners('event1'),
		array(array($this, 'consumer2')) );
		$this->assertEquals($d->getListeners(NULL),
		array(array($this, 'consumer1')) );

		// Disconnect event1 listener2 (TRUE)
		$this->assertTrue($d->disconnect('event1', array($this, 'consumer2')));
		$this->assertEquals($d->getListeners('event1'),
		array() );
		$this->assertEquals($d->getListeners(NULL),
		array(array($this, 'consumer1')) );

		// Disconnect ANY listener1 (TRUE)
		$this->assertTrue($d->disconnect(NULL, array($this, 'consumer1')));
		$this->assertEquals($d->getListeners('event1'),
		array() );
		$this->assertEquals(array(), $d->getListeners(NULL));

		// Disconnect again ANY listener1 (TRUE)
		$this->assertFalse($d->disconnect(NULL, array($this, 'consumer1')));
		$this->assertEquals($d->getListeners('event1'),
		array() );
		$this->assertEquals(array(), $d->getListeners(NULL));
	}

	public function testHasListener()
	{
		$d = new EventDispatcher(
		array('event1', 'event2', 'group.event1', 'group.event2')
		);
		$d->connect('event1', array($this, 'consumer1'));
		$d->connect('event1', array($this, 'consumer2'));
		$d->connect(NULL, array($this, 'consumer1'));

		$this->assertTrue($d->hasListener('event1', array($this, 'consumer1')));
		$this->assertTrue($d->hasListener('event1', array($this, 'consumer2')));
		$this->assertTrue($d->hasListener(NULL, array($this, 'consumer1')));

		$this->assertFalse($d->hasListener(NULL, array($this, 'unknown')));
		$this->assertFalse($d->hasListener('unknown', array($this, 'unknown')));
		$this->assertFalse($d->hasListener('event1', array($this, 'unknown')));
	}

	public function testNotify()
	{
		$d = new EventDispatcher(
		array('event1', 'event2', 'group.event1', 'group.event2')
		);
		$d->connect('event1', array($this, 'consumer1'));
		$d->connect('event1', array($this, 'consumer2'));


		// Notify with no listeners
		$s = $d->notify('event2');
		$this->assertType('toolib\Event', $s);
		$this->assertFalse($s->processed);
		$this->assertEquals($s->name, 'event2');
		$this->assertEquals($s->type, 'notify');
		$this->assertEquals($s->filtered_value, NULL);
		$this->assertEquals($s->arguments, array());

		// Notify with global listener
		$d->connect(NULL, array($this, 'consumer1'));
		$s = $d->notify('group.event1');
		$this->assertType('toolib\Event', $s);
		$this->assertTrue($s->processed);
		$this->assertEquals($s->name, 'group.event1');
		$this->assertEquals($s->type, 'notify');
		$this->assertEquals($s->filtered_value, NULL);
		$this->assertEquals($s->arguments, array());

		// Notify with callers
		$this->called_listener = array();
		$s = $d->notify('event1', array('test', 'keke', '123' => '456'));
		$this->assertEquals($this->called_listener[0]['func'], 'consumer1');
		$this->assertEquals($this->called_listener[1]['func'], 'consumer2');
		$this->assertEquals($this->called_listener[2]['func'], 'consumer1');
		foreach($this->called_listener as $l) {
			$s = $l['event'];
			$this->assertType('toolib\Event', $s);
			$this->assertTrue($s->processed);
			$this->assertEquals($s->name, 'event1');
			$this->assertEquals($s->type, 'notify');
			$this->assertEquals($s->filtered_value, NULL);
			$this->assertEquals($s->arguments, array('test', 'keke', '123' => '456'));
		}
	}

	public function testNotifyUntil()
	{
		$d = new EventDispatcher(
		array('event1', 'event2', 'group.event1', 'group.event2')
		);
		$d->connect('event1', array($this, 'consumer1'));
		$d->connect('event1', array($this, 'consumer2'));

		// Notify with no listeners
		$s = $d->notifyUntil('event2');
		$this->assertType('toolib\Event', $s);
		$this->assertFalse($s->processed);
		$this->assertEquals($s->name, 'event2');
		$this->assertEquals($s->type, 'notifyUntil');
		$this->assertEquals($s->filtered_value, NULL);
		$this->assertEquals($s->arguments, array());

		// Notify with non-consuming listeners
		$s = $d->notifyUntil('event1');
		$this->assertType('toolib\Event', $s);
		$this->assertFalse($s->processed);
		$this->assertEquals($s->name, 'event1');
		$this->assertEquals($s->type, 'notifyUntil');
		$this->assertEquals($s->filtered_value, NULL);
		$this->assertEquals($s->arguments, array());

		// Notify with consuming listeners
		$d->connect('event2', array($this, 'consumer1'));
		$d->connect('event2', array($this, 'consumer_final'));
		$d->connect('event2', array($this, 'consumer2'));
		$this->called_listener = array();
		$s = $d->notifyUntil('event2');
		$this->assertType('toolib\Event', $s);
		$this->assertTrue($s->processed);
		$this->assertEquals($s->name, 'event2');
		$this->assertEquals($s->type, 'notifyUntil');
		$this->assertEquals($s->filtered_value, NULL);
		$this->assertEquals($s->arguments, array());
		$this->assertEquals(count($this->called_listener), 2);
	}


	/**
	 * @depends testNotify
	 **/
	public function testFilter()
	{
		$d = new EventDispatcher(
		array('event1', 'event2', 'group.event1', 'group.event2')
		);
		$d->connect('event1', array($this, 'appendDash'));
		$d->connect('event1', array($this, 'appendDot'));

		// Filter with no listeners
		$value = 'passed variable';
		$s = $d->filter('event2', $value);
		$this->assertType('toolib\Event', $s);
		$this->assertFalse($s->processed);
		$this->assertEquals($s->name, 'event2');
		$this->assertEquals($s->type, 'filter');
		$this->assertEquals($s->filtered_value, 'passed variable');
		$this->assertEquals($s->arguments, array());

		// Filter with global listener
		$d->connect(NULL, array($this, 'consumer1'));
		$s = $d->filter('group.event1', $value);
		$this->assertType('toolib\Event', $s);
		$this->assertTrue($s->processed);
		$this->assertEquals($s->name, 'group.event1');
		$this->assertEquals($s->type, 'filter');
		$this->assertEquals($s->filtered_value, 'passed variable');
		$this->assertEquals($s->arguments, array());

		// Notify with callers
		$this->called_listener = array();
		$value = 'big sp';
		$s = $d->filter('event1', $value, array('test', 'keke', '123' => '456'));
		$this->assertEquals($this->called_listener[0]['func'], 'appendDash');
		$this->assertEquals($this->called_listener[1]['func'], 'appendDot');
		$this->assertEquals($this->called_listener[2]['func'], 'consumer1');
		$this->assertEquals($this->called_listener[0]['event']->filtered_value, 'big sp-.');
		$this->assertEquals($this->called_listener[1]['event']->filtered_value, 'big sp-.');
		$this->assertEquals($this->called_listener[2]['event']->filtered_value, 'big sp-.');
		$this->assertEquals($value, 'big sp-.');
		foreach($this->called_listener as $l) {
			$s = $l['event'];
			$this->assertType('toolib\Event', $s);
			$this->assertTrue($s->processed);
			$this->assertEquals($s->name, 'event1');
			$this->assertEquals($s->type, 'filter');
			$this->assertEquals($s->arguments, array('test', 'keke', '123' => '456'));
		}
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testNotifyUnknown()
	{
		$d = new EventDispatcher();

		$d->notify('unknown');
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testNotifyUntilUnknown()
	{
		$d = new EventDispatcher();

		$d->notifyUntil('unknown');
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testFilterUnknown()
	{
		$d = new EventDispatcher();

		$value = 'tst';
		$d->filter('unknown', $value);
	}
}
