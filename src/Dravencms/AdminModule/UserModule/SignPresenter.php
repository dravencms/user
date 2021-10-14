<?php declare(strict_types = 1);

namespace Dravencms\AdminModule\UserModule;

use Dravencms\AdminModule\BasePresenter;
use Dravencms\AdminModule\Components\User\DoResetPasswordForm\DoResetPasswordForm;
use Dravencms\AdminModule\Components\User\DoResetPasswordForm\DoResetPasswordFormFactory;
use Dravencms\AdminModule\Components\User\ResetPasswordForm\ResetPasswordForm;
use Dravencms\AdminModule\Components\User\ResetPasswordForm\ResetPasswordFormFactory;
use Dravencms\AdminModule\Components\User\SignInForm\SignInForm;
use Dravencms\AdminModule\Components\User\SignInForm\SignInFormFactory;
use Dravencms\AdminModule\Components\User\SignUpForm\SignUpForm;
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

    public function startup(): void
    {
        parent::startup();
        $this->authenticator->setNamespace($this->getUser()->getStorage()->getNamespace());
        $this->allowRegister = false; //!FIXME INTO CONFIG
    }

    /**
     * @return \Dravencms\AdminModule\Components\User\SignInForm\SignInForm
     */
    protected function createComponentSignInForm(): SignInForm
    {
        $signInControl = $this->signInFormFactory->create();
        $signInControl['form']->onSuccess[] = [$this, 'signInFormSucceeded'];
        return $signInControl;
    }

    /**
     * @param Form $form
     */
    public function signInFormSucceeded(Form $form): void
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

    public function actionOut(): void
    {
        $this->getUser()->logout();
        $this->flashMessage('You have been signed out.', 'alert-info');
        $this->redirect('in');
    }

    /**
     * @return \Dravencms\AdminModule\Components\User\ResetPasswordForm\ResetPasswordForm
     */
    protected function createComponentResetPasswordForm(): ResetPasswordForm
    {
        $control = $this->resetPasswordFormFactory->create();
        $control->onSuccess[] = function()
        {
            $this->flashMessage('Email with reset url was send.', 'alert-success');
            $this->redirect('in');
        };
        return $control;
    }


    /**
     * @param string $hash
     */
    public function actionPasswordReset(string $hash): void
    {
        $foundPasswordReset = $this->userPasswordResetRepository->getActiveByHash($hash);
        if(!$foundPasswordReset)
        {
            $this->error();
        }

        $this->foundPasswordReset = $foundPasswordReset;
    }

    /**
     * @return \Dravencms\AdminModule\Components\User\DoResetPasswordForm\DoResetPasswordForm
     */
    public function createComponentDoPasswordReset(): DoResetPasswordForm
    {
        $control = $this->doResetPasswordFormFactory->create($this->foundPasswordReset);
        $control->onSuccess[] = function(){
            $this->flashMessage('Password has been successfully changed', 'alert-success');
            $this->redirect('Sign:in');
        };

        return $control;
    }

    public function renderUp(): void
    {
        if (!$this->allowRegister) {
            $this->error();
        }

        $this->template->h1 = 'Registrace';
    }

    public function renderIn(): void
    {
        $this->template->allowRegister = $this->allowRegister;
    }

    /**
     * @return \Dravencms\AdminModule\Components\User\SignUpForm\SignUpForm
     */
    public function createComponentSignUpForm(): SignUpForm
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
