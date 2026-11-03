<?php declare(strict_types=1);

namespace Dravencms\User\Acl;

final class AclRegistry
{
    /** @var list<AclProviderInterface> */
    private array $providers = [];
    /** @var array<string, AclPermission>|null */
    private ?array $permissions = null;
    private array $resourceLabels = [];

    public function addProvider(AclProviderInterface $provider): void
    {
        $this->providers[] = $provider;
        $this->permissions = null;
    }

    /** @return array<string, AclPermission> */
    public function getPermissions(): array
    {
        if ($this->permissions !== null) {
            return $this->permissions;
        }
        $permissions = []; $labels = []; $canonicalIds = []; $canonicalResources = [];
        foreach ($this->providers as $provider) {
            foreach ($provider->getPermissions() as $permission) {
                if (!$permission instanceof AclPermission) {
                    throw new \InvalidArgumentException('ACL providers must return AclPermission instances.');
                }
                $folded = strtolower($permission->id);
                $resourceKey = strtolower($permission->resource);
                if (isset($canonicalIds[$folded]) && $canonicalIds[$folded] !== $permission->id) {
                    throw new \InvalidArgumentException("Case-ambiguous ACL permission '$permission->id'.");
                }
                if (isset($canonicalResources[$resourceKey]) && $canonicalResources[$resourceKey] !== $permission->resource) {
                    throw new \InvalidArgumentException("Case-ambiguous ACL resource '$permission->resource'.");
                }
                $canonicalIds[$folded] = $permission->id;
                $canonicalResources[$resourceKey] = $permission->resource;
                if (isset($permissions[$permission->id])) {
                    throw new \InvalidArgumentException("Duplicate ACL permission '$permission->id'.");
                }
                if ($permission->resourceLabel !== null) {
                    if (isset($labels[$permission->resource]) && $labels[$permission->resource] !== $permission->resourceLabel) {
                        throw new \InvalidArgumentException("Conflicting labels for ACL resource '$permission->resource'.");
                    }
                    $labels[$permission->resource] = $permission->resourceLabel;
                }
                $permissions[$permission->id] = $permission;
            }
        }
        $done = []; $visiting = [];
        $visit = function (string $id) use (&$visit, &$done, &$visiting, $permissions): void {
            if (isset($done[$id])) { return; }
            if (isset($visiting[$id])) {
                throw new \InvalidArgumentException("ACL dependency cycle at '$id'.");
            }
            if (!isset($permissions[$id])) {
                throw new \InvalidArgumentException("Unknown ACL dependency '$id'.");
            }
            $visiting[$id] = true;
            foreach ($permissions[$id]->requires as $required) { $visit($required); }
            unset($visiting[$id]); $done[$id] = true;
        };
        foreach ($permissions as $id => $permission) {
            $visit($id);
            $labels[$permission->resource] ??= $permission->resource;
        }
        ksort($permissions); ksort($labels);
        $this->resourceLabels = $labels;
        return $this->permissions = $permissions;
    }

    public function getResourceLabels(): array
    {
        $this->getPermissions();
        return $this->resourceLabels;
    }

    /** Resolve prerequisites; stored inactive-plugin grants are ignored when strict is false.
     * @param list<string> $explicit @return list<string>
     */
    public function resolve(array $explicit, bool $strict = true): array
    {
        $permissions = $this->getPermissions(); $resolved = [];
        $visit = function (string $id) use (&$visit, &$resolved, $permissions): void {
            if (isset($resolved[$id])) { return; }
            foreach ($permissions[$id]->requires as $required) { $visit($required); }
            $resolved[$id] = true;
        };
        foreach ($explicit as $id) {
            if (!is_string($id) || !isset($permissions[$id])) {
                if ($strict) { throw new \InvalidArgumentException('Unknown ACL permission: ' . (is_scalar($id) ? $id : get_debug_type($id))); }
                continue;
            }
            $visit($id);
        }
        return array_keys($resolved);
    }

    /** @return array<string, list<string>> */
    public function getRequiredBy(array $explicit): array
    {
        $requiredBy = [];
        foreach ($explicit as $id) {
            foreach ($this->resolve([$id]) as $required) {
                if ($required !== $id) { $requiredBy[$required][] = $id; }
            }
        }
        return $requiredBy;
    }
}
