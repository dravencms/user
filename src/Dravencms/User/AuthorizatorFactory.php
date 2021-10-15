<?php declare(strict_types = 1);
/**
 * Created by PhpStorm.
 * User: sadam
 * Date: 10/15/21
 * Time: 2:57 AM
 */

namespace Dravencms\User;


use Nette\Security\IIdentity;
use Nette\Security\Permission;


class AuthorizatorFactory
{
    /** @var IIdentity */
    private $identity;

    public function __construct(IIdentity $identity)
    {
        $this->identity = $identity;
    }

    public function create(): Permission
    {
        $acl = new Permission();

        /** @var Group $role */
        foreach ($this->identity->getRoles() AS $role)
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

        return $acl;
    }
}