<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Repository;


use Dravencms\Model\User\Entities\AclOperation;
use Dravencms\Model\User\Entities\AclResource;
use Kdyby\Doctrine\EntityManager;

/**
 * Class AclOperationRepository
 * @package App\Model\Repository
 */
class AclOperationRepository
{
    /** @var \Kdyby\Doctrine\EntityRepository */
    private $aclOperationRepository;

    /**
     * AclRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->aclOperationRepository = $entityManager->getRepository(AclOperation::class);
    }

    /**
     * @param $id
     * @return null|AclOperation
     */
    public function getById($id)
    {
        return $this->aclOperationRepository->findBy(['id' => $id]);
    }

    /**
     * @param $id
     * @return null|AclOperation
     */
    public function getOneById($id)
    {
        return $this->aclOperationRepository->find($id);
    }


    /**
     * @return array
     */
    public function getPairs()
    {
        return $this->aclOperationRepository->findPairs('name');
    }

    /**
     * @param $name
     * @return mixed|null|AclOperation
     */
    public function getOneByName($name)
    {
        return $this->aclOperationRepository->findOneBy(['name' => $name]);
    }

    /**
     * @param AclResource $aclResource
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getAclOperationQueryBuilder(AclResource $aclResource)
    {
        $qb = $this->aclOperationRepository->createQueryBuilder('ao')
            ->select('ao')
            ->where('ao.aclResource = :aclResource')
            ->setParameters(
                [
                    'aclResource' => $aclResource
                ]
            );

        return $qb;
    }

    /**
     * @param $name
     * @param AclResource $aclResource
     * @param AclOperation|null $ignoreAclOperation
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isNameFree($name, AclResource $aclResource, AclOperation $ignoreAclOperation = null)
    {
        $qb = $this->aclOperationRepository->createQueryBuilder('ao')
            ->select('ao')
            ->join('ao.aclResource', 'ar')
            ->where('ao.name = :name')
            ->andWhere('ar = :aclResource')
            ->setParameters([
                'name' => $name,
                'aclResource' => $aclResource
            ]);

        if ($ignoreAclOperation)
        {
            $qb->andWhere('ao != :ignoreAclOperation')
                ->setParameter('ignoreAclOperation', $ignoreAclOperation);
        }

        return (is_null($qb->getQuery()->getOneOrNullResult()));
    }
}