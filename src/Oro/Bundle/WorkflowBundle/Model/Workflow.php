<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException;
use Oro\Bundle\WorkflowBundle\Exception\UnknownStepException;
use Oro\Bundle\WorkflowBundle\Exception\UnknownTransitionException;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Transition;

class Workflow
{
    const DEFAULT_START_TRANSITION_NAME = '__start__';
    const TYPE_ENTITY = 'entity';
    const TYPE_WIZARD = 'wizard';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var boolean
     */
    protected $enabled;

    /**
     * @var StepManager
     */
    protected $stepManager;

    /**
     * @var AttributeManager
     */
    protected $attributeManager;

    /**
     * @var TransitionManager
     */
    protected $transitionManager;

    /**
     * @var string
     */
    protected $label;

    /**
     * @param StepManager $stepManager
     * @param AttributeManager $attributeManager
     * @param TransitionManager $transitionManager
     */
    public function __construct(
        StepManager $stepManager,
        AttributeManager $attributeManager,
        TransitionManager $transitionManager
    ) {
        $this->stepManager       = $stepManager;
        $this->attributeManager  = $attributeManager;
        $this->transitionManager = $transitionManager;

        $this->enabled = true;
    }

    /**
     * Set enabled.
     *
     * @param boolean $enabled
     * @return Workflow
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool)$enabled;
        return $this;
    }

    /**
     * Is workflow enabled.
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Workflow
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set type.
     *
     * @param string $type
     * @return Workflow
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set label.
     *
     * @param string $label
     * @return Workflow
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Get label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get step by name
     *
     * @param string $stepName
     * @return Step
     */
    public function getStep($stepName)
    {
        return $this->stepManager->getStep($stepName);
    }

    /**
     * Set steps.
     *
     * @param Step[]|Collection $steps
     * @return Workflow
     */
    public function setSteps($steps)
    {
        $this->stepManager->setSteps($steps);
        return $this;
    }

    /**
     * Get steps.
     *
     * @return Collection
     */
    public function getSteps()
    {
        return $this->stepManager->getSteps();
    }

    /**
     * Get steps sorted by order.
     *
     * @return Collection
     */
    public function getOrderedSteps()
    {
        $steps = $this->getSteps()->toArray();
        usort(
            $steps,
            function (Step $stepOne, Step $stepTwo) {
                return ($stepOne->getOrder() >= $stepTwo->getOrder()) ? 1 : -1;
            }
        );
        return new ArrayCollection($steps);
    }

    /**
     * Set attributes.
     *
     * @param Attribute[]|Collection $attributes
     * @return Workflow
     */
    public function setAttributes($attributes)
    {
        $this->attributeManager->setAttributes($attributes);
        return $this;
    }

    /**
     * Get step attributes.
     *
     * @return Collection
     */
    public function getAttributes()
    {
        return $this->attributeManager->getAttributes();
    }

    /**
     * Get attribute by name
     *
     * @param string $attributeName
     * @return Attribute|null
     */
    public function getAttribute($attributeName)
    {
        return $this->attributeManager->getAttribute($attributeName);
    }

    /**
     * Get attributes with option "managed_entity"
     *
     * @return Collection
     */
    public function getManagedEntityAttributes()
    {
        return $this->attributeManager->getManagedEntityAttributes();
    }

    /**
     * Get list of attributes that require binding
     *
     * @return Collection
     */
    public function getBindEntityAttributes()
    {
        return $this->attributeManager->getBindEntityAttributes();
    }

    /**
     * Get list of attributes names that require binding
     *
     * @return array
     */
    public function getBindEntityAttributeNames()
    {
        return $this->attributeManager->getBindEntityAttributeNames();
    }

    /**
     * Set transitions.
     *
     * @param Transition[]|Collection $transitions
     * @return Workflow
     */
    public function setTransitions($transitions)
    {
        $this->transitionManager->setTransitions($transitions);
        return $this;
    }

    /**
     * Get transitions.
     *
     * @return Collection
     */
    public function getTransitions()
    {
        return $this->transitionManager->getTransitions();
    }

    /**
     * @param string $transitionName
     * @return Transition|null
     */
    public function getTransition($transitionName)
    {
        return $this->transitionManager->getTransition($transitionName);
    }

    /**
     * Start workflow.
     *
     * @param array $data
     * @param string $startTransitionName
     * @return WorkflowItem
     */
    public function start(array $data = array(), $startTransitionName = null)
    {
        if (null === $startTransitionName) {
            $startTransitionName = self::DEFAULT_START_TRANSITION_NAME;
        }

        $workflowItem = $this->createWorkflowItem($data);
        $this->transit($workflowItem, $startTransitionName);

        return $workflowItem;
    }

    /**
     * Check if transition allowed for workflow item
     *
     * @param WorkflowItem $workflowItem
     * @param string|Transition $transition
     * @return bool
     */
    public function isTransitionAllowed(WorkflowItem $workflowItem, $transition)
    {
        // get current transition
        $transition = $this->transitionManager->extractTransition($transition);
        if (!$transition) {
            return false;
        }

        // get current step
        $currentStep = null;
        $currentStepName = $workflowItem->getCurrentStepName();
        if ($currentStepName) {
            $currentStep = $this->getStep($currentStepName);
        }

        // if there is no current step - consider transition as a start transition
        if (!$currentStep) {
            return $transition->isStart();
        }

        // if transition is not allowed for current step
        if (!$currentStep->isAllowedTransition($transition->getName())) {
            return false;
        }

        return $transition->isAllowed($workflowItem);
    }

    /**
     * Transit workflow item.
     *
     * @param WorkflowItem $workflowItem
     * @param string|Transition $transition
     * @throws ForbiddenTransitionException
     * @throws UnknownStepException
     * @throws UnknownTransitionException
     */
    public function transit(WorkflowItem $workflowItem, $transition)
    {
        $transition = $this->transitionManager->extractTransition($transition);

        if ($this->isTransitionAllowed($workflowItem, $transition)) {
            $transition->transit($workflowItem);
        } else {
            throw new ForbiddenTransitionException(
                sprintf('Transition "%s" is not allowed.', $transition->getName())
            );
        }
    }

    /**
     * Create workflow item.
     *
     * @param array $data
     * @return WorkflowItem
     * @throws \LogicException
     */
    protected function createWorkflowItem(array $data = array())
    {
        $workflowItem = new WorkflowItem();
        $workflowItem->setWorkflowName($this->getName());
        $workflowItem->getData()->add($data);

        return $workflowItem;
    }

    /**
     * Get allowed start transitions.
     *
     * @param array $data
     * @return Collection
     */
    public function getAllowedStartTransitions(array $data = array())
    {
        $workflowItem = $this->createWorkflowItem($data);

        return $this->transitionManager->getAllowedStartTransitions($workflowItem);
    }

    /**
     * Get allowed transitions for existing workflow item.
     *
     * @param WorkflowItem $workflowItem
     * @return Collection
     * @throws UnknownStepException
     */
    public function getAllowedTransitions(WorkflowItem $workflowItem)
    {
        $currentStepName = $workflowItem->getCurrentStepName();
        $currentStep = $this->getStep($currentStepName);
        if (!$currentStep) {
            throw new UnknownStepException($currentStepName);
        }

        $allowedTransitions = new ArrayCollection();
        $transitionNames = $currentStep->getAllowedTransitions();
        foreach ($transitionNames as $transitionName) {
            if ($this->isTransitionAllowed($workflowItem, $transitionName)) {
                $transition = $this->getTransition($transitionName);
                $allowedTransitions->add($transition);
            }
        }

        return $allowedTransitions;
    }
}
