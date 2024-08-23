<?php

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\Entity\Node;

/**
 * Implements hook_token_info_alter().
 *
 * Add _fr tokens for Message field Entity Reference fields.
 */
function ssc_common_token_info_alter(&$info): void {
  $fields = _get_message_entity_ref_fields();
  if (!empty($fields)) {
    foreach ($fields as $field) {
      $info['tokens']['message'][$field . '_fr'] = [
        'name' => "FR tokens for Entity Reference: $field",
        'description' => "Provides tokens for FR field values of the field: $field",
      ];
    }
  }
}

/**
 * Implements hook_token_info().
 */
function ssc_common_token_info(): array {
  $info = [];
  $info['tokens']['node']['slug_or_title'] = [
    'name' => t('Slug or Title'),
    'description' =>  t('Value from path_slug field if available or node title.'),
  ];

  // Add dynamic LP NID tokens for use in Custom Breadcrumbs and elsewhere.
  $landing_pages = get_landing_pages();
  foreach ($landing_pages as $bundle => $nid) {
    $bundle_label = ucwords(str_replace('_', ' ', $bundle));
    $info['tokens']['site'][$bundle . '-landing_page-nid'] = [
      'name' => t($bundle_label . ' Landing Page NID'),
      'description' => t('The NID of the ' . $bundle_label . ' Landing Page.'),
    ];
  }

  // Dynamic TREE tokens for Custom Breadcrumbs and Pathauto
  $info['tokens']['node']['tree_pathauto_path'] = [
    'name' => t('Tree path for pathauto'),
    'description' =>  t('Full path to use in pathauto.'),
  ];
  $info['tokens']['node']['tree_breadcrumb_paths'] = [
    'name' => t('Tree paths for breadcrumb'),
    'description' =>  t('Paths array for Custom Breadcrumbs.'),
  ];
  $info['tokens']['node']['tree_breadcrumb_titles'] = [
    'name' => t('Tree titles for breadcrumbs'),
    'description' =>  t('Titles array for use in Custom Breadcrumbs.'),
  ];

  // Add missing Paragraph "parent" tokens: https://drupal.stackexchange.com/questions/254406/how-to-get-token-of-parent-entity-in-paragraph
  $info['tokens']['paragraph']['parent'] = [
    'name' => 'Parent',
    'description' => 'Parent entity of the paragraph',
  ];

  // Add HTTP REFERER token.
  $info['tokens']['current-page']['referer'] = [
    'name' => t('Referring page'),
    'description' =>  t('Previous page (HTTP REFERER) absolute URL.'),
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function  ssc_common_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array|string {
  $replacements = [];
  switch ($type) {
    case 'node':
      if (empty($data['node'])) {
        return [];
      }
      $node = $data['node'];
      foreach ($tokens as $name => $original) {
        // Find the desired token by name.
        switch ($name) {
          case 'slug_or_title':
            if (!isset($node->field_path_slug) || $node->field_path_slug->isEmpty()) {
              $value = $node->getTitle();
            }
            else {
              $value = $node->field_path_slug->value;
            }
            $replacements[$original] = $value;
            break;

          case 'tree_pathauto_path':
            if (!$node->hasField('field_parent_page')) return '';
            $replacements[$original] = _get_pathauto_tree($node);
            break;

          case 'tree_breadcrumb_paths':
            if (!$node->hasField('field_parent_page')) return '';
            $replacements[$original] = _get_breadcrumb_paths($node);
            break;

          case 'tree_breadcrumb_titles':
            if (!$node->hasField('field_parent_page')) return '';
            $replacements[$original] = _get_breadcrumb_titles($node);
            break;
        }
      }
      break;

    case 'site':
      foreach ($tokens as $name => $original) {
        // Find the desired token by name.
        if (stristr($name, 'landing_page-nid')) {
          $landing_pages = get_landing_pages();
          $bundle = str_replace('-landing_page-nid', '', $name);
          $replacements[$original] = $landing_pages[$bundle] ?? NULL;
        }
      }
      break;

    case 'paragraph':
      $paragraph = $data['paragraph'];
      $parent = $paragraph->getParentEntity();
      $bubbleable_metadata->addCacheableDependency($parent);

      foreach ($tokens as $name => $original) {
        if ($name == 'parent') {
          $replacements[$original] = $parent->label();
        }
      }

      $token_service = \Drupal::token();
      if ($parent_entity_tokens = $token_service->findWithPrefix($tokens, 'parent')) {
        $replacements += $token_service->generate($parent->getEntityTypeId(), $parent_entity_tokens,
          [$parent->getEntityTypeId() => $parent], $options, $bubbleable_metadata);
      }
      break;

    case 'current-page':
      foreach ($tokens as $name => $original) {
        if (stristr($name, 'referer')) {
          $replacements[$original] = $_SERVER["HTTP_REFERER"];
        }
      }
      break;

    case 'message':
      if (empty($data['message'])) {
        return [];
      }

      /** @var \Drupal\message\Entity\Message $message */
      $message = $data['message'];$token_service = \Drupal::token();
      $fields = _get_message_entity_ref_fields();
      if (!empty($fields)) {
        foreach ($fields as $field) {
          if ($shared_entity_tokens = $token_service->findWithPrefix($tokens, $field . '_fr')) {
            if (empty($message->{$field}->target_id)) {
              return [];
            }
            $node = Node::load($message->{$field}->target_id);
            if (empty($node) || !$node->hasTranslation('fr')) {
              return [];
            }
            $node_fr = $node->getTranslation('fr');
            $bubbleable_metadata->addCacheableDependency($node_fr);

            // Add sub-tokens.
            $options['langcode'] = 'fr';
            $replacements += $token_service->generate($node_fr->getEntityTypeId(), $shared_entity_tokens,
              [$node->getEntityTypeId() => $node_fr], $options, $bubbleable_metadata);

            // Replacement for parent token with no sub-token listed. Same as :title.
            foreach ($tokens as $name => $original) {
              if ($name == $field . '_fr') {
                $replacements[$original] = $node_fr->label();
              }
            }
          }
        }
      }
      break;
  }

  return $replacements;
}

/*
 * Use get_parent_tree() to build Pathauto token for Topic, Guide and Structured pages.
 *
 * - only include first and last item as well as furthest (from Home) Guide page if it exists.
 */
function _get_pathauto_tree($node): string {
  $language_manager = \Drupal::languageManager();
  $lang = $language_manager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $tree = &drupal_static('ssc_parent_tree');
  get_parent_tree($node);
  $count = count($tree[$lang]);
  $x = 1;
  $guide = FALSE;
  $paths = [];
  foreach ($tree[$lang] as $branch) {
    if ($x == 1 || $x == $count) {
      $paths[] = $branch['path'];
    }
    if (!$guide && $branch['type'] == 'guide' && $x != 1 && $x != $count) {
      $paths[] = $branch['path'];
      $guide = TRUE;
    }
    $x++;
  }
  return implode('/', array_reverse($paths));
}

function _get_breadcrumb_paths($node): string {
  $language_manager = \Drupal::languageManager();
  $lang = $language_manager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $tree = &drupal_static('ssc_parent_tree');
  get_parent_tree($node);
  $paths = [];
  foreach (array_reverse($tree[$lang]) as $branch) {
    $paths[]= $branch['crumb']['path'];
  }
  array_pop($paths);
  return implode('::', $paths);
}

function _get_breadcrumb_titles($node): string {
  $language_manager = \Drupal::languageManager();
  $lang = $language_manager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $tree = &drupal_static('ssc_parent_tree');
  get_parent_tree($node);
  $titles = [];
  foreach (array_reverse($tree[$lang]) as $branch) {
    $titles[]= $branch['crumb']['title'];
  }
  array_pop($titles);
  return implode('::', $titles);
}

/**
 * Returns a list of all Entity Reference fields used in Message Bundles.
 *
 * @return array
 */
function _get_message_entity_ref_fields(): array {
  $bundle_info = \Drupal::service('entity_type.bundle.info');
  $field_manager = \Drupal::service('entity_field.manager');

  // Get the bundles for the 'message' entity type.
  $message_bundles = $bundle_info->getBundleInfo('message');

  // Iterate over each bundle to get fields.
  $results = [];
  foreach ($message_bundles as $bundle_id => $bundle_data) {
    $fields = $field_manager->getFieldDefinitions('message', $bundle_id);
    foreach ($fields as $field_name => $field_definition) {
      if (!stristr($field_name, 'field_')) continue;
      if ($field_definition instanceof FieldDefinitionInterface && $field_definition->getType() === 'entity_reference') {
        // Output field info
        $results[] = $field_name;
      }
    }
  }

  return $results;
}
