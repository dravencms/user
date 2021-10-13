<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Repository;


use Dravencms\Model\User\Entities\Group;
use Dravencms\Database\EntityManager;

/**
 * Class GroupRepository
 * @package App\Model\Repository
 */
class GroupRepository
{
    /** @var \Doctrine\Persistence\ObjectRepository|Group  */
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
    public function getOneById(int $id): ?Group
    {
        return $this->groupRepository->find($id);
    }

    /**
     * @return array
     */
    public function getPairs(): array
    {
        return $this->groupRepository->findPairs('name');
    }

    /**
     * @param string $name
     * @param Group|null $ignoreGroup
     * @return bool
     */
    public function isNameFree(string $name, Group $ignoreGroup = null): bool
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
    public function getOneByName(string $name): ?Group
    {
        return $this->groupRepository->findOneBy(['name' => $name]);
    }
}