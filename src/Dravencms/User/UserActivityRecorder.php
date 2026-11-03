<?php declare(strict_types=1);

namespace Dravencms\User;

use Doctrine\DBAL\{ParameterType, Types\Types};
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Dravencms\Model\User\Entities\User;

final class UserActivityRecorder
{
    public function __construct(private EntityManagerInterface $manager, private int $interval = 300) {}

    public function record(User $user): void
    {
        $now = new \DateTime(); $cutoff = (clone $now)->modify('-' . $this->interval . ' seconds');
        if ($user->getLastActivity() > $cutoff) { return; }
        $metadata = $this->manager->getClassMetadata(User::class);
        $connection = $this->manager->getConnection(); $table = $metadata->getTableName();
        if ($connection->getDatabasePlatform() instanceof PostgreSQLPlatform) { $table = strtolower($table); }
        $quote = $connection->quoteIdentifier(...);
        $column = $quote($metadata->getColumnName('lastActivity'));
        $changed = $connection->executeStatement('UPDATE ' . $quote($table) . ' SET ' . $column . ' = ? WHERE ' . $quote($metadata->getSingleIdentifierColumnName()) . ' = ? AND ' . $column . ' <= ?',
            [$now, $user->getId(), $cutoff], [Types::DATETIME_MUTABLE, ParameterType::INTEGER, Types::DATETIME_MUTABLE]);
        if ($changed > 0) {
            $user->setLastActivity($now);
            $this->manager->getUnitOfWork()->setOriginalEntityProperty(spl_object_id($user), 'lastActivity', $now);
        }
    }
}
