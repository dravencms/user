<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Dravencms\Model\Admin\Entities\Menu;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class AdminMenuFixtures extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        if (!class_exists(Menu::class)) {
            trigger_error('dravencms/admin module not found, dravencms/user module wont install Admin menu entries', E_USER_NOTICE);
            return;
        }

        $menu = $manager->getRepository(Menu::class);
        // Users
        $root = new Menu('Users', null, 'fa-users', $this->getReference('user-acl-operation-user-edit'), null);
        $manager->persist($root);
        $child = new Menu('Overview', ':Admin:User:User', 'fa-bars', $this->getReference('user-acl-operation-user-edit'));
        $manager->persist($child);
        $menu->persistAsLastChildOf($child, $root);
        $child = new Menu('Groups', ':Admin:User:Group', 'fa-sitemap', $this->getReference('user-acl-operation-user-edit'));
        $manager->persist($child);
        $menu->persistAsLastChildOf($child, $root);
        $child = new Menu('Acl', ':Admin:User:Acl', 'fa-unlock', $this->getReference('user-acl-operation-user-edit'));
        $manager->persist($child);
        $menu->persistAsLastChildOf($child, $root);
        $manager->flush();
    }
    /**
     * Get the order of this fixture
     *
     * @return array
     */
    public function getDependencies(): array
    {
        return ['Dravencms\Model\User\Fixtures\AclOperationFixtures'];
    }
}