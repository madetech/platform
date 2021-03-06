<?php

namespace Oro\Bundle\WorkflowBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\AddConditionAndPostActionCompilerPass;
use Oro\Bundle\WorkflowBundle\DependencyInjection\Compiler\AddAttributeNormalizerCompilerPass;

class OroWorkflowBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new AddConditionAndPostActionCompilerPass());
        $container->addCompilerPass(new AddAttributeNormalizerCompilerPass());
    }
}
