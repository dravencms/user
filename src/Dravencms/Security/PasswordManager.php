<?php declare(strict_types = 1);
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Security;

use Nette;

/**
 * Class PasswordManager
 * @package App\Model\Security
 */
class PasswordManager
{
    use Nette\SmartObject;

    /**
     * @param string $password
     * @return string
     */
    public function hash(string $password)
    {
        return Nette\Security\Passwords::hash($password);
    }

    /**
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verify(string $password, string $hash)
    {
        return Nette\Security\Passwords::verify($password, $hash);
    }

}