<?php

/*
 * Trim text on sentence boundary up to a length.
 *
 * - used with _post_update to copy trimmed Body to Summary
 */

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

function trim_on_sentence_boundary($text, $tags = NULL, $length = NUll) {
  $split_sentences = '%(?#!php/i split_sentences Rev:20160820_1800)
    # Split sentences on whitespace between them.
    # See: http://stackoverflow.com/a/5844564/433790
    (?<=          # Sentence split location preceded by
      [.!?]       # either an end of sentence punct,
    | [.!?][\'"]  # or end of sentence punct and quote.
    )             # End positive lookbehind.
    (?<!          # But don\'t split after these:
      Mr\.        # Either "Mr."
    | Mrs\.       # Or "Mrs."
    | Ms\.        # Or "Ms."
    | Jr\.        # Or "Jr."
    | Dr\.        # Or "Dr."
    | Prof\.      # Or "Prof."
    | Sr\.        # Or "Sr."
    | T\.V\.A\.   # Or "T.V.A."
                 # Or... (you get the idea).
    )             # End negative lookbehind.
    \s+           # Split on whitespace between sentences,
    (?=\S)        # (but not at end of string).
    %xi';

  // Strip HTML
  $text = strip_tags($text, $tags);

  // Split into sentences.
  $sentences = preg_split($split_sentences, $text, -1, PREG_SPLIT_NO_EMPTY);

  // Add sentences until length is exceeded.
  $output = '';
  foreach ($sentences as $sentence) {
    if (strlen($output . ' ' . $sentence) > $length) {
      break;
    }
    $output .= ' ' . $sentence;
  }

  $output = preg_replace('^\n\s+|\n^', "</p><p>", $output);

  return '<p>' . trim($output) . '</p>';
}

/*
 * Recursively build up Parent/Child Tree of a node (Topic/Structured/Guide).
 *
 * @param Drupal\node\Entity\Node $node
 */
function get_parent_tree(Node $node) {
  $tree = &drupal_static('ssc_parent_tree');
  if (empty($tree)) {
    $tree = ['en' => [], 'fr' => []];
  }

  // If no Parent Page field
  if (!$node->hasField('field_parent_page')) {
    \Drupal::messenger()->addError(t('Breadcrumb set to use Parent Page but this bundle has no Parent Page field.'));
    return NULL;
  }

  // Required, but just in case Parent not set.
  // NEW (0824): not required on LPs which now set bcrumbs off Parent. But will only be
  //  an issue for the Home page but easier to hide for all LPS.
  if (!$node->field_parent_page->getString()) {
    if ($node->bundle() != 'landing_page') {
      \Drupal::messenger()
        ->addError(t('Breadcrumb set to use Parent Page but value not set.'));
    }
    return NULL;
  }

  // Key by current Content language
  $language_manager = \Drupal::languageManager();
  $lang = $language_manager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

  // Is this for En of Fr?
  if ($lang == 'fr' && $node->hasTranslation('fr')) {
    $node = $node->getTranslation('fr');
  }

  // Note: could use pathauto api to clean: https://www.drupal.org/project/pathauto/issues/3271465
  // but not really required as this is just to create a token for Pathauto - which will clean anyway.
  $path = $node->getTitle();
  $pid = $node->field_parent_page->target_id;
  $nid = $node->id();
  $branch = [
    'pid' => $pid,
    'path' => $path,
    'type' => $node->getType(),
    'crumb' => [
      'path' => \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $nid),
      'title' => $node->getTitle(),
    ]
  ];
  $tree[$lang][$nid] = $branch;

  $home_id = get_landing_page_nid('home');
  if ($pid != $home_id) {
    $parent = Node::load($pid);
    // This should never happen as Parent is a required field, but.
    if (empty($parent)) {
      $url = Url::fromUri('internal:/node/' . $node->id());
      \Drupal::logger('SSC')->error(t('Page has missing parent: %parent', [
        '%parent' => $url->toString(),
      ]));
      return NULL;
    }
    get_parent_tree($parent);
  }
  return $tree;
}

function get_landing_page_nid($type) {
  $landing_pages = get_landing_pages();
  return $landing_pages[$type];
}

function get_landing_pages() {
  $landing_pages = &drupal_static(__FUNCTION__);
  $cid = 'ssc_custom:und';

  if ($cache = \Drupal::cache()->get($cid)) {
    $landing_pages = $cache->data;
  }
  else {
    $view = \Drupal\views\Views::getView('landing_pages');
    $view->setDisplay('default');
    $view->execute();

    if (!empty($view->result)) {
      foreach ($view->result as $rid => $row) {
        $child_type = $row->_entity->field_child_type->target_id;
        if (!empty($child_type)) {
          // Even though we have a TID returned by the View, the Term maybe have been removed - which results in a WSOD here
          $bundle_term = Term::load($child_type);
          if (!$bundle_term) {
            $message = t('Landing page News Type term has been deleted: %lp', [
              '%lp' => $row->_entity->getTitle(),
            ]);
            \Drupal::messenger()->addError($message);
            \Drupal::logger('SSC')->error($message);
            continue;
          }
          $bundle = Term::load($child_type)->getName();
          if (!empty($bundle)) {
            $values[$bundle] = $row->nid;
          }
        }
      }
      $landing_pages = $values;
      \Drupal::cache()->set($cid, $landing_pages);
    }
  }

  return $landing_pages;
}

function get_parent_topic($branch) {
  $tree = &drupal_static('ssc_parent_tree');
  $top = &drupal_static('ssc_parent_topic_top');
  $home_id = get_landing_page_nid('home');
  $language_manager = \Drupal::languageManager();
  $lang = $language_manager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
  if ($branch['pid'] == $home_id) {
    $top = $branch;
    return NULL;
  }
  else {
    get_parent_topic($tree[$lang][$branch['pid']]);
  }
}

/**
*  Replacement for file_get_contents() asa this doesn't work with SSL sites like Test/Dev/Prod
*/
function file_get_contents_ssl($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($ch, CURLOPT_HEADER, false);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_REFERER, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3000); // 3 sec.
  curl_setopt($ch, CURLOPT_TIMEOUT, 10000); // 10 sec.
  $result = curl_exec($ch);
  curl_close($ch);
  return $result;
}

function get_active_config() {
  $environments = ['local', 'dev', 'test', 'prod', 'sand'];
  foreach ($environments as $environment) {
    $config_split_config = \Drupal::config('config_split.config_split.' . $environment);
    $status = $config_split_config->get('status');
    if ($status) {
      return $environment;
    }
  }
  return 'other';
}

function get_content_language_code() {
  $language_manager = \Drupal::languageManager();
  return $language_manager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
}

function limit_text_words($text, $limit) {
  if (str_word_count($text, 0) > $limit) {
    $words = str_word_count($text, 2);
    $pos   = array_keys($words);
    $text  = substr($text, 0, $pos[$limit]);
  }
  return $text;
}

function get_tid_by_name($name = NULL, $vid = NULL) {
  $properties = [];
  if (!empty($name)) {
    $properties['name'] = $name;
  }
  if (!empty($vid)) {
    $properties['vid'] = $vid;
  }
  $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties($properties);
  $term = reset($terms);

  return !empty($term) ? $term->id() : 0;
}
