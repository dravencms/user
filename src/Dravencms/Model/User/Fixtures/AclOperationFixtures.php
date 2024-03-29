<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Fixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Dravencms\Model\User\Entities\AclOperation;
use Dravencms\Model\User\Entities\AclResource;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class AclOperationFixtures extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * Load data fixtures with the passed EntityManager
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager): void
    {
        $operations = [
            'user' => [
                'edit' => 'Umožnuje editaci uživatelů',
                'delete' => 'Umožnuje smazání uživatelů	',
                'companyEdit' => 'Allows editation of company',
                'companyDelete' => 'Allows deletion of company'
            ]
        ];
        foreach ($operations AS $resourceName => $operationList)
        {
            /** @var AclResource $aclResource */
            $aclResource = $this->getReference('user-acl-resource-'.$resourceName);
            foreach ($operationList AS $operationName => $operationDescription)
            {
                $aclOperation = new AclOperation($aclResource, $operationName, $operationDescription);
                //Allow all operations to administrator group
                $aclOperation->addGroup($this->getReference('user-group-administrator'));
                $manager->persist($aclOperation);
                $this->addReference('user-acl-operation-'.$resourceName.'-'.$operationName, $aclOperation);
            }
        }
        $manager->flush();
    }

    /**
     * Get the order of this fixture
     *
     * @return array
     */
    public function getDependencies(): array
    {
        return ['Dravencms\Model\User\Fixtures\AclResourceFixtures', 'Dravencms\Model\User\Fixtures\GroupFixtures'];
    }
}