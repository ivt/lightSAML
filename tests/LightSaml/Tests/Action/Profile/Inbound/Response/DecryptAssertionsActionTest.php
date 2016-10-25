<?php

namespace LightSaml\Tests\Action\Profile\Inbound\Response;

use LightSaml\Action\Profile\Inbound\Response\DecryptAssertionsAction;
use LightSaml\Context\Profile\ProfileContext;
use LightSaml\Credential\Criteria\EntityIdCriteria;
use LightSaml\Credential\Criteria\MetadataCriteria;
use LightSaml\Credential\Criteria\UsageCriteria;
use LightSaml\Credential\UsageType;
use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Protocol\Response;
use LightSaml\Profile\Profiles;
use LightSaml\Resolver\Credential\CredentialResolverQuery;
use LightSaml\Tests\TestHelper;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class DecryptAssertionsActionTest extends \PHPUnit_Framework_TestCase
{
    public function test_constructs_with_logger_and_credential_resolver()
    {
        new DecryptAssertionsAction(TestHelper::getLoggerMock($this), $this->getCredentialResolverMock());
    }

    public function resolves_credentials_for_own_entity_id_party_role_and_encryption_usage_provider()
    {
        return array(
            array(ProfileContext::ROLE_IDP, MetadataCriteria::TYPE_IDP),
            array(ProfileContext::ROLE_SP, MetadataCriteria::TYPE_SP),
        );
    }

    /**
     * @dataProvider resolves_credentials_for_own_entity_id_party_role_and_encryption_usage_provider
     */
    public function test_resolves_credentials_and_decrypts_assertions($ownRole, $expectedMetadataCriteria)
    {
        $action = new DecryptAssertionsAction(
            $loggerMock = TestHelper::getLoggerMock($this),
            $credentialResolverMock = $this->getCredentialResolverMock()
        );

        $context = new ProfileContext(Profiles::SSO_IDP_RECEIVE_AUTHN_REQUEST, $ownRole);
        $context->getOwnEntityContext()->setEntityDescriptor(new EntityDescriptor($entityId = 'http://entity.id'));

        $context->getInboundContext()->setMessage($response = new Response());
        $response->addEncryptedAssertion($encryptedAssertionMock1 = $this->getEncryptedAssertionReaderMock());

        $encryptedAssertionMock1->expects($this->once())
            ->method('decryptMultiAssertion')
            ->willReturn($decryptedAssertion = new Assertion());

        $credentialResolverMock->expects($this->once())
            ->method('query')
            ->willReturn($query = new CredentialResolverQuery($credentialResolverMock));
        $credentialResolverMock->expects($this->once())
            ->method('resolve')
            ->with($query)
            ->willReturn($credentials = array(
                $credentialMock1 = $this->getCredentialMock(),
            ));

        $credentialMock1->expects($this->any())
            ->method('getPrivateKey')
            ->willReturn($privateKey = new XMLSecurityKey(XMLSecurityKey::TRIPLEDES_CBC));

        $action->execute($context);

        $this->assertTrue($query->has('\LightSaml\Credential\Criteria\EntityIdCriteria'));
        $this->assertEquals($entityId, $query->getSingle('\LightSaml\Credential\Criteria\EntityIdCriteria')->getEntityId());

        $this->assertTrue($query->has('\LightSaml\Credential\Criteria\MetadataCriteria'));
        $this->assertEquals($expectedMetadataCriteria, $query->getSingle('\LightSaml\Credential\Criteria\MetadataCriteria')->getMetadataType());

        $this->assertTrue($query->has('\LightSaml\Credential\Criteria\UsageCriteria'));
        $this->assertEquals(UsageType::ENCRYPTION, $query->getSingle('\LightSaml\Credential\Criteria\UsageCriteria')->getUsage());

        $this->assertCount(1, $response->getAllAssertions());
        $this->assertSame($decryptedAssertion, $response->getFirstAssertion());
    }

    public function test_does_nothing_if_no_encrypted_assertions()
    {
        $action = new DecryptAssertionsAction(
            $loggerMock = TestHelper::getLoggerMock($this),
            $credentialResolverMock = $this->getCredentialResolverMock()
        );

        $context = new ProfileContext(Profiles::SSO_IDP_RECEIVE_AUTHN_REQUEST, ProfileContext::ROLE_IDP);
        $context->getOwnEntityContext()->setEntityDescriptor(new EntityDescriptor($entityId = 'http://entity.id'));

        $context->getInboundContext()->setMessage($response = new Response());

        $loggerMock->expects($this->once())
            ->method('debug')
            ->with('Response has no encrypted assertions', $this->isType('array'));

        $action->execute($context);
    }

    /**
     * @expectedException \LightSaml\Error\LightSamlContextException
     * @expectedExceptionMessage No credentials resolved for assertion decryption
     */
    public function test_throws_context_exception_when_no_credentials_resolved()
    {
        $action = new DecryptAssertionsAction(
            $loggerMock = TestHelper::getLoggerMock($this),
            $credentialResolverMock = $this->getCredentialResolverMock()
        );

        $context = new ProfileContext(Profiles::SSO_IDP_RECEIVE_AUTHN_REQUEST, ProfileContext::ROLE_IDP);
        $context->getOwnEntityContext()->setEntityDescriptor(new EntityDescriptor($entityId = 'http://entity.id'));

        $context->getInboundContext()->setMessage($response = new Response());
        $response->addEncryptedAssertion($encryptedAssertionMock1 = $this->getEncryptedAssertionReaderMock());

        $credentialResolverMock->expects($this->once())
            ->method('query')
            ->willReturn($query = new CredentialResolverQuery($credentialResolverMock));

        $credentialResolverMock->expects($this->once())
            ->method('resolve')
            ->with($query)
            ->willReturn(array());

        $action->execute($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\LightSaml\Credential\CredentialInterface
     */
    private function getCredentialMock()
    {
        return $this->getMock('\LightSaml\Credential\CredentialInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\LightSaml\Model\Assertion\EncryptedAssertionReader
     */
    private function getEncryptedAssertionReaderMock()
    {
        return $this->getMock('\LightSaml\Model\Assertion\EncryptedAssertionReader');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\LightSaml\Resolver\Credential\CredentialResolverInterface
     */
    private function getCredentialResolverMock()
    {
        return $this->getMock('\LightSaml\Resolver\Credential\CredentialResolverInterface');
    }
}
