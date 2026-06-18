<?php

namespace Drupal\ssc_common\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Render\Markup;

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
      // Required in order to avoid the stripping out of web components.
      '#markup' => Markup::create($config->get('footer_aside.value')),
      '#cache' => [
        'tags' => $config->getCacheTags(),
      ]
    ];
  }
}
