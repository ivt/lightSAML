<?php

namespace LightSaml\Tests\Action\Assertion\Inbound;

use LightSaml\Action\Assertion\Inbound\RecipientValidatorAction;
use LightSaml\Criteria\CriteriaSet;
use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Assertion\AuthnStatement;
use LightSaml\Model\Assertion\Subject;
use LightSaml\Model\Assertion\SubjectConfirmation;
use LightSaml\Model\Assertion\SubjectConfirmationData;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria;
use LightSaml\Resolver\Endpoint\Criteria\LocationCriteria;
use LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria;
use LightSaml\SamlConstants;
use LightSaml\Tests\TestHelper;

class RecipientValidatorActionTest extends \PHPUnit_Framework_TestCase
{
    public function test_constructs_with_logger()
    {
        new RecipientValidatorAction(TestHelper::getLoggerMock($this), TestHelper::getEndpointResolverMock($this));
    }

    public function test_does_nothing_when_assertion_has_bearer_subject_but_no_authn_statement()
    {
        $action = new RecipientValidatorAction($loggerMock = TestHelper::getLoggerMock($this), TestHelper::getEndpointResolverMock($this));

        $assertionContext = TestHelper::getAssertionContext($assertion = new Assertion());
        $assertion->setSubject(new Subject());
        $subjectConfirmation = new SubjectConfirmation();
        $assertion->getSubject()->addSubjectConfirmation($subjectConfirmation->setMethod(SamlConstants::CONFIRMATION_METHOD_BEARER));

        $action->execute($assertionContext);
    }

    public function test_does_nothing_when_assertion_has_authn_statement_but_no_bearer_subject()
    {
        $action = new RecipientValidatorAction($loggerMock = TestHelper::getLoggerMock($this), TestHelper::getEndpointResolverMock($this));

        $assertionContext = TestHelper::getAssertionContext($assertion = new Assertion());
        $assertion->addItem(new AuthnStatement());

        $action->execute($assertionContext);
    }

    /**
     * @expectedException \LightSaml\Error\LightSamlContextException
     * @expectedExceptionMessage Bearer SubjectConfirmation must contain Recipient attribute
     */
    public function test_throws_context_exception_when_bearer_confirmation_has_no_recipient()
    {
        $action = new RecipientValidatorAction($loggerMock = TestHelper::getLoggerMock($this), TestHelper::getEndpointResolverMock($this));

        $assertionContext = TestHelper::getAssertionContext($assertion = new Assertion());
        $assertion->addItem(new AuthnStatement());
        $assertion->setSubject(new Subject());
        $subjectConfirmation1 = new SubjectConfirmation();
        $assertion->getSubject()->addSubjectConfirmation($subjectConfirmation = $subjectConfirmation1->setMethod(SamlConstants::CONFIRMATION_METHOD_BEARER));
        $subjectConfirmation->setSubjectConfirmationData(new SubjectConfirmationData());

        $loggerMock->expects($this->once())
            ->method('error')
            ->with('Bearer SubjectConfirmation must contain Recipient attribute');

        $action->execute($assertionContext);
    }

    /**
     * @expectedException \LightSaml\Error\LightSamlContextException
     * @expectedExceptionMessage Recipient 'http://recipient.com' does not match SP descriptor
     */
    public function test_throws_context_exception_when_recipient_does_not_match_any_own_acs_service_location()
    {
        $action = new RecipientValidatorAction(
            $loggerMock = TestHelper::getLoggerMock($this),
            $endpointResolver = TestHelper::getEndpointResolverMock($this)
        );

        $assertionContext = TestHelper::getAssertionContext($assertion = new Assertion());
        $assertion->addItem(new AuthnStatement());
        $assertion->setSubject(new Subject());
        $subjectConfirmation1 = new SubjectConfirmation();
        $assertion->getSubject()->addSubjectConfirmation($subjectConfirmation = $subjectConfirmation1->setMethod(SamlConstants::CONFIRMATION_METHOD_BEARER));
        $subjectConfirmationData = new SubjectConfirmationData();
        $subjectConfirmation->setSubjectConfirmationData($subjectConfirmationData->setRecipient($recipient = 'http://recipient.com'));

        $profileContext = TestHelper::getProfileContext();
        $profileContext->getOwnEntityContext()->setEntityDescriptor($ownEntityDescriptor = new EntityDescriptor());
        $assertionContext->setParent($profileContext);

        $endpointResolver->expects($this->once())
            ->method('resolve')
            ->with($this->isInstanceOf('\LightSaml\Criteria\CriteriaSet'), $this->isType('array'))
            ->willReturnCallback(function (\LightSaml\Criteria\CriteriaSet $criteriaSet) use ($recipient) {
                TestHelper::assertCriteria($this, $criteriaSet, '\LightSaml\Resolver\Endpoint\Criteria\DescriptorTypeCriteria', 'getDescriptorType', '\LightSaml\Model\Metadata\SpSsoDescriptor');
                TestHelper::assertCriteria($this, $criteriaSet, '\LightSaml\Resolver\Endpoint\Criteria\ServiceTypeCriteria', 'getServiceType', '\LightSaml\Model\Metadata\AssertionConsumerService');
                TestHelper::assertCriteria($this, $criteriaSet, '\LightSaml\Resolver\Endpoint\Criteria\LocationCriteria', 'getLocation', $recipient);

                return array();
            });

        $loggerMock->expects($this->once())
            ->method('error')
            ->with("Recipient 'http://recipient.com' does not match SP descriptor");

        $action->execute($assertionContext);
    }

    public function test_does_nothing_if_recipient_matches_own_acs_service_location()
    {
        $action = new RecipientValidatorAction(
            $loggerMock = TestHelper::getLoggerMock($this),
            $endpointResolver = TestHelper::getEndpointResolverMock($this)
        );

        $assertionContext = TestHelper::getAssertionContext($assertion = new Assertion());
        $assertion->addItem(new AuthnStatement());
        $assertion->setSubject(new Subject());
        $subjectConfirmation1 = new SubjectConfirmation();
        $assertion->getSubject()->addSubjectConfirmation($subjectConfirmation = $subjectConfirmation1->setMethod(SamlConstants::CONFIRMATION_METHOD_BEARER));
        $subjectConfirmationData = new SubjectConfirmationData();
        $subjectConfirmation->setSubjectConfirmationData($subjectConfirmationData->setRecipient($recipient = 'http://recipient.com'));

        $profileContext = TestHelper::getProfileContext();
        $profileContext->getOwnEntityContext()->setEntityDescriptor($ownEntityDescriptor = new EntityDescriptor());
        $assertionContext->setParent($profileContext);

        $endpointResolver->expects($this->once())
            ->method('resolve')
            ->willReturnCallback(function () use ($recipient) {
                return array(TestHelper::getEndpointReferenceMock($this, new AssertionConsumerService()));
            });

        $action->execute($assertionContext);
    }
}
