<?php

namespace Drupal\os2web_meetings\Entity;

/**
 * Wrapper for OS2Web location taxonomy term.
 */
class Location extends Os2webTaxonomyTermBase {

  /**
   * {@inheritdoc}
   */
  public function bundle() {
    return 'os2web_m_location';
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByEsdhId($esdhId) {
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage('taxonomy_term');

    $entities = $storage->loadByProperties([
      'vid' => 'os2web_m_location',
      'field_os2web_m_esdh_id' => $esdhId,
    ]);
    if ($entity = reset($entities)) {
      return new Location($entity);
    }

    return NULL;
  }

}
