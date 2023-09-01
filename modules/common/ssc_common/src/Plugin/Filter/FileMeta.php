<?php

namespace Drupal\ssc_common\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to convert URLs into links.
 *
 * @Filter(
 *   id = "file_meta",
 *   title = @Translation("Add meta info to file links"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FileMeta extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult($this->fileLinkSearch($text, $langcode, $this));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Appends file type and file size to text of the A tag for a linked file.');
  }

  /**
   * {@inheritdoc}
   */
  public function fileLinkSearch($text, $langcode, $filter) {
    // Embedded wet map gets messed up with this so let's just not support it.
    if (stristr($text, 'data-wb-geomap')) {
      return $text;
    }

    $dom = Html::load($text);
    foreach ($dom->getElementsByTagName('a') as $element) {
      $link = $element->getAttribute('href');
      if (!$link || !stristr($link, '/files/')) {
        continue;
      }

      // Size full title.
      $sizes = [
        'B' => $this->t('bytes'),
        'bytes' => $this->t('bytes'),
        'KB' => $this->t('kilobytes'),
        'MB' => $this->t('megabytes'),
        'octets' => $this->t('bytes'),
        'Ko' => $this->t('kilobytes'),
        'Mo' => $this->t('megabytes'),

      ];
      // Doc types full title.
      $doctypes = [
        'PDF' => $this->t('Portable document format'),
        'DOCX' => $this->t('Word'),
        'XLSX' => $this->t('Excel'),
        'PPTX' => $this->t('Powerpoint'),
        'TXT' => $this->t('Plain text'),
      ];

      // Load file with this path.
      $uri = str_replace(['/sites/default/files/', '%20'], ['public://', ' '], $link);
      /** @var \Drupal\file\FileInterface[] $files */
      $files = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->loadByProperties(['uri' => $uri]);

      // File may have been deleted; so let's skip those.
      if (!$files) {
        continue;
      }

      $file = reset($files) ?: NULL;
      $filesize = format_size($file->getSize());
      $size_bits = explode(' ', $filesize);
      // Drupal's stored mimetype is stupidly based on file extension; so use PHP function.
      $filemime = mime_content_type($uri);
      $filemime = $this->cleanMime($filemime);
      $doc_type_title = isset($doctypes[$filemime]) ? $doctypes[$filemime] : 'Unknown file type';

      // Create a <span> element with <attr> children.
      $span = $dom->createElement('span');
      $span->setAttribute('class', 'small nowrap');
      $attr1 = $dom->createElement('attr');
      $attr1->setAttribute('title', $doc_type_title);
      $attr1_text = $dom->createTextNode(' (' . $filemime);
      $attr1->appendChild($attr1_text);
      $attr_sep = $dom->createTextNode(', ' . $size_bits[0]);
      $attr2 = $dom->createElement('attr');
      $attr2->setAttribute('title', $sizes[$size_bits[1]]);
      $attr2_text = $dom->createTextNode($size_bits[1] . ')');
      $attr2->appendChild($attr2_text);

      // Append <attr> tags to <span>.
      $span->appendChild($attr1);
      $span->appendChild($attr_sep);
      $span->appendChild($attr2);

      // Append the <span> element inside the <a> tag
      $element->appendChild($span);
    }

    $result = HTML::serialize($dom);
    return $result;
  }

  public function cleanMime($mime) {
    if (stristr($mime, 'word')) return 'DOCX';
    if (stristr($mime, 'sheet')) return 'XLSX';
    if (stristr($mime, 'text')) return 'TXT';
    if (stristr($mime, 'pdf')) return 'PDF';
    if (stristr($mime, 'presentation')) return 'PPTX';
  }

}
