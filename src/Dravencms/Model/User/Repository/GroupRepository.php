<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Repository;


use Dravencms\Model\User\Entities\Group;
use Kdyby\Doctrine\EntityManager;

/**
 * Class GroupRepository
 * @package App\Model\Repository
 */
class GroupRepository
{
    /** @var \Kdyby\Doctrine\EntityRepository */
    private $groupRepository;

    /**
     * AclRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->groupRepository = $entityManager->getRepository(Group::class);
    }

    /**
     * @param $id
     * @return null|Group
     */
    public function getById($id)
    {
        return $this->groupRepository->findBy(['id' => $id]);
    }

    /**
     * @param $id
     * @return null|Group
     */
    public function getOneById($id)
    {
        return $this->groupRepository->find($id);
    }

    /**
     * @return array
     */
    public function getPairs()
    {
        return $this->groupRepository->findPairs('name');
    }

    /**
     * @param $name
     * @param Group|null $ignoreGroup
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isNameFree($name, Group $ignoreGroup = null)
    {
        $qb = $this->groupRepository->createQueryBuilder('g')
            ->select('g')
            ->where('g.name = :name')
            ->setParameters([
                'name' => $name
            ]);

        if ($ignoreGroup)
        {
            $qb->andWhere('g != :ignoreGroup')
                ->setParameter('ignoreGroup', $ignoreGroup);
        }

        return (is_null($qb->getQuery()->getOneOrNullResult()));
    }

    /**
     * @return Group[]
     */
    public function getRegister()
    {
        return $this->groupRepository->findBy(['register' => true]);
    }

    /**
     * @return \Kdyby\Doctrine\QueryBuilder
     */
    public function getGroupQueryBuilder()
    {
        $qb = $this->groupRepository->createQueryBuilder('g')
            ->select('g');

        return $qb;
    }

    /**
     * @param $name
     * @return Group|null
     */
    public function getOneByName($name)
    {
        return $this->groupRepository->findOneBy(['name' => $name]);
    }
}