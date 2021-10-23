<?php declare(strict_types = 1);

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Security;

use Dravencms\Model\User\Entities\User;
use Dravencms\Model\User\Repository\UserRepository;
use Nette\Security\AuthenticationException;
use Nette\Security\IdentityHandler;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;
use Nette\SmartObject;

class Authenticator implements \Nette\Security\Authenticator, IdentityHandler
{
    use SmartObject;
    
    /** @var PasswordManager */
    private $passwordManager;

    /** @var UserRepository */
    private $userRepository;

    /** @var string */
    private $namespace;

    /**
     * Authenticator constructor.
     * @param string $namespace
     * @param PasswordManager $passwordManager
     * @param UserRepository $userRepository
     */
    public function __construct(string $namespace, PasswordManager $passwordManager, UserRepository $userRepository)
    {
        $this->namespace = $namespace;
        $this->passwordManager = $passwordManager;
        $this->userRepository = $userRepository;
    }

    /**
     * @param IIdentity $identity
     * @return IIdentity
     */
    public function sleepIdentity(IIdentity $identity): IIdentity
    {
        //Save only simple identity with id
        return new SimpleIdentity($identity->getId());
    }

    /**
     * @param IIdentity $identity
     * @return IIdentity|null
     */
    public function wakeupIdentity(IIdentity $identity): ?IIdentity
    {
        return $this->userRepository->getOneById($identity->getId());
    }

    /**
     * @param array $credentials
     * @return User
     * @throws AuthenticationException
     */
    function authenticate(string $user, string $password): IIdentity
    {
        $criteria = ['email' => $user];

        if ($this->namespace) $criteria['namespace'] = $this->namespace;

        /** @var User|null $user */
        $foundUser = $this->userRepository->getUserRepository()->findOneBy($criteria);

        if (!$foundUser) {
            throw new AuthenticationException('User not found', self::IDENTITY_NOT_FOUND);
        }

        if (!$foundUser->isActive())
        {
            throw new AuthenticationException('User is not active', self::IDENTITY_NOT_FOUND);
        }

        $verifyPassword = $foundUser->verifyPassword($password, function($password, $hash) { return $this->passwordManager->verify($password, $hash); });
        if (!$verifyPassword) {
            throw new AuthenticationException('Invalid credentials', self::INVALID_CREDENTIAL);
        }

        // Entity User implements IIdentity - can return as User Identity
        return $foundUser;
    }

}