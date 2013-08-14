<?php
namespace M6Web\Bundle\StatsdBundle\Tests\Units\Validator\Constraints;

use M6Web\Bundle\StatsdBundle\Validator\Constraints\NodeValidator as TestedClass;
use mageekguy\atoum;

require_once __DIR__.'/../../../../../../../../vendor/autoload.php';

/**
* Nodevalidator test class
*/
class NodeValidator extends atoum\test
{

    /**
     * simple test against validatePattern
     * @return void
     */
    public function testValidate()
    {
        // $nodevalidator = new TestedClass();

        // $context = \mock\Symfony\Component\Validator\ExecutionContextInterface

        // var_dump($nodevalidator->context);
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
            ->isIdenticalTo(false);
    }
}
