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
 *   settings = {
 *     "default_duration" = "42",
 *     "default_marker_text" = @Translation("New"),
 *     "default_wrapper_classes" = "label label-default mrgn-rght-sm"
 *   },
 * )
 * @see \Drupal\filter\Annotation\Filter
 * @see \Drupal\filter\Plugin\FilterInterface
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
    return $this->t('Adds a configurable marker to the content based on date. Supports the following inline
      settings:
      <ul>
        <li>startDate</li>
        <li>endDate</li>
        <li>duration<sup>*</sup></li>
        <li>text<sup>*</sup></li>
        <li>class<sup>*</sup></li>
      </ul>
      Items marked with a * have default settings.');
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['default_duration'] = [
      '#type' => 'number',
      '#title' => t('Default expiry (in days)'),
      '#default_value' => $this->settings['default_duration'] ?? 42,
      '#required' => TRUE,
    ];

    $form['default_marker_text_en'] = [
      '#type' => 'textfield',
      '#title' => t('Default marker text (EN)'),
      '#default_value' => $this->settings['default_marker_text_en'] ?? 'New',
      '#required' => TRUE,
    ];

    $form['default_marker_text_fr'] = [
      '#type' => 'textfield',
      '#title' => t('Default marker text (FR)'),
      '#default_value' => $this->settings['default_marker_text_fr'] ?? 'Nouveau',
      '#required' => TRUE,
    ];

    $form['default_wrapper_classes'] = [
      '#type' => 'textfield',
      '#title' => t('Default wrapper classes'),
      '#default_value' => $this->settings['default_wrapper_classes'] ?? 'label label-default mrgn-rght-sm',
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
    $default_duration = $this->settings['default_duration'] ?? '42';
    $marker_text_setting = 'default_marker_text_' . $langcode;
    $default_marker_text = $this->settings[$marker_text_setting] ?? t('New');
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

      // Priority: inline endDate, inline duration, global durationIf no End, assume Start + Default duration
      $end = isset($settings->endDate)
        ? strtotime($settings->endDate)
        : (
          isset($settings->duration)
          ? strtotime(date('Y-m-d', $start) . ' +' . $settings->duration . ' days')
          : strtotime(date('Y-m-d', $start) . ' +' . $default_duration . ' days')
        );

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
