<?php declare(strict_types = 1);

namespace Dravencms\User\DI;

use Dravencms\User\User;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\DI\CompilerExtension;

/**
 * Class UserExtension
 * @package Dravencms\User\DI
 */
class UserExtension extends CompilerExtension
{
    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();
        $builder->addDefinition($this->prefix('user'))
            ->setFactory(User::class);

        $builder->addDefinition($this->prefix('filters'))
            ->setFactory(\Dravencms\Latte\User\Filters\User::class)
            ->setAutowired(false);

        $this->loadComponents();
        $this->loadModels();
        $this->loadConsole();
    }


    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();

        $latteFactoryService = $builder->getDefinitionByType(LatteFactory::class)->getResultDefinition();
        $latteFactoryService->addSetup('addFilter', ['formatUserName', [$this->prefix('@filters'), 'formatUserName']]);
        $latteFactoryService->addSetup('addFilter', ['isAllowed', [$this->prefix('@filters'), 'isAllowed']]);
        $latteFactoryService->addSetup('addFilter', ['getUserService', [$this->prefix('@filters'), 'getUserService']]);
        $latteFactoryService->addSetup('Dravencms\Latte\User\Macros\Acl::install(?->getCompiler())', ['@self']);
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

        foreach ($this->loadFromFile(__DIR__ . '/console.neon') as $i => $command) {
            $cli = $builder->addDefinition($this->prefix('cli.' . $i))
                ->setAutowired(false);

            if (is_string($command)) {
                $cli->setFactory($command);
            } else {
                throw new \InvalidArgumentException;
            }
        }
    }

}
