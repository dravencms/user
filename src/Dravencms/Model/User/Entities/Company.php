<?php

namespace Dravencms\Model\User\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Dravencms\Model\Location\Entities\StreetNumber;
use Nette;

/**
 * Class Company
 * @package App\Model\Entities
 * @ORM\Entity
 * @ORM\Table(name="userCompany")
 */
class Company
{
    use Nette\SmartObject;
    use Identifier;
    use TimestampableEntity;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,unique=true,nullable=true)
     */
    private $companyIdentifier;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,unique=true,nullable=true)
     */
    private $vatIdentifier;

    /**
     * @var string
     * @ORM\Column(type="string",length=255)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string",length=255, nullable=true)
     */
    private $email;

    /**
     * @var string
     * @ORM\Column(type="string",length=255, nullable=true)
     */
    private $phone;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=true)
     */
    private $www;


    /**
     * @var string
     * @ORM\Column(type="text",nullable=true)
     */
    private $description;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="companies")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id",nullable=true)
     */
    private $user;

    /**
     * @var StreetNumber
     * @ORM\ManyToOne(targetEntity="Dravencms\Model\Location\Entities\StreetNumber", inversedBy="companies")
     * @ORM\JoinColumn(name="street_number_id", referencedColumnName="id")
     */
    private $streetNumber;

    /**
     * @var ArrayCollection|User[]
     * @ORM\OneToMany(targetEntity="User", mappedBy="company",cascade={"persist"})
     */
    private $users;

    /**
     * Company constructor.
     * @param string $companyIdentifier
     * @param string $vatIdentifier
     * @param string $name
     * @param string $email
     * @param string $phone
     * @param string $www
     * @param string $description
     * @param User $user
     * @param StreetNumber $streetNumber
     */
    public function __construct($companyIdentifier, $vatIdentifier, $name, $email, $phone, $www, StreetNumber $streetNumber = null, $description = null, User $user = null)
    {
        $this->companyIdentifier = $companyIdentifier;
        $this->vatIdentifier = $vatIdentifier;
        $this->name = $name;
        $this->email = $email;
        $this->phone = $phone;
        $this->www = $www;
        $this->description = $description;
        $this->user = $user;
        $this->streetNumber = $streetNumber;
    }


    /**
     * @param string $companyIdentifier
     */
    public function setCompanyIdentifier($companyIdentifier)
    {
        $this->companyIdentifier = $companyIdentifier;
    }

    /**
     * @param string $vatIdentifier
     */
    public function setVatIdentifier($vatIdentifier)
    {
        $this->vatIdentifier = $vatIdentifier;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @param string $www
     */
    public function setWww($www)
    {
        $this->www = $www;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @param StreetNumber $streetNumber
     */
    public function setStreetNumber($streetNumber)
    {
        $this->streetNumber = $streetNumber;
    }

    /**
     * @return string
     */
    public function getCompanyIdentifier()
    {
        return $this->companyIdentifier;
    }

    /**
     * @return string
     */
    public function getVatIdentifier()
    {
        return $this->vatIdentifier;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @return string
     */
    public function getWww()
    {
        return $this->www;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return StreetNumber
     */
    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    /**
     * @return User[]|ArrayCollection
     */
    public function getUsers()
    {
        return $this->users;
    }
}