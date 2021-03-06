<?php

namespace Drupal\os2web_meetings\Entity;

use Drupal\node\Entity\Node;

/**
 * Wrapper for OS2Web bullet point node.
 */
class BulletPoint extends Os2webNodeBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return 'os2web_meetings_bp';
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByEsdhId($esdhId) {
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage('node');

    $entities = $storage->loadByProperties([
      'type' => 'os2web_meetings_bp',
      'field_os2web_m_esdh_id' => $esdhId,
    ]);
    if ($entity = reset($entities)) {
      return new BulletPoint($entity);
    }

    return NULL;
  }

  /**
   * Returns related bullet point attachments.
   *
   * @param bool $load
   *   If the returned nodes shall be load. If FALSE, array of nids is returned.
   *
   * @return array
   *   If load is TRUE array of nodes is returned,
   *   If load is FALSE array of nids is returned,
   *   If field is empty, empty array is returned.
   */
  public function getBulletPointAttachments($load = TRUE) {
    if ($fieldBpas = $this->getEntity()->get('field_os2web_m_bp_bpas')) {
      if ($load) {
        return $fieldBpas->referencedEntities();
      }
      else {
        return array_column($fieldBpas->getValue(), 'target_id');
      }
    }

    return [];
  }

  /**
   * Returns bullet point attachment having this ESDH ID.
   *
   * @param string $esdhId
   *   Expected ESDH ID of the entity.
   * @param bool $load
   *   If the returned node shall be load. If FALSE, nid is returned.
   *
   * @return \Drupal\Core\Entity\EntityInterface|int|null
   *   Bullet point attachment node, or Bullet point attachment nid.
   *   NULL is nothing is found.
   */
  public function getBulletPointAttachmentByEsdhId($esdhId, $load = TRUE) {
    // Getting all BPA IDs of this bullet point.
    $bpaIds = $this->getBulletPointAttachments(FALSE);

    if (!empty($bpaIds)) {
      $query = \Drupal::entityQuery('node')
        ->condition('nid', $bpaIds, 'IN')
        ->condition('type', 'os2web_meetings_bpa')
        ->condition('field_os2web_m_esdh_id', $esdhId);

      $nids = $query->execute();
      if (!empty($nids)) {
        $nid = reset($nids);
        return ($load) ? Node::load($nid) : $nid;
      }
    }

    return NULL;
  }

}
