<?php

namespace Drupal\backlinks\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\views\Views;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class EventsSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * HelloWorldRedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   */
  public function __construct(AccountProxyInterface $currentUser) {
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['setOrphanMessage'];
    return $events;
  }
  /**
   * Handler for the kernel request event.
   *
   * @param RequestEvent $event
   */
  public function setOrphanMessage(RequestEvent $event) {
    // Only display on the Node "View" page.
    $route_match = \Drupal::routeMatch();
    if ($route_match->getRouteName() != 'entity.node.canonical') {
      return;
    }

    // Let's not show on the Home page.
    $request = $event->getRequest();
    $path = $request->getPathInfo();
    if ($path == '/en' || $path == '/fr') return;

    $roles = $this->currentUser->getRoles();
    // Only show for Admins.
    if (in_array('administrator', $roles)) {
      $node = $route_match->getParameter('node');
      $view = Views::getView('orphaned_pages');
      $view->setDisplay('block_1');
      $view->setArguments([$node->id()]);
      $view->execute();

      // If no results let's bail, so we don't have an empty block.
      if (!count($view->result)) return;

      $rendered = $view->render();
      $output = \Drupal::service('renderer')->renderPlain($rendered);
      \Drupal::messenger()
        ->addMessage($output, 'warning', FALSE);
    }
  }

}
