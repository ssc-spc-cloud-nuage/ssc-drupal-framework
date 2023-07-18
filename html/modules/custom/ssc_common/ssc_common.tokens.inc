<?php

use Drupal\Core\Language\LanguageInterface;

/**
 * Implements hook_token_info().
 */
function ssc_common_token_info() {
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
function  ssc_common_tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];
  switch ($type) {
    case 'node':
      if (empty($data['node'])) return;
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

    case 'site':
      foreach ($tokens as $name => $original) {
        // Find the desired token by name.
        if (stristr($name, 'landing_page-nid')) {
          $landing_pages = get_landing_pages();
          $bundle = str_replace('-landing_page-nid', '', $name);
          $replacements[$original] = isset($landing_pages[$bundle]) ? $landing_pages[$bundle] : NULL;
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
        $replacements += $token_service->generate($parent->getEntityTypeId(), $parent_entity_tokens, [$parent->getEntityTypeId() => $parent], $options, $bubbleable_metadata);
      }
      break;

    case 'current-page':
      foreach ($tokens as $name => $original) {
        if (stristr($name, 'referer')) {
          $replacements[$original] = $_SERVER["HTTP_REFERER"];
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
function _get_pathauto_tree($node) {
  $language_manager = \Drupal::languageManager();
  $lang = $language_manager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $tree = &drupal_static('ssc_parent_tree');
  get_parent_tree($node);
  $count = count($tree[$lang]);
  $x = 1;
  $guide = FALSE;
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

function _get_breadcrumb_paths($node) {
  $language_manager = \Drupal::languageManager();
  $lang = $language_manager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $tree = &drupal_static('ssc_parent_tree');
  get_parent_tree($node);
  foreach (array_reverse($tree[$lang]) as $branch) {
    $paths[]= $branch['crumb']['path'];
  }
  array_pop($paths);
  return implode('::', $paths);
}

function _get_breadcrumb_titles($node) {
  $language_manager = \Drupal::languageManager();
  $lang = $language_manager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  $tree = &drupal_static('ssc_parent_tree');
  get_parent_tree($node);

  foreach (array_reverse($tree[$lang]) as $branch) {
    $titles[]= $branch['crumb']['title'];
  }
  array_pop($titles);
  return implode('::', $titles);
}
