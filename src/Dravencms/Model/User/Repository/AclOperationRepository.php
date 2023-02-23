<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Repository;


use Dravencms\Model\User\Entities\AclOperation;
use Dravencms\Model\User\Entities\AclResource;
use Dravencms\Database\EntityManager;

/**
 * Class AclOperationRepository
 * @package App\Model\Repository
 */
class AclOperationRepository
{
    /** @var \Doctrine\Persistence\ObjectRepository|AclOperation */
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
     * @return AclOperation[]
     */
    public function getById($id)
    {
        return $this->aclOperationRepository->findBy(['id' => $id]);
    }

    /**
     * @param $id
     * @return null|AclOperation
     */
    public function getOneById(int $id): ?AclOperation
    {
        return $this->aclOperationRepository->find($id);
    }


    /**
     * @return array
     */
    public function getPairs(): array
    {
        return $this->aclOperationRepository->findPairs('name');
    }

    /**
     * @param $name
     * @return AclOperation|null
     */
    public function getOneByName($name): ?AclOperation
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
     * @param string $name
     * @param AclResource $aclResource
     * @param AclOperation|null $ignoreAclOperation
     * @return bool
     */
    public function isNameFree(string $name, AclResource $aclResource, AclOperation $ignoreAclOperation = null): bool
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
