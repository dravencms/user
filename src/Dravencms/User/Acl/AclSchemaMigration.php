<?php declare(strict_types=1);

namespace Dravencms\User\Acl;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\{AbstractMySQLPlatform, SQLitePlatform};

final class AclSchemaMigration
{
    public function __construct(private Connection $connection)
    {
    }

    /** Only the ACL definition tables are changed; IDs and foreign keys are preserved. */
    public function getSql(): array
    {
        $manager = $this->connection->createSchemaManager();
        $old = $manager->introspectSchema(); $new = clone $old;
        foreach (['userAclResource', 'userAclOperation'] as $name) {
            if (!$new->hasTable($name)) {
                throw new \RuntimeException('Create the application schema before migrating ACL definitions.');
            }
            $table = $new->getTable($name);
            $identifier = $table->hasColumn('identifier') ? 'identifier' : 'name';
            $columns = $name === 'userAclOperation' ? ['aclresource_id', $identifier] : [$identifier];
            $quoted = implode(', ', array_map($this->connection->quoteIdentifier(...), $columns));
            if ($this->connection->fetchOne('SELECT COUNT(*) FROM ' . $this->connection->quoteIdentifier($table->getName()) . ' GROUP BY ' . $quoted . ' HAVING COUNT(*) > 1') !== false) {
                throw new \RuntimeException("Duplicate identifiers in '$name'; resolve them before migrating.");
            }
            if ($identifier === 'name') { $table->renameColumn('name', 'identifier'); }
            foreach ($table->getColumns() as $column) {
                if (in_array(strtolower(str_replace('_', '', $column->getName())), ['description', 'createdat', 'updatedat'], true)) {
                    $table->dropColumn($column->getName());
                }
            }
            if ($name === 'userAclOperation') {
                $q = $this->connection->quoteIdentifier(...);
                if ($this->connection->fetchOne('SELECT 1 FROM ' . $q($old->getTable('userAclOperation')->getName()) . ' o LEFT JOIN ' . $q($old->getTable('userAclResource')->getName()) . ' r ON o.aclresource_id = r.id WHERE r.id IS NULL') !== false) {
                    throw new \RuntimeException('Orphan ACL operations must be resolved before migration.');
                }
                $table->getColumn('aclresource_id')->setNotnull(true);
            }
            $expectedColumns = $name === 'userAclOperation' ? ['id', 'identifier', 'aclresource_id'] : ['id', 'identifier'];
            if (array_diff(array_map(static fn(\Doctrine\DBAL\Schema\Column $column): string => $column->getName(), $table->getColumns()), $expectedColumns) !== []) {
                throw new \RuntimeException("Unexpected columns in '$name'; review the schema before migration.");
            }
            $expected = $name === 'userAclOperation' ? ['aclresource_id', 'identifier'] : ['identifier'];
            $unique = false;
            foreach (array_merge($table->getIndexes(), $table->getUniqueConstraints()) as $constraint) {
                $columns = $constraint->getColumns(); sort($columns);
                $isUnique = $constraint instanceof \Doctrine\DBAL\Schema\Index ? $constraint->isUnique() : true;
                if ($isUnique && $columns === $expected) { $unique = true; }
            }
            if (!$unique) { $table->addUniqueIndex($expected, $name === 'userAclOperation' ? 'acl_resource_identifier' : 'acl_resource_key'); }

        }
        $sql = [];
        foreach (['userAclResource', 'userAclOperation'] as $name) {
            $diff = $manager->createComparator()->compareTables($old->getTable($name), $new->getTable($name));
            if (!$diff->isEmpty()) {
                array_push($sql, ...$this->connection->getDatabasePlatform()->getAlterTableSQL($diff));
            }
        }
        return $sql;
    }

    public function apply(): void
    {
        $sql = $this->getSql();
        $execute = function () use ($sql): void {
            if ($this->connection->getDatabasePlatform() instanceof SQLitePlatform) {
                $this->connection->executeStatement('PRAGMA defer_foreign_keys = ON');
            }
            foreach ($sql as $statement) { $this->connection->executeStatement($statement); }
            if ($this->connection->getDatabasePlatform() instanceof SQLitePlatform && $this->connection->fetchAllAssociative('PRAGMA foreign_key_check') !== []) {
                throw new \RuntimeException('ACL migration failed foreign-key validation.');
            }
        };
        // MySQL DDL commits implicitly; do not pretend that it can be rolled back.
        if ($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform) { $execute(); }
        else { $this->connection->transactional($execute); }
    }
}
