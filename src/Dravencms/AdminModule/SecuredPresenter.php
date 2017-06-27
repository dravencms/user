<?php

namespace Dravencms\AdminModule;

use Dravencms\User\DefaultDataCreator;
use Dravencms\Model\User\Entities\Group;
use Dravencms\Model\User\Entities\User;
use Kdyby\Doctrine\EntityManager;
use Nette\Http\IResponse;
use Nette\Security\Permission;
use Nette\Reflection\Method;


/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */
abstract class SecuredPresenter extends BasePresenter
{
    /** @var EntityManager @inject */
    public $entityManager;

    /** @var DefaultDataCreator @inject */
    public $defaultDataCreator;

    /** @var \Nette\Security\Permission */
    private $acl;

    /** @var bool */
    private $assigned = false;

    /**
     * Checks authorization.
     * @param $element
     * @throws \Exception
     * @return void
     */
    public function checkRequirements($element)
    {
        parent::checkRequirements($element);

        if (!$this->getUser()->isLoggedIn())
        {
            $this->redirect(':Admin:User:Sign:In', ['backlink' => $this->storeRequest()]);
        }
        elseif ($this->getUser()->isLoggedIn())
        {
            if ($this->getUserEntity()->initializeDefaultData($this->defaultDataCreator))
            {
                $this->entityManager->flush();
                $this->redirect('this');
            }

            $this->assignUserInfo();
        }


        if ($element->hasAnnotation('isAllowed'))
        {
            $methodReflection = Method::from($element->class, $element->name);

            if ($methodReflection->hasAnnotation('isAllowed'))
            {
                $data = $methodReflection->getAnnotation('isAllowed');
                $this->checkPermission($data[0], $data[1]);
            }
        }
    }

    private function assignUserInfo()
    {
        if ($this->assigned) return;

        /** @var User $user */
        $user = $this->getUser()->getIdentity();
        $user->setLastActivity(new \DateTime());
        $this->entityManager->flush();

        $this->template->userInfo = $user;

        $this->acl = new Permission;

        /** @var Group $role */
        foreach ($user->getRoles() AS $role)
        {
            $this->acl->addRole($role->getName());

            foreach($role->getAclOperations() AS $aclOperation)
            {
                $resourceName = $aclOperation->getAclResource()->getName();
                if (!$this->acl->hasResource($resourceName))
                {
                    $this->acl->addResource($resourceName);
                }
                $this->acl->allow($role->getName(), $resourceName, $aclOperation->getName());
            }
        }

        $this->assigned = true;
    }

    /**
     * @param $resource
     * @param $operation
     * @param null $role
     * @return bool
     */
    public function isAllowed($resource, $operation, $role = null)
    {
        if (is_null($role))
        {
            /** @var Group $role */
            foreach ($this->user->getRoles() AS $role)
            {
                if ($this->acl->hasResource($resource) && $this->acl->isAllowed($role->getName(), $resource, $operation))
                {
                    return true;
                }
            }
        }
        else
        {
            return ($this->acl->hasResource($resource) && $this->acl->isAllowed($role, $resource, $operation));
        }

        return false;
    }

    /**
     * @param string $resource
     * @param string $operation
     * @throws \Nette\Application\BadRequestException
     */
    public function checkPermission($resource, $operation)
    {
        if (!$this->isAllowed($resource, $operation))
        {
            $this->error('FORBIDDEN '.$resource.':'.$operation, IResponse::S403_FORBIDDEN);
        }
    }
}