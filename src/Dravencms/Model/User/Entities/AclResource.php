<?php declare(strict_types=1);

namespace Dravencms\Model\User\Entities;

use Doctrine\Common\Collections\{ArrayCollection, Collection};
use Doctrine\ORM\Mapping as ORM;
use Dravencms\Database\Attributes\Identifier;

#[ORM\Entity]
#[ORM\Table(name: 'userAclResource')]
class AclResource
{
    use Identifier;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $identifier;

    #[ORM\OneToMany(targetEntity: AclOperation::class, mappedBy: 'aclResource')]
    private Collection $aclOperations;

    public function __construct(string $identifier)
    {
        if ($identifier === '') { throw new \InvalidArgumentException('Empty ACL resource identifier.'); }
        $this->identifier = $identifier;
        $this->aclOperations = new ArrayCollection();
    }

    public function getIdentifier(): string { return $this->identifier; }
    public function getAclOperations(): Collection { return $this->aclOperations; }
}
