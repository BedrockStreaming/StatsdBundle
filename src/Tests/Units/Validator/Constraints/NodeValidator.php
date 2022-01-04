<?php

namespace M6Web\Bundle\StatsdBundle\Tests\Units\Validator\Constraints;

use M6Web\Bundle\StatsdBundle\Validator\Constraints\NodeValidator as TestedClass;

/**
 * Nodevalidator test class
 */
class NodeValidator extends \atoum
{
    /**
     * Simple test against validatePattern
     */
    public function testValidate()
    {
        $this
            ->boolean(TestedClass::validatePattern('raoul'))
            ->isIdenticalTo(true)
            ->boolean(TestedClass::validatePattern('23'))
            ->isIdenticalTo(true)
            ->boolean(TestedClass::validatePattern('raoul.node.raoul'))
            ->isIdenticalTo(true)
            ->boolean(TestedClass::validatePattern('raoul.$\\'))
            ->isIdenticalTo(false)
            ->boolean(TestedClass::validatePattern('é'))
            ->isIdenticalTo(false)
            ->boolean(TestedClass::validatePattern('î'))
            ->isIdenticalTo(false)
        ;
    }
}
