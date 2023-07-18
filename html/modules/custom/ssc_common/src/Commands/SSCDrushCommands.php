<?php

namespace Drupal\ssc_common\Commands;

use Drupal\Core\Database\Database;
use Drush\Commands\DrushCommands;

/**
 * Upgrade Status Drush command
 */
class SSCDrushCommands extends DrushCommands {

  /**
   * Clear cache tables as CR no longer works in Windows.
   *
   * @command cache_clear:completex
   * @aliases ccc
   *
   * @throws \InvalidArgumentException
   *   Thrown when one of the passed arguments is invalid or no arguments were provided.
   */
  public function cacheClearComplete() {
    // Get all cache tables.
    $tables = $this->getTablesStartingWithPrefix('cache');

    foreach ($tables as $table) {
      \Drupal::database()->truncate($table)->execute();
    }

    $output = 'All cache tables emptied.';
    $this->output()->writeln($output);
  }

  private function getTablesStartingWithPrefix($prefix) {
    $connection = Database::getConnection();

    // Retrieve the list of tables.
    $query = "SHOW TABLES LIKE :prefix";
    $tables = $connection->query($query, [':prefix' => 'cache_' . '%'])->fetchAll();

    $result = [];
    foreach ($tables as $table) {
      $result[] = current((array) $table);
    }

    return $result;
  }

}
