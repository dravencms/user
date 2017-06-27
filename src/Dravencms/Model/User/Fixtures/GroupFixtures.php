<?php

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */
namespace Dravencms\Model\User\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Dravencms\Model\User\Entities\Group;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;


class GroupFixtures extends AbstractFixture
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $group = new Group('Administrator', 'Skupina se vsemi pravy', 'd9534f');
        $manager->persist($group);
        $this->addReference('user-group-administrator', $group);
        $manager->flush();
    }
}