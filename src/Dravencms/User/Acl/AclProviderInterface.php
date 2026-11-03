<?php declare(strict_types=1);

namespace Dravencms\User\Acl;

interface AclProviderInterface
{
    /** @return iterable<AclPermission> */
    public function getPermissions(): iterable;
}
