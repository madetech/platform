<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Pim\Bundle\FlexibleEntityBundle\Form\Type\FlexibleValueType;

class UserValueType extends FlexibleValueType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_user_user_value';
    }
}
