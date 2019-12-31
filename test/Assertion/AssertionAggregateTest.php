<?php

/**
 * @see       https://github.com/laminas/laminas-permissions-acl for the canonical source repository
 * @copyright https://github.com/laminas/laminas-permissions-acl/blob/master/COPYRIGHT.md
 * @license   https://github.com/laminas/laminas-permissions-acl/blob/master/LICENSE.md New BSD License
 */
namespace LaminasTest\Permissions\Acl\Assertion;

use InvalidArgumentException;
use Laminas\Permissions\Acl\Assertion\AssertionAggregate;

class AssertionAggregateTest extends \PHPUnit_Framework_TestCase
{
    protected $assertionAggregate;

    public function setUp()
    {
        $this->assertionAggregate = new AssertionAggregate();
    }

    public function testAddAssertion()
    {
        $assertion = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');
        $this->assertionAggregate->addAssertion($assertion);

        $this->assertAttributeEquals([
            $assertion
        ], 'assertions', $this->assertionAggregate);

        $aggregate = $this->assertionAggregate->addAssertion('other.assertion');
        $this->assertAttributeEquals([
            $assertion,
            'other.assertion'
        ], 'assertions', $this->assertionAggregate);

        // test fluent interface
        $this->assertSame($this->assertionAggregate, $aggregate);

        return clone $this->assertionAggregate;
    }

    public function testAddAssertions()
    {
        $assertions[] = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');

        $aggregate = $this->assertionAggregate->addAssertions($assertions);

        $this->assertAttributeEquals($assertions, 'assertions', $this->assertionAggregate);

        // test fluent interface
        $this->assertSame($this->assertionAggregate, $aggregate);
    }

    /**
     * @depends testAddAssertion
     */
    public function testClearAssertions(AssertionAggregate $assertionAggregate)
    {
        $this->assertAttributeCount(2, 'assertions', $assertionAggregate);

        $aggregate = $assertionAggregate->clearAssertions();

        $this->assertAttributeEmpty('assertions', $assertionAggregate);

        // test fluent interface
        $this->assertSame($assertionAggregate, $aggregate);
    }

    public function testDefaultModeValue()
    {
        $this->assertAttributeEquals(AssertionAggregate::MODE_ALL, 'mode', $this->assertionAggregate);
    }

    /**
     * @dataProvider getDataForTestSetMode
     */
    public function testSetMode($mode, $exception = false)
    {
        if ($exception) {
            $this->setExpectedException('\Laminas\Permissions\Acl\Exception\InvalidArgumentException');
            $this->assertionAggregate->setMode($mode);
        } else {
            $this->assertionAggregate->setMode($mode);
            $this->assertAttributeEquals($mode, 'mode', $this->assertionAggregate);
        }
    }

    public static function getDataForTestSetMode()
    {
        return [
            [
                AssertionAggregate::MODE_ALL
            ],
            [
                AssertionAggregate::MODE_AT_LEAST_ONE
            ],
            [
                'invalid mode',
                true
            ]
        ];
    }

    public function testManagerAccessors()
    {
        $manager = $this->getMockBuilder('Laminas\Permissions\Acl\Assertion\AssertionManager')
                        ->disableOriginalConstructor()
                        ->getMock();

        $aggregate = $this->assertionAggregate->setAssertionManager($manager);
        $this->assertAttributeEquals($manager, 'assertionManager', $this->assertionAggregate);
        $this->assertEquals($manager, $this->assertionAggregate->getAssertionManager());
        $this->assertSame($this->assertionAggregate, $aggregate);
    }

    public function testCallingAssertWillFetchAssertionFromManager()
    {
        $acl = $this->getMock('\Laminas\Permissions\Acl\Acl');
        $role = $this->getMock('\Laminas\Permissions\Acl\Role\GenericRole', [], [
            'test.role'
        ]);
        $resource = $this->getMock('\Laminas\Permissions\Acl\Resource\GenericResource', [], [
            'test.resource'
        ]);

        $assertion = $this->getMockForAbstractClass('Laminas\Permissions\Acl\Assertion\AssertionInterface');
        $assertion->expects($this->once())
            ->method('assert')
            ->will($this->returnValue(true));

        $manager = $this->getMockBuilder('Laminas\Permissions\Acl\Assertion\AssertionManager')
                        ->disableOriginalConstructor()
                        ->getMock();

        $manager->expects($this->once())
            ->method('get')
            ->with('assertion')
            ->will($this->returnValue($assertion));

        $this->assertionAggregate->setAssertionManager($manager);
        $this->assertionAggregate->addAssertion('assertion');

        $this->assertTrue($this->assertionAggregate->assert($acl, $role, $resource, 'privilege'));
    }

    public function testAssertThrowsAnExceptionWhenReferingToNonExistentAssertion()
    {
        $acl = $this->getMock('\Laminas\Permissions\Acl\Acl');
        $role = $this->getMock('\Laminas\Permissions\Acl\Role\GenericRole', [], [
            'test.role'
        ]);
        $resource = $this->getMock('\Laminas\Permissions\Acl\Resource\GenericResource', [], [
            'test.resource'
        ]);

        $manager = $this->getMockBuilder('Laminas\Permissions\Acl\Assertion\AssertionManager')
                        ->disableOriginalConstructor()
                        ->getMock();

        $manager->expects($this->once())
            ->method('get')
            ->with('assertion')
            ->will($this->throwException(new InvalidArgumentException()));

        $this->assertionAggregate->setAssertionManager($manager);

        $this->setExpectedException('Laminas\Permissions\Acl\Assertion\Exception\InvalidAssertionException');
        $this->assertionAggregate->addAssertion('assertion');
        $this->assertionAggregate->assert($acl, $role, $resource, 'privilege');
    }

    public function testAssertWithModeAll()
    {
        $acl = $this->getMock('\Laminas\Permissions\Acl\Acl');
        $role = $this->getMock('\Laminas\Permissions\Acl\Role\GenericRole', [], [
            'test.role'
        ]);
        $resource = $this->getMock('\Laminas\Permissions\Acl\Resource\GenericResource', [], [
            'test.resource'
        ]);

        $assertions[] = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');

        $assertions[0]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(true));
        $assertions[1]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(true));
        $assertions[2]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(true));

        foreach ($assertions as $assertion) {
            $this->assertionAggregate->addAssertion($assertion);
        }

        $this->assertTrue($this->assertionAggregate->assert($acl, $role, $resource, 'privilege'));
    }

    public function testAssertWithModeAtLeastOne()
    {
        $acl = $this->getMock('\Laminas\Permissions\Acl\Acl');
        $role = $this->getMock('\Laminas\Permissions\Acl\Role\GenericRole', [], [
            'test.role'
        ]);
        $resource = $this->getMock('\Laminas\Permissions\Acl\Resource\GenericResource', [], [
            'test.resource'
        ]);

        $assertions[] = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');

        $assertions[0]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(false));
        $assertions[1]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(false));
        $assertions[2]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(true));

        foreach ($assertions as $assertion) {
            $this->assertionAggregate->addAssertion($assertion);
        }

        $this->assertionAggregate->setMode(AssertionAggregate::MODE_AT_LEAST_ONE);
        $this->assertTrue($this->assertionAggregate->assert($acl, $role, $resource, 'privilege'));
    }

    public function testDoesNotAssertWithModeAll()
    {
        $acl = $this->getMock('\Laminas\Permissions\Acl\Acl');
        $role = $this->getMock('\Laminas\Permissions\Acl\Role\GenericRole', [
            'assert'
        ], [
            'test.role'
        ]);
        $resource = $this->getMock('\Laminas\Permissions\Acl\Resource\GenericResource', [
            'assert'
        ], [
            'test.resource'
        ]);

        $assertions[] = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');

        $assertions[0]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(true));
        $assertions[1]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(true));
        $assertions[2]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(false));

        foreach ($assertions as $assertion) {
            $this->assertionAggregate->addAssertion($assertion);
        }

        $this->assertFalse($this->assertionAggregate->assert($acl, $role, $resource, 'privilege'));
    }

    public function testDoesNotAssertWithModeAtLeastOne()
    {
        $acl = $this->getMock('\Laminas\Permissions\Acl\Acl');
        $role = $this->getMock('\Laminas\Permissions\Acl\Role\GenericRole', [
            'assert'
        ], [
            'test.role'
        ]);
        $resource = $this->getMock('\Laminas\Permissions\Acl\Resource\GenericResource', [
            'assert'
        ], [
            'test.resource'
        ]);

        $assertions[] = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');
        $assertions[] = $this->getMockForAbstractClass('\Laminas\Permissions\Acl\Assertion\AssertionInterface');

        $assertions[0]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(false));
        $assertions[1]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(false));
        $assertions[2]->expects($this->once())
            ->method('assert')
            ->with($acl, $role, $resource, 'privilege')
            ->will($this->returnValue(false));

        foreach ($assertions as $assertion) {
            $this->assertionAggregate->addAssertion($assertion);
        }

        $this->assertionAggregate->setMode(AssertionAggregate::MODE_AT_LEAST_ONE);
        $this->assertFalse($this->assertionAggregate->assert($acl, $role, $resource, 'privilege'));
    }

    public function testAssertThrowsAnExceptionWhenNoAssertionIsAggregated()
    {
        $acl = $this->getMock('\Laminas\Permissions\Acl\Acl');
        $role = $this->getMock('\Laminas\Permissions\Acl\Role\GenericRole', [
            'assert'
        ], [
            'test.role'
        ]);
        $resource = $this->getMock('\Laminas\Permissions\Acl\Resource\GenericResource', [
            'assert'
        ], [
            'test.resource'
        ]);

        $this->setExpectedException('Laminas\Permissions\Acl\Exception\RuntimeException');

        $this->assertionAggregate->assert($acl, $role, $resource, 'privilege');
    }
}
