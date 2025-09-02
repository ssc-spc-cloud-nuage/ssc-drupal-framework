<?php

namespace Drupal\ssc_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'FooterAsideBlock' block.
 *
 * @Block(
 *  id = "footer_aside_block",
 *  admin_label = @Translation("Footer aside"),
 * )
 */
class FooterAsideBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('ssc_common.settings');

    return [
      '#type' => 'markup',
      '#markup' => $config->get('footer_aside.value'),
      '#cache' => [
        'tags' => $config->getCacheTags(),
      ]
    ];
  }
}
