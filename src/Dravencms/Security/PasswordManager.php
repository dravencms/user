<?php
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
     * @param $password
     * @return string
     */
    public function hash($password)
    {
        return Nette\Security\Passwords::hash($password);
    }

    /**
     * @param $password
     * @param $hash
     * @return bool
     */
    public function verify($password, $hash)
    {
        return Nette\Security\Passwords::verify($password, $hash);
    }

}