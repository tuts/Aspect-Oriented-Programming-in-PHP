<?php

namespace Go\Aop\Framework;

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2012-12-20 at 11:58:54.
 */
class ClosureDynamicMethodInvocationTest extends \PHPUnit_Framework_TestCase
{

    const FIRST_CLASS_NAME = 'Go\Tests\First';

    /**
     * {@inheritdoc}
     */
    public static function setUpBeforeClass()
    {
        include_once __DIR__ . '/../../Tests/First.php';
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (version_compare(PHP_VERSION, '5.4.0') < 0) {
            $this->markTestSkipped("Closure Method Invocation works only on PHP 5.4 and greater");
        }
    }

    /**
     * Tests dynamic method invocations
     *
     * @dataProvider dynamicMethodsBatch
     */
    public function testDynamicMethodInvocation($methodName, $expectedResult)
    {
        $child      = $this->getMock(self::FIRST_CLASS_NAME, array('none'));
        $invocation = new ClosureDynamicMethodInvocation(self::FIRST_CLASS_NAME, $methodName, array());

        $result = $invocation($child);
        $this->assertEquals($expectedResult, $result);
    }

    public function testValueChangedByReference()
    {
        $child      = $this->getMock(self::FIRST_CLASS_NAME, array('none'));
        $invocation = new ClosureDynamicMethodInvocation(self::FIRST_CLASS_NAME, 'passByReference', array());

        $value  = 'test';
        $result = $invocation($child, array(&$value));
        $this->assertEquals(null, $result);
        $this->assertEquals(null, $value);
    }

    public function testRecursionWorks()
    {
        $child      = $this->getMock(self::FIRST_CLASS_NAME, array('recursion'));
        $invocation = new ClosureDynamicMethodInvocation(self::FIRST_CLASS_NAME, 'recursion', array());

        $child->expects($this->exactly(5))->method('recursion')->will($this->returnCallback(
            function ($value, $level) use ($child, $invocation) {
                return $invocation($child, array($value, $level));
            }
        ));

        $this->assertEquals(5, $child->recursion(5,0));
        $this->assertEquals(20, $child->recursion(5,3));
    }

    public function testAdviceIsCalledForInvocation()
    {
        $child  = $this->getMock(self::FIRST_CLASS_NAME, array('none'));
        $value  = 'test';
        $advice = new MethodBeforeInterceptor(function ($object) use (&$value) {
            $value = 'ok';
        });

        $invocation = new ClosureDynamicMethodInvocation(self::FIRST_CLASS_NAME, 'publicMethod', array($advice));

        $result = $invocation($child, array());
        $this->assertEquals('ok', $value);
        $this->assertEquals(T_PUBLIC, $result);
    }

    public function dynamicMethodsBatch()
    {
        return array(
            array('publicMethod', T_PUBLIC),
            array('protectedMethod', T_PROTECTED),
            // array('privateMethod', T_PRIVATE), This will throw an ReflectionException, need to add use case for that
        );
    }

    public function staticSelfMethodsBatch()
    {
        return array(
            array('staticSelfPublic', T_PUBLIC),
            array('staticSelfProtected', T_PROTECTED),
            // array('staticSelfPrivate', T_PRIVATE), // This will give a Fatal Error for scope
        );
    }

    public function staticLsbMethodsBatch()
    {
        return array(
            array('staticLsbPublic'),
            array('staticLsbProtected'),
        );
    }

}
