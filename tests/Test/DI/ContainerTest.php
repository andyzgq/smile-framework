<?php


namespace Smile\Test\DI;

use PHPUnit\Framework\TestCase;
use Smile\Di\Container;
use Smile\Di\ElementDefinition;
use Smile\Interfaces\ContainerInterface;

/**
 * Class DefaultContainerImplTest
 * @package Smile\Test\DI
 */
class ContainerTest extends TestCase
{
    public function containerProvider()
    {
        $container = new Container();

        //之所以这里是个二维数组, 是因为phpunit每次都会扫描这里的一个一维数组作为用dataProvider注入的函数
        return [
            [$container]
        ];
    }

    /**
     * 测试命名空间自动组装
     *
     * @dataProvider containerProvider
     * @param \Smile\Interfaces\ContainerInterface $container
     */
    public function testAutowired(ContainerInterface $container)
    {
        $container->enableAutowiredForNamespace(__NAMESPACE__);
        $object = $container->getByType(TestClassA::class);
        $this->assertInstanceOf(TestClassA::class, $object);
    }

    /**
     * 测试立即初始化
     *
     * @dataProvider containerProvider
     * @param \Smile\Interfaces\ContainerInterface $container
     */
    public function testEagerInit(ContainerInterface $container)
    {
        $container->set(
            (new ElementDefinition())
            ->setType(TestClassB::class)
            ->setEager()
            ->setSingletonScope()
        );

        $object = $container->getByType(TestClassB::class);
        $this->assertInstanceOf(TestClassB::class, $object);

        $this->expectException(\Smile\Exceptions\ContainerException::class);
        $this->expectExceptionMessageRegExp('/原型作用域不支持立即实例化/');
        $container->set(
            (new ElementDefinition())
                ->setType(TestClassC::class)
                ->setEager()
        );
        $object = $container->getByType(TestClassC::class);
    }

    /**
     * 测试延迟初始化
     *
     * @dataProvider containerProvider
     * @param ContainerInterface $container
     */
    public function testDeferredInit(\Smile\Interfaces\ContainerInterface $container)
    {
        $container->set(
            (new ElementDefinition())
                ->setType(TestClassB::class)
                ->setDeferred()
        );
        $object = $container->getByType(TestClassB::class);
        $this->assertInstanceOf(TestClassB::class, $object);
    }

    /**
     * 测试原型作用域
     *
     * @dataProvider containerProvider
     * @param \Smile\Interfaces\ContainerInterface $container
     */
    public function testPrototype(ContainerInterface $container)
    {
        $container->set(
            (new ElementDefinition())
                ->setType(TestClassB::class)
                ->setDeferred()
                ->setPrototypeScope()
        );

        $obj1 = $container->getByType(TestClassB::class);
        $obj2 = $container->getByType(TestClassB::class);

        $this->assertNotSame($obj1, $obj2);
    }

    /**
     * 测试单例作用域
     *
     * @dataProvider containerProvider
     * @param \Smile\Interfaces\ContainerInterface $container
     */
    public function testSingleton(\Smile\Interfaces\ContainerInterface $container)
    {
        $container->set(
            (new ElementDefinition())
                ->setType(TestClassB::class)
                ->setDeferred()
                ->setSingletonScope()
        );

        $obj1 = $container->getByType(TestClassB::class);
        $obj2 = $container->getByType(TestClassB::class);

        $this->assertSame($obj1, $obj2);
    }

    /**
     * 测试循环引用报错
     *
     * @dataProvider containerProvider
     * @param ContainerInterface $container
     */
    public function testCircleDep(\Smile\Interfaces\ContainerInterface $container)
    {
        $this->expectException(\Smile\Exceptions\ContainerException::class);
        $this->expectExceptionMessageRegExp('/循环/');
        $container->enableAutowiredForNamespace(__NAMESPACE__);
        $obj = $container->getByType(TestCircleDepClassA::class);
    }

    /**
     * 测试别名
     *
     * @dataProvider containerProvider
     * @param ContainerInterface $container
     */
    public function testAlias(\Smile\Interfaces\ContainerInterface $container)
    {
        $container->set(
            (new ElementDefinition())
                ->setType(ElementDefinition::TYPE_ARRAY)
                ->setBuilder(function ($hello, $helloCharCount) {
                    return [$hello, $helloCharCount];
                })
                ->setAlias('helloWorldAndItsLength')
        );

        $container->set(
            (new ElementDefinition())
            ->setType(ElementDefinition::TYPE_STRING)
            ->setInstance('hello, world')
            ->setAlias('hello')
        );

        $container->set(
            (new ElementDefinition())
            ->setType(ElementDefinition::TYPE_INT)
            ->setBuilder(function ($hello) {
                return strlen($hello);
            })
            ->setAlias('helloCharCount')
        );

        $arrayResult = $container->getByAlias('helloWorldAndItsLength');

        $this->assertEquals(['hello, world', 12], $arrayResult);
    }
}

class TestClassA
{
    public function __construct(TestClassC $b)
    {
    }
}

class TestClassB
{
}

class TestClassC
{
    public function __construct(TestClassB $a)
    {
    }
}

class TestCircleDepClassA
{
    public function __construct(TestCircleDepClassB $a)
    {
    }
}

class TestCircleDepClassB
{
    public function __construct(TestCircleDepClassC $a)
    {
    }
}

class TestCircleDepClassC
{
    public function __construct(TestCircleDepClassA $a)
    {
    }
}
