<?php

namespace Drupal\ssc_common\Twig;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SSCTwig extends AbstractExtension {
  use StringTranslationTrait;

  protected DateFormatterInterface $dateFormatter;

  public function __construct(DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
  }

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
      $time_until = $this->dateFormatter->formatTimeDiffUntil($timestamp, ['granularity' => 1]);
      $time_until_translated = $this->translateTimeInterval($time_until);
      return new TranslatableMarkup('in @interval', ['@interval' => $time_until_translated]);
    }
    else {
      $time_since = $this->dateFormatter->formatTimeDiffSince($timestamp, ['granularity' => 1]);
      $time_since_translated = $this->translateTimeInterval($time_since);
      return new TranslatableMarkup('@interval ago', ['@interval' => $time_since_translated]);
    }
  }

  protected function translateTimeInterval($time_interval) {
    if (preg_match('/(\d+)\s+(\w+)/', $time_interval, $matches)) {
      $number = $matches[1];
      $unit = $matches[2];

      // Translate the unit
      $translated_unit = new TranslatableMarkup($unit);

      // Use formatPlural for proper translation
      return \Drupal::translation()->formatPlural(
        $number,
        '1 @unit',
        '@count @unit',
        ['@unit' => $translated_unit]
      );
    } else {
      return $time_interval; // Fallback if parsing fails
    }
  }


}
