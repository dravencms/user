<?php declare(strict_types=1);

namespace Dravencms\User\Acl;

final readonly class AclPermission
{
    public string $id;

    /** @param list<string> $requires */
    public function __construct(
        public string $resource,
        public string $privilege,
        public string $label,
        public ?string $description = null,
        public ?string $resourceLabel = null,
        public array $requires = [],
    ) {
        foreach ([$resource, $privilege] as $identifier) {
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/D', $identifier)) {
                throw new \InvalidArgumentException("Invalid ACL identifier '$identifier'.");
            }
        }
        if ($label === '') {
            throw new \InvalidArgumentException('Permission labels must not be empty.');
        }
        foreach ($requires as $dependency) {
            if (!is_string($dependency) || $dependency === '') {
                throw new \InvalidArgumentException('Permission dependencies must be permission IDs.');
            }
        }
        $this->id = $resource . '.' . $privilege;
    }
}
