<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Dravencms\Security\PasswordManager;
use Dravencms\Model\User\Entities\User;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

class UserFixtures extends AbstractFixture implements DependentFixtureInterface
{
    /** @var PasswordManager */
    private $passwordManager;
    /**
     * UsersFixtures constructor.
     */
    public function __construct()
    {
        $this->passwordManager = new PasswordManager();
    }
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        $users = [];
        $users['admin@example.com'] = [
            'password' => 'adminExample',
            'firstName' => 'Admin',
            'lastName' => 'Example'
        ];
        foreach($users AS $email => $data)
        {
            $user = new User($data['firstName'], $data['lastName'], $email, $data['password'], 'Front', true, false, true, function($password) { return $this->passwordManager->hash($password); });
            $manager->persist($user);
            $user = new User($data['firstName'], $data['lastName'], $email, $data['password'], 'Admin', true, false, true, function($password) { return $this->passwordManager->hash($password); });
            $user->addGroup($this->getReference('user-group-administrator'));
            $manager->persist($user);
        }
        $manager->flush();
    }
    /**
     * Get the order of this fixture
     *
     * @return integer
     */
    public function getDependencies()
    {
        return ['Dravencms\Model\User\Fixtures\GroupFixtures'];
    }
}
