<?php

namespace Oro\Bundle\ConfigBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Oro\Bundle\ConfigBundle\Provider\SystemConfigurationFormProvider;
use Oro\Bundle\UserBundle\Annotation\Acl;

/**
 * @Acl(
 *     id="oro_config",
 *     name="Configuration",
 *     description="Configuration",
 *     parent="root"
 * )
 */
class ConfigurationController extends Controller
{
    /**
     * @Route(
     *      "/system/{activeGroup}/{activeSubGroup}",
     *      name="oro_config_configuration_system",
     *      defaults={"activeGroup" = null, "activeSubGroup" = null}
     * )
     * @Template()
     * @Acl(
     *      id="oro_config_system",
     *      name="System configuration",
     *      description="System configuration",
     *      parent="oro_config"
     * )
     */
    public function systemAction($activeGroup = null, $activeSubGroup = null)
    {
        $provider = $this->get('oro_config.provider.system_configuration.form_provider');

        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups($activeGroup, $activeSubGroup);

        $tree = $provider->getTree();
        $form = false;
        if ($activeSubGroup !== null) {
            $form = $provider->getForm($activeSubGroup);

            if ($this->get('oro_config.form.handler.config')->process($form, $this->getRequest())) {
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.config.controller.config.saved.message')
                );
            }
        }

        return array(
            'data'           => $tree,
            'form'           => $form ? $form->createView() : null,
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup,
        );
    }
}
