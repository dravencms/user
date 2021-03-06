<?php

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\AdminModule\Components\User\SignInForm;

use Dravencms\Components\BaseControl\BaseControl;
use Dravencms\Components\BaseForm\BaseFormFactory;
use Nette\Application\UI\Form;

class SignInForm extends BaseControl
{
    /** @var BaseFormFactory */
    private $baseFormFactory;

    /**
     * SignInForm constructor.
     * @param BaseFormFactory $baseFormFactory
     */
    public function __construct(BaseFormFactory $baseFormFactory)
    {
        parent::__construct();
        $this->baseFormFactory = $baseFormFactory;
    }

    /**
     * @return \Dravencms\Components\BaseForm
     */
    public function createComponentForm()
    {
        $form = $this->baseFormFactory->create();

        $form->addText('email')
            ->setRequired('Please enter your email.')
            ->addRule(Form::EMAIL, 'Please enter a valid email');

        $form->addPassword('password')
            ->setRequired('Please enter password.');

        $form->addCheckbox('remember');

        $form->addSubmit('sign');

        return $form;
    }

    public function render()
    {
        $template = $this->template;
        $template->setFile(__DIR__ . '/SignInForm.latte');
        $template->render();
    }
}