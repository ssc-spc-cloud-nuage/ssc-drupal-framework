<?php

namespace Drupal\ssc_common\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SSCTwig extends AbstractExtension {

  public function getFilters() {
    return [
      new TwigFilter('is_news', [$this, 'isNews']),
    ];
  }

  public function isNews($text) {
    $news_types = [
      'article', 'blog_post', 'corporate_message', 'event', 'gigabit', 'campaign',
    ];
    if (in_array($text->__toString(), $news_types)) {
      return TRUE;
    }
    return FALSE;
  }

}
