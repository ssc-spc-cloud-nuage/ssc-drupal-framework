<?php

namespace Drupal\ssc_common\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Field\Annotation\FieldFormatter;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter;
use Drupal\node\Entity\Node;

/**
 * Plugin implementation of the 'entity reference rendered entity' formatter.
 *
 * Modified for Topics pages to sort based on custom field for either Delta (default)
 * or Alpha sort
 *
 * @FieldFormatter(
 *   id = "sorted_rendered_entity_formatter",
 *   label = @Translation("Sorted rendered entity"),
 *   description = @Translation("Display the referenced entities rendered by entity_view() with SSC custom sort."),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class SortedRenderedEntityFormatter extends EntityReferenceRevisionsEntityFormatter {
  /**
   * Returns the referenced entities for display.
   *
   * The method takes care of:
   * - checking entity access,
   * - placing the entities in the language expected for display.
   * It is thus strongly recommended that formatters use it in their
   * implementation of viewElements($items) rather than dealing with $items
   * directly.
   *
   * For each entity, the EntityReferenceItem by which the entity is referenced
   * is available in $entity->_referringItem. This is useful for field types
   * that store additional values next to the reference itself.
   *
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The item list.
   * @param string $langcode
   *   The language code of the referenced entities to display.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The array of referenced entities to display, keyed by delta.
   *
   * @see ::prepareView()
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    // If no items; nothing to sort.
    if (!count($items)) {
      return [];
    }

    $entities = [];
    $parent = $items[0]->getParent()->getParent()->getEntity();
    $sort = isset($parent->field_sort_order->value) ? $parent->field_sort_order->value : 0;

    foreach ($items as $delta => $item) {
      // Ignore items where no entity could be loaded in prepareView().
      if (!empty($item->_loaded)) {
        $entity = $item->entity;

        // Set the entity in the correct language for display.
        if ($entity instanceof TranslatableInterface) {
          $entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $langcode);
        }

        $access = $this->checkAccess($entity);
        // Add the access result's cacheability, ::view() needs it.
        $item->_accessCacheability = CacheableMetadata::createFromObject($access);
        if ($access->isAllowed()) {
          // Add the referring item, in case the formatter needs it.
          $entity->_referringItem = $items[$delta];
          $entities[$delta] = $entity;

          // Add in Titles if doing Alpha sort.
          if ($sort) {
            if ($entity->hasField('field_internal_page')) {
              $internal_node = Node::load($entity->field_internal_page->target_id);
              if ($internal_node->hasTranslation($langcode)) {
                $internal_node = $internal_node->getTranslation($langcode);
              }
              $entities[$delta]->sort_title = strtolower($internal_node->getTitle());
            }
            else {
              $entities[$delta]->sort_title = strtolower($entity->field_title->value);
            }
          }
        }
      }
    }

    if ($sort) {
      // Alpha sort by title
      usort($entities, fn($a, $b) => strcmp($a->sort_title, $b->sort_title));
    }

    return $entities;
  }

}
