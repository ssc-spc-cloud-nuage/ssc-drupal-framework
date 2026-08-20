<?php

namespace Drupal\ssc_common\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders a field's value from a specific translation of its entity.
 *
 * Useful for pages that intentionally show more than one language's
 * content at once.
 *
 * @FieldFormatter(
 *   id = "ssc_translation_language",
 *   label = @Translation("Value from a specific translation"),
 *   field_types = {
 *     "string",
 *     "string_long",
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   },
 * )
 */
class TranslationLanguageFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a TranslationLanguageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, LanguageManagerInterface $language_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'language_mode' => 'opposite',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['language_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Language to display'),
      '#options' => $this->languageModeOptions(),
      '#default_value' => $this->getSetting('language_mode'),
      '#description' => $this->t('Which translation of the entity to pull this value from, regardless of the language the entity is currently being rendered in. "Opposite" assumes a bilingual English/French site.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $options = $this->languageModeOptions();
    $mode = $this->getSetting('language_mode');
    return [
      $this->t('Language: @label', ['@label' => $options[$mode] ?? $mode]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $entity = $items->getEntity();

    if (!$entity instanceof ContentEntityInterface || !$entity->isTranslatable()) {
      return $elements;
    }

    $target_langcode = $this->getTargetLangcode();
    if (!$entity->hasTranslation($target_langcode)) {
      return $elements;
    }

    $translation = $entity->getTranslation($target_langcode);
    $field_name = $this->fieldDefinition->getName();
    if (!$translation->hasField($field_name)) {
      return $elements;
    }

    $translated_items = $translation->get($field_name);
    $field_type = $this->fieldDefinition->getType();
    $is_formatted_text = in_array($field_type, ['text', 'text_long', 'text_with_summary'], TRUE);

    $cacheability = CacheableMetadata::createFromObject($translation);
    $cacheability->addCacheContexts(['languages:' . LanguageInterface::TYPE_INTERFACE]);

    foreach ($translated_items as $delta => $item) {
      if ($item->isEmpty()) {
        continue;
      }
      if ($is_formatted_text) {
        $elements[$delta] = [
          '#type' => 'processed_text',
          '#text' => $item->value,
          '#format' => $item->format,
          '#langcode' => $target_langcode,
        ];
      }
      else {
        $elements[$delta] = [
          '#plain_text' => $item->value,
        ];
      }
      $cacheability->applyTo($elements[$delta]);
    }

    return $elements;
  }

  /**
   * The selectable "which translation" options.
   *
   * @return array
   *   Option labels keyed by setting value.
   */
  protected function languageModeOptions() {
    return [
      'en' => $this->t('English'),
      'fr' => $this->t('French'),
      'opposite' => $this->t('Opposite of the page content language'),
    ];
  }

  /**
   * Determines the target langcode based on the configured mode.
   *
   * @return string
   *   The langcode whose translation should be rendered.
   */
  protected function getTargetLangcode() {
    $mode = $this->getSetting('language_mode');
    if ($mode === 'en' || $mode === 'fr') {
      return $mode;
    }
    // "Opposite" mode assumes a bilingual (en/fr) site, matching SSC's
    // setup: show whichever of en/fr is NOT the current interface
    // language.
    $current = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)->getId();
    return $current === 'fr' ? 'en' : 'fr';
  }

}
