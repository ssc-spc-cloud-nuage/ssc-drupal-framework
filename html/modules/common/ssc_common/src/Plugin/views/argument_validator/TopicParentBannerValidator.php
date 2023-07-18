<?php

namespace Drupal\ssc_common\Plugin\views\argument_validator;

use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;

/**
 * Convert Content ID from URL to Node ID of 1st parent with a Banner Image.
 *
 * @ingroup views_argument_validate_plugins
 *
 * @ViewsArgumentValidator(
 *   id = "topic_parent_banner",
 *   title = @Translation("Topic parent banner")
 * )
 */
class TopicParentBannerValidator extends ArgumentValidatorPluginBase {

  /**
   * Sets the argument to top parent with Banner Image.
   *
   * @param \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument
   *   The parent argument to set.
   */
  public function validateArgument($argument) {
    if (!isset($argument)) {
      return NULL;
    }
    $this->argument->argument = $this->getParentBanner($argument);

    return TRUE;
  }

  private function getParentBanner($nid) {
    $node = Node::load($nid);
    if (!$node) {
      return $nid;
    }
    if ($node->hasField('field_banner_image') && $node->field_banner_image->isEmpty() &&
      $node->hasField('field_parent_page')) {
      return $this->getParentBanner($node->field_parent_page->target_id);
    }
    return $nid;
  }

}
