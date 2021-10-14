<?php declare(strict_types = 1);

namespace Dravencms\User;

use Dravencms\BasePresenter;
use Dravencms\Model\User\Entities\Group;
use Dravencms\Model\User\Entities\User;
use Dravencms\Database\EntityManager;
use Nette\Http\IResponse;
use Nette\Security\Permission;


/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */
abstract class SecuredPresenter extends BasePresenter
{
    public static $redirectUnauthorizedTo = null;

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
    public function checkRequirements($element): void
    {
        parent::checkRequirements($element);

        if (!$this->getUser()->isLoggedIn())
        {
            if (is_null(self::$redirectUnauthorizedTo)) {
                $this->error('Unauthorized', IResponse::S401_UNAUTHORIZED);
            } else {
                $this->redirect(':Admin:User:Sign:In', ['backlink' => $this->storeRequest()]);
            }
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
            list($resource, $operation) = $element->getAnnotation('isAllowed');
            $this->checkPermission($resource, $operation);
        }
    }

    private function assignUserInfo(): void
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
     * @param string $resource
     * @param string $operation
     * @param string|null $role
     * @return bool
     */
    public function isAllowed(string $resource, string $operation, string $role = null): bool
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
    public function checkPermission(string $resource, string $operation): void
    {
        if (!$this->isAllowed($resource, $operation))
        {
            $this->error('FORBIDDEN '.$resource.':'.$operation, IResponse::S403_FORBIDDEN);
        }
    }
}