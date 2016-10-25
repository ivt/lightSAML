<?php

namespace LightSaml\Tests\Resolver\Credential;

use LightSaml\Credential\CredentialInterface;
use LightSaml\Criteria\CriteriaSet;
use LightSaml\Resolver\Credential\CredentialResolverInterface;
use LightSaml\Resolver\Credential\CredentialResolverQuery;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class CredentialResolverQueryTest extends \PHPUnit_Framework_TestCase
{
    public function test__extends_criteria_set()
    {
        $reflectionClass = new \ReflectionClass('LightSaml\Resolver\Credential\CredentialResolverQuery');
        $this->assertEquals('LightSaml\Criteria\CriteriaSet', $reflectionClass->getParentClass()->name);
    }

    public function test__instantiates_with_credential_resolver()
    {
        new CredentialResolverQuery($this->getMock('LightSaml\Resolver\Credential\CredentialResolverInterface'));
    }

    public function test__resolve_calls_credential_resolver_and_stores_returned_credentials()
    {
        $credentialResolverMock = $this->getMock('LightSaml\Resolver\Credential\CredentialResolverInterface');
        $query = new CredentialResolverQuery($credentialResolverMock);
        $credentialResolverMock
            ->expects($this->once())
            ->method('resolve')
            ->with($query)
            ->willReturn($expectedCredentials = array(1, 2, 3))
        ;
        $query->resolve();

        $this->assertEquals($expectedCredentials, $query->allCredentials());
    }

    public function test__first_credential()
    {
        $credentialResolverMock = $this->getMock('LightSaml\Resolver\Credential\CredentialResolverInterface');
        $query = new CredentialResolverQuery($credentialResolverMock);
        $credentialResolverMock
            ->expects($this->once())
            ->method('resolve')
            ->with($query)
            ->willReturn($expectedCredentials = array(
                $firstCredential = $this->getMock('LightSaml\Credential\CredentialInterface'),
                $secondCredential = $this->getMock('LightSaml\Credential\CredentialInterface'),
                $thirdCredential = $this->getMock('LightSaml\Credential\CredentialInterface'),
            ))
        ;
        $query->resolve();

        $this->assertSame($firstCredential, $query->firstCredential());
    }

    public function test__public_keys()
    {
        $credentialResolverMock = $this->getMock('LightSaml\Resolver\Credential\CredentialResolverInterface');
        $query = new CredentialResolverQuery($credentialResolverMock);
        $credentialResolverMock
            ->expects($this->once())
            ->method('resolve')
            ->with($query)
            ->willReturn($expectedCredentials = array(
                $firstCredential = $this->getMock('LightSaml\Credential\CredentialInterface'),
                $secondCredential = $this->getMock('LightSaml\Credential\CredentialInterface'),
                $thirdCredential = $this->getMock('LightSaml\Credential\CredentialInterface'),
            ))
        ;

        $firstCredential->expects($this->any())
            ->method('getPublicKey')
            ->willReturn($this->getXmlSecurityKeyMock());
        $thirdCredential->expects($this->any())
            ->method('getPublicKey')
            ->willReturn($this->getXmlSecurityKeyMock());
        $query->resolve();

        $publicKeys = $query->getPublicKeys();

        $this->assertCount(2, $publicKeys);
        $this->assertSame($firstCredential, $publicKeys[0]);
        $this->assertSame($thirdCredential, $publicKeys[1]);

        $this->assertCount(0, $query->getPrivateKeys());
    }

    public function test__private_keys()
    {
        $credentialResolverMock = $this->getMock('LightSaml\Resolver\Credential\CredentialResolverInterface');
        $query = new CredentialResolverQuery($credentialResolverMock);
        $credentialResolverMock
            ->expects($this->once())
            ->method('resolve')
            ->with($query)
            ->willReturn($expectedCredentials = array(
                $firstCredential = $this->getMock('LightSaml\Credential\CredentialInterface'),
                $secondCredential = $this->getMock('LightSaml\Credential\CredentialInterface'),
                $thirdCredential = $this->getMock('LightSaml\Credential\CredentialInterface'),
            ))
        ;

        $secondCredential->expects($this->any())
            ->method('getPrivateKey')
            ->willReturn($this->getXmlSecurityKeyMock());
        $thirdCredential->expects($this->any())
            ->method('getPrivateKey')
            ->willReturn($this->getXmlSecurityKeyMock());
        $query->resolve();

        $privateKeys = $query->getPrivateKeys();

        $this->assertCount(2, $privateKeys);
        $this->assertSame($secondCredential, $privateKeys[0]);
        $this->assertSame($thirdCredential, $privateKeys[1]);

        $this->assertCount(0, $query->getPublicKeys());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|XMLSecurityKey
     */
    private function getXmlSecurityKeyMock()
    {
        return $this->getMock('\RobRichards\XMLSecLibs\XMLSecurityKey', array(), array(), '', false);
    }
}
