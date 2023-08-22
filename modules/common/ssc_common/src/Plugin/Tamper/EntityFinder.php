<?php

namespace Drupal\ssc_common\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Plugin implementation of the EQF plugin.
 *
 * @Tamper(
 *   id = "entity_finder",
 *   label = @Translation("Entity Finder"),
 *   description = @Translation("Finds an Entity based on properties and fields.  Returns the ID of the entity"),
 *   category = "Other",
 *   handle_multiples = TRUE
 * )
 */
class EntityFinder extends TamperBase {

  const SETTING_ENTITY_TYPE = 'entity_type';
  const SETTING_BUNDLE = 'bundle';
  const SETTING_FIELD = 'field';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config[self::SETTING_ENTITY_TYPE] = '';
    $config[self::SETTING_BUNDLE] = '';
    $config[self::SETTING_FIELD] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['#prefix'] = '<div id="feeds-tamper-entity-finder-wrapper">';
    $form['#suffix'] = '</div>';

    // $form_state->setCached(FALSE);

    // Gets the button that triggers the ajax call
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element) {
      // Retrieve the parents, so we can climb back up the tree and do not have to hard code the unknown position of our subform
      $parents = array_slice($triggering_element['#array_parents'], 0, -1);
      if($parents) {
        $all_values = $form_state->getCompleteFormState()->getValues();
        $values = NestedArray::getValue($all_values, $parents);
      }
    }

    $entityTypes = $this->getEntityTypes();

    $form[self::SETTING_ENTITY_TYPE] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type'),
      '#options' => $entityTypes,
      '#default_value' => $this->getSetting(self::SETTING_ENTITY_TYPE),
      '#ajax' => array(
         'callback' => [$this,'changeSelect'],
         'event' => 'change',
         'wrapper' => 'feeds-tamper-entity-finder-wrapper',
      ),
      '#required' => TRUE,
      '#empty_option' => t('-- Select --'),
    ];

    // Set EntityType to the value in the config settings, or populate it with the form_state (from ajax)
    $entityType = (isset($values) && $values[self::SETTING_ENTITY_TYPE]) ? $values[self::SETTING_ENTITY_TYPE] : $this->getSetting(self::SETTING_ENTITY_TYPE);
    $bundles =  $this->getBundles($entityType);

    $form[self::SETTING_BUNDLE] = array(
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#options' => $bundles,
      '#default_value' => $this->getSetting(self::SETTING_BUNDLE),
      '#ajax' => array(
          'callback' => [$this,'changeSelect'],
          'event' => 'change',
          'wrapper' => 'feeds-tamper-entity-finder-wrapper',
      ),
      '#empty_option' => t('-- Select --'),
    );

    // Set bundle to the value in the config settings, or populate it with the form_state (from ajax)
    $bundle = (isset($values) && $values[self::SETTING_BUNDLE]) ? $values[self::SETTING_BUNDLE] : $this->getSetting(self::SETTING_BUNDLE);

    // Gather field definitions.
    $fields = $this->getFields($entityType, $bundle);

    $form[self::SETTING_FIELD] = array(
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#options' => $fields,
      '#default_value' => $this->getSetting(self::SETTING_FIELD),
      '#empty_option' => t('-- Select --'),
    );

    return $form;
  }

  /**
   * Ajax callback for form changes.
   */
  public function changeSelect(array &$form, FormStateInterface $form_state) {
    // Gets the button that triggers the ajax call
    $triggering_element = $form_state->getTriggeringElement();
    // Retrieve the parents, so we can climb back up the tree and do not have to hard code the unknown position of our subform
    $parents = array_slice($triggering_element['#array_parents'], 0, -1);

    // Use NestedArray to get the element in `$form` at the path that `$parents` describes.
    return NestedArray::getValue($form, $parents);
  }

  /**
   * Get all Entity types.
   *
   * @return array
   *   A list of Entity Types.
   */
  protected function getEntityTypes() {
    // Get some info on entity types.
    $entity_types = array();
    $definitions = \Drupal::entityTypeManager()->getDefinitions();
    foreach ($definitions as $machine_name => $info) {
      $entity_types[$machine_name] = check_markup($info->getLabel());
    }
    return $entity_types;
  }

  /**
   * Get the bundles for an entity type
   *
   * @return array
   *   A list of Bundles
   */
  protected function getBundles($entity_type) {
    if ($entity_type) {
      $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo($entity_type);
      foreach ($bundle_info as $machine_name => $info) {
        $bundles[$machine_name] = check_markup($info['label']);
      }
      return $bundles;
    } else {
      return array();
    }
  }


  /**
   * Get the fields for an entity type and bundle
   *
   * @return array
   *   A list of Bundles
   */
  protected function getfields($entity_type, $bundle) {
    if ($entity_type && $bundle) {
      $field_info = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity_type, $bundle);
      foreach ($field_info as $field_name => $field_definition) {
        $fields[$field_name] = check_markup($field_definition->getLabel());
      }
      return $fields;
    } else {
      return array();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Test the regex.
    // $test = @preg_replace($form_state->getValue(self::SETTING_ENTITY_TYPE), '', 'asdfsadf');
    // if ($test === NULL) {
    //   $form_state->setErrorByName(self::SETTING_FIND, $this->t('Invalid regular expression.'));
    // }
  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      self::SETTING_ENTITY_TYPE => $form_state->getValue(self::SETTING_ENTITY_TYPE),
      self::SETTING_BUNDLE => $form_state->getValue(self::SETTING_BUNDLE),
      self::SETTING_FIELD => $form_state->getValue(self::SETTING_FIELD),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    $entityType = $this->getSetting(self::SETTING_ENTITY_TYPE);
    $bundle = $this->getSetting(self::SETTING_BUNDLE);
    $field = $this->getSetting(self::SETTING_FIELD);
    if ($field === 'uuid') {
      if (NULL !== ($entity = \Drupal::service('entity.repository')->loadEntityByUuid($entityType, $data))) {
        return $entity->id();
      }
    }
    else {
      //$query = $this->queryFactory->get($entityType);
      $query = \Drupal::entityTypeManager()->getStorage($entityType)->getQuery();

      // limit search by bundle if there is a bundleKey (user has none)
      if ($bundle && $type = $this->getBundleKey($entityType)) {
          $query->condition($type, $bundle, '=');
      }

      $ids = array_filter($query->condition($field, $data)->range(0, 1)->execute());
      if ($ids) {
        return reset($ids);
      }
    }
  }

  /**
   * Returns the entity type's bundle key.
   *
   * @return string
   *   The bundle key of the entity type.
   */
  protected function getBundleKey($entity) {
    return \Drupal::entityTypeManager()->getDefinition($entity)->getKey('bundle');
  }

}
