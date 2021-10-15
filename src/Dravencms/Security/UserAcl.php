<?php declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: sadam
 * Date: 10/15/21
 * Time: 2:57 AM
 */

namespace Dravencms\Security;


use Nette\Security\IIdentity;
use Nette\Security\IUserStorage;
use Nette\Security\Permission;
use Nette\Security\UserStorage;


class UserAcl
{
    /** @var \Nette\Security\User */
    private $user;

    /** @var Permission */
    private $acl;

    /**
     * UserAcl constructor.
     * @param \Nette\Security\User $user
     */
    public function __construct(\Nette\Security\User $user)
    {
        $this->user = $user;
    }

    public function initiate()
    {
        $acl = new Permission();
        
        /** @var Group $role */
        foreach ($this->user->getIdentity()->getRoles() AS $role)
        {
            $acl->addRole($role->getName());

            foreach($role->getAclOperations() AS $aclOperation)
            {
                $resourceName = $aclOperation->getAclResource()->getName();
                if (!$acl->hasResource($resourceName))
                {
                    $acl->addResource($resourceName);
                }
                $acl->allow($role->getName(), $resourceName, $aclOperation->getName());
            }
        }

        $this->acl = $acl;
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
            foreach ($this->user->getIdentity()->getRoles() AS $role)
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