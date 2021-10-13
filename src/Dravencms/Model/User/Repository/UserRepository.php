<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Repository;

use Dravencms\Model\User\Entities\User;
use Dravencms\Database\EntityManager;

class UserRepository
{
    /** @var \Doctrine\Persistence\ObjectRepository|User */
    private $userRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->userRepository = $entityManager->getRepository(User::class);
    }

    /**
     * @return \Doctrine\Persistence\ObjectRepository|User
     */
    public function getUserRepository()
    {
        return $this->userRepository;
    }

    /**
     * @param string $email
     * @param string $namespace
     * @return User|null
     */
    public function getOneByEmail(string $email, string $namespace): ?User
    {
        return $this->userRepository->findOneBy(['email' => $email, 'namespace' => $namespace]);
    }

    /**
     * @param int $id
     * @return null|User
     */
    public function getOneById(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    /**
     * @param $id
     * @return User[]
     */
    public function getById($id)
    {
        return $this->userRepository->findBy(['id' => $id]);
    }

    /**
     * @param $namespace
     * @param bool $isShadow
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getUsersQueryBuilder(string $namespace, bool $isShadow = false)
    {
        $qb = $this->userRepository->createQueryBuilder('u')
            ->select('u')
            ->where('u.isShadow = :isShadow')
            ->andWhere('u.namespace = :namespace')
            ->setParameters([
                'namespace' => $namespace,
                'isShadow' => $isShadow
            ]);

        return $qb;
    }

    /**
     * @param $newEmail
     * @param $namespace
     * @param User|null $ignoreUser
     * @return bool
     */
    public function isEmailFree(string $newEmail, string $namespace, User $ignoreUser = null): bool
    {
        $qb = $this->userRepository->createQueryBuilder('u')
            ->select('u')
            ->where('u.email = :email')
            ->andWhere('u.namespace = :namespace')
            ->setParameters([
                'email' => $newEmail,
                'namespace' => $namespace
            ]);

        if ($ignoreUser)
        {
            $qb->andWhere('u != :ignoreUser')
                ->setParameter('ignoreUser', $ignoreUser);
        }

        return (is_null($qb->getQuery()->getOneOrNullResult()));
    }

    /**
     * @param $namespace
     * @return User[]
     */
    public function getAll(string $namespace)
    {
        return $this->userRepository->findBy(['namespace' => $namespace]);
    }
}