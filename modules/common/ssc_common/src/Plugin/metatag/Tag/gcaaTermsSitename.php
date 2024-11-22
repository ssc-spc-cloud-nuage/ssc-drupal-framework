<?php

namespace Drupal\wxt_ext_metatag\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * Adobe Analytics 'gcaaterms.sitename' meta tag.
 *
 * @MetatagTag(
 *   id = "gcaaterms_sitename",
 *   label = @Translation("Adobe Analytics meta tag <code>gcaaterms.sitename</code>"),
 *   description = @Translation("Replaces dcterms.service"),
 *   name = "gcaaterms.sitename",
 *   group = "adobe_analytics",
 *   weight = 0,
 *   type = "string",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class gcaaTermsSitename extends MetaNameBase {
  // Nothing here yet. Just a placeholder class for a plugin.
}
