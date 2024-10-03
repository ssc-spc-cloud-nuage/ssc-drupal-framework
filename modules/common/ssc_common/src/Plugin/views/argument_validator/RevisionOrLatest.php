<?php

namespace Drupal\ssc_common\Plugin\views\argument_validator;

use Drupal\node\Entity\Node;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;

/**
 * Convert Content ID from URL to Node ID of 1st parent with a Banner Image.
 *
 * @ingroup views_argument_validate_plugins
 *
 * @ViewsArgumentValidator(
 *   id = "revision_or_latest",
 *   title = @Translation("Revision ID or Latest")
 * )
 */
class RevisionOrLatest extends ArgumentValidatorPluginBase {

  /**
   * Sets the argument based on incoming argument:
   *  - NULL (std node/nid): set to null
   *  - latest: set to latest vid
   *  - revisions: set to vid from url
   *
   * @param \Drupal\views\Plugin\views\argument\ArgumentPluginBase $argument
   *   The parent argument to set.
   */
  public function validateArgument($argument) {
    $url = explode('/', \Drupal::service('path.current')->getPath());
    $args = $this->view->args;
    // If ARG is null (i.e. std node/[nid]: return NULL
    if (!isset($argument) || $argument == 'views') {
      $this->argument->argument = $this->getCurrentRevision($args[0]);
      return TRUE;
    }
    // If Revision page, return VID from URL
    if ($argument == 'revisions') {
      $this->argument->argument = $url[4];
      return TRUE;
    }
    // If Latest Revision page, return latest vid
    if ($argument == 'latest') {
      $this->argument->argument = $this->getLatestRevision($args[0]);
      return TRUE;
    }
    // Views preview case - handle revision case
    if (isset($url[4]) && $url[4] == 'views' && is_numeric($args[1])) {
      $this->argument->argument = $args[1];
      return TRUE;
    }
    // Handle case when view called programmatically, e.g. ICN emails.
    if ($argument == 'current') {
      $this->argument->argument = $this->getCurrentRevision($args[0]);
      return TRUE;
    }

    return FALSE;
  }

  private function getLatestRevision($nid) {
    $lang = get_content_language_code();
    $vid = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getLatestTranslationAffectedRevisionId($nid, $lang);
    return $vid;
  }

  private function getCurrentRevision($nid) {
    $node = Node::load($nid);
    return $node->getRevisionId();
  }
}
