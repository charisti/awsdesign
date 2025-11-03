<?php

namespace Drupal\eca_migrate\Event;

/**
 * Events dispatched by the eca_migrate module.
 */
final class EcaMigrateEvents {

  /**
   * Dispatches on migrate processing.
   *
   * @Event
   *
   * @var string
   */
  public const PROCESS = 'eca_migrate.process';

}
