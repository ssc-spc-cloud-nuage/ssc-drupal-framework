<?php

namespace Drupal\ssc_common\Plugin\Block;

use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\toc_api\Entity\TocType;

/**
 * Provides a TOC block using the TOC API.
 *
 * @Block(
 *  id = "toc_block",
 *  admin_label = @Translation("SSC TOC"),
 * )
 */
class TOCBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Get list of available TOC Types.
    $ids = \Drupal::entityQuery('toc_type')->execute();

    $form = parent::blockForm($form, $form_state);
    $form['toc_type'] = [
      '#type' => 'select',
      '#title' => $this->t('TOC Type'),
      '#options' => $ids,
      '#description' => $this->t('Select the type of TOC to be displayed in this block.'),
      '#default_value' => $this->configuration['toc_type'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['toc_type'] = $form_state->getValue('toc_type');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Required to break block - render page - insert block - render page, etc, etc.
    $full_content = &drupal_static('toc_block_full');
    if (!empty($full_content)) return [];

    $build = [];

    // Get TOC Type configured for this Block.
    $toc_type_id = $this->configuration['toc_type'];

    $node = \Drupal::routeMatch()->getParameter('node');
    if (!($node instanceof \Drupal\node\NodeInterface)) {
      return [
        '#markup' => $this->t('TOC (' . $toc_type_id . '): Not a NODE page') // , ['%type' => $toc_type_id]) not passing in var ??,
      ];
    }

    $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
    $full_content = $view_builder->view($node, 'full');
    $content = (string) \Drupal::service('renderer')->render($full_content);

    // Get TOC options for this Type.
    /** @var \Drupal\toc_api\TocTypeInterface $toc_type */
    $toc_type = TocType::load($toc_type_id);
    $options = ($toc_type) ? $toc_type->getOptions() : [];

    // Create a TOC instance using the TOC manager.
    /** @var \Drupal\toc_api\TocManagerInterface $toc_manager */
    $toc_manager = \Drupal::service('toc_api.manager');
    /** @var \Drupal\toc_api\TocInterface $toc */
    $toc = $toc_manager->create('toc_filter', $content, $options);

    // If the TOC is visible (ie has more than X headers), replace the body
    // render array with the TOC and update body content using the TOC builder.
    if ($toc->isVisible()) {
      /** @var \Drupal\toc_api\TocBuilderInterface $toc_builder */
      $toc_builder = \Drupal::service('toc_api.builder');
      $build = [
        'toc' => $toc_builder->buildToc($toc),
        //'content' => $toc_builder->buildContent($toc),
      ];
    }

    return $build;
  }

}
