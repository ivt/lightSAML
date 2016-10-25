<?php

namespace LightSaml\Tests\Action\Profile\Outbound\Message;

use LightSaml\Action\Profile\Outbound\Message\ResolveEndpointBaseAction;
use LightSaml\Action\Profile\Outbound\Message\ResolveEndpointSpAcsAction;
use LightSaml\Context\Profile\ProfileContext;
use LightSaml\Criteria\CriteriaSet;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Resolver\Endpoint\EndpointResolverInterface;
use LightSaml\Tests\TestHelper;
use Psr\Log\LoggerInterface;

class ResolveEndpointSpAcsActionTest extends AbstractResolveEndpointActionTest
{
    public function test_adds_service_type_acs()
    {
        $message = new AuthnRequest();
        $context = $this->createContext(ProfileContext::ROLE_IDP, $message);

        $self = $this;
        $this->setEndpointResolver(true, function (CriteriaSet $criteriaSet) use ($self) {
            $self->criteriaSetShouldHaveServiceTypeCriteria($criteriaSet, 'LightSaml\Model\Metadata\AssertionConsumerService');

            return array(TestHelper::getEndpointReferenceMock($self, $endpoint = new AssertionConsumerService()));
        });

        $this->action->execute($context);
    }

    /**
     * @param LoggerInterface           $logger
     * @param EndpointResolverInterface $endpointResolver
     *
     * @return ResolveEndpointBaseAction
     */
    protected function createAction(LoggerInterface $logger, EndpointResolverInterface $endpointResolver)
    {
        return new ResolveEndpointSpAcsAction($logger, $endpointResolver);
    }
}
