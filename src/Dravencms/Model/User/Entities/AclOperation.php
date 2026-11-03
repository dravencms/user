<?php declare(strict_types=1);

namespace Dravencms\Model\User\Entities;

use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Dravencms\Database\Attributes\Identifier;

#[ORM\Entity]
#[ORM\Table(name: 'userAclOperation')]
#[ORM\UniqueConstraint(name: 'acl_resource_identifier', columns: ['aclresource_id', 'identifier'])]
class AclOperation
{
    use Identifier;

    #[ORM\Column(type: 'string', length: 255)]
    private string $identifier;

    #[ORM\ManyToOne(targetEntity: AclResource::class, inversedBy: 'aclOperations')]
    #[ORM\JoinColumn(name: 'aclresource_id', referencedColumnName: 'id', nullable: false)]
    private AclResource $aclResource;

    #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'aclOperations')]
    private Collection $groups;

    public function __construct(AclResource $resource, string $identifier)
    {
        if ($identifier === '') { throw new \InvalidArgumentException('Empty ACL operation identifier.'); }
        $this->aclResource = $resource;
        $this->identifier = $identifier;
        $this->groups = new ArrayCollection();
    }

    public function getIdentifier(): string { return $this->identifier; }
    public function getAclResource(): AclResource { return $this->aclResource; }
    public function getKey(): string { return $this->aclResource->getIdentifier() . '.' . $this->identifier; }
    public function getGroups(): Collection { return $this->groups; }

    public function addGroup(Group $group): void
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group); $group->addAclOperation($this);
        }
    }

    public function removeGroup(Group $group): void
    {
        if ($this->groups->contains($group)) {
            $this->groups->removeElement($group); $group->removeAclOperation($this);
        }
    }
}
