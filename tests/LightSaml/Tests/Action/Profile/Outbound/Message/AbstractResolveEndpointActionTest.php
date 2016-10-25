<?php

namespace LightSaml\Tests\Action\Profile\Outbound\Message;

use LightSaml\Action\Profile\Outbound\Message\ResolveEndpointBaseAction;
use LightSaml\Context\Profile\ProfileContext;
use LightSaml\Criteria\CriteriaSet;
use LightSaml\Model\Metadata\Endpoint;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Profile\Profiles;
use LightSaml\Resolver\Endpoint\Criteria\BindingCriteria;
use LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria;
use LightSaml\Resolver\Endpoint\Criteria\IndexCriteria;
use LightSaml\Resolver\Endpoint\Criteria\LocationCriteria;
use LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria;
use LightSaml\Resolver\Endpoint\EndpointResolverInterface;
use LightSaml\Tests\TestHelper;
use Psr\Log\LoggerInterface;

abstract class AbstractResolveEndpointActionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ResolveEndpointBaseAction|\PHPUnit_Framework_MockObject_MockObject */
    protected $action;

    /** @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    /** @var  EndpointResolverInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $endpointResolver;

    /**
     *
     */
    protected function setUp()
    {
        $this->logger = TestHelper::getLoggerMock($this);
        $this->endpointResolver = $this->getMock('\LightSaml\Resolver\Endpoint\EndpointResolverInterface');
        $this->action = $this->createAction($this->logger, $this->endpointResolver);
    }

    /**
     * @param LoggerInterface           $logger
     * @param EndpointResolverInterface $endpointResolver
     *
     * @return ResolveEndpointBaseAction
     */
    abstract protected function createAction(LoggerInterface $logger, EndpointResolverInterface $endpointResolver);

    /**
     * @param bool     $shouldBeCalled
     * @param callable $callback
     */
    protected function setEndpointResolver($shouldBeCalled, $callback)
    {
        if ($shouldBeCalled) {
            $this->endpointResolver->expects($this->once())
                ->method('resolve')
                ->willReturnCallback($callback);
        } else {
            $this->endpointResolver->expects($this->never())
                ->method('resolve');
        }
    }

    /**
     * @param string           $ownRole
     * @param SamlMessage      $inboundMessage
     * @param Endpoint         $endpoint
     * @param EntityDescriptor $partyEntityDescriptor
     * @param string           $profileId
     *
     * @return \LightSaml\Context\Profile\ProfileContext
     */
    protected function createContext(
        $ownRole = ProfileContext::ROLE_IDP,
        SamlMessage $inboundMessage = null,
        Endpoint $endpoint = null,
        EntityDescriptor $partyEntityDescriptor = null,
        $profileId = Profiles::SSO_IDP_RECEIVE_AUTHN_REQUEST
    ) {
        $context = TestHelper::getProfileContext($profileId, $ownRole);

        if ($endpoint) {
            $context->getEndpointContext()->setEndpoint($endpoint);
        }

        if (null == $partyEntityDescriptor) {
            $partyEntityDescriptor = EntityDescriptor::load(__DIR__.'/../../../../../../../resources/sample/EntityDescriptor/idp2-ed-formatted.xml');
        }
        $context->getPartyEntityContext()->setEntityDescriptor($partyEntityDescriptor);

        if ($inboundMessage) {
            $context->getInboundContext()->setMessage($inboundMessage);
        }

        return $context;
    }

    /**
     * @param CriteriaSet $criteriaSet
     * @param array       $bindings
     */
    public function criteriaSetShouldHaveBindingCriteria(CriteriaSet $criteriaSet, array $bindings)
    {
        if (empty($bindings)) {
            $this->assertFalse($criteriaSet->has('\LightSaml\Resolver\Endpoint\Criteria\BindingCriteria'));
        } else {
            $this->assertTrue($criteriaSet->has('\LightSaml\Resolver\Endpoint\Criteria\BindingCriteria'));
            /** @var BindingCriteria $criteria */
            $criteria = $criteriaSet->getSingle('\LightSaml\Resolver\Endpoint\Criteria\BindingCriteria');
            $this->assertEquals($bindings, $criteria->getAllBindings());
        }
    }

    /**
     * @param CriteriaSet $criteriaSet
     * @param string      $value
     */
    public function criteriaSetShouldHaveDescriptorTypeCriteria(CriteriaSet $criteriaSet, $value)
    {
        if ($value) {
            $this->assertTrue($criteriaSet->has('\LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria'));
            /** @var DescriptorTypeCriteria $criteria */
            $criteria = $criteriaSet->getSingle('\LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria');
            $this->assertEquals($value, $criteria->getDescriptorType());
        } else {
            $this->assertFalse($criteriaSet->has('\LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria'));
        }
    }

    /**
     * @param CriteriaSet $criteriaSet
     * @param string      $value
     */
    public function criteriaSetShouldHaveServiceTypeCriteria(CriteriaSet $criteriaSet, $value)
    {
        if ($value) {
            $this->assertTrue($criteriaSet->has('\LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria'));
            /** @var ServiceTypeCriteria $criteria */
            $criteria = $criteriaSet->getSingle('\LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria');
            $this->assertEquals($value, $criteria->getServiceType());
        } else {
            $this->assertFalse($criteriaSet->has('\LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria'));
        }
    }

    /**
     * @param CriteriaSet $criteriaSet
     * @param string      $value
     */
    public function criteriaSetShouldHaveIndexCriteria(CriteriaSet $criteriaSet, $value)
    {
        if ($value) {
            $this->assertTrue($criteriaSet->has('\LightSaml\Resolver\Endpoint\Criteria\IndexCriteria'));
            /** @var IndexCriteria $criteria */
            $criteria = $criteriaSet->getSingle('\LightSaml\Resolver\Endpoint\Criteria\IndexCriteria');
            $this->assertEquals($value, $criteria->getIndex());
        } else {
            $this->assertFalse($criteriaSet->has('\LightSaml\Resolver\Endpoint\Criteria\IndexCriteria'));
        }
    }

    /**
     * @param CriteriaSet $criteriaSet
     * @param string      $value
     */
    public function criteriaSetShouldHaveLocationCriteria(CriteriaSet $criteriaSet, $value)
    {
        if ($value) {
            $this->assertTrue($criteriaSet->has('\LightSaml\Resolver\Endpoint\Criteria\LocationCriteria'));
            /** @var LocationCriteria $criteria */
            $criteria = $criteriaSet->getSingle('\LightSaml\Resolver\Endpoint\Criteria\LocationCriteria');
            $this->assertEquals($value, $criteria->getLocation());
        } else {
            $this->assertFalse($criteriaSet->has('\LightSaml\Resolver\Endpoint\Criteria\LocationCriteria'));
        }
    }
}
