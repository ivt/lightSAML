<?php

namespace LightSaml\Tests\Action;

use LightSaml\Action\ActionInterface;
use LightSaml\Action\WrappedAction;

class WrappedActionTest extends \PHPUnit_Framework_TestCase
{
    public function test__before_and_after_called()
    {
        $context = $this->getContextMock();

        /** @var ActionInterface|\PHPUnit_Framework_MockObject_MockObject $action */
        $action = $this->getMock('LightSaml\Action\ActionInterface');
        /** @var WrappedAction|\PHPUnit_Framework_MockObject_MockObject $wrapper */
        $wrapper = $this->getMockForAbstractClass('LightSaml\Action\WrappedAction', array($action));

        $beforeCalled = false;
        $executeCalled = false;
        $afterCalled = false;

        $wrapper->expects($this->once())
            ->method('beforeAction')
            ->with($context)
            ->willReturnCallback(function () use (&$beforeCalled, &$executeCalled, &$afterCalled) {
                \PHPUnit_Framework_Assert::assertFalse($beforeCalled, 'beforeAction already called - should be called only once');
                \PHPUnit_Framework_Assert::assertFalse($executeCalled, 'execute should not been executed before beforeAction');
                \PHPUnit_Framework_Assert::assertFalse($afterCalled, 'afterAction should be executed before beforeAction');
                $beforeCalled = true;
            });

        $action->expects($this->once())
            ->method('execute')
            ->with($context)
            ->willReturnCallback(function () use (&$beforeCalled, &$executeCalled, &$afterCalled) {
                \PHPUnit_Framework_Assert::assertTrue($beforeCalled, 'beforeAction should have been called');
                \PHPUnit_Framework_Assert::assertFalse($executeCalled, 'execute already called - should be executed only once');
                \PHPUnit_Framework_Assert::assertFalse($afterCalled, 'afterAction should be executed before beforeAction');
                $executeCalled = true;
            });

        $wrapper->expects($this->once())
            ->method('afterAction')
            ->with($context)
            ->willReturnCallback(function () use (&$beforeCalled, &$executeCalled, &$afterCalled) {
                \PHPUnit_Framework_Assert::assertTrue($beforeCalled, 'beforeAction should have been called');
                \PHPUnit_Framework_Assert::assertTrue($executeCalled, 'execute should be executed before afterAction');
                \PHPUnit_Framework_Assert::assertFalse($afterCalled, 'afterAction already called - should be executed only once');
                $afterCalled = true;
            });

        $wrapper->execute($context);

        $this->assertTrue($beforeCalled);
        $this->assertTrue($executeCalled);
        $this->assertTrue($afterCalled);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\LightSaml\Context\ContextInterface
     */
    private function getContextMock()
    {
        return $this->getMock('LightSaml\Context\ContextInterface');
    }
}
