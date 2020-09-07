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
    // Import settings.
    $form['meetings_import_details'] = [
      '#type' => 'details',
      '#title' => t('Import settings'),
      '#open' => TRUE,
    ];
    $form['meetings_import_details']['import_closed_agenda'] = [
      '#type' => 'checkbox',
      '#title' => t('Import closed agenda'),
      '#description' => t('If closed agenda will be imported, otherwise the closed bullet point is imported empty, and its children are skipped'),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('import_closed_agenda'),
    ];
    $form['meetings_import_details']['closed_bp_body_text'] = [
      '#type' => 'textarea',
      '#title' => t('Text for closed bullet point'),
      '#description' => t('Text that will be shown in closed bullet point body'),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('closed_bp_body_text'),
      '#states' => [
        'visible' => [
          ':input[name="import_closed_agenda"]' => ['checked' => FALSE],
        ],
      ],
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

    $form['meetings_import_details']['process_enclosures_as_attachments'] = [
      '#type' => 'checkbox',
      '#title' => t('Process enclosures (file) as attachments'),
      '#description' => t('This decides if the enclosures shall be processed as the attachments with files. If unchecked enclosures will be added to the list of bullet point enclosures.'),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('process_enclosures_as_attachments'),
    ];

    $form['meetings_import_details']['clear_html_tags_list'] = [
      '#type' => 'textfield',
      '#title' => t('Clear HTML tags'),
      '#description' => t('Comma-separated list of HTML tags, which style attribute shall be removed during import (it will remove only style HTML attribute of a given tag)'),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('clear_html_tags_list'),
    ];

    $form['meetings_import_details']['replace_multiple_nbsp'] = [
      '#type' => 'checkbox',
      '#title' => t('Replace multiple sequential non-breakable-space with a single one'),
      '#description' => t('Replace all occurrences of multiple %nbsp tags with single one', ['%nbsp' => '&nbsp;']),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('replace_multiple_nbsp'),
    ];

    $form['meetings_import_details']['replace_empty_paragraphs'] = [
      '#type' => 'checkbox',
      '#title' => t('Replace empty Paragraphs with br-tag'),
      '#description' => t('Replace all %ptag and %ptag2 tags with %brtag', [
        '%ptag' => '<p></p>',
        '%ptag2' => '<p>&nbsp;</p>',
        '%brtag' => '<br/>',
      ]),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('replace_empty_paragraphs'),
    ];

    $form['meetings_import_details']['max_sequential_br'] = [
      '#type' => 'number',
      '#title' => t('Maximum allowed amount of sequential br-tags'),
      '#description' => t('Everything above the limit will be removed'),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('max_sequential_br') ?? 1,
    ];

    // Import view settings.
    $form['meetings_view_details'] = [
      '#type' => 'details',
      '#title' => t('View settings'),
      '#open' => FALSE,
    ];
    $form['meetings_view_details']['resume_bpa_title'] = [
      '#type' => 'textfield',
      '#title' => t('Title of Resume field'),
      '#description' => t('The bullet point attachments with this title will be marked as Resume. Case insensitive'),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('resume_bpa_title'),
    ];
    $form['meetings_view_details']['decision_bpa_title'] = [
      '#type' => 'textfield',
      '#title' => t('Title of Beslutning field'),
      '#description' => t('The bullet point attachments with this title will be marked as Beslutning. Case insensitive'),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('decision_bpa_title'),
    ];
    $form['meetings_view_details']['enclosures_max_title_length'] = [
      '#type' => 'number',
      '#title' => t('Maximum length for BP enclosures title'),
      '#description' => t('If enclosure lenght if above the limit, it will be cut and ... will be added.'),
      '#default_value' => $this->config(SettingsForm::$configName)
        ->get('enclosures_max_title_length'),
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
