<?php

namespace LightSaml\Tests\Model\Assertion;

use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Assertion\AttributeStatement;
use LightSaml\Model\Assertion\AuthnStatement;
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Assertion\Subject;

class AssertionTest extends \PHPUnit_Framework_TestCase
{
    public function equals_provider()
    {
        $assertion = new Assertion();
        $assertion1 = new Assertion();
        $assertion2 = new Assertion();
        $assertion3 = new Assertion();
        $assertion4 = new Assertion();
        $subject = new Subject();
        $subject1 = new Subject();
        $subject2 = new Subject();
        $subject3 = new Subject();
        return array(
            array('nameId', 'format', false, new Assertion()),
            array('nameId', 'format', false, $assertion->setSubject(new Subject())),
            array('nameId', 'format', false, $assertion1->setSubject($subject->setNameID(new NameID('nameId')))),
            array('nameId', 'format', true, $assertion2->setSubject($subject1->setNameID(new NameID('nameId', 'format')))),
            array('nameId', 'format', false, $assertion3->setSubject($subject2->setNameID(new NameID('other', 'format')))),
            array('nameId', 'format', false, $assertion4->setSubject($subject3->setNameID(new NameID('nameId', 'other')))),
        );
    }

    /**
     * @dataProvider equals_provider
     */
    public function test_equals($nameId, $format, $expectedValue, Assertion $assertion)
    {
        $this->assertEquals($expectedValue, $assertion->equals($nameId, $format));
    }

    public function has_session_index_provider()
    {
        $assertion = new Assertion();
        $assertion1 = new Assertion();
        $authnStatement = new AuthnStatement();
        $authnStatement1 = new AuthnStatement();
        $authnStatement2 = new AuthnStatement();
        $assertion2 = new Assertion();
        return array(
            array('1111', false, new Assertion()),
            array('1111', false, $assertion->addItem(new AuthnStatement())),
            array('1111', false, $assertion1->addItem($authnStatement->setSessionIndex('222'))),
            array('1111', true, $assertion2
                ->addItem($authnStatement1->setSessionIndex('222'))
                ->addItem($authnStatement2->setSessionIndex('1111'))
            ),
        );
    }

    /**
     * @dataProvider has_session_index_provider
     */
    public function test_has_session_index($sessionIndex, $expectedValue, Assertion $assertion)
    {
        $this->assertEquals($expectedValue, $assertion->hasSessionIndex($sessionIndex));
    }

    public function has_any_session_index_provider()
    {
        $assertion = new Assertion();
        $assertion1 = new Assertion();
        $authnStatement = new AuthnStatement();
        $authnStatement1 = new AuthnStatement();
        $authnStatement2 = new AuthnStatement();
        $assertion2 = new Assertion();
        return array(
            array(false, new Assertion()),
            array(false, $assertion->addItem(new AuthnStatement())),
            array(true, $assertion1->addItem($authnStatement2->setSessionIndex('123'))),
            array(true, $assertion2
                ->addItem($authnStatement->setSessionIndex('111'))
                ->addItem($authnStatement1->setSessionIndex('222'))
            ),
        );
    }

    /**
     * @dataProvider has_any_session_index_provider
     */
    public function test_has_any_session_index($expectedValue, Assertion $assertion)
    {
        $this->assertEquals($expectedValue, $assertion->hasAnySessionIndex());
    }

    public function test_get_all_attribute_statements()
    {
        $assertion = new Assertion();
        $assertion->addItem(new AuthnStatement());
        $assertion->addItem($attributeStatement1 = new AttributeStatement());
        $assertion->addItem(new AuthnStatement());
        $assertion->addItem($attributeStatement2 = new AttributeStatement());

        $arr = $assertion->getAllAttributeStatements();

        $this->assertCount(2, $arr);
        $this->assertSame($attributeStatement1, $arr[0]);
        $this->assertSame($attributeStatement2, $arr[1]);
    }
}
