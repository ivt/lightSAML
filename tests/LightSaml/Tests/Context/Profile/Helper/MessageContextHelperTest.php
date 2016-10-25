<?php

namespace LightSaml\Tests\Context\Profile\Helper;

use LightSaml\Context\Profile\Helper\MessageContextHelper;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Error\LightSamlContextException;
use LightSaml\Model\Protocol\AbstractRequest;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Model\Protocol\LogoutRequest;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\Protocol\StatusResponse;

class MessageContextHelperTest extends \PHPUnit_Framework_TestCase
{
    public function helperProvider()
    {
        return array(
            array('asSamlMessage', null, 'LightSaml\Error\LightSamlContextException', 'Missing SamlMessage'),
            array('asSamlMessage', $this->getMockForAbstractClass('LightSaml\Model\Protocol\SamlMessage'), null, null),

            array('asAuthnRequest', null, 'LightSaml\Error\LightSamlContextException', 'Expected AuthnRequest'),
            array('asAuthnRequest', $this->getMockForAbstractClass('LightSaml\Model\Protocol\SamlMessage'), 'LightSaml\Error\LightSamlContextException', 'Expected AuthnRequest'),
            array('asAuthnRequest', new Response(), 'LightSaml\Error\LightSamlContextException', 'Expected AuthnRequest'),
            array('asAuthnRequest', new AuthnRequest(), null, null),

            array('asAbstractRequest', null, 'LightSaml\Error\LightSamlContextException', 'Expected AbstractRequest'),
            array('asAbstractRequest', new Response(), 'LightSaml\Error\LightSamlContextException', 'Expected AbstractRequest'),
            array('asAbstractRequest', $this->getMockForAbstractClass('LightSaml\Model\Protocol\AbstractRequest'), null, null),
            array('asAbstractRequest', new AuthnRequest(), null, null),
            array('asAbstractRequest', new LogoutRequest(), null, null),

            array('asResponse', null, 'LightSaml\Error\LightSamlContextException', 'Expected Response'),
            array('asResponse', new AuthnRequest(), 'LightSaml\Error\LightSamlContextException', 'Expected Response'),
            array('asResponse', new LogoutResponse(), 'LightSaml\Error\LightSamlContextException', 'Expected Response'),
            array('asResponse', new Response(), null, null),

            array('asStatusResponse', null, 'LightSaml\Error\LightSamlContextException', 'Expected StatusResponse'),
            array('asStatusResponse', new AuthnRequest(), 'LightSaml\Error\LightSamlContextException', 'Expected StatusResponse'),
            array('asStatusResponse', new Response(), null, null),
            array('asStatusResponse', new LogoutResponse(), null, null),
            array('asStatusResponse', $this->getMockForAbstractClass('LightSaml\Model\Protocol\StatusResponse'), null, null),

            array('asLogoutRequest', null, 'LightSaml\Error\LightSamlContextException', 'Expected LogoutRequest'),
            array('asLogoutRequest', new AuthnRequest(), 'LightSaml\Error\LightSamlContextException', 'Expected LogoutRequest'),
            array('asLogoutRequest', new LogoutRequest(), null, null),

            array('asLogoutResponse', null, 'LightSaml\Error\LightSamlContextException', 'Expected LogoutResponse'),
            array('asLogoutResponse', new AuthnRequest(), 'LightSaml\Error\LightSamlContextException', 'Expected LogoutResponse'),
            array('asLogoutResponse', new LogoutRequest(), 'LightSaml\Error\LightSamlContextException', 'Expected LogoutResponse'),
            array('asLogoutResponse', new LogoutResponse(), null, null),
        );
    }

    /**
     * @dataProvider helperProvider
     */
    public function test__helper($method, SamlMessage $message = null, $expectedException = null, $expectedMessage = null)
    {
        $context = new MessageContext();
        if ($message) {
            $context->setMessage($message);
        }

        if ($expectedException) {
            try {
                call_user_func(array('LightSaml\Context\Profile\Helper\MessageContextHelper', $method), $context);
            } catch (\Exception $ex) {
                $this->assertInstanceOf($expectedException, $ex);
                if ($expectedMessage) {
                    $this->assertEquals($expectedMessage, $ex->getMessage());
                }
            }
        } else {
            $actualMessage = call_user_func(array('LightSaml\Context\Profile\Helper\MessageContextHelper', $method), $context);
            $this->assertSame($message, $actualMessage);
        }
    }

    public function test__as_saml_message_returns_message()
    {
        $context = new MessageContext();
        $context->setMessage($expectedMessage = $this->getMessageMock());

        $this->assertSame($expectedMessage, MessageContextHelper::asSamlMessage($context));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SamlMessage
     */
    private function getMessageMock()
    {
        return $this->getMockForAbstractClass('LightSaml\Model\Protocol\SamlMessage');
    }
}
