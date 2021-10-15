<?php declare(strict_types = 1);

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */
namespace Dravencms\Model\User\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Dravencms\Database\Attributes\Identifier;
use Nette;

/**
 * Class Group
 * @ORM\Entity
 * @ORM\Table(name="userGroup")
 */
class Group
{
    use Nette\SmartObject;
    use Identifier;
    use TimestampableEntity;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,unique=true,nullable=false)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string",length=6000,nullable=true)
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(type="string",length=6,unique=true,nullable=true)
     */
    private $color;

    /**
     * @var bool
     * @ORM\Column(type="boolean",nullable=false)
     */
    private $isRegister;

    /**
     * @var \Doctrine\Common\Collections\Collection|User[]
     *
     * @ORM\ManyToMany(targetEntity="User", mappedBy="groups")
     */
    private $users;

    /**
     * @var \Doctrine\Common\Collections\Collection|AclOperation[]
     *
     * @ORM\ManyToMany(targetEntity="AclOperation", inversedBy="groups")
     * @ORM\JoinTable(
     *  name="user_group_acloperation",
     *  joinColumns={
     *      @ORM\JoinColumn(name="group_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="acloperation_id", referencedColumnName="id")
     *  }
     * )
     */
    private $aclOperations;

    /**
     * Group constructor.
     * @param string $name
     * @param string $description
     * @param string $color
     * @param bool $isRegister
     * @throws \Exception
     */
    public function __construct(string $name, string $description, string $color, bool $isRegister = false)
    {
        $this->setName($name);
        $this->setDescription($description);
        $this->setColor($color);
        $this->setIsRegister($isRegister);

        $this->users = new ArrayCollection();
        $this->aclOperations = new ArrayCollection();
    }

    /**
     * @param bool $register
     */
    public function setIsRegister(bool $register): void
    {
        if (!is_bool($register))
        {
            throw new \InvalidArgumentException('Invalid $register value');
        }
        $this->isRegister = $register;
    }

    /**
     * @param User $user
     */
    public function addUser(User $user): void
    {
        if ($this->users->contains($user)) {
            return;
        }
        $this->users->add($user);
        $user->addGroup($this);
    }
    /**
     * @param User $user
     */
    public function removeUser(User $user): void
    {
        if (!$this->users->contains($user)) {
            return;
        }
        $this->users->removeElement($user);
        $user->removeGroup($this);
    }

    /**
     * @param AclOperation $aclOperation
     */
    public function addAclOperation(AclOperation $aclOperation): void
    {
        if ($this->aclOperations->contains($aclOperation))
        {
            return;
        }
        $this->aclOperations->add($aclOperation);
        $aclOperation->addGroup($this);
    }
    /**
     * @param AclOperation $aclOperation
     */
    public function removeAclOperation(AclOperation $aclOperation): void
    {
        if (!$this->aclOperations->contains($aclOperation))
        {
            return;
        }
        $this->aclOperations->removeElement($aclOperation);
        $aclOperation->removeGroup($this);
    }

    /**
     *
     * @param ArrayCollection $aclOperations
     */
    public function setAclOperations(ArrayCollection $aclOperations): void
    {
        //Remove all not in
        foreach($this->aclOperations AS $aclOperation)
        {
            if (!$aclOperations->contains($aclOperation))
            {
                $this->removeAclOperation($aclOperation);
            }
        }

        //Add all new
        foreach($aclOperations AS $aclOperation)
        {
            if (!$this->aclOperations->contains($aclOperation))
            {
                $this->addAclOperation($aclOperation);
            }
        }
    }

    /**
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     *
     * @return ArrayCollection|AclOperation[]
     */
    public function getAclOperations(): Collection
    {
        return $this->aclOperations;
    }

    /**
     *
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return boolean
     */
    public function isRegister(): bool
    {
        return $this->isRegister;
    }

    /**
     * @param $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @param $color
     * @throws \Exception
     */
    public function setColor(string $color): void
    {
        if (strlen($color) != 6)
        {
            throw new \InvalidArgumentException('$color have wrong format');
        }
        $this->color = $color;
    }
}
