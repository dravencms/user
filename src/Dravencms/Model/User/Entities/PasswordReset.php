<?php declare(strict_types = 1);

namespace Dravencms\Model\User\Entities;

use Doctrine\Common\Collections\ArrayCollection;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping as ORM;
use Dravencms\Database\Attributes\SoftDeleteableEntity;
use Dravencms\Database\Attributes\TimestampableEntity;
use Dravencms\Database\Attributes\Identifier;
use Nette;

/**
 * Class PasswordReset
 * @package App\Model\Entities
 */
#[ORM\Entity]
#[Gedmo\SoftDeleteable(fieldName: "deletedAt", timeAware: true)]
#[ORM\Table(name: "userPasswordReset")]
class PasswordReset
{
    use Nette\SmartObject;
    use Identifier;
    use TimestampableEntity;
    use SoftDeleteableEntity;

    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: "User", inversedBy: "passwordResets")]
    #[ORM\JoinColumn(name: "user_id", referencedColumnName: "id")]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(type: "string", length: 64, unique: true, nullable: false)]
    private $hash;

    private ?string $token = null;

    /**
     * @var boolean
     */
    #[ORM\Column(type: "boolean", nullable: false)]
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
        $this->token = bin2hex(random_bytes(32));
        $this->hash = hash('sha256', $this->token);
        $this->isUsed = false;
    }

    /**
     * @return string
     */
    /** Available only immediately after issuing a reset; never persisted. */
    public function getToken(): string
    {
        return $this->token ?? throw new \LogicException('The reset token is only available when issued.');
    }

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