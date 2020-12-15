<?php

namespace Drupal\os2web_meetings\Plugin\migrate_plus\data_parser;

use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\SimpleXml;
use Drupal\os2web_meetings\Form\SettingsForm;

/**
 * Extension of contrib SimpleXml.
 *
 * Only difference is that all the child XML will be converted to an array
 * rather returning them as unprocessed SimpleXML elements.
 *
 * @DataParser(
 *   id = "os2web_meetings_simple_xml_array",
 *   title = @Translation("Simple XML Array")
 * )
 */
class SimpleXmlArray extends SimpleXml {

  /**
   * XML directory path.
   *
   * @var string
   */
  public $directoryPath;

  /**
   * Overrides inherited openSourceUrl function.
   *
   * The difference with inherited function is that simplexml is loaded
   * with LIBXML_NOCDATA flag, that allows to read CDATA properties.
   * Another difference with inherited function is saving XML directory path.
   *
   * @param string $url
   *   URL to open.
   *
   * @see \Drupal\migrate_plus\Plugin\migrate_plus\data_parser\SimpleXml::openSourceUrl()
   *
   * @return bool
   *   TRUE if the URL was successfully opened, FALSE otherwise.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function openSourceUrl($url) {
    // Clear XML error buffer. Other Drupal code that executed during the
    // migration may have polluted the error buffer and could create false
    // positives in our error check below. We are only concerned with errors
    // that occur from attempting to load the XML string into an object here.
    libxml_clear_errors();
    $settingFormConfig = \Drupal::config(SettingsForm::$configName);
    $bannedSpecialChar = $settingFormConfig->get('banned_special_char');
    
    $xml_data = $this->getDataFetcherPlugin()->getResponseContent($url);
    if (!empty($bannedSpecialChar)) {
      $xml_data = str_replace(explode(',', $bannedSpecialChar), '', $xml_data);
    }
    $xml = simplexml_load_string(trim($xml_data), 'SimpleXMLElement', LIBXML_NOCDATA);
    foreach (libxml_get_errors() as $error) {
      $error_string = self::parseLibXmlError($error);
      throw new MigrateException($error_string);
    }
    $this->registerNamespaces($xml);
    $xpath = $this->configuration['item_selector'];
    $this->matches = $xml->xpath($xpath);

    // Saving directory path.
    $this->directoryPath = dirname($url);

    return TRUE;
  }

  /**
   * Overrides inherited fetchNextRow function.
   *
   * The only difference is that child SimpleXML elements are getting converted
   * to a an array instead of being kept as SimpleXML elements.
   * Keeping child elements as SimpleXML raises the serialization problem.
   *
   * More information: https://www.drupal.org/project/drupal/issues/3050924.
   *
   * @see \Drupal\migrate_plus\Plugin\migrate_plus\data_parser\SimpleXml::fetchNextRow()
   */
  protected function fetchNextRow() {
    $target_element = array_shift($this->matches);
    $arrayFields = [];

    // If we've found the desired element, populate the currentItem and
    // currentId with its data.
    if ($target_element !== FALSE && !is_null($target_element)) {
      foreach ($this->fieldSelectors() as $field_name => $xpath) {
        foreach ($target_element->xpath($xpath) as $value) {
          if ($value->children() && !trim((string) $value)) {
            // Converting simpleXML structures to array.
            $json = json_encode($value);
            $value_array = json_decode($json, TRUE);

            $this->currentItem[$field_name][] = $value_array;
            $arrayFields[$field_name] = $field_name;
          }
          else {
            $this->currentItem[$field_name][] = (string) $value;
          }
        }
      }
      // Reduce single-value results to scalars, but ignore array fields to
      // ensure a consistent data structure.
      foreach ($this->currentItem as $field_name => $values) {
        if (is_array($values) && count($values) == 1 && !isset($arrayFields[$field_name])) {
          $this->currentItem[$field_name] = reset($values);
        }
      }

      $this->currentItem['directory_path'] = $this->directoryPath;
    }

  }

}
