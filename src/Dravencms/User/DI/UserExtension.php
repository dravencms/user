<?php declare(strict_types = 1);

namespace Dravencms\User\DI;

use Contributte\Translation\DI\TranslationProviderInterface;
use Dravencms\User\User;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\DI\CompilerExtension;

/**
 * Class UserExtension
 * @package Dravencms\User\DI
 */
class UserExtension extends CompilerExtension implements TranslationProviderInterface
{
    public function getConfigSchema(): \Nette\Schema\Schema
    {
        return \Nette\Schema\Expect::structure(['activityInterval' => \Nette\Schema\Expect::int(300)->min(1)]);
    }

    public function getTranslationResources(): array
    {
        return [__DIR__ . '/../lang'];
    }

    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix('activityRecorder'))->setFactory(\Dravencms\User\UserActivityRecorder::class, ['interval' => $this->getConfig()->activityInterval ?? 300]);
        $builder->addDefinition($this->prefix('securityMigration'))->setFactory(\Dravencms\User\PasswordResetSchemaMigration::class);
        $builder->addDefinition($this->prefix('aclProvider'))->setFactory(\Dravencms\User\AclProvider::class);
        $builder->addDefinition($this->prefix('aclRegistry'))->setFactory(\Dravencms\User\Acl\AclRegistry::class);
        $builder->addDefinition($this->prefix('aclGrantStore'))->setFactory(\Dravencms\User\Acl\AclGrantStore::class);
        $builder->addDefinition($this->prefix('aclBuilder'))->setFactory(\Dravencms\User\Acl\AclPermissionBuilder::class);
        $builder->addDefinition($this->prefix('aclMigration'))->setFactory(\Dravencms\User\Acl\AclSchemaMigration::class);

        if ($this->compiler->getExtensions(\Dravencms\Admin\DI\AdminExtension::class) !== []) {
            $builder->addDefinition($this->prefix('adminMenuProvider'))->setFactory(\Dravencms\User\AdminMenuProvider::class);
            $builder->addDefinition($this->prefix('groupPresenter'))->setFactory(\Dravencms\AdminModule\UserModule\GroupPresenter::class);
            $builder->addDefinition($this->prefix('userPresenter'))->setFactory(\Dravencms\AdminModule\UserModule\UserPresenter::class);
            $this->loadComponents();
        }
        $builder->addDefinition($this->prefix('user'))
            ->setFactory(User::class);

        $builder->addDefinition($this->prefix('filters'))
            ->setFactory(\Dravencms\Latte\User\Filters\User::class)
            ->setAutowired(false);

        $this->loadModels();
        $this->loadConsole();
    }


    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();

        $registry = $builder->getDefinition($this->prefix('aclRegistry'));
        foreach ($builder->findByType(\Dravencms\User\Acl\AclProviderInterface::class) as $name => $definition) {
            $registry->addSetup('addProvider', ['@' . $name]);
        }
        $registry->addSetup('getPermissions');

        $latteFactoryService = $builder->getDefinitionByType(LatteFactory::class)->getResultDefinition();
        $latteFactoryService->addSetup('addFilter', ['formatUserName', [$this->prefix('@filters'), 'formatUserName']]);
        $latteFactoryService->addSetup('addFunction', ['isAllowed', [$this->prefix('@filters'), 'isAllowed']]);
        $latteFactoryService->addSetup('addFilter', ['getUserService', [$this->prefix('@filters'), 'getUserService']]);
    }


    protected function loadComponents(): void
    {
        $builder = $this->getContainerBuilder();
        foreach ($this->loadFromFile(__DIR__ . '/components.neon') as $i => $command) {
            $cli = $builder->addFactoryDefinition($this->prefix('components.' . $i));
            if (is_string($command)) {
                $cli->setImplement($command);
            } else {
                throw new \InvalidArgumentException;
            }
        }
    }

    protected function loadModels(): void
    {
        $builder = $this->getContainerBuilder();
        foreach ($this->loadFromFile(__DIR__ . '/models.neon') as $i => $command) {
            $cli = $builder->addDefinition($this->prefix('models.' . $i));
            if (is_string($command)) {
                $cli->setFactory($command);
            } else {
                throw new \InvalidArgumentException;
            }
        }
    }

    protected function loadConsole(): void
    {
        $builder = $this->getContainerBuilder();

        foreach ($this->loadFromFile(__DIR__ . '/console.neon') as $name => $command) {
            if (!is_string($name) || !is_string($command)) { throw new \InvalidArgumentException('Console commands require a name and class.'); }
            $builder->addDefinition($this->prefix('cli.' . str_replace(':', '.', $name)))
                ->setFactory($command)->setAutowired(false)->addTag('console.command', $name);
        }
    }
}
