<?php

declare(strict_types=1);

namespace Drupal\marvin_frontend_product\Lint;

use Drupal\marvin\Lint\CommandEvent as BaseCommandEvent;

class CommandEvent extends BaseCommandEvent {

  public const string EVENT_RUN_TASKS_COLLECT = 'marvin.lint.frontend.run.tasks.collect';

  public const string EVENT_RUN_TASKS_ALTER = 'marvin.lint.frontend.run.tasks.alter';

}
