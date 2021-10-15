<?php declare(strict_types = 1);

namespace Dravencms\User;

use Dravencms\Model\User\Entities\User;
use Dravencms\Database\EntityManager;
use Nette\Http\IResponse;
use Dravencms\Security\UserAcl;



/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */
trait TSecuredPresenter
{
    /** @var EntityManager @inject */
    public $entityManager;

    /** @var DefaultDataCreator @inject */
    public $defaultDataCreator;

    /** @var UserAcl @inject */
    public $userAcl;

    /** @var bool */
    private $assigned = false;

    /**
     * Checks authorization.
     * @param $element
     * @throws \Exception
     * @return void
     */
    public function checkRequirements($element): void
    {
        parent::checkRequirements($element);

        if (!$this->getUser()->isLoggedIn())
        {
            if (is_null(self::$redirectUnauthorizedTo)) {
                $this->error('Unauthorized', IResponse::S401_UNAUTHORIZED);
            } else {
                $this->redirect(':Admin:User:Sign:In', ['backlink' => $this->storeRequest()]);
            }
        }
        elseif ($this->getUser()->isLoggedIn())
        {
            if ($this->getUserEntity()->initializeDefaultData($this->defaultDataCreator))
            {
                $this->entityManager->flush();
                $this->redirect('this');
            }

            $this->assignUserInfo();
        }


        if ($element->hasAnnotation('isAllowed'))
        {
            list($resource, $operation) = $element->getAnnotation('isAllowed');
            $this->userAcl->checkPermission($resource, $operation);
        }
    }

    private function assignUserInfo(): void
    {
        if ($this->assigned) return;

        /** @var User $user */
        $user = $this->getUser()->getIdentity();
        $user->setLastActivity(new \DateTime());
        $this->entityManager->flush();
        $this->userAcl->initiate();
        $this->template->userInfo = $user;

        $this->assigned = true;
    }

    /**
     * @deprecated replace with UserAcl::isAllowed
     * @param string $resource
     * @param string $operation
     * @param string|null $role
     * @return bool
     */
    public function isAllowed(string $resource, string $operation, string $role = null): bool {
        return $this->userAcl->isAllowed($resource, $operation, $role);
    }
}