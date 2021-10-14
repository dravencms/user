<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Security;



use Nette\Security\Passwords;
use Nette\SmartObject;

/**
 * Class PasswordManager
 * @package App\Model\Security
 */
class PasswordManager
{
    use SmartObject;

    private $passwords;

    /**
     * PasswordManager constructor.
     * @param Passwords $passwords
     */
    public function __construct(Passwords $passwords)
    {
        $this->passwords = $passwords;
    }

    /**
     * @param string $password
     * @return string
     */
    public function hash(string $password): string
    {
        return $this->passwords->hash($password);
    }

    /**
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verify(string $password, string $hash): bool
    {
        return $this->passwords->verify($password, $hash);
    }

}