<?php declare(strict_types=1);

namespace Dravencms\User;

use Dravencms\User\Acl\AclPermissionBuilder;
use Nette\Security\{IIdentity, Permission};

class AuthorizatorFactory
{
    public function __construct(private IIdentity $identity, private AclPermissionBuilder $builder)
    {
    }

    public function create(): Permission
    {
        return $this->builder->build($this->identity->getRoles());
    }
}
