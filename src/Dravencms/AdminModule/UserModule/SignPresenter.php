<?php

namespace Dravencms\AdminModule\UserModule;

use Dravencms\AdminModule\BasePresenter;
use Dravencms\AdminModule\Components\User\DoResetPasswordForm\DoResetPasswordFormFactory;
use Dravencms\AdminModule\Components\User\ResetPasswordForm\ResetPasswordFormFactory;
use Dravencms\AdminModule\Components\User\SignInForm\SignInFormFactory;
use Dravencms\AdminModule\Components\User\SignUpForm\SignUpFormFactory;
use Dravencms\Model\Admin\Entities\Menu;
use Dravencms\Model\Admin\Repository\MenuRepository;
use Dravencms\Security\Authenticator;
use Dravencms\Model\User\Repository\PasswordResetRepository;
use Nette;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;

/**
 * Sign in/out presenters.
 */
class SignPresenter extends BasePresenter
{
    /** @persistent */
    public $backlink = '';

    /** @var Authenticator @inject */
    public $authenticator;

    /** @var PasswordResetRepository @inject */
    public $userPasswordResetRepository;

    /** @var MenuRepository @inject */
    public $adminMenuRepository;

    /** @var SignInFormFactory @inject */
    public $signInFormFactory;

    /** @var ResetPasswordFormFactory @inject */
    public $resetPasswordFormFactory;

    /** @var SignUpFormFactory @inject */
    public $signUpFormFactory;

    /** @var DoResetPasswordFormFactory @inject */
    public $doResetPasswordFormFactory;

    private $allowRegister = false;

    private $foundPasswordReset = null;

    public function startup()
    {
        parent::startup();
        $this->authenticator->setNamespace($this->getUser()->getStorage()->getNamespace());
        $this->allowRegister = false; //!FIXME INTO CONFIG
    }

    /**
     * Sign-in form factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentSignInForm()
    {
        $signInControl = $this->signInFormFactory->create();
        $signInControl['form']->onSuccess[] = [$this, 'signInFormSucceeded'];
        return $signInControl;
    }

    public function signInFormSucceeded(Form $form)
    {
        $values = $form->getValues();

        if ($values->remember) {
            $this->getUser()->setExpiration('14 days', false);
        } else {
            $this->getUser()->setExpiration('50 minutes', true);
        }

        try {
            $this->getUser()->login($values->email, $values->password);


            // Custom implementation of restoreRequest
            // Check if restore request is not targeting Admin homepage, if is not, continue, if is try to go to menu home, if no right for menuhome go to Home
            $homepage = ':Admin:Homepage:Homepage:default';
            $session = $this->getSession('Nette.Application/requests');
            if (!(!isset($session[$this->backlink]) || ($session[$this->backlink][0] !== null && $session[$this->backlink][0] !== $this->getUser()->getId()))) {
                $presenterName = $session[$this->backlink][1]->getPresenterName();
                $parameters = $session[$this->backlink][1]->getParameters();

                if ($homepage != ':' . $presenterName . ':' . $parameters['action']) {
                    //Restore request if its not homepage
                    $this->restoreRequest($this->backlink);
                }
            }

            /** @var Menu $homepage */
            if ($homepage = $this->adminMenuRepository->getHomePageForUser($this->getUserEntity())) {
                $redirect = $homepage->getPresenter() . ($homepage->getAction() ? ':' . $homepage->getAction() : ':default');
            } else {
                $redirect = ':Admin:Homepage:Homepage:default';
            }

            $this->redirect($redirect);
        } catch (AuthenticationException $e) {
            $form->addError($e->getMessage());
        }

    }

    public function actionOut()
    {
        $this->getUser()->logout();
        $this->flashMessage('You have been signed out.', 'alert-info');
        $this->redirect('in');
    }

    protected function createComponentResetPasswordForm()
    {
        $control = $this->resetPasswordFormFactory->create();
        $control->onSuccess[] = function()
        {
            $this->flashMessage('Email with reset url was send.', 'alert-success');
            $this->redirect('in');
        };
        return $control;
    }


    public function actionPasswordReset($hash)
    {
        $foundPasswordReset = $this->userPasswordResetRepository->getActiveByHash($hash);
        if(!$foundPasswordReset)
        {
            $this->error();
        }

        $this->foundPasswordReset = $foundPasswordReset;
    }

    public function createComponentDoPasswordReset()
    {
        $control = $this->doResetPasswordFormFactory->create($this->foundPasswordReset);
        $control->onSuccess[] = function(){
            $this->flashMessage('Password has been successfully changed', 'alert-success');
            $this->redirect('Sign:in');
        };

        return $control;
    }

    public function renderUp()
    {
        if (!$this->allowRegister) {
            $this->error();
        }

        $this->template->h1 = 'Registrace';
    }

    public function renderIn()
    {
        $this->template->allowRegister = $this->allowRegister;
    }

    /**
     * @return \AdminModule\Components\User\SignUpForm
     */
    public function createComponentSignUpForm()
    {
        $component = $this->signUpFormFactory->create();
        $component->onSuccess[] = function()
        {
            $this->flashMessage('Registrace proběhla úspěšně.', 'alert-success');
            $this->redirect('Sign:in');
        };
        return $component;
    }
}
