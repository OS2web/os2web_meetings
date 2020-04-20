<?php

namespace Drupal\os2web_meetings\Entity;

/**
 * Wrapper for OS2Web bullet point attachment node.
 */
class BulletPointAttachment extends Os2webNodeBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return 'os2web_meetings_bpa';
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByEsdhId($esdhId) {
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage('node');

    $entities = $storage->loadByProperties([
      'type' => 'os2web_meetings_bpa',
      'field_os2web_m_esdh_id' => $esdhId,
    ]);
    if ($entity = reset($entities)) {
      return new BulletPointAttachment($entity);
    }

    return NULL;
  }

}
