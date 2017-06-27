<?php

namespace Dravencms\AdminModule\Components\User\CompanyForm;

use Dravencms\Model\User\Entities\Company;


/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */
interface CompanyFormFactory
{
    /**
     * @param Company|null $company
     * @return CompanyForm
     */
    public function create(Company $company = null);
}