<?php

namespace M6Web\Bundle\StatsdBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * validate a graphite node
 * is the value suitable for graphite
 *
 * @Annotation
 *
 */
class NodeValidator extends ConstraintValidator
{

     /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            // throw new UnexpectedTypeException($value, 'string');
            $this->context->addViolation('node is not given or wrong datatype');

            return;
        }

        if (empty($value)) {
            $this->context->addViolation('the node is empty and isn\'t suitable for graphite');

            return;
        }

        if (!self::validatePattern($value)) {
            $this->context->addViolation('the node : '.$value.' isn\'t suitable for graphite');

            return;
        }
    }

    /**
     * validate against the node patern
     * @param string $value
     *
     * @return boolean
     */
    public static function validatePattern($value)
    {
        $pattern = "#^[a-z0-9\.]+$#i";

        return (boolean) preg_match($pattern, $value);
    }
}
