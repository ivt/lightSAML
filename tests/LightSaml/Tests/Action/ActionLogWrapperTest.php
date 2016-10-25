<?php

namespace LightSaml\Tests\Action;

use LightSaml\Action\ActionInterface;
use LightSaml\Action\ActionLogWrapper;
use Psr\Log\LoggerInterface;

class ActionLogWrapperTest extends \PHPUnit_Framework_TestCase
{
    public function test__builds_loggable_action_with_given_logger()
    {
        $context = $this->getContextMock();

        $action = $this->getActionMock();
        $action->expects($this->once())
            ->method('execute')
            ->with($context);

        $loggerMock  = $this->getLoggerMock();
        $loggerMock->expects($this->once())
            ->method('debug')
            ->willReturnCallback(function ($pMessage, $pContext) use ($action, $context) {
                $expectedMessage = sprintf('Executing action "%s"', get_class($action));
                \PHPUnit_Framework_Assert::assertEquals($expectedMessage, $pMessage);
                \PHPUnit_Framework_Assert::assertArrayHasKey('context', $pContext);
                \PHPUnit_Framework_Assert::assertArrayHasKey('action', $pContext);
                \PHPUnit_Framework_Assert::assertSame($action, $pContext['action']);
                \PHPUnit_Framework_Assert::assertSame($context, $pContext['context']);
            });

        $wrapper = new ActionLogWrapper($loggerMock);

        $wrappedAction = $wrapper->wrap($action);

        $wrappedAction->execute($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface
     */
    private function getLoggerMock()
    {
        return $this->getMock('Psr\Log\LoggerInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ActionInterface
     */
    private function getActionMock()
    {
        return $this->getMock('LightSaml\Action\ActionInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\LightSaml\Context\ContextInterface
     */
    private function getContextMock()
    {
        return $this->getMock('LightSaml\Context\ContextInterface');
    }
}
