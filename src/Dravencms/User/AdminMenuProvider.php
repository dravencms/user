<?php declare(strict_types=1);

namespace Dravencms\User;

use Dravencms\Admin\Menu\{AdminMenuItem, AdminMenuProviderInterface};

final class AdminMenuProvider implements AdminMenuProviderInterface
{
    public function getItems(): iterable
    {
        yield new AdminMenuItem('user', 'user.menu.root', icon: 'fa-users', order: 800);
        yield new AdminMenuItem('user.overview', 'user.menu.overview', destination: ':Admin:User:User:default', parent: 'user', icon: 'fa-bars', order: 10, resource: 'user', privilege: 'edit');
        yield new AdminMenuItem('user.groups', 'user.menu.groups', destination: ':Admin:User:Group:default', parent: 'user', icon: 'fa-sitemap', order: 20, resource: 'user', privilege: 'edit');
    }
}
