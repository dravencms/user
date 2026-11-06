<?php declare(strict_types=1);
namespace Dravencms\User;

use Dravencms\Admin\Dashboard\{DashboardDefinition, DashboardMetric, StatisticsComponent};
use Dravencms\Database\EntityManager;

final class DashboardComponent extends StatisticsComponent
{
    public function __construct(private EntityManager $entityManager) {}

    public function getDefinition(): DashboardDefinition
    {
        return new DashboardDefinition('user.overview', 'user.dashboard.title', 800,
            resource: 'user', privilege: 'edit', destination: ':Admin:User:User:default', icon: 'fa-users');
    }

    protected function getMetrics(): iterable
    {
        $query = $this->entityManager->createQuery('SELECT COUNT(u.id) AS total, COALESCE(SUM(CASE WHEN u.isActive = true THEN 1 ELSE 0 END), 0) AS active FROM Dravencms\\Model\\User\\Entities\\User u WHERE u.namespace = :namespace AND u.isShadow = false');
        $query->setParameter('namespace', $this->getPresenter()->getNamespace());
        $stats = $query->getSingleResult();
        yield new DashboardMetric('user.dashboard.total', (int) $stats['total']);
        yield new DashboardMetric('user.dashboard.active', (int) $stats['active']);
    }
}
