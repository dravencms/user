<?php
/**
 * Copyright (C) 2016 Adam Schubert <adam.schubert@sg1-game.net>.
 */

namespace Dravencms\Model\User\Entities;


use Dravencms\Model\Admin\Entities\Menu;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Nette;

/**
 * Class AclOperation
 * @ORM\Entity
 * @ORM\Table(name="userAclOperation", uniqueConstraints={@UniqueConstraint(name="resorce_name", columns={"aclresource_id", "name"})})
 */
class AclOperation
{
    use Nette\SmartObject;
    use Identifier;
    use TimestampableEntity;

    /**
     * @var string
     * @ORM\Column(type="string",length=255,nullable=false)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string",length=6000,nullable=true)
     */
    private $description;

    /**
     * @var \Doctrine\Common\Collections\Collection|Group[]
     *
     * @ORM\ManyToMany(targetEntity="Group", mappedBy="aclOperations")
     */
    private $groups;

    /**
     * @var AclResource
     * @ORM\ManyToOne(targetEntity="AclResource", inversedBy="aclOperations")
     * @ORM\JoinColumn(name="aclresource_id", referencedColumnName="id")
     */
    private $aclResource;

    /**
     * @var ArrayCollection|Menu[]
     * @ORM\OneToMany(targetEntity="Dravencms\Model\Admin\Entities\Menu", mappedBy="aclOperation",cascade={"persist"})
     */
    private $adminMenus;

    /**
     * AclOperation constructor.
     * @param AclResource $aclResource
     * @param $name
     * @param $description
     */
    public function __construct(AclResource $aclResource, $name, $description)
    {
        $this->aclResource = $aclResource;
        $this->setName($name);
        $this->setDescription($description);

        $this->groups = new ArrayCollection;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $name = Nette\Utils\Strings::trim($name);
        if (Nette\Utils\Strings::length($name) === 0)
        {
            throw new Nette\InvalidArgumentException('Invalid $name value');
        }
        $this->name = $name;
    }

    /**
     * @return AclResource
     */
    public function getAclResource()
    {
        return $this->aclResource;
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
        $group->addAclOperation($this);
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
        $group->removeAclOperation($this);
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
    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return Group[]|\Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @return \Dravencms\Model\Admin\Entities\Menu[]|ArrayCollection
     */
    public function getAdminMenus()
    {
        return $this->adminMenus;
    }
}