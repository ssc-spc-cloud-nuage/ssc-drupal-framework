<?php

namespace Drupal\ssc_common\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RelatedSearchAutocomplete {
  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request) {
    $langcode =  \Drupal::languageManager()->getCurrentLanguage()->getId();
    $results = [];
    $input = $request->query->get('q');
    if (!$input) {
      return new JsonResponse($results);
    }
    $input = Xss::filter($input);
    $query = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', 'topics')
      ->condition('name', $input, 'CONTAINS')
      ->groupBy('tid')
      ->sort('name', 'ASC')
      ->range(0, 10);
    $ids = $query->execute();
    $terms = $ids ? Term::loadMultiple($ids) : [];
    foreach ($terms as $term) {
      if ($term->hasTranslation($langcode)) {
        $translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($term, $langcode);
        $results[] = [
          'value' => $translated_term->getName(),
          'label' => $translated_term->getName(),
        ];
      }
    }
    return new JsonResponse($results);
  }

}
