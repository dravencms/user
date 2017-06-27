<?php


namespace Dravencms\AdminModule\UserModule;


use Dravencms\AdminModule\Components\User\CompanyForm\CompanyFormFactory;
use Dravencms\AdminModule\Components\User\CompanyGrid\CompanyGridFactory;
use Dravencms\AdminModule\SecuredPresenter;
use Dravencms\Model\User\Entities\Company;
use Dravencms\Model\Location\Repository\CityRepository;
use Dravencms\Model\User\Repository\CompanyRepository;
use Dravencms\Model\Location\Repository\StreetRepository;

/**
 * Description of CustomersPresenter
 *
 * @author Adam Schubert
 */
class CompanyPresenter extends SecuredPresenter
{
    /** @var CompanyRepository @inject */
    public $userCompanyRepository;

    /** @var CityRepository @inject */
    public $userCityRepository;

    /** @var StreetRepository @inject */
    public $userStreetNumberRepository;

    /** @var CompanyFormFactory @inject */
    public $userCompanyFormFactory;

    /** @var CompanyGridFactory @inject */
    public $userCompanyGridFactory;

    /** @var Company */
    private $userCompanyFormEntity;
    
    /**
     * @isAllowed(user, companyEdit)
     */
    public function actionDefault()
    {
        $this->template->h1 = 'Přehled firem';
    }

    /**
     * @param integer|null $id
     * @isAllowed(user, companyEdit)
     * @throws \Exception
     */
    public function actionEdit($id = null)
    {
        if ($id) {
            $company = $this->userCompanyRepository->getOneById($id);
            if (!$company) {
                $this->error();
            }

            $this->userCompanyFormEntity = $company;
            $this->template->h1 = sprintf('Company „%s“', $company->getName());
        } else {
            $this->template->h1 = "New company";
        }
    }

    /**
     * @return \AdminModule\Components\User\CompanyGrid
     */
    public function createComponentGridCompany()
    {
        $control = $this->userCompanyGridFactory->create();
        $control->onDelete[] = function(){
            $this->flashMessage('Company has been successfully deleted', 'alert-success');
            $this->redirect('Company:');
        };
        return $control;
    }

    /**
     * @return \AdminModule\Components\User\CompanyForm
     */
    public function createComponentFormCompany()
    {
        $company = $this->userCompanyFormFactory->create($this->userCompanyFormEntity);
        $company->onSuccess[] = function()
        {
            $this->flashMessage('Company has been successfully saved', 'alert-success');
            $this->redirect('Company:');
        };
        return $company;
    }

}
