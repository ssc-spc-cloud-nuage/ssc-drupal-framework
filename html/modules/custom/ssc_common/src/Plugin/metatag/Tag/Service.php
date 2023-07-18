<?php

namespace Drupal\ssc_common\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The Dublin Core "accessRights" meta tag.
 *
 * @MetatagTag(
 *   id = "dcterms_service",
 *   label = @Translation("Service"),
 *   description = @Translation("Defines the application provided. Custom for SSC to use with Adobe Analytics."),
 *   name = "dcterms.service",
 *   group = "dublin_core_advanced",
 *   weight = 100,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Service extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
