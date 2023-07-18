<?php

namespace Drupal\ssc_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'AlertIconBlock' block.
 *
 * @Block(
 *  id = "alert_icon_block",
 *  admin_label = @Translation("Alert icon"),
 * )
 */
class AlertIconBlock extends BlockBase {

  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'alert_icon_block';
    $build['#attached']['library'][] = 'ssc_common/header-link';

    return $build;
  }

}
