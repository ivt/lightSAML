<?php

namespace LightSaml\Tests\Action\Profile\Outbound\AuthnRequest;

use LightSaml\Action\Profile\Outbound\AuthnRequest\ACSUrlAction;
use LightSaml\Context\Profile\ProfileContext;
use LightSaml\Criteria\CriteriaSet;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Profile\Profiles;
use LightSaml\Resolver\Endpoint\Criteria\BindingCriteria;
use LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria;
use LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria;
use LightSaml\SamlConstants;
use LightSaml\Tests\TestHelper;

class ACSUrlActionTest extends \PHPUnit_Framework_TestCase
{
    public function test_constructs_with_logger_and_endpoint_resolver()
    {
        new ACSUrlAction(TestHelper::getLoggerMock($this), $this->getEndpointResolverMock());
    }

    public function test_finds_acs_endpoint_and_sets_outbounding_authn_request_acs_url()
    {
        $action = new ACSUrlAction(
            $loggerMock = TestHelper::getLoggerMock($this),
            $endpointResolverMock = $this->getEndpointResolverMock()
        );

        $context = new ProfileContext(Profiles::SSO_SP_SEND_AUTHN_REQUEST, ProfileContext::ROLE_SP);
        $context->getOwnEntityContext()->setEntityDescriptor($entityDescriptorMock = $this->getEntityDescriptorMock());

        $entityDescriptorMock->expects($this->once())
            ->method('getAllEndpoints')
            ->willReturn(array(TestHelper::getEndpointReferenceMock($this, $endpoint = new AssertionConsumerService('http://localhost/acs'))));

        $endpointResolverMock->expects($this->once())
            ->method('resolve')
            ->with($this->isInstanceOf('\LightSaml\Criteria\CriteriaSet'), $this->isType('array'))
            ->willReturnCallback(function (CriteriaSet $criteriaSet, array $candidates) {
                \PHPUnit_Framework_Assert::assertTrue($criteriaSet->has('\LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria'));
                \PHPUnit_Framework_Assert::assertEquals('\LightSaml\Model\Metadata\SpSsoDescriptor', $criteriaSet->getSingle('\LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria')->getDescriptorType());

                \PHPUnit_Framework_Assert::assertTrue($criteriaSet->has('\LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria'));
                \PHPUnit_Framework_Assert::assertEquals('\LightSaml\Model\Metadata\AssertionConsumerService', $criteriaSet->getSingle('\LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria')->getServiceType());

                \PHPUnit_Framework_Assert::assertTrue($criteriaSet->has('\LightSaml\Resolver\Endpoint\Criteria\BindingCriteria'));
                \PHPUnit_Framework_Assert::assertEquals(array(SamlConstants::BINDING_SAML2_HTTP_POST), $criteriaSet->getSingle('\LightSaml\Resolver\Endpoint\Criteria\BindingCriteria')->getAllBindings());

                return $candidates;
            })
        ;
        $context->getOutboundContext()->setMessage($authnRequest = new AuthnRequest());

        $action->execute($context);

        $this->assertEquals($endpoint->getLocation(), $authnRequest->getAssertionConsumerServiceURL());
    }

    /**
     * @expectedException \LightSaml\Error\LightSamlContextException
     * @expectedExceptionMessage Missing ACS Service with HTTP POST binding in own SP SSO Descriptor
     */
    public function test_throws_context_exception_if_no_own_acs_service()
    {
        $action = new ACSUrlAction(
            $loggerMock = TestHelper::getLoggerMock($this),
            $endpointResolverMock = $this->getEndpointResolverMock()
        );

        $context = new ProfileContext(Profiles::SSO_SP_SEND_AUTHN_REQUEST, ProfileContext::ROLE_SP);
        $context->getOwnEntityContext()->setEntityDescriptor($entityDescriptorMock = $this->getEntityDescriptorMock());

        $entityDescriptorMock->expects($this->once())
            ->method('getAllEndpoints')
            ->willReturn(array());

        $endpointResolverMock->expects($this->once())
            ->method('resolve')
            ->willReturn(array());

        $loggerMock->expects($this->once())
            ->method('error');

        $action->execute($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityDescriptor
     */
    private function getEntityDescriptorMock()
    {
        return $this->getMock('\LightSaml\Model\Metadata\EntityDescriptor');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\LightSaml\Resolver\Endpoint\EndpointResolverInterface
     */
    private function getEndpointResolverMock()
    {
        return $this->getMock('\LightSaml\Resolver\Endpoint\EndpointResolverInterface');
    }
}
