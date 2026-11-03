<?php declare(strict_types=1);

namespace Dravencms\User\Acl;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Platforms\{AbstractMySQLPlatform, PostgreSQLPlatform, SQLitePlatform};
use Doctrine\ORM\EntityManagerInterface;
use Dravencms\Model\User\Entities\{AclOperation, Group};

final class AclGrantStore
{
    public function __construct(private EntityManagerInterface $manager, private AclRegistry $registry)
    {
    }

    /** @return list<string> */
    public function getExplicitKeys(Group $group, bool $includeInactive = false): array
    {
        $known = $this->registry->getPermissions(); $keys = [];
        foreach ($group->getAclOperations() as $operation) {
            $key = $operation->getKey();
            if ($includeInactive || isset($known[$key])) { $keys[] = $key; }
        }
        return $keys;
    }

    /** Only explicit selections are granted. Dependency rows can exist without group grants. */
    public function assign(Group $group, array $explicit): void
    {
        $effective = $this->registry->resolve($explicit);
        $definitions = $this->registry->getPermissions();
        $this->manager->wrapInTransaction(function () use ($group, $explicit, $effective, $definitions): void {
            $operations = [];
            foreach ($effective as $key) {
                $permission = $definitions[$key];
                $resourceId = $this->ensureRow('userAclResource', ['identifier' => $permission->resource]);
                $id = $this->ensureRow('userAclOperation', ['aclresource_id' => $resourceId, 'identifier' => $permission->privilege]);
                $operations[$key] = $this->manager->getReference(AclOperation::class, $id);
            }
            $selected = [];
            foreach (array_unique($explicit) as $key) { $selected[] = $operations[$key]; }
            // An unrelated group edit must not erase grants belonging to a disabled plugin.
            foreach ($group->getAclOperations() as $operation) {
                if (!isset($definitions[$operation->getKey()])) { $selected[] = $operation; }
            }
            $group->setAclOperations(new ArrayCollection($selected));
            $this->manager->persist($group);
        });
    }

    private function ensureRow(string $table, array $values): int
    {
        $connection = $this->manager->getConnection(); $platform = $connection->getDatabasePlatform();
        // The ORM's unquoted mixed-case table names are folded by PostgreSQL.
        if ($platform instanceof PostgreSQLPlatform) { $table = strtolower($table); }
        $quote = $connection->quoteIdentifier(...);
        $columns = implode(', ', array_map($quote, array_keys($values)));
        $sql = 'INSERT INTO ' . $quote($table) . ' (' . $columns . ') VALUES (' . implode(', ', array_fill(0, count($values), '?')) . ')';
        if ($platform instanceof AbstractMySQLPlatform) {
            $sql .= ' ON DUPLICATE KEY UPDATE ' . $quote('id') . ' = ' . $quote('id');
        } elseif ($platform instanceof SQLitePlatform || $platform instanceof PostgreSQLPlatform) {
            $sql .= ' ON CONFLICT (' . $columns . ') DO NOTHING';
        } else {
            throw new \LogicException('ACL assignment supports MySQL/MariaDB, PostgreSQL and SQLite.');
        }
        $connection->executeStatement($sql, array_values($values));
        $where = implode(' AND ', array_map(static fn(string $column): string => $quote($column) . ' = ?', array_keys($values)));
        $id = $connection->fetchOne('SELECT ' . $quote('id') . ' FROM ' . $quote($table) . ' WHERE ' . $where . ($platform instanceof SQLitePlatform ? '' : ' FOR UPDATE'), array_values($values));
        if ($id === false) { throw new \LogicException('Could not resolve persisted ACL identifier.'); }
        return (int) $id;
    }
}
