<?php

declare(strict_types=1);

namespace Drupal\ssc_common;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class SitewideAlertManager extends \Drupal\sitewide_alert\SitewideAlertManager {

  /**
   * Returns all active and currently visible sitewide alerts.
   *
   * @return \Drupal\sitewide_alert\Entity\SitewideAlertInterface[]
   *   Array of active sitewide alerts indexed by their ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function activeVisibleSitewideAlerts(): array {
    /** @var \Drupal\sitewide_alert\Entity\SitewideAlertInterface[] $activeVisibleSitewideAlerts */
    $activeVisibleSitewideAlerts = $this->activeSitewideAlerts();

    // Flip to show in Desc arder: https://www.drupal.org/project/sitewide_alert/issues/3250700
    $activeVisibleSitewideAlerts = array_reverse($activeVisibleSitewideAlerts);

    // Remove any sitewide alerts that are scheduled and it is not time to show them.
    foreach ($activeVisibleSitewideAlerts as $id => $sitewideAlert) {
      if ($sitewideAlert->isScheduled() &&
        !$sitewideAlert->isScheduledToShowAt(parent::requestDateTime())) {
        unset($activeVisibleSitewideAlerts[$id]);
      }
    }

    return $activeVisibleSitewideAlerts;
  }

}
