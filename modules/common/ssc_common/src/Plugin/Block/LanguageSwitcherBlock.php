<?php

namespace Drupal\ssc_common\Plugin\Block;

use Drupal\Component\Utility\Xss;
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

  /**
   * Generates the language switcher links.
   *
   * @return array
   *   An array containing the full and narrow switcher links.
   */
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

    // Modify the links based on your custom logic.
    if ($route_match->getRouteName() === 'group.sg_reports' && isset($links->links[$switch])) {
      $group = $route_match->getParameter('group');
      $group_other = $group->getTranslation($switch);
      $group_url = $group ? $group_other->toUrl()->toString() : NULL;
      $report_path = $route_match->getParameter('pbi_report_path');
      $translated_path = $this->getTranslatedPath($report_path, $switch);
      $links->links[$switch]['url'] = Url::fromUri("internal:$group_url" . ($switch == 'fr' ? '/rapports' : '/reports') . "/{$translated_path}");
    }

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
        if (!empty($links->links[$switch]['query']['r'])) {
          $query = \Drupal::entityQuery('taxonomy_term')
            ->condition('vid', 'topics')
            ->condition('name', $links->links[$switch]['query']['r'], '=')
            ->accessCheck(FALSE);
           $ids = $query->execute();
           $tid = current($ids);
           $term = Term::load($tid);
           if ($term && $term->hasTranslation($switch)) {
              $translated_term = \Drupal::service('entity.repository')->getTranslationFromContext($term, $switch);
              $links->links[$switch]['query']['r'] = Xss::filter($translated_term->getName());
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
        'title' => $title,
      ],
      'child' => [
        '#type' => 'link',
        '#url' => $links->links[$switch]['url'],
        '#title' => substr($title, 0, 2),
        '#attributes' => [
          'lang' => $switch,
          'class' => ['btn', 'header-link', 'header-link--icon'],
        ],
      ],
    ];

    $output['full'] = [
      '#type' => 'link',
      '#url' => $links->links[$switch]['url'],
      '#title' => $title,
      '#attributes' => [
        'lang' => $switch,
        'hreflang' => $switch,
        'class' => ['btn', 'header-link', 'header-link--text'],
      ],
    ];

    return $output;
  }

  /**
   * Method to fetch the translated path for a given report path.
   *
   * @param string $report_path
   *   The original report path.
   * @param string $langcode
   *   The language code for the translation.
   *
   * @return string
   *   The translated path if found, or the original path as a fallback.
   */
  private function getTranslatedPath($report_path, $langcode) {
    $storage = \Drupal::entityTypeManager()->getStorage('pbi_report');
    $query = $storage->getQuery();
    $query->condition('path', $report_path);
    $entity_ids = $query->execute();

    if ($entity_ids) {
      $entity_id = reset($entity_ids);
      $entity = $storage->load($entity_id);

      if ($entity) {
        if ($langcode != 'en') {
          // Load the language-specific configuration override for French.
          $config_name = $entity->getConfigDependencyName();
          $config_translation = \Drupal::languageManager()->getLanguageConfigOverride($langcode, $config_name);

          if ($config_translation) {
            $translated_path = $config_translation->get('path');
            if ($translated_path) {
              return $translated_path;
            }
          }
        }
        else {
          // For English, return the original path from the raw configuration.
          $config_name = $entity->getConfigDependencyName();
          $config = \Drupal::config($config_name);
          $original_path = $config->get('path');
          if ($config) {
            $raw_data = $config->getRawData();
            return $raw_data['path'];
          }
        }
      }
    }
    return $report_path; // Fallback to original if no translation found.
  }

}
