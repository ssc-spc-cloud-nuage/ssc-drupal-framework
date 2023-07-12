<?php

namespace Drupal\backlinks\Plugin\Action;

use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * Update Backlinks table.
 *
 * @Action(
 *   id = "update_backlinks",
 *   label = @Translation("Update backlinks"),
 *   type = "node"
 * )
 */
class UpdateBacklinks extends ActionBase {

  /**
   * {@inheritdoc}
   *
   */
  public function execute(Node $node = NULL) {
    if (!$node) return FALSE;

    // Run backlink processing as if this node was being updated.
    update_backlinks($node);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $object->access('update', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

}

