<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Repository;

use Dravencms\Model\User\Entities\User;
use Kdyby\Doctrine\EntityManager;

class UserRepository
{
    /** @var \Kdyby\Doctrine\EntityRepository */
    private $userRepository;

    public function __construct(EntityManager $entityManager)
    {
        $this->userRepository = $entityManager->getRepository(User::class);
    }

    /**
     * @return \Kdyby\Doctrine\EntityRepository
     */
    public function getUserRepository()
    {
        return $this->userRepository;
    }

    /**
     * @param $email
     * @param $namespace
     * @return User
     */
    public function getOneByEmail($email, $namespace)
    {
        return $this->userRepository->findOneBy(['email' => $email, 'namespace' => $namespace]);
    }

    /**
     * @param $id
     * @return null|User
     */
    public function getOneById($id)
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
    public function getUsersQueryBuilder($namespace, $isShadow = false)
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
    public function isEmailFree($newEmail, $namespace, User $ignoreUser = null)
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
    public function getAll($namespace)
    {
        return $this->userRepository->findBy(['namespace' => $namespace]);
    }
}