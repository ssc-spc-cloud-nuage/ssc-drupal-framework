<?php

namespace Drupal\ssc_common\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "h1_filter",
 *   title = @Translation("H1 Filter"),
 *   description = @Translation("Remove H1s from text (and replace with H2s)."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_HTML_RESTRICTOR,
 * )
 */
class H1Filter extends FilterBase {

  public function process($text, $langcode) {
    $find =  ['<h1 ', '<h1>', '</h1>'];
    $replace = ['<h2 ', '<h2>', '</h2>'];
    $new = str_replace($find, $replace, $text);
    return new FilterProcessResult($new);
  }

}
