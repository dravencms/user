<?php

namespace Dravencms\User\DI;

use Kdyby\Console\DI\ConsoleExtension;
use Nette;
use Nette\DI\Compiler;
use Nette\DI\Configurator;

/**
 * Class UserExtension
 * @package Dravencms\User\DI
 */
class UserExtension extends Nette\DI\CompilerExtension
{
    public function loadConfiguration()
    {
        $config = $this->getConfig();
        $builder = $this->getContainerBuilder();


        $builder->addDefinition($this->prefix('user'))
            ->setClass('Dravencms\User\User', []);

        $builder->addDefinition($this->prefix('filters'))
            ->setClass('Dravencms\Latte\User\Filters\User')
            ->setInject(FALSE);

        $this->loadComponents();
        $this->loadModels();
        $this->loadConsole();
    }


    /**
     * @param Configurator $config
     * @param string $extensionName
     */
    public static function register(Configurator $config, $extensionName = 'userExtension')
    {
        $config->onCompile[] = function (Configurator $config, Compiler $compiler) use ($extensionName) {
            $compiler->addExtension($extensionName, new UserExtension());
        };
    }


    public function beforeCompile()
    {
        $builder = $this->getContainerBuilder();
        $registerToLatte = function (Nette\DI\ServiceDefinition $def) {
            $def->addSetup('?->onCompile[] = function($engine) { Dravencms\Latte\User\Macros\Acl::install($engine->getCompiler()); }', ['@self']);

            $def->addSetup('addFilter', ['formatUserName', [$this->prefix('@filters'), 'formatUserName']]);
        };

        $latteFactoryService = $builder->getByType('Nette\Bridges\ApplicationLatte\ILatteFactory');
        if (!$latteFactoryService || !self::isOfType($builder->getDefinition($latteFactoryService)->getClass(), 'Latte\engine')) {
            $latteFactoryService = 'nette.latteFactory';
        }

        if ($builder->hasDefinition($latteFactoryService) && self::isOfType($builder->getDefinition($latteFactoryService)->getClass(), 'Latte\Engine')) {
            $registerToLatte($builder->getDefinition($latteFactoryService));
        }

        if ($builder->hasDefinition('nette.latte')) {
            $registerToLatte($builder->getDefinition('nette.latte'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig(array $defaults = [], $expand = true)
    {
        $defaults = [
        ];

        return parent::getConfig($defaults, $expand);
    }

    protected function loadComponents()
    {
        $builder = $this->getContainerBuilder();
        foreach ($this->loadFromFile(__DIR__ . '/components.neon') as $i => $command) {
            $cli = $builder->addDefinition($this->prefix('components.' . $i))
                ->setInject(FALSE); // lazy injects
            if (is_string($command)) {
                $cli->setImplement($command);
            } else {
                throw new \InvalidArgumentException;
            }
        }
    }

    protected function loadModels()
    {
        $builder = $this->getContainerBuilder();
        foreach ($this->loadFromFile(__DIR__ . '/models.neon') as $i => $command) {
            $cli = $builder->addDefinition($this->prefix('models.' . $i))
                ->setInject(FALSE); // lazy injects
            if (is_string($command)) {
                $cli->setClass($command);
            } else {
                throw new \InvalidArgumentException;
            }
        }
    }

    protected function loadConsole()
    {
        $builder = $this->getContainerBuilder();

        foreach ($this->loadFromFile(__DIR__ . '/console.neon') as $i => $command) {
            $cli = $builder->addDefinition($this->prefix('cli.' . $i))
                ->addTag(ConsoleExtension::TAG_COMMAND)
                ->setInject(FALSE); // lazy injects

            if (is_string($command)) {
                $cli->setClass($command);

            } else {
                throw new \InvalidArgumentException;
            }
        }
    }

    /**
     * @param string $class
     * @param string $type
     * @return bool
     */
    private static function isOfType($class, $type)
    {
        return $class === $type || is_subclass_of($class, $type);
    }
}
