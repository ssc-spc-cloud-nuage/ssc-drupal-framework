<?php
namespace Drupal\ckeditor_pastefilter\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "Paste Filter" plugin.
 *
 * @CKEditorPlugin(
 *   id = "ckeditor_pastefilter",
 *   label = @Translation("Paste Filter"),
 *   module = "ckeditor_pastefilter"
 * )
 */
class PasteFilter extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginContextualInterface {
    /**
     * {@inheritdoc}
     */
  public function isEnabled(Editor $editor) {
    $settings = $editor->getSettings();
    $enabled = isset($settings["plugins"]["ckeditor_pastefilter"]["ckeditor_pastefilter_enabled"]) ? $settings["plugins"]["ckeditor_pastefilter"]["ckeditor_pastefilter_enabled"] : 0;
    return $enabled;
  }

    /**
     * {@inheritdoc}
     */
  public function getButtons() {
    return [];
  }

    /**
     * {@inheritdoc}
     */
  public function getFile() {
    $path_service = \Drupal::service('extension.path.resolver');
    return $path_service->getPath("module", "ckeditor_pastefilter") . "/js/plugins/paste-filter/plugin.js";
  }

    /**
     * {@inheritdoc}
     */
  public function getConfig(Editor $editor) {
    $config = [];
    $settings = $editor->getSettings();
    if (!isset($settings["plugins"]["ckeditor_pastefilter"])) {
      return $config;
    }

    $rules = $settings["plugins"]["ckeditor_pastefilter"]["ckeditor_pastefilter_rules"];
    $enabled = $settings["plugins"]["ckeditor_pastefilter"]["ckeditor_pastefilter_enabled"];

    // Check if custom config is populated.
    if ($enabled && !empty($rules)) {
      // Build array from string.
      $rules_array = preg_split("/\R/", $rules);
      // Loop through config lines and append to editorSettings.
      foreach ($rules_array as $rule) {
        if (substr(trim($rule), 0, 1) == '#') {
          continue;
        }
        $exploded_value = explode(",", $rule);
        $key = $exploded_value[0];
        // If valid, value will be added
        if (isset($exploded_value[1])) {
          $config["ckeditor_pastefilter"][$key] = $exploded_value[1];
        }
      }
    }

    return $config;
  }

    /**
     * {@inheritdoc}
     */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();
    if (isset($settings["plugins"]["ckeditor_pastefilter"])) {
      $config = $settings["plugins"]["ckeditor_pastefilter"];
    }

    $form["ckeditor_pastefilter_enabled"] = [
      "#type" => "checkbox",
      "#title" => $this->t("Enable the paste filter"),
      "#default_value" => isset($config["ckeditor_pastefilter_enabled"]) ? $config["ckeditor_pastefilter_enabled"] : 0,
    ];

    $form["ckeditor_pastefilter_rules"] = [
      "#type" => "textarea",
      "#title" => $this->t("Paste filter configuration"),
      "#default_value" => isset($config["ckeditor_pastefilter_rules"]) ? $config["ckeditor_pastefilter_rules"] : "",
      "#description" => $this->t(
        'Enter each rule on a separate line formatted as "[regex filter],[replace value]". Start lines with a
        "#" to add comments.<br><br>
        Examples:
        <ul>
        <li>#remove all html attributes except href<br><([a-z][a-z0-9]*)(?:[^>]*(href=["][^"]*["]))?[^>]*?(\/?)>,<$1 $2></li>
        <li>#replace non-std double quotes with normal quote<br>
          <ul>
            <li>“,"</li>
            <li>”,"</li>
          </ul>
        </ul>'),
    ];

    return $form;
  }

    /**
     * Custom validator for the "custom_config" element in settingsForm().
     */
  public function validateCustomConfig(array $element, FormStateInterface $form_state) {
    return;
    // Convert submitted value into an array. Return if empty or not enabled.
    $config_value = $element["#value"];
    if (empty($config_value) || !$form_state->getValues()["editor"]["settings"]["plugins"]["pastefilter"]["ckeditor_pastefilter_enable"]) {
      return;
    }

    $config_array = preg_split("/\R/", $config_value);
    // Loop through lines.
    $i = 1;
    foreach ($config_array as $config_value) {
      // Check that syntax matches "[something] = [something]".
      preg_match("/(.*?) \= (.*)/", $config_value, $matches);
      if (empty($matches)) {
        $form_state->setError($element, $this->t('The configuration syntax on line @line is incorrect. The correct syntax is "[setting.name] = [value]"', ["@line" => $i]));
      }
      // If syntax is valid, then check JSON validity.
      else {
        // Check is value is valid JSON.
        $exploded_value = explode(" = ", $config_value);
        $key = $exploded_value[0];
        $value = $exploded_value[1];

        // Create JSON string and attempt to decode.
        // If invalid, then set error.
        $json = '{ "' . $key . '": ' . $value . " }";
        $decoded_json = Json::decode($json, true);
        if (is_null($decoded_json)) {
          $form_state->setError($element, $this->t("The configuration value on line @line is not valid JSON.", ["@line" => $i]));
        }
      }

      $i++;
    }
  }
}
