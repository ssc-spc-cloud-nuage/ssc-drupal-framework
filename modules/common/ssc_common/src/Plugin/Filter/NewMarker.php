<?php

namespace Drupal\ssc_common\Plugin\Filter;

use DOMXPath;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to convert URLs into links.
 *
 * @Filter(
 *   id = "new_marker",
 *   title = @Translation("NEW marker"),
 *   description = @Translation("Add NEW marker to content based on data-wb-label attr."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class NewMarker extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult($this->addMarkers($text, $langcode, $this));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Adds a configurable marker to the content based on date.');
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['default_expiry'] = [
      '#type' => 'number',
      '#title' => t('Default expiry (in days)'),
      '#default_value' => 42,
      '#required' => TRUE,
    ];

    $form['default_marker_text'] = [
      '#type' => 'textfield',
      '#title' => t('Default marker text'),
      '#default_value' => 'New',
      '#required' => TRUE,
    ];

    $form['default_wrapper_classes'] = [
      '#type' => 'textfield',
      '#title' => t('Default wrapper classes'),
      '#default_value' => 'label label-default mrgn-rght-sm',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function addMarkers($text, $langcode, $filter) {
    $dom = Html::load($text);

    // Create an XPath object to query the DOM.
    $xpath = new DOMXPath($dom);
    $attr = 'data-wb-label';

    // Use XPath to query for elements with the specified attribute.
    $query = "//*[@$attr]";
    $elements = $xpath->query($query);

    if (!$elements) {
      return $text;
    }

    // Get config settings.
    $default_expiry = $this->settings['default_expiry'] ?? '42';
    $default_marker_text = $this->settings['default_marker_text'] ?? 'New';
    $default_wrapper_classes = $this->settings['default_wrapper_classes'];

    // Create marker pieces.
    $bracket1 = $dom->createElement('span');
    $bracket1->setAttribute('class', 'wb-inv');
    $bracket1_text = $dom->createTextNode('(');
    $bracket1->appendChild($bracket1_text);

    $bracket2 = $dom->createElement('span');
    $bracket2->setAttribute('class', 'wb-inv');
    $bracket2_text = $dom->createTextNode(')');
    $bracket2->appendChild($bracket2_text);

    $now = time();

    // Loop through the selected elements and add markers.
    foreach ($elements as $element) {
      $attr_value = $element->getAttribute($attr);
      $settings = json_decode($attr_value);

      // If no Start, assume today.
      $start = isset($settings->startDate)
        ? strtotime($settings->startDate)
        : strtotime(date('Y-m-d', $now));

      // If no End, assume Start + Default expiry
      $end = isset($settings->endDate)
        ? strtotime($settings->endDate)
        : strtotime(date('Y-m-d', $start) . ' +' . $default_expiry . ' days');

      // Determine if marker is active.
      if ($end < $now || $start >= $now) {
        continue;
      }

      // Assemble marker.
      $classes = $settings->class ?? $default_wrapper_classes;
      $marker = $dom->createElement('span');
      $marker->setAttribute('class', $classes);

      $marker_text = $settings->text ?? $default_marker_text;
      $text_node = $dom->createTextNode($marker_text);
      $marker->appendChild($bracket1);
      $marker->appendChild($text_node);
      $marker->appendChild($bracket2);

      // Place marker.
      $position = $settings->position ?? 'before';
      if ($position == 'before') {
        $element->insertBefore($marker, $element->firstChild);
      }
      else {
        $element->appendChild($marker);
      }

    }

    $result = HTML::serialize($dom);
    return $result;
  }

}
