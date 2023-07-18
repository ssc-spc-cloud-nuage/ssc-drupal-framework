<?php

namespace Drupal\ssc_common\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Allow Subscriptions page (/user/{user}/message-subscribe) only to Admins
    if ($route = $collection->get('message_subscribe_ui.tab')) {
      $route->setRequirement('_role', 'administrator');
    }

    // Block access to User Edit page unless Admin.
    // This allows Quickedit of user data without allowing access to User edit page.
    if ($route = $collection->get('entity.user.edit_form')) {
      $route->setRequirement('_role', 'administrator');
    }

    // Remove access to Reset Password.
    if ($route = $collection->get('user.pass')) {
      $route->setRequirement('_role', 'authenticated');
    }
    if ($route = $collection->get('user.pass,http')) {
      $route->setRequirement('_role', 'authenticated');
    }

  }

}
