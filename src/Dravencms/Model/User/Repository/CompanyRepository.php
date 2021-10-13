<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Repository;


use Dravencms\Model\User\Entities\Company;
use Dravencms\Model\Location\Entities\Country;
use Dravencms\Database\EntityManager;

class CompanyRepository
{
    /** @var \Doctrine\Persistence\ObjectRepository|Company */
    private $companyRepository;

    /** @var EntityManager */
    private $entityManager;

    /**
     * CompanyRepository constructor.
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->companyRepository = $entityManager->getRepository(Company::class);
    }

    /**
     * @param $id
     * @return null|Company
     */
    public function getOneById(int $id): ?Company
    {
        return $this->companyRepository->find($id);
    }

    /**
     * @param $name
     * @return null|Company
     */
    public function getOneByName(string $name): ?Company
    {
        return $this->companyRepository->findOneBy(['name' => $name]);
    }

    /**
     * @param $id
     * @return Company[]
     */
    public function getById($id)
    {
        return $this->companyRepository->findBy(['id' => $id]);
    }

    /**
     * @return \Kdyby\Doctrine\QueryBuilder
     */
    public function getCompanyQueryBuilder()
    {
        $qb = $this->companyRepository->createQueryBuilder('c')
            ->select('c');
        return $qb;
    }

    /**
     * @param string $name
     * @param Country $country
     * @param Company|null $ignoreCompany
     * @return bool
     */
    public function isCompanyNameFree(string $name, Country $country, Company $ignoreCompany = null): bool
    {
        $qb = $this->companyRepository->createQueryBuilder('c')
            ->select('c')
            ->join('c.streetNumber', 'sn')
            ->join('sn.street', 's')
            ->join('s.zipCode', 'zc')
            ->join('zc.city', 'ci')
            ->where('c.name = :name')
            ->andWhere('ci.country = :country')
            ->setParameters([
                'name' => $name,
                'country' => $country
            ]);

        if ($ignoreCompany)
        {
            $qb->andWhere('c != :ignoreCompany')
                ->setParameter('ignoreCompany', $ignoreCompany);
        }

        return (is_null($qb->getQuery()->getOneOrNullResult()));
    }

    /**
     * @param string $companyIdentifier
     * @param Country $country
     * @param Company|null $ignoreCompany
     * @return bool
     */
    public function isCompanyIdentifierNameFree(string $companyIdentifier, Country $country, Company $ignoreCompany = null): bool
    {
        $qb = $this->companyRepository->createQueryBuilder('c')
            ->select('c')
            ->join('c.streetNumber', 'sn')
            ->join('sn.street', 's')
            ->join('s.zipCode', 'zc')
            ->join('zc.city', 'ci')
            ->where('c.companyIdentifier = :companyIdentifier')
            ->andWhere('ci.country = :country')
            ->setParameters([
                'companyIdentifier' => $companyIdentifier,
                'country' => $country
            ]);

        if ($ignoreCompany)
        {
            $qb->andWhere('c != :ignoreCompany')
                ->setParameter('ignoreCompany', $ignoreCompany);
        }

        return (is_null($qb->getQuery()->getOneOrNullResult()));
    }

    /**
     * @return Company[]
     */
    public function getAll()
    {
        return $this->companyRepository->findAll();
    }
}