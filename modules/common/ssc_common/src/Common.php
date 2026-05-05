<?php

namespace Drupal\ssc_common;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\message\Entity\Message;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Views;

/**
 * Utilities common across all SSC sites.
 */

class Common {

  /**
   * Sends an email using Message Notify.
   *
   * @param string $template
   *     ID from Message template
   * @param array $options
   *      $options: to, from, cc, bcc = User object OR email OR array of these
   *       - from: defaults to Site if not provided
   *      $options['context'][ENTITY_TYPE] = ENTITY Object
   *      $options['fields'] = Message Template field values
   *      $options['attached'] = Attachment file
   */
  static function sendMail($template, $options = []) {
    // If no To then nothing to do.
    if (empty($options['to'])) {
      \Drupal::messenger()->addWarning(t('Attempted to send email with no To address.'));
      \Drupal::logger('sendMail')
        ->warning('Attempted to send email with no To address.');
      return FALSE;
    }

    $site_config = \Drupal::config('system.site');
    $site_name = $site_config->get('name');
    $site_mail = $site_config->get('mail');

    // If From not set use Site email.
    if (!isset($options['from'])) {
      $options['from'] = $site_name . ' <' . $site_mail . '>';
    }

    // Process To, From, CC, BCC
    $fields = [
      'to' => 'To',
      'from' => 'From',
      'cc' => 'Cc',
      'bcc' => 'Bcc',
    ];
    $headers = [];
    $to_plain_emails = [];
    $to_display_emails = [];

    foreach ($fields as $key => $field) {
      if (empty($options[$key])) continue;

      $addresses = is_array($options[$key]) ? $options[$key] : [$options[$key]];

      $display_emails = [];
      $plain_emails = [];

      foreach ($addresses as $address) {
        if (is_object($address)) {
          $display = '"' . addcslashes($address->getDisplayName(), '"\\') . '" <' . $address->getEmail() . '>';
        }
        elseif ($address == 'site') {
          $display = '"' . addcslashes($site_name, '"\\') . '" <' . $site_mail . '>';
        }
        else {
          $display = static::normalizeEmailAddress($address);
        }

        $display_emails[] = $display;
        $plain_emails[] = static::extractEmailOnly($display);
      }

      if ($key == 'to') {
        $to_plain_emails = $plain_emails;
        $to_display_emails = $display_emails;
      }
      else {
        $headers[$field] = implode(', ', $display_emails);

        // Optional but safer if backend uses these somehow.
        $headers['X-Plain-' . $field] = implode(', ', $plain_emails);
      }

    }

    $notifier = \Drupal::service('message_notify.sender');
    $message = Message::create(['template' => $template, 'uid' => \Drupal::currentUser()->id()]);

    // Add in any Message fields passed in
    // NEW (1024): if field is long formatted text, set to Email Templates format.
    if (!empty($options['fields'])) {
      foreach ($options['fields'] as $field => $value) {
        if (Common::isFormattedLongText($message, $field)) {
          $message->set($field, ['value' => $value, 'format' => 'email_templates']);
        }
        else {
          $message->set($field, $value);
        }
      }
    }

    // set default user/node token context (https://www.drupal.org/project/message/issues/2981259)
    // set contexts if sent - to use for tokens
    if (isset($options['context'])) {
      foreach ($options['context'] as $entity_type => $entity) {
        if ($entity_type == 'user') {
          $message->setOwner($entity);
        }
        else {
          $message->addContext($entity_type, $entity);
        }
      }
    }
    $message->save();

    // Let's set Sender as From or else some clients show From as "Sender on behalf of From".
    $headers['Sender'] = $headers['From'];

    $attachments = [];
    if (isset($options['attached'])) {
      $attachments[] = [
        'filepath' => $options['attached'],
        'filename' => basename($options['attached']),
        'filemime' => 'application/pdf'
      ];
    }

    $options = [
      'mail' => implode(', ', $to_plain_emails),
      'params' => [
        'headers' => $headers + [
            'To' => implode(', ', $to_display_emails),
          ],
        'attachments' => $attachments,
      ],
    ];

    $result = $notifier->send($message, $options);
    // @todo: log failed attempt.

    return $result;
  }

  /**
   * Check if a field is a formatted long text field based on its definition.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity (e.g., a node).
   * @param string $field_name
   *   The machine name of the field.
   *
   * @return bool
   *   TRUE if the field is a formatted long text field, FALSE otherwise.
   */
  static function isFormattedLongText(EntityInterface $entity, string $field_name): bool {
    if ($entity->hasField($field_name)) {
      // Get the field definition.
      $field_definition = $entity->getFieldDefinition($field_name);

      // Check if the field type is 'text_long' (long text).
      $field_type = $field_definition->getType();

      // Check if the field allows formats (i.e., it has 'allowed_formats').
      $allowed_formats = $field_definition->getSetting('allowed_formats');

      // Return TRUE if it's a formatted long text field.
      return $field_type === 'text_long' && isset($allowed_formats);
    }

    return FALSE;
  }

  protected static function normalizeEmailAddress(string $address): string {
    $address = trim($address);

    // Match: optional display name + <email@example.com>
    if (preg_match('/^\s*(.*?)\s*<\s*([^<>]+@[^<>]+)\s*>\s*$/', $address, $matches)) {
      $name = trim($matches[1]);
      $email = trim($matches[2]);

      // Remove surrounding quotes if editor entered them.
      if (str_starts_with($name, '"') && str_ends_with($name, '"')) {
        $name = substr($name, 1, -1);
      }

      // Decode escaped quotes/backslashes if already double-escaped.
      $name = stripcslashes($name);
      $name = trim($name, '"');

      if ($name !== '') {
        return '"' . addcslashes($name, '"\\') . '" <' . $email . '>';
      }

      return $email;
    }

    // Plain email address.
    return $address;
  }

  protected static function extractEmailOnly(string $address): string {
    $address = trim($address);

    if (preg_match('/<\s*([^<>]+@[^<>]+)\s*>/', $address, $matches)) {
      return trim($matches[1]);
    }

    return $address;
  }

/**
   * Renders a Views block in a specific language.
   *
   *  This should not be this difficult.. ughh.
   *
   * @return array
   *   The render array for the Views block.
   */
  static function renderViewsBlock($view_id, $display_id, $langcode, array $arguments = []): array {
    $output = [];

    $language_manager = \Drupal::languageManager();
    $custom_language_negotiator = \Drupal::service('backlinks.language_negotiator');
    $language_manager->setNegotiator($custom_language_negotiator);

    // Get original "current language" so we can set it back later.
    $original_language_id = $language_manager->getCurrentLanguage()->getId();

    // Set new language by its langcode
    $language_manager->reset(); // Needed to re-run language negotiation
    $language_manager->getNegotiator()->setLanguageCode($langcode);

    // Get the desired language.
    $desired_language = $language_manager->getLanguage($langcode);
    \Drupal::languageManager()->setConfigOverrideLanguage($desired_language);

    // Load the view.
    $view = Views::getView($view_id);
    if ($view) {
      // Set the display ID.
      $view->setDisplay($display_id);

      // Set any arguments if needed.
      if (!empty($arguments)) {
        $view->setArguments($arguments);
      }

      // Isolate the render context to avoid side effects.
      $context = new RenderContext();
      // Render the view block in the desired language.
      $output = \Drupal::service('renderer')->executeInRenderContext($context, function () use ($view, $desired_language) {
        // Execute and render the view.
        $view->preExecute();
        $view->execute();
        return $view->render();
      });
    }

    // Reset language back as it was.
    $language_manager->reset();
    $language_manager->getNegotiator()->setLanguageCode($original_language_id);

    // Return the rendered block.
    return $output;
  }

  static function textToArray($area) {
    $items = explode("\r\n", $area);
    $items = array_map('trim', $items);
    $items = array_filter($items);
    $items = array_unique($items);
    $items = array_values($items);
    sort($items);
    return $items;
  }

  static function loadTermByName(string $name, string $vocabulary): ?Term {
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('name', $name)
      ->condition('vid', $vocabulary)
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->execute();

    if (!empty($tids)) {
      return Term::load(reset($tids));
    }

    return NULL;
  }

}
