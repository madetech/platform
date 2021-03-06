<?php

namespace Oro\Bundle\GridBundle\Action\MassAction;

interface MassActionResponseInterface
{
    /**
     * @return boolean
     */
    public function isSuccessful();

    /**
     * @return array
     */
    public function getOptions();

    /**
     * @return string
     */
    public function getMessage();

    /**
     * @param string $name
     * @return mixed
     */
    public function getOption($name);
}
