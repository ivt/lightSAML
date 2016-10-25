<?php

namespace LightSaml\Tests\Action\Profile\Inbound\Message;

use LightSaml\Action\Profile\Inbound\Message\AbstractDestinationValidatorAction;
use LightSaml\Context\Profile\ProfileContext;
use LightSaml\Criteria\CriteriaSet;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\IdpSsoDescriptor;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Profile\Profiles;
use LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria;
use LightSaml\Resolver\Endpoint\Criteria\LocationCriteria;
use LightSaml\Resolver\Endpoint\EndpointResolverInterface;
use LightSaml\Tests\TestHelper;

class AbstractDestinationValidatorActionTest extends \PHPUnit_Framework_TestCase
{
    public function test_constructs_with_logger_and_endpoint_resolver()
    {
        $this->getMockForAbstractClass(
            'LightSaml\Action\Profile\Inbound\Message\AbstractDestinationValidatorAction',
            array(
                TestHelper::getLoggerMock($this),
                $this->getEndpointResolverMock(),
            )
        );
    }

    public function test_passes_if_inbound_message_destination_is_empty()
    {
        $loggerMock = TestHelper::getLoggerMock($this);
        $endpointResolverMock = $this->getEndpointResolverMock();
        /** @var AbstractDestinationValidatorAction $action */
        $action = $this->getMockForAbstractClass('LightSaml\Action\Profile\Inbound\Message\AbstractDestinationValidatorAction', array($loggerMock, $endpointResolverMock));

        $context = $this->buildContext(ProfileContext::ROLE_IDP, null);

        $action->execute($context);
    }

    public function test_passes_if_message_destination_matches_to_one_of_own_locations()
    {
        $loggerMock = TestHelper::getLoggerMock($this);
        $endpointResolverMock = $this->getEndpointResolverMock();
        /** @var AbstractDestinationValidatorAction $action */
        $action = $this->getMockForAbstractClass('LightSaml\Action\Profile\Inbound\Message\AbstractDestinationValidatorAction', array($loggerMock, $endpointResolverMock));

        $context = $this->buildContext(ProfileContext::ROLE_IDP, $expectedDestination = 'http://localhost/foo');

        $endpointResolverMock->expects($this->once())
            ->method('resolve')
            ->with(
                $this->isInstanceOf('LightSaml\Criteria\CriteriaSet'),
                $this->isType('array')
            )
            ->willReturn(true);

        $action->execute($context);
    }

    public function makes_descriptor_type_criteria_for_own_role_provider()
    {
        return array(
           array(ProfileContext::ROLE_IDP, 'LightSaml\Model\Metadata\IdpSsoDescriptor'),
           array(ProfileContext::ROLE_SP, 'LightSaml\Model\Metadata\SpSsoDescriptor'),
        );
    }

    /**
     * @dataProvider makes_descriptor_type_criteria_for_own_role_provider
     */
    public function test_makes_descriptor_type_criteria_for_own_role($ownRole, $descriptorType)
    {
        $loggerMock = TestHelper::getLoggerMock($this);
        $endpointResolverMock = $this->getEndpointResolverMock();
        /** @var AbstractDestinationValidatorAction $action */
        $action = $this->getMockForAbstractClass('LightSaml\Action\Profile\Inbound\Message\AbstractDestinationValidatorAction', array($loggerMock, $endpointResolverMock));

        $context = $this->buildContext($ownRole, $expectedDestination = 'http://localhost/foo');

        $endpointResolverMock->expects($this->once())
            ->method('resolve')
            ->willReturnCallback(function (CriteriaSet $criteriaSet, array $endpoints) use ($descriptorType, $expectedDestination) {
                \PHPUnit_Framework_Assert::assertTrue($criteriaSet->has('LightSaml\Resolver\Endpoint\Criteria\LocationCriteria'));
                $arr = $criteriaSet->get('LightSaml\Resolver\Endpoint\Criteria\LocationCriteria');
                \PHPUnit_Framework_Assert::assertCount(1, $arr);
                /** @var LocationCriteria $criteria */
                $criteria = $arr[0];
                \PHPUnit_Framework_Assert::assertEquals($expectedDestination, $criteria->getLocation());

                \PHPUnit_Framework_Assert::assertTrue($criteriaSet->has('LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria'));
                $arr = $criteriaSet->get('LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria');
                \PHPUnit_Framework_Assert::assertCount(1, $arr);
                /** @var DescriptorTypeCriteria $criteria */
                $criteria = $arr[0];
                \PHPUnit_Framework_Assert::assertInstanceOf('LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria', $criteria);
                \PHPUnit_Framework_Assert::assertEquals($descriptorType, $criteria->getDescriptorType());

                return true;
            });

        $action->execute($context);
    }

    /**
     * @expectedException \LightSaml\Error\LightSamlContextException
     * @expectedExceptionMessage Invalid inbound message destination "http://localhost/foo"
     */
    public function test_throws_exception_when_destination_does_not_match()
    {
        $loggerMock = TestHelper::getLoggerMock($this);
        $endpointResolverMock = $this->getEndpointResolverMock();
        /** @var AbstractDestinationValidatorAction $action */
        $action = $this->getMockForAbstractClass('LightSaml\Action\Profile\Inbound\Message\AbstractDestinationValidatorAction', array($loggerMock, $endpointResolverMock));

        $context = $this->buildContext(ProfileContext::ROLE_IDP, $expectedDestination = 'http://localhost/foo');

        $endpointResolverMock->expects($this->once())
            ->method('resolve')
            ->willReturn(false);

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
        return $this->getMock('LightSaml\Resolver\Endpoint\EndpointResolverInterface');
    }
}
