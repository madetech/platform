<?php

namespace Oro\Bundle\WindowsBundle\Controller\Api;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use FOS\Rest\Util\Codes;
use Symfony\Component\Security\Core\User\UserInterface;

use Oro\Bundle\WindowsBundle\Entity\WindowsState;
use Oro\Bundle\UserBundle\Annotation\Acl;

/**
 * @RouteResource("windows")
 * @NamePrefix("oro_api_")
 *
 * @Acl(
 *     id="oro_windows_state_api_rest",
 *     name="Windows state",
 *     description="Windows state",
 *     parent="root"
 * )
 */
class WindowsStateController extends FOSRestController
{
    /**
     * REST GET list
     *
     * @ApiDoc(
     *  description="Get all Windows States for user",
     *  resource=true
     * )
     * @return Response
     *
     * @Acl(
     *     id="oro_windows_state_api_rest_get",
     *     name="Get windows state",
     *     description="Get windows state",
     *     parent="oro_windows_state_api_rest"
     * )
     */
    public function cgetAction()
    {
        $items = $this->getDoctrine()->getRepository('OroWindowsBundle:WindowsState')->findBy(array('user' => $this->getUser()));

        return $this->handleView(
            $this->view($items, is_array($items) ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND)
        );
    }

    /**
     * REST POST
     *
     * @ApiDoc(
     *  description="Add Windows State",
     *  resource=true
     * )
     * @return Response
     *
     * @Acl(
     *     id="oro_windows_state_api_rest_post",
     *     name="Create windows state",
     *     description="Create windows state item",
     *     parent="oro_windows_state_api_rest"
     * )
     */
    public function postAction()
    {
        $postArray = $this->getPost();

        /** @var $user UserInterface */
        $user = $this->getUser();
        $postArray['user'] = $user;

        /** @var $entity \Oro\Bundle\WindowsBundle\Entity\WindowsState */
        $entity = new WindowsState();
        $entity->setData($postArray['data']);
        $entity->setUser($user);

        $manager = $this->getManager();
        $manager->persist($entity);
        $manager->flush();

        return $this->handleView(
            $this->view(array('id' => $entity->getId()), Codes::HTTP_CREATED)
        );
    }

    /**
     * REST PUT
     *
     * @param int $windowId Window state id
     *
     * @ApiDoc(
     *  description="Update Windows state item",
     *  resource=true
     * )
     * @return Response
     *
     * @Acl(
     *     id="oro_windows_state_api_rest_put",
     *     name="Update windows state",
     *     description="Update windows state item",
     *     parent="oro_windows_state_api_rest"
     * )
     */
    public function putAction($windowId)
    {
        $postArray = $this->getPost();

        /** @var $entity \Oro\Bundle\WindowsBundle\Entity\WindowsState */
        $entity = $this->getManager()->find('OroWindowsBundle:WindowsState', (int)$windowId);
        if (!$entity) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }
        if (!$this->validatePermissions($entity->getUser())) {
            return $this->handleView($this->view(array(), Codes::HTTP_FORBIDDEN));
        }

        $entity->setData($postArray['data']);

        $em = $this->getManager();
        $em->persist($entity);
        $em->flush();

        return $this->handleView($this->view(array(), Codes::HTTP_OK));
    }

    /**
     * REST DELETE
     *
     * @param int $windowId
     *
     * @ApiDoc(
     *  description="Remove Windows state",
     *  resource=true
     * )
     * @return Response
     *
     * @Acl(
     *     id="oro_windows_state_api_rest_delete",
     *     name="Delete windows state item",
     *     description="Delete windows state item",
     *     parent="oro_windows_state_api_rest"
     * )
     */
    public function deleteAction($windowId)
    {
        /** @var $entity \Oro\Bundle\WindowsBundle\Entity\WindowsState */
        $entity = $this->getManager()->find('OroWindowsBundle:WindowsState', (int)$windowId);
        if (!$entity) {
            return $this->handleView($this->view(array(), Codes::HTTP_NOT_FOUND));
        }
        if (!$this->validatePermissions($entity->getUser())) {
            return $this->handleView($this->view(array(), Codes::HTTP_FORBIDDEN));
        }

        $em = $this->getManager();
        $em->remove($entity);
        $em->flush();

        return $this->handleView($this->view(array(), Codes::HTTP_NO_CONTENT));
    }

    /**
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @return array
     */
    protected function getPost()
    {
        $postArray = $this->getRequest()->request->all();
        if (is_array($postArray) && array_key_exists('data', $postArray)) {
            if (array_key_exists('url', $postArray['data'])) {
                $postArray['data']['cleanUrl'] = str_replace($this->getRequest()->server->get('SCRIPT_NAME'), '', $postArray['data']['url']);
            }
        } else {
            throw new HttpException(Codes::HTTP_BAD_REQUEST, 'Wrong JSON inside POST body');
        }
        return $postArray;
    }

    /**
     * Validate permissions
     *
     * TODO: refactor this to use Symfony2 ACL
     *
     * @param UserInterface $user
     * @return bool
     */
    protected function validatePermissions(UserInterface $user)
    {
        return $user->getUsername() == $this->getUser()->getUsername();
    }

    /**
     * Get entity Manager
     *
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getManager()
    {
        return $this->getDoctrine()->getManagerForClass('OroWindowsBundle:WindowsState');
    }
}
