<?php declare(strict_types=1);

namespace Dravencms\User\Acl;

use Nette\Security\Permission;

final class AclPermissionBuilder
{
    public function __construct(private AclRegistry $registry)
    {
    }

    public function build(iterable $groups): Permission
    {
        $acl = new Permission(); $definitions = $this->registry->getPermissions();
        foreach ($groups as $group) {
            $role = $group->getName();
            if (!$acl->hasRole($role)) { $acl->addRole($role); }
            $explicit = [];
            foreach ($group->getAclOperations() as $operation) { $explicit[] = $operation->getKey(); }
            foreach ($this->registry->resolve($explicit, strict: false) as $key) {
                $permission = $definitions[$key];
                if (!$acl->hasResource($permission->resource)) { $acl->addResource($permission->resource); }
                $acl->allow($role, $permission->resource, $permission->privilege);
            }
        }
        return $acl;
    }
}
