<?php

namespace Drupal\ssc_common\Twig;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SSCTwig extends AbstractExtension {

  public function getFilters() {
    return [
      new TwigFilter('is_news', [$this, 'isNews']),
      new TwigFilter('time_ago', [$this, 'timeAgo']),
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

  public function timeAgo($text) {
    $timestamp = $text->__toString();
    if ($timestamp > time()) {
      $time_until = \Drupal::service('date.formatter')
        ->formatTimeDiffUntil($timestamp, ['granularity' => 1]);
      return new TranslatableMarkup('in @interval', ['@interval' => $time_until]);
    }
    else {
      $time_since = \Drupal::service('date.formatter')
        ->formatTimeDiffSince($timestamp,  ['granularity' => 1]);
      return new TranslatableMarkup('@interval ago', ['@interval' => $time_since]);
    }
  }
}
