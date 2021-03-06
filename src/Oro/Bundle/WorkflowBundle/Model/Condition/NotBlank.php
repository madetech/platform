<?php

namespace Oro\Bundle\WorkflowBundle\Model\Condition;

class NotBlank implements ConditionInterface
{
    /**
     * @var Blank
     */
    protected $blankCondition;

    /**
     * Constructor
     *
     * @param Blank $blankCondition
     */
    public function __construct(Blank $blankCondition)
    {
        $this->blankCondition = $blankCondition;
    }

    /**
     * Check if values equals.
     *
     * @param mixed $context
     * @return boolean
     */
    public function isAllowed($context)
    {
        return !$this->blankCondition->isAllowed($context);
    }

    /**
     * Initialize condition options
     *
     * @param array $options
     * @return NotBlank
     */
    public function initialize(array $options)
    {
        $this->blankCondition->initialize($options);

        return $this;
    }
}
