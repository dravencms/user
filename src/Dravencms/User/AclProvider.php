<?php declare(strict_types=1);

namespace Dravencms\User;

use Dravencms\User\Acl\{AclPermission, AclProviderInterface};

final class AclProvider implements AclProviderInterface
{
    public function getPermissions(): iterable
    {
        yield new AclPermission('user', 'edit', 'user.acl.permissions.edit.label', resourceLabel: 'user.acl.resource', description: 'user.acl.permissions.edit.description');
        yield new AclPermission('user', 'delete', 'user.acl.permissions.delete.label', resourceLabel: 'user.acl.resource');
        yield new AclPermission('user', 'companyEdit', 'user.acl.permissions.companyEdit.label', resourceLabel: 'user.acl.resource');
        yield new AclPermission('user', 'companyDelete', 'user.acl.permissions.companyDelete.label', resourceLabel: 'user.acl.resource');
    }
}
