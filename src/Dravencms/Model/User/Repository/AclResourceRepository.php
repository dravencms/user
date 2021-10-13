<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Repository;


use Dravencms\Model\User\Entities\AclResource;
use Dravencms\Database\EntityManager;

/**
 * Class AclResourceRepository
 * @package App\Model\Repository
 */
class AclResourceRepository
{
    /** @var \Doctrine\Persistence\ObjectRepository|AclResource  */
    private $aclResourceRepository;

    /**
     * AclRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->aclResourceRepository = $entityManager->getRepository(AclResource::class);
    }

    /**
     * @param $id
     * @return null|AclResource
     */
    public function getById($id): ?AclResource
    {
        return $this->aclResourceRepository->findBy(['id' => $id]);
    }

    /**
     * @param $id
     * @return null|AclResource
     */
    public function getOneById(int $id): ?AclResource
    {
        return $this->aclResourceRepository->find($id);
    }


    /**
     * @return array
     */
    public function getPairs(): array
    {
        return $this->aclResourceRepository->findPairs('name');
    }

    /**
     * @return AclResource[]
     */
    public function getAll()
    {
        return $this->aclResourceRepository->findAll();
    }

    /**
     * @return \Kdyby\Doctrine\QueryBuilder
     */
    public function getAclResourceQueryBuilder()
    {
        $qb = $this->aclResourceRepository->createQueryBuilder('ar')
            ->select('ar');

        return $qb;
    }

    /**
     * @param string $name
     * @param AclResource|null $ignoreAclResource
     * @return bool
     */
    public function isNameFree(string $name, AclResource $ignoreAclResource = null): bool
    {
        $qb = $this->aclResourceRepository->createQueryBuilder('ar')
            ->select('ar')
            ->where('ar.name = :name')
            ->setParameters([
                'name' => $name
            ]);

        if ($ignoreAclResource)
        {
            $qb->andWhere('ar != :ignoreAclResource')
                ->setParameter('ignoreAclResource', $ignoreAclResource);
        }

        return (is_null($qb->getQuery()->getOneOrNullResult()));
    }

    /**
     * @param string $name
     * @return AclResource|null
     */
    public function getOneByName(string $name): ?AclResource
    {
        return $this->aclResourceRepository->findOneBy(['name' => $name]);
    }
}