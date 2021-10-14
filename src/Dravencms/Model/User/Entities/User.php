<?php declare(strict_types = 1);

namespace Dravencms\Model\User\Entities;

use Dravencms\Model\Location\Entities\Street;
use Dravencms\Model\Location\Entities\StreetNumber;
use Dravencms\User\DefaultDataCreator;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Dravencms\Database\Attributes\Identifier;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Nette\Security\IIdentity;
use Nette\SmartObject;
use Nette\Utils\Strings;
use Nette\Utils\Validators;

/**
 * Class User
 * @package App\Model\Entities
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 * @ORM\Table(name="userUser", uniqueConstraints={@UniqueConstraint(name="user_unique", columns={"email", "namespace"})})
 */
class User implements IIdentity
{
    use SmartObject;
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
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     * @param string $password
     * @param string $namespace
     * @param callable $passwordHashCallable
     * @param bool $isActive
     * @param bool $isShadow
     * @param bool $isNewsletter
     * @throws \Exception
     */
    public function __construct(string $firstName, string $lastName, string $email, string $password, string $namespace, callable $passwordHashCallable, bool $isActive = true, bool $isShadow = false, bool $isNewsletter = true)
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
    public function setEmail(string $email): void
    {
        $email = Strings::trim(Strings::lower($email));
        if (!Validators::isEmail($email)) throw new \InvalidArgumentException(sprintf('Invalid $email value %s', $email));
        $this->email = $email;
    }

    /**
     * @param string $password
     * @param callable $hash
     */
    public function setPassword(string $password, callable $hash): void
    {
        $password = Strings::trim($password);
        if (Strings::length($password) === 0) throw new \InvalidArgumentException('Password cannot be empty');
        $this->password = $hash($password);
    }

    /**
     * @param string $password
     * @param callable $hash
     */
    public function changePassword(string $password, callable $hash): void
    {
        $this->setPassword($password, $hash);
    }

    /**
     * @param string $password
     * @param callable $verifyPassword
     * @return bool
     */
    public function verifyPassword(string $password, callable $verifyPassword): bool
    {
        return $verifyPassword($password, $this->password);
    }

    /**
     * Returns a list of roles that the user is a member of.
     * @return Group[]
     */
    public function getRoles(): array
    {
        return $this->groups;
    }

    /**
     * @param DefaultDataCreator $defaultDataCreator
     * @return bool
     * @throws \Exception
     */
    public function initializeDefaultData(DefaultDataCreator $defaultDataCreator): bool
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
    public function setLastActivity(\DateTimeInterface $lastActivity): void
    {
        $this->lastActivity = $lastActivity;
    }

    /**
     * @param string $degree
     */
    public function setDegree(string $degree): void
    {
        $this->degree = $degree;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @param string $phone
     */
    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    /**
     * @param boolean $isActive
     */
    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    /**
     * @param StreetNumber $streetNumber
     */
    public function setStreetNumber(StreetNumber $streetNumber): void
    {
        $this->streetNumber = $streetNumber;
    }

    /**
     * @param Gender $gender
     */
    public function setGender(Gender $gender): void
    {
        $this->gender = $gender;
    }

    /**
     * @param Company $company
     */
    public function setCompany(Company $company = null): void
    {
        $this->company = $company;
    }

    /**
     * @param Group $group
     */
    public function addGroup(Group $group): void
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
    public function setGroups(ArrayCollection $groups): void
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
    public function removeGroup(Group $group): void
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
    public function getDegree(): string
    {
        return $this->degree;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @return boolean
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return boolean
     */
    public function isShadow(): bool
    {
        return $this->isShadow;
    }

    /**
     * @return boolean
     */
    public function isNewsletter(): bool
    {
        return $this->isNewsletter;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getLastActivity(): \DateTimeInterface
    {
        return $this->lastActivity;
    }

    /**
     * @return StreetNumber
     */
    public function getStreetNumber(): StreetNumber
    {
        return $this->streetNumber;
    }

    /**
     * @return Gender
     */
    public function getGender(): Gender
    {
        return $this->gender;
    }

    /**
     * @return Company|null
     */
    public function getCompany(): ?Company
    {
        return $this->company;
    }

    /**
     * @return ArrayCollection|PasswordReset[]
     */
    public function getPasswordResets(): ArrayCollection
    {
        return $this->passwordResets;
    }

    /**
     * @return ArrayCollection|Company[]
     */
    public function getCompanies(): ArrayCollection
    {
        return $this->companies;
    }

    public function getData()
    {
        return [];
    }
}