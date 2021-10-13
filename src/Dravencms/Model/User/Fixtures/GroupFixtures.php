<?php declare(strict_types = 1);

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */
namespace Dravencms\Model\User\Fixtures;

use Dravencms\Model\User\Entities\Group;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;


class GroupFixtures extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $group = new Group('Administrator', 'Skupina se vsemi pravy', 'd9534f');
        $manager->persist($group);
        $this->addReference('user-group-administrator', $group);
        $manager->flush();
    }
}