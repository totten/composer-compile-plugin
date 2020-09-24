<?php

namespace Civi\CompilePlugin\Subscriber;

use Civi\CompilePlugin\Event\CompileEvents;
use Civi\CompilePlugin\Event\CompileListEvent;
use Civi\CompilePlugin\Event\CompileTaskEvent;
use Civi\CompilePlugin\Exception\TaskFailedException;
use Civi\CompilePlugin\LazyEnvironment;
use Civi\CompilePlugin\Task;
use Civi\CompilePlugin\Util\ComposerIoTrait;
use Civi\CompilePlugin\Util\ShellRunner;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Package\PackageInterface;

class ShellSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
          CompileEvents::PRE_COMPILE_LIST => 'buildLazyEnv',
          CompileEvents::POST_COMPILE_LIST => 'applyDefaultCallback'
        ];
    }

    use ComposerIoTrait;

    /**
     * @var LazyEnvironment
     */
    protected $lazyEnv = null;

    /**
     * @return \Civi\CompilePlugin\LazyEnvironment
     */
    public function buildLazyEnv()
    {
        if ($this->lazyEnv) {
            return;
        }

        $composer = $this->composer;
        $installationManager = $composer->getInstallationManager();

        $envVars = [];
        $addPkgVar = function (PackageInterface $pkg, $path) use (&$envVars) {
            list ($vName, $pName) = explode('/', $pkg->getName());
            $key = 'PKG__' . preg_replace(';[^A-Z0-9_];', '_', strtoupper("{$vName}__{$pName}"));
            $envVars[$key] = $path;
        };

        $envVars['PKG__ROOT'] = realpath('.');
        $addPkgVar($composer->getPackage(), realpath('.'));
        foreach ($composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages() as $package) {
            $addPkgVar($package, $installationManager->getInstallPath($package));
        }

        $this->lazyEnv = new LazyEnvironment($envVars, '[A-Z0-9_]+');
    }

    /**
     * When evaluating the tasks, any task with a 'shell'
     * property will (by default) by handled by us.
     *
     * @param \Civi\CompilePlugin\Event\CompileListEvent $e
     */
    public function applyDefaultCallback(CompileListEvent $e)
    {
        $tasks = $e->getTasks();
        foreach ($tasks as $task) {
            /** @var Task $task */
            if ($task->callback === null && isset($task->definition['shell'])) {
                $task->callback = [$this, 'runTask'];
            }
        }
    }

    public function runTask(CompileTaskEvent $e)
    {
        /** @var Task $task */
        $task = $e->getTask();

        if (empty($task->definition['shell'])) {
            throw new \InvalidArgumentException("Invalid or missing \"shell\" option");
        }

        $r = new ShellRunner($e->getComposer(), $e->getIO());
        $shellCmds = (array) $task->definition['shell'];
        foreach ($shellCmds as $shellCmd) {
            $this->lazyEnv->wrap($shellCmd, function () use ($r, $shellCmd) {
                $r->run($shellCmd);
            });
        }
    }
}
