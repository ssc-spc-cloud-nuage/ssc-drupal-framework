<?php

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;

function _expire_featured_posts_cron() {
  // Expire Featured posts
  $lists = ['news', 'parent'];
  $view = 'featured_expiry';

  foreach ($lists as $display) {
    $items = views_get_view_result($view, $display);
    if (!empty($items)) {
      foreach ($items as $item) {
        $nid = $item->nid;
        $node = Node::load($nid);
        $node->set('field_featured_on_' . $display, 0);
        $node->save();
      }
    }
  }
}

/*
 * Add or remove home page promo paragraphs depending on Active date values is
 * automatically managed through its View; but need CRON to manage changing layout.
 */
function _expire_home_promo_cron() {
  // Get the Home promo block.
  $items = views_get_view_result('home_promo_blocks', 'default');
  if (empty($items)) {
    return;
  }
  $block_id = $items[0]->id;

  // Check if any have expired.
  $expired = views_get_view_result('home_promo_singles', 'expired', $block_id);
  if (empty($expired)) {
    return;
  }
  foreach ($expired as $expire) {
    $removes[] = $expire->id;
  }

  // Load home page promo block.
  $block = BlockContent::load($block_id);

  // Remove expired promo items.
  $promos = $block->get('field_1x1_cards');
  if (count($promos)) {
    $has_promoted = 0;
    $promo_first = NULL;
    for ($i = 0; $i < $promos->count(); $i++) {
      if (in_array($promos[$i]->target_id, $removes)) {
        $promos->removeItem($i);
        $i--;
      }
      // If not being removed; check if it is promoted.
      else {
        // Check if promoted promo yet.
        $promo = Paragraph::load($promos[$i]->target_id);
        if ($promo->field_promoted->value) {
          $has_promoted++;
        }
        else {
          $promo_first = $promo_first ?: $promo;
        }
      }
    }
  }

  // If there are no promoted promos they have likely just expired. Let's promote the first one.
  if (!$has_promoted && $promo_first) {
    $promo_first->set('field_promoted', 1);
    $promo_first->save();
    $has_promoted++;
  }

  // End case: if only 2 we need to have both promoted/layout 1
  if ($promos->count() == 2) {
    foreach($promos as $promo) {
      $promo = Paragraph::load($promo->target_id);
      $promo->set('field_promoted', 1);
      $promo->save();
    }
  }

  // Adjust layout for number of remaining unpromoted promos.
  $layouts = [];
  $current_layout = $block->layout_selection->target_id;
  $size = substr($current_layout,-1);
  $singles = $promos->count() - $has_promoted;
  if ($singles >= $size) {
    $new_layout = $current_layout;
  }
  else if ($singles == 5 || $singles == 4) {
    $new_layout = 'home_promo_5';
  }
  else if ($singles == 3 || $singles == 2) {
    $new_layout = 'home_promo_3';
  }
  else {
    $new_layout = 'home_promo_1';
  }
  // Set layout.
  $block->set('layout_selection', $new_layout);
  $block->save();
}

/**
 * Sets active Sitewide Alerts whose scheduled end date has passed to inactive.
 *
 * The sitewide_alert module only hides expired alerts at display time and
 * leaves them active, so do the cleanup here.
 */
function _expire_sitewide_alerts_cron(): void {
  $storage = \Drupal::entityTypeManager()->getStorage('sitewide_alert');

  // Daterange values are stored in UTC as Y-m-d\TH:i:s.
  $now = (new DrupalDateTime('now', DateTimeItemInterface::STORAGE_TIMEZONE))
    ->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

  $ids = $storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('status', 1)
    ->condition('scheduled_alert', 1)
    ->condition('scheduled_date.end_value', $now, '<=')
    ->execute();

  if (empty($ids)) {
    return;
  }

  /** @var \Drupal\sitewide_alert\Entity\SitewideAlertInterface $alert */
  foreach ($storage->loadMultiple($ids) as $alert) {
    $alert->setUnpublished()->save();
  }

  \Drupal::logger('ssc_common')->notice('Set @count expired sitewide alert(s) to inactive: @ids', [
    '@count' => count($ids),
    '@ids' => implode(', ', $ids),
  ]);
}
