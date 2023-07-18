<?php

namespace Drupal\ssc_common;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;

class SscCustomServiceProvider extends ServiceProviderBase implements ServiceProviderInterface {

  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('sitewide_alert.sitewide_alert_manager');
    $definition->setClass('Drupal\ssc_common\SitewideAlertManager');

    $definition = $container->getDefinition('date.formatter');
    $definition->setClass('Drupal\ssc_common\DateFormatter');
  }

}
