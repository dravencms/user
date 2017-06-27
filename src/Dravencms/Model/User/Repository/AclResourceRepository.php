<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Repository;


use Dravencms\Model\User\Entities\AclResource;
use Kdyby\Doctrine\EntityManager;

/**
 * Class AclResourceRepository
 * @package App\Model\Repository
 */
class AclResourceRepository
{
    /** @var \Kdyby\Doctrine\EntityRepository */
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
    public function getById($id)
    {
        return $this->aclResourceRepository->findBy(['id' => $id]);
    }

    /**
     * @param $id
     * @return null|AclResource
     */
    public function getOneById($id)
    {
        return $this->aclResourceRepository->find($id);
    }


    /**
     * @return array
     */
    public function getPairs()
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
     * @param $name
     * @param AclResource|null $ignoreAclResource
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isNameFree($name, AclResource $ignoreAclResource = null)
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
     * @param $name
     * @return mixed|null|AclResource
     */
    public function getOneByName($name)
    {
        return $this->aclResourceRepository->findOneBy(['name' => $name]);
    }
}