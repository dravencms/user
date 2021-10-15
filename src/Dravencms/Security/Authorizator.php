<?php declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: sadam
 * Date: 10/15/21
 * Time: 2:57 AM
 */

namespace Dravencms\Security;


use Dravencms\Model\User\Entities\User;
use Nette\Security\IIdentity;
use Nette\Security\IUserStorage;
use Nette\Security\Permission;
use Nette\Security\UserStorage;


class Authorizator implements \Nette\Security\Authorizator
{
    /** @var UserStorage */
    private $userStorage;

    /** @var Authenticator */
    private $authenticator;

    /** @var Permission */
    private $acl;

    /** @var null|User */
    private $identity = null;

    /**
     * UserAcl constructor.
     * @param \Nette\Security\User $user
     */
    public function __construct(UserStorage $userStorage, Authenticator $authenticator)
    {
        $this->userStorage = $userStorage;
        $this->authenticator = $authenticator;
    }

    private function getUserIdentity(): User {

        if (is_null($this->identity)){
            [$loggedIn, $identity, $logoutReason] = $this->userStorage->getState();
            $this->identity = $this->authenticator->wakeupIdentity($identity);
        }

        return $this->identity;
    }

    public function initiate()
    {
        $acl = new Permission();

        $identity = $this->getUserIdentity();

        /** @var Group $role */
        foreach ($identity->getRoles() AS $role)
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
    public function isAllowed($role, $resource, $privilege): bool
    {
        if (is_null($role))
        {
            $identity = $this->getUserIdentity();
            /** @var Group $role */
            foreach ($identity->getRoles() AS $role)
            {
                if ($this->acl->hasResource($resource) && $this->acl->isAllowed($role->getName(), $resource, $privilege))
                {
                    return true;
                }
            }
        }
        else
        {
            return ($this->acl->hasResource($resource) && $this->acl->isAllowed($role->getName(), $resource, $privilege));
        }

        return false;
    }
}