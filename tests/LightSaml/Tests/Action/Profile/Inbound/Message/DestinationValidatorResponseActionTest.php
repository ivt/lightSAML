<?php

namespace LightSaml\Tests\Action\Profile\Inbound\Message;

use LightSaml\Action\Profile\Inbound\Message\DestinationValidatorResponseAction;
use LightSaml\Context\Profile\ProfileContext;
use LightSaml\Criteria\CriteriaSet;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Profile\Profiles;
use LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria;
use LightSaml\Resolver\Endpoint\EndpointResolverInterface;
use LightSaml\Tests\TestHelper;

class DestinationValidatorResponseActionTest extends \PHPUnit_Framework_TestCase
{
    public function test_creates_acs_service_type_criteria()
    {
        $endpointResolverMock = $this->getEndpointResolverMock();

        $action = new DestinationValidatorResponseAction(TestHelper::getLoggerMock($this), $endpointResolverMock);

        $context = $this->buildContext(ProfileContext::ROLE_IDP, 'http://localhost');

        $endpointResolverMock->expects($this->once())
            ->method('resolve')
            ->willReturnCallback(function (CriteriaSet $criteriaSet, array $endpoints) {
                \PHPUnit_Framework_Assert::assertTrue($criteriaSet->has('\LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria'));
                $arr = $criteriaSet->get('\LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria');
                \PHPUnit_Framework_Assert::assertCount(1, $arr);
                /** @var ServiceTypeCriteria $criteria */
                $criteria = $arr[0];
                \PHPUnit_Framework_Assert::assertEquals('\LightSaml\Model\Metadata\AssertionConsumerService', $criteria->getServiceType());

                return true;
            });

        $action->execute($context);
    }

    /**
     * @param string $ownRole
     * @param string $destination
     *
     * @return ProfileContext
     */
    private function buildContext($ownRole, $destination)
    {
        $context = new ProfileContext(Profiles::SSO_IDP_RECEIVE_AUTHN_REQUEST, $ownRole);
        $context->getInboundContext()->setMessage(new AuthnRequest());
        if ($destination) {
            $context->getInboundMessage()->setDestination($destination);
        }

        $context->getOwnEntityContext()->setEntityDescriptor(new EntityDescriptor());

        return $context;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\LightSaml\Resolver\Endpoint\EndpointResolverInterface
     */
    private function getEndpointResolverMock()
    {
        return $this->getMock('\LightSaml\Resolver\Endpoint\EndpointResolverInterface');
    }
}
