<?php
/**
 * Created by PhpStorm.
 * User: sadam
 * Date: 10/14/21
 * Time: 10:37 PM
 */

namespace Dravencms\User;

use Nette\Security\IIdentity;

trait TUserPresenter
{
    /**
     * @return mixed
     */
    public function getNamespace(): string
    {
        return $this->getUser()->getStorage()->getNamespace();
    }

    /**
     * @return IIdentity|null
     * @throws \Exception
     */
    public function getUserEntity(): ?\Dravencms\Model\User\Entities\User
    {
        $identity = $this->getUser()->getIdentity();
        if ($identity instanceof \Dravencms\Model\User\Entities\User || is_null($identity)) {
            return $identity;
        } else {
            throw new \Exception('Non User object was returned from getUser()->getIdentity()');
        }
    }

    /**
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->getUser()->isLoggedIn();
    }
}