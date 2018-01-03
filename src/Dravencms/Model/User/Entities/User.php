<?php

namespace Dravencms\Model\User\Entities;

use Dravencms\Model\Location\Entities\StreetNumber;
use Dravencms\User\DefaultDataCreator;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Nette;

/**
 * Class User
 * @package App\Model\Entities
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 * @ORM\Table(name="userUser", uniqueConstraints={@UniqueConstraint(name="user_unique", columns={"email", "namespace"})})
 */
class User implements Nette\Security\IIdentity
{
    use Nette\SmartObject;
    use Identifier;
    use TimestampableEntity;
    use SoftDeleteableEntity;

    const NAMESPACE_ADMIN = 'Admin';
    const NAMESPACE_FRONT = 'Front';

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=true)
     */
    private $degree;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=false)
     */
    private $firstName;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=false)
     */
    private $lastName;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=false)
     */
    private $email;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=true)
     */
    private $phone;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=false)
     */
    private $password;

    /**
     * @var string
     * @ORM\Column(type="string",length=50,nullable=true)
     */
    private $namespace;

    /**
     * @var bool
     * @ORM\Column(type="boolean",nullable=false)
     */
    private $isActive;

    /**
     * @var bool
     * @ORM\Column(type="boolean",nullable=false)
     */
    private $isShadow;

    /**
     * @var bool
     * @ORM\Column(type="boolean",nullable=false)
     */
    private $isNewsletter;

    /**
     * @var \DateTimeInterface
     * @ORM\Column(type="datetime",nullable=false)
     */
    private $lastActivity;

    /**
     * @var bool
     * @ORM\Column(type="boolean",nullable=false)
     */
    private $isInitialized;

    /**
     * @var StreetNumber
     * @ORM\ManyToOne(targetEntity="Dravencms\Model\Location\Entities\StreetNumber", inversedBy="users")
     * @ORM\JoinColumn(name="street_number_id", referencedColumnName="id")
     */
    private $streetNumber;

    /**
     * @var Gender
     * @ORM\ManyToOne(targetEntity="Gender", inversedBy="users")
     * @ORM\JoinColumn(name="gender_id", referencedColumnName="id")
     */
    private $gender;

    /**
     * @var Company
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="users")
     * @ORM\JoinColumn(name="company_id", referencedColumnName="id",nullable=true)
     */
    private $company;

    /**
     * @var ArrayCollection|PasswordReset[]
     * @ORM\OneToMany(targetEntity="PasswordReset", mappedBy="user",cascade={"persist"})
     */
    private $passwordResets;

    /**
     * @var ArrayCollection|Company[]
     * @ORM\OneToMany(targetEntity="Company", mappedBy="user",cascade={"persist"})
     */
    private $companies;

    /**
     * @var \Doctrine\Common\Collections\Collection|Group[]
     *
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="users")
     * @ORM\JoinTable(
     *  name="user_aclgroup",
     *  joinColumns={
     *      @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     *  }
     * )
     */
    protected $groups;


    /**
     * User constructor.
     * @param $firstName
     * @param $lastName
     * @param $email
     * @param $password
     * @param $namespace
     * @param bool $isActive
     * @param bool $isShadow
     * @param bool $isNewsletter
     * @param callable $passwordHashCallable
     */
    public function __construct($firstName, $lastName, $email, $password, $namespace, $isActive = true, $isShadow = false, $isNewsletter = true, callable $passwordHashCallable)
    {
        $this->setEmail($email);
        $this->setPassword($password, $passwordHashCallable);
        $this->namespace = $namespace;
        $this->firstName = $firstName;
        $this->lastName = $lastName;

        $this->isActive = $isActive;
        $this->isShadow = $isShadow;
        $this->isNewsletter = $isNewsletter;
        $this->isInitialized = false;
        $this->lastActivity = new \DateTime;

        $this->passwordResets = new ArrayCollection();
        $this->companies = new ArrayCollection();
        $this->groups = new ArrayCollection();
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $email = Nette\Utils\Strings::trim(Nette\Utils\Strings::lower($email));
        if (!Nette\Utils\Validators::isEmail($email)) throw new Nette\InvalidArgumentException(sprintf('Invalid $email value %s', $email));
        $this->email = $email;
    }

    /**
     * @param string $password
     * @param callable $hash
     */
    public function setPassword($password, callable $hash)
    {
        $password = Nette\Utils\Strings::trim($password);
        if (Nette\Utils\Strings::length($password) === 0) throw new Nette\InvalidArgumentException('Password cannot be empty');
        $this->password = $hash($password);
    }

    /**
     * @param string $password
     * @param callable $hash
     */
    public function changePassword($password, callable $hash)
    {
        $this->setPassword($password, $hash);
    }

    /**
     * @param string $password
     * @param callable $verifyPassword
     * @return bool
     */
    public function verifyPassword($password, callable $verifyPassword)
    {
        return $verifyPassword($password, $this->password);
    }

    /**
     * Returns a list of roles that the user is a member of.
     * @return Group[]
     */
    public function getRoles()
    {
        return $this->groups;
    }
    
    public function initializeDefaultData(DefaultDataCreator $defaultDataCreator)
    {
        if ($this->isInitialized) return false;

        $defaultDataCreator->create($this);
        $this->isInitialized = true;
        $this->lastActivity = new \DateTime();

        return true;
    }

    /**
     * @param \DateTimeInterface $lastActivity
     */
    public function setLastActivity(\DateTimeInterface $lastActivity)
    {
        $this->lastActivity = $lastActivity;
    }

    /**
     * @param string $degree
     */
    public function setDegree($degree)
    {
        $this->degree = $degree;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @param boolean $isActive
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    }

    /**
     * @param StreetNumber $streetNumber
     */
    public function setStreetNumber($streetNumber)
    {
        $this->streetNumber = $streetNumber;
    }

    /**
     * @param Gender $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @param Company $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @param Group $group
     */
    public function addGroup(Group $group)
    {
        if ($this->groups->contains($group))
        {
            return;
        }
        $this->groups->add($group);
        $group->addUser($this);
    }

    /**
     * @param ArrayCollection $groups
     */
    public function setGroups(ArrayCollection $groups)
    {
        //Remove all not in
        foreach($this->groups AS $group)
        {
            if (!$groups->contains($group))
            {
                $this->removeGroup($group);
            }
        }

        //Add all new
        foreach($groups AS $group)
        {
            if (!$this->groups->contains($group))
            {
                $this->addGroup($group);
            }
        }
    }


    /**
     * @param Group $group
     */
    public function removeGroup(Group $group)
    {
        if (!$this->groups->contains($group))
        {
            return;
        }
        $this->groups->removeElement($group);
        $group->removeUser($this);
    }

    /**
     * @return string
     */
    public function getDegree()
    {
        return $this->degree;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
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
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return boolean
     */
    public function isShadow()
    {
        return $this->isShadow;
    }

    /**
     * @return boolean
     */
    public function isNewsletter()
    {
        return $this->isNewsletter;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getLastActivity()
    {
        return $this->lastActivity;
    }

    /**
     * @return StreetNumber
     */
    public function getStreetNumber()
    {
        return $this->streetNumber;
    }

    /**
     * @return Gender
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @return ArrayCollection|PasswordReset[]
     */
    public function getPasswordResets()
    {
        return $this->passwordResets;
    }

    /**
     * @return ArrayCollection|Company[]
     */
    public function getCompanies()
    {
        return $this->companies;
    }
}