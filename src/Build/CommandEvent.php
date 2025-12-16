<?php

declare(strict_types=1);

namespace Drupal\marvin_frontend_product\Build;

use Drupal\marvin\Build\CommandEvent as BaseCommandEvent;

class CommandEvent extends BaseCommandEvent {

  public const string EVENT_BUILD_TASKS_COLLECT = 'marvin.build.frontend.tasks.collect';

  public const string EVENT_BUILD_TASKS_ALTER = 'marvin.build.frontend.tasks.alter';

}
