<?php declare(strict_types=1);

namespace Dravencms\Security;

use Dravencms\Model\User\Entities\User;
use Dravencms\User\Acl\AclPermissionBuilder;
use Nette\Security\{Permission, UserStorage};

class Authorizator implements \Nette\Security\Authorizator
{
    private ?Permission $acl = null;
    private ?User $identity = null;

    public function __construct(private UserStorage $userStorage, private Authenticator $authenticator, private AclPermissionBuilder $builder)
    {
    }

    private function getUserIdentity(): ?User
    {
        if ($this->identity === null) {
            [$loggedIn, $identity] = $this->userStorage->getState();
            if (!$loggedIn || $identity === null) { return null; }
            $this->identity = $this->authenticator->wakeupIdentity($identity);
        }
        return $this->identity;
    }

    public function initiate(): void
    {
        $this->acl = $this->builder->build($this->getUserIdentity()?->getRoles() ?? []);
    }

    public function isAllowed($role, $resource, $privilege): bool
    {
        if ($this->acl === null) { $this->initiate(); }
        if ($resource !== null && !$this->acl->hasResource($resource)) { return false; }
        if ($role === null) {
            foreach ($this->getUserIdentity()?->getRoles() ?? [] as $group) {
                if ($this->acl->isAllowed($group->getName(), $resource, $privilege)) { return true; }
            }
            return false;
        }
        if ($role instanceof \Dravencms\Model\User\Entities\Group) { $role = $role->getName(); }
        elseif ($role instanceof \Nette\Security\Role) { $role = $role->getRoleId(); }
        return is_string($role) && $this->acl->hasRole($role) && $this->acl->isAllowed($role, $resource, $privilege);
    }
}
