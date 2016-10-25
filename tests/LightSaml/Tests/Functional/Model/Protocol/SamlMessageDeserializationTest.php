<?php

namespace LightSaml\Tests\Functional\Model\Protocol;

use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Model\Protocol\LogoutRequest;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\SamlMessage;

class SamlMessageDeserializationTest extends \PHPUnit_Framework_TestCase
{
    public function deserialize_provider()
    {
        return array(
            array('<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"></samlp:AuthnRequest>', '\LightSaml\Model\Protocol\AuthnRequest'),
            array('<!--comment--><samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"></samlp:AuthnRequest>', '\LightSaml\Model\Protocol\AuthnRequest'),
            array('<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"></samlp:Response>', '\LightSaml\Model\Protocol\Response'),
            array('<!--comment--><samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"></samlp:Response>', '\LightSaml\Model\Protocol\Response'),
            array('<samlp:LogoutRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"></samlp:LogoutRequest>', '\LightSaml\Model\Protocol\LogoutRequest'),
            array('<!--comment--><samlp:LogoutRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"></samlp:LogoutRequest>', '\LightSaml\Model\Protocol\LogoutRequest'),
            array('<samlp:LogoutResponse xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"></samlp:LogoutResponse>', '\LightSaml\Model\Protocol\LogoutResponse'),
            array('<!--comment--><samlp:LogoutResponse xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"></samlp:LogoutResponse>', '\LightSaml\Model\Protocol\LogoutResponse'),
        );
    }

    /**
     * @dataProvider deserialize_provider
     */
    public function test_deserialize($xml, $expectedType)
    {
        $deserializationContext = new DeserializationContext();
        $samlMessage = SamlMessage::fromXML($xml, $deserializationContext);
        $this->assertInstanceOf($expectedType, $samlMessage);
    }
}
