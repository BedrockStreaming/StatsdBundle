<?php

namespace M6Web\Bundle\StatsdBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * constraint for a statsd node
 *
 * @Annotation
 */
class Node extends Constraint
{
    /**
     * constraint constructor
     *
     * @param array $options options
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
    }
}
