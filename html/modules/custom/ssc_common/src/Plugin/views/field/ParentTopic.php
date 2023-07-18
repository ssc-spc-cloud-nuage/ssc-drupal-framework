<?php

namespace Drupal\ssc_common\Plugin\views\field;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to list top most Topic page.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("parent_topic")
 */
class ParentTopic extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    return $options;
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $top = &drupal_static('ssc_parent_topic_top');
    $node = $this->getEntity($values);
    $tree = get_parent_tree($node);
    if (!empty($tree)) {
      // Load the content language for the page.
      $language_manager = \Drupal::languageManager();
      $langcode = $language_manager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
      get_parent_topic(end($tree[$langcode]));
      $url = Url::fromUri('internal:' . $top['crumb']['path']);;
      $link = Link::fromTextAndUrl($top['crumb']['title'], $url);

      return $link->toString();
    }
    else {
      return "No tree";
    }
  }

}
