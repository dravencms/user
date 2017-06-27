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
 * Class AclResource
 * @package App\Model\Entities
 * @ORM\Entity
 * @ORM\Table(name="userAclResource")
 */
class AclResource extends Nette\Object
{
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
     * @var ArrayCollection|AclOperation[]
     * @ORM\OneToMany(targetEntity="AclOperation", mappedBy="aclResource",cascade={"persist"})
     */
    private $aclOperations;


    public function __construct($name, $description)
    {
        $this->setName($name);

        $this->setDescription($description);
        $this->aclOperations = new ArrayCollection;
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
     * @param AclOperation $aclOperation
     */
    public function addAclOperation(AclOperation $aclOperation)
    {
        if ($this->aclOperations->contains($aclOperation))
        {
            return;
        }
        $this->aclOperations->add($aclOperation);
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
    public function getName()
    {
        return $this->name;
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
     * @param $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }
}