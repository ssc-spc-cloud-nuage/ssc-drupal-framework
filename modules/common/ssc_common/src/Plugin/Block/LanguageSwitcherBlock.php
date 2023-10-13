<?php

namespace Drupal\ssc_common\Plugin\Block;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\wxt_library\Plugin\Block\LanguageBlock;

/**
 * Provides a 'LanguageSwitcherBlock' block.
 *
 * @Block(
 *  id = "language_switcher_block",
 *  admin_label = @Translation("Language switcher"),
 * )
 */
class LanguageSwitcherBlock extends LanguageBlock {

  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['#theme'] = 'language_switcher_block';
    $build['#attached']['library'][] = 'ssc_common/header-link';

    $links = $this->getSwitcher();
    $build['#content']['full_switcher'] = $links['full'];
    $build['#content']['narrow_switcher'] = $links['narrow'];

    return $build;
  }

  private function getSwitcher(): array {
    $current = $this->urlGenerator->generateFromRoute('<current>', [], [], TRUE)->getGeneratedUrl();
    $front = $this->aliasManager->getPathByAlias($current);
    $frontAlias = $this->configFactory->get('system.site')->get('page.front');
    $route_match = \Drupal::routeMatch();
    // If there is no route match, for example when creating blocks on 404 pages
    // for logged-in users with big_pipe enabled using the front page instead.
    $url = $route_match->getRouteObject() ? Url::fromRouteMatch($route_match) : Url::fromRoute('<front>');

    $path_elements = explode('/', trim($front, '/'));
    foreach ($this->languageManager->getLanguages() as $language) {
      if (!empty($path_elements[0]) && $path_elements[0] == $language->getId()) {
        array_shift($path_elements);
        if (implode($path_elements) == trim($frontAlias, '/')) {
          $url = Url::fromRoute('<front>');
        }
      }
    }

    $language_types = $this->languageManager->getLanguageTypes();
    $type = in_array(LanguageInterface::TYPE_CONTENT, $language_types) ? LanguageInterface::TYPE_CONTENT : LanguageInterface::TYPE_INTERFACE;
    $language = $this->languageManager->getCurrentLanguage($type)->getId();
    // This may have used to return EN if there was no FR; but now return NULL which will cause an issue with setOption
    $links = $this->languageManager->getLanguageSwitchLinks($type, $url);
    $switch = $language == 'en' ? 'fr' : 'en';
    // If there is no switch version then use original language.
    if (!isset($links->links[$switch])) {
      $label = $switch == 'fr' ? 'French' : 'English';
      $title = $this->t($label, [], ['langcode' => $switch]);
      // Link to 404.
      $site_config = $this->configFactory->get('system.site');
      $page_404_url = $site_config->get('page.404');
      $links->links[$switch]['url'] = Url::fromUri('internal:' . $page_404_url);
    }
    else {
      $title = Markup::create($links->links[$switch]['title']);

      // If Search page, change Related Topic to FR version.
      if (stristr($url->getRouteName(), 'page_manager.page_view_search_search-layout_builder')) {
        if (isset($links->links[$switch]['query']['r'])) {
          $query = \Drupal::entityQuery('taxonomy_term')
            ->condition('vid', 'topics')
            ->condition('name', $links->links[$switch]['query']['r'], '=');
           $ids = $query->execute();
           $tid = current($ids);
           $term = Term::load($tid);
            if($term->hasTranslation($switch)) {
              $translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($term, $switch);
              $links->links[$switch]['query']['r'] = $translated_term->getName();
            }
        }
      }

      // Add language to the links.
      $links->links[$switch]['url']->setOption('query', $links->links[$switch]['query']);
    }
    $links->links[$switch]['url']->setOption('language', $this->languageManager->getLanguage($switch));

    $output = [];
    $output['narrow'] = [
      '#type' => 'html_tag',
      '#tag' => 'abbr',
      '#attributes' => [
        'title' => $title
      ],
      'child' => [
        '#type' => 'link',
        '#url' => $links->links[$switch]['url'],
        '#title' => substr($title, 0, 2),
        '#attributes' => [
          'lang' => $switch,
          'class' => ['btn', 'header-link', 'header-link--icon']
        ]
      ]
    ];

    $output['full'] = [
      '#type' => 'link',
      '#url' => $links->links[$switch]['url'],
      '#title' => $title,
      '#attributes' => [
        'lang' => $switch,
        'hreflang' => $switch,
        'class' => ['btn', 'header-link', 'header-link--text']
      ]
    ];

    return $output;
  }

}
