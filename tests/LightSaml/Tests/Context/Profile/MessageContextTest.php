<?php

namespace LightSaml\Tests\Context\Profile;

use LightSaml\Context\Profile\MessageContext;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Model\Protocol\LogoutRequest;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\SamlMessage;

class MessageContextTest extends \PHPUnit_Framework_TestCase
{
    public function message_as_concrete_type_provider()
    {
        return array(
            array('asAuthnRequest', true, new AuthnRequest()),
            array('asAuthnRequest', false, new Response()),

            array('asLogoutRequest', true, new LogoutRequest()),
            array('asLogoutRequest', false, new Response()),

            array('asResponse', true, new Response()),
            array('asResponse', false, new LogoutRequest()),

            array('asLogoutResponse', true, new LogoutResponse()),
            array('asLogoutResponse', false, new LogoutRequest()),
        );
    }

    /**
     * @dataProvider message_as_concrete_type_provider
     */
    public function test_message_as_concrete_type($method, $hasValue, SamlMessage $message = null)
    {
        $context = new MessageContext();
        if ($message) {
            $context->setMessage($message);
        }

        $actualValue = $context->{$method}();

        if ($hasValue) {
            $this->assertSame($message, $actualValue);
        } else {
            $this->assertNull($actualValue);
        }
    }
}
