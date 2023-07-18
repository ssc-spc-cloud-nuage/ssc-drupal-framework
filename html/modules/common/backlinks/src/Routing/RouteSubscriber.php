<?php

namespace Drupal\backlinks\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * Set backlinks page to use Admin theme.
   *
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $route = $collection->get('view.backlinks_per_node.page_1');
    if ($route) {
      $route->setOption('_admin_route', TRUE);
    }
  }
}
