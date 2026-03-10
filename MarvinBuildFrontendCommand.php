<?php

declare(strict_types=1);

namespace Drush\Commands\marvin_frontend_product;

use Drupal\marvin\Build\CommandEvent as BuildCommandEvent;
use Drupal\marvin\ContainerInitializer;
use Drupal\marvin\MarvinTaskDefinitionCommandTrait;
use Drupal\marvin\ProcessFactoryInterface;
use Drupal\marvin\Utils;
use Drupal\marvin_frontend\FrontendCommandTrait;
use Drupal\marvin_frontend_product\Build\CommandEvent as BuildFrontendCommandEvent;
use Drupal\marvin_git\GitHook\CommandEvent as GitHookCommandEvent;
use Drupal\marvin_product\CommandsBaseTrait;
use Drush\Attributes\Bootstrap as CliBootstrap;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\AutowireTrait;
use Drush\Config\DrushConfig;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Robo\Collection\Tasks as ForEachTaskLoader;
use Robo\Contract\BuilderAwareInterface;
use Robo\Task\Base\Tasks as BaseTaskLoader;
use Robo\TaskAccessor;
use Sweetchuck\Robo\Git\GitTaskLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
  name: self::NAME,
  description: 'Builds frontend assets.',
)]
#[CliBootstrap(level: DrupalBootLevels::NONE)]
final class MarvinBuildFrontendCommand extends Command implements
  BuilderAwareInterface,
  ContainerAwareInterface
{

  use AutowireTrait {
    create as protected autowireCreate;
  }
  use ContainerAwareTrait;
  use TaskAccessor;
  use BaseTaskLoader;
  use ForEachTaskLoader;
  use CommandsBaseTrait;
  use GitTaskLoader;
  use FrontendCommandTrait;
  use MarvinTaskDefinitionCommandTrait;

  public const string NAME = 'marvin:build:frontend';

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    ContainerInitializer::initialize($container);

    return self::autowireCreate($container);
  }

  public function __construct(
    #[Autowire('config')]
    protected DrushConfig $drushConfig,
    #[Autowire('eventDispatcher')]
    protected EventDispatcherInterface $eventDispatcher,
    #[Autowire(LoggerInterface::class)]
    protected LoggerInterface $logger,
    #[Autowire(Utils::class)]
    protected Utils $utils,
    #[Autowire(Filesystem::class)]
    protected Filesystem $fs,
    #[Autowire(ProcessFactoryInterface::class)]
    protected ProcessFactoryInterface $processFactory,
  ) {
    parent::__construct();

    $this->eventDispatcher->addListener(
      BuildCommandEvent::EVENT_BUILD_TASKS_COLLECT,
      $this->onEventMarvinBuildCollectTasks(...),
    );

    $this->eventDispatcher->addListener(
      BuildFrontendCommandEvent::EVENT_BUILD_TASKS_COLLECT,
      $this->onEventMarvinBuildFrontendCollectTasks(...),
    );

    if (class_exists(GitHookCommandEvent::class)) {
      $this->eventDispatcher->addListener(
        GitHookCommandEvent::EVENT_PRE_COMMIT_TASKS_COLLECT,
        $this->onEventMarvinGitHookPreCommitCollectTasks(...),
      );
    }
  }

  /**
   * {@inheritdoc }
   */
  protected function execute(
    InputInterface $input,
    OutputInterface $output,
  ): int {
    $event = $this->eventDispatcher->dispatch(
      new BuildFrontendCommandEvent(
        $input,
        $output,
        NULL,
        $this->collectionBuilder(),
        [],
      ),
      BuildFrontendCommandEvent::EVENT_BUILD_TASKS_COLLECT,
    );

    $event = $this->eventDispatcher->dispatch(
      new BuildFrontendCommandEvent(
        $input,
        $output,
        NULL,
        $event->collectionBuilder,
        $event->taskDefinitions,
      ),
      BuildFrontendCommandEvent::EVENT_BUILD_TASKS_ALTER,
    );

    $this->mtdRun(
      self::NAME,
      $event->collectionBuilder,
      $event->taskDefinitions,
    );

    return Command::SUCCESS;
  }

  public function onEventMarvinBuildFrontendCollectTasks(BuildFrontendCommandEvent $event): void {
    $event->taskDefinitions += $this->getTaskDefsInitStateDataBase($event);
    $event->taskDefinitions += $this->getTaskDefsBuildFrontend($event, '.');
  }

  public function onEventMarvinBuildCollectTasks(BuildCommandEvent $event): void {
    $event->taskDefinitions += $this->getTaskDefsBuildFrontend($event, '.');
  }

  public function onEventMarvinGitHookPreCommitCollectTasks(GitHookCommandEvent $event): void {
    $event->taskDefinitions += $this->getTaskDefsLintFrontend($event, '.');
  }

}
