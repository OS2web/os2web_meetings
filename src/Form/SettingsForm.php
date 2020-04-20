<?php

namespace Drupal\os2web_meetings\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure os2web_borgerdk settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Name of the config.
   *
   * @var string
   */
  public static $configName = 'os2web_meetings.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2web_meetings_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [SettingsForm::$configName];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['meetings_import_details'] = [
      '#type' => 'details',
      '#title' => t('Import settings'),
      '#open' => TRUE,
    ];

    $form['meetings_import_details']['import_closed_agenda'] = [
      '#type' => 'checkbox',
      '#title' => t('Import closed agenda'),
      '#description' => t('If closed agenda will be imported, otherwise the closed content is skipped'),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('import_closed_agenda'),
    ];

    $form['meetings_import_details']['committee_whitelist'] = [
      '#type' => 'textfield',
      '#title' => t('Whitelist of the committees'),
      '#description' => t('If committee is not whitelisted, its meetings will be ignored. Use comma separated list of committee IDs. Empty list = allow ALL.'),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('committee_whitelist'),
    ];

    $form['meetings_import_details']['unpublish_missing_agendas'] = [
      '#type' => 'checkbox',
      '#title' => t('Unpublish missing agendas'),
      '#description' => t('If this plugin missing agendas will be unpublished. DO NOT use this setting if you are planning to import agendas in with max limit'),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('unpublish_missing_agendas'),
    ];

    $form['meetings_import_details']['clear_html_tags_list'] = [
      '#type' => 'textfield',
      '#title' => t('Clear HTML tags'),
      '#description' => t('Comma-separated list of HTML tags, which style attribute shall be removed during import (it will remove only style HTML attribute of a given tag)'),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('clear_html_tags_list'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $config = $this->config(SettingsForm::$configName);
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
