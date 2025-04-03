<?php

/**
 * Update the body field format for all nodes and paragraphs.
 */
function ssc_common_post_update_body_format(&$sandbox) {
  $old_format = 'rich_text';
  $format = 'full_html';
  $database = \Drupal::database();

  $database->update('node__body')
    ->fields(['body_format' => $format])
    ->condition('body_format', $old_format)
    ->execute();

  $database->update('node_revision__body')
    ->fields(['body_format' => $format])
    ->condition('body_format', $old_format)
    ->execute();

  $database->update('paragraph__field_body')
    ->fields(['field_body_format' => $format])
    ->condition('field_body_format', $old_format)
    ->execute();

  $database->update('paragraph_revision__field_body')
    ->fields(['field_body_format' => $format])
    ->condition('field_body_format', $old_format)
    ->execute();

  $sandbox['#finished'] = 1;
}