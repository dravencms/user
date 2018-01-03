<?php

/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */
namespace Dravencms\Model\User\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
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
     * @param $name
     * @param $description
     * @param $color
     * @param bool $isRegister
     */
    public function __construct($name, $description, $color, $isRegister = false)
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
    public function setIsRegister($register)
    {
        if (!is_bool($register))
        {
            throw new Nette\InvalidArgumentException('Invalid $register value');
        }
        $this->isRegister = $register;
    }

    /**
     * @param User $user
     */
    public function addUser(User $user)
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
    public function removeUser(User $user)
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
    public function addAclOperation(AclOperation $aclOperation)
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
    public function removeAclOperation(AclOperation $aclOperation)
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
    public function setAclOperations(ArrayCollection $aclOperations)
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
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * @return ArrayCollection|AclOperation[]
     */
    public function getAclOperations()
    {
        return $this->aclOperations;
    }

    /**
     *
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return boolean
     */
    public function isRegister()
    {
        return $this->isRegister;
    }

    /**
     * @param $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @param $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @param $color
     * @throws \Exception
     */
    public function setColor($color)
    {
        if (strlen($color) != 6)
        {
            throw new \Exception('$color have wrong format');
        }
        $this->color = $color;
    }
}
