<?php declare(strict_types = 1);

namespace Dravencms\Model\User\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Dravencms\Database\Attributes\Identifier;
use Nette;

/**
 * Class PasswordReset
 * @package App\Model\Entities
 * @ORM\Entity
 * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=true)
 * @ORM\Table(name="userPasswordReset")
 */
class PasswordReset
{
    use Nette\SmartObject;
    use Identifier;
    use TimestampableEntity;
    use SoftDeleteableEntity;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="passwordResets")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var string
     * @ORM\Column(type="string",length=32,unique=true,nullable=false)
     */
    private $hash;

    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false)
     */
    private $isUsed;

    /**
     * PasswordReset constructor.
     * @param User $user
     * @param \DateTimeInterface $deletedAt
     */
    public function __construct(User $user, \DateTimeInterface $deletedAt)
    {
        $this->user = $user;
        $this->deletedAt = $deletedAt;
        $this->hash = md5($user->getEmail() . rand() . microtime());
        $this->isUsed = false;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return boolean
     */
    public function isUsed(): bool
    {
        return $this->isUsed;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @param boolean $isUsed
     */
    public function setIsUsed(bool $isUsed): void
    {
        $this->isUsed = $isUsed;
    }

}