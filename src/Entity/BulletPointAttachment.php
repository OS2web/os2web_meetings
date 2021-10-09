<?php

namespace Drupal\os2web_meetings\Entity;

use Drupal\node\Entity\Node;

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

  /**
   * Returns related meeting.
   *
   * @param bool $load
   *   If the returned node shall be load. If FALSE, nid is returned.
   *
   * @return \Drupal\node\NodeInterface|int|null
   *   Meeting node, or Meeting nid.
   *   NULL is nothing is found.
   *
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   */
  public function getMeeting($load = TRUE) {
    // Getting BP first.
    $bp = $this->getBulletPoint();

    if ($bp) {
      $bulletPoint = new BulletPoint($bp);
      return $bulletPoint->getMeeting($load);
    }

    return NULL;
  }

  /**
   * Returns related bullet point.
   *
   * @param bool $load
   *   If the returned node shall be load. If FALSE, nid is returned.
   *
   * @return \Drupal\node\NodeInterface|int|null
   *   Bullet point node, or Bullet point nid.
   *   NULL is nothing is found.
   */
  public function getBulletPoint($load = TRUE) {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'os2web_meetings_bp')
      ->condition('field_os2web_m_bp_bpas', $this->getEntity()->id());

    $nids = $query->execute();

    if (!empty($nids)) {
      $nid = reset($nids);
      return ($load) ? Node::load($nid) : $nid;
    }

    return NULL;
  }

}
