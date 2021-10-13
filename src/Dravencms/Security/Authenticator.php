<?php declare(strict_types = 1);

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Security;

use Dravencms\Model\User\Entities\User;
use Dravencms\Model\User\Repository\UserRepository;
use Nette;
use Nette\Security\AuthenticationException;
use Nette\Security\IIdentity;

class Authenticator implements Nette\Security\IAuthenticator
{
    use Nette\SmartObject;
    
    /** @var PasswordManager */
    private $passwordManager;

    /** @var UserRepository */
    private $userRepository;

    /** @var string */
    private $namespace;

    /**
     * Authenticator constructor.
     * @param PasswordManager $passwordManager
     * @param UserRepository $userRepository
     */
    public function __construct(PasswordManager $passwordManager, UserRepository $userRepository)
    {
        $this->passwordManager = $passwordManager;
        $this->userRepository = $userRepository;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace(string $namespace): void
    {
        $this->namespace = $namespace ?: null;
    }

    /**
     * @param array $credentials
     * @return User
     * @throws AuthenticationException
     */
    function authenticate(array $credentials): User
    {
        list ($email, $password) = $credentials;

        $criteria = ['email' => $email];

        if ($this->namespace) $criteria['namespace'] = $this->namespace;

        /** @var User|null $user */
        $user = $this->userRepository->getUserRepository()->findOneBy($criteria);

        if (!$user) {
            throw new AuthenticationException('User not found', self::IDENTITY_NOT_FOUND);
        }

        if (!$user->isActive())
        {
            throw new AuthenticationException('User is not active', self::IDENTITY_NOT_FOUND);
        }

        $verifyPassword = $user->verifyPassword($password, function($password, $hash) { return $this->passwordManager->verify($password, $hash); });
        if (!$verifyPassword) {
            throw new AuthenticationException('Invalid credentials', self::INVALID_CREDENTIAL);
        }

        // Entity User implements IIdentity - can return as User Identity
        return $user;
    }

}