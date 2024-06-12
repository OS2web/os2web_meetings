<?php

namespace Drupal\os2web_meetings\Entity;

use Drupal\node\Entity\Node;

/**
 * Wrapper for OS2Web meeting node.
 */
class Meeting extends Os2webNodeBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return 'os2web_meetings_meeting';
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByEsdhId($esdhId) {
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage('node');

    $entities = $storage->loadByProperties([
      'type' => 'os2web_meetings_meeting',
      'field_os2web_m_esdh_id' => $esdhId,
    ]);
    if ($entity = reset($entities)) {
      return new Meeting($entity);
    }

    return NULL;
  }

  /**
   * Gets ESDH Meeting id.
   *
   * @return string
   *   ESDH Meeting ID.
   */
  public function getMeetingId() {
    return $this->getEntity()->get('field_os2web_m_meet_id')->value;
  }

  /**
   * Returns related committee.
   *
   * @param bool $load
   *   If the returned term shall be load. If FALSE, tid is returned.
   *
   * @return \Drupal\taxonomy\TermInterface|int|null
   *   Department term, or Department tid.
   *   NULL is nothing is found.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function getCommittee($load = TRUE) {
    if ($fieldCommittee = $this->getEntity()->get('field_os2web_m_committee')->first()) {
      if ($load) {
        return $fieldCommittee->get('entity')->getTarget()->getValue();
      }
      else {
        return $fieldCommittee->getValue()['target_id'];
      }
    }

    return NULL;
  }

  /**
   * Returns related bullet points.
   *
   * @param bool $load
   *   If the returned nodes shall be load. If FALSE, array of nids is returned.
   *
   * @return array
   *   If load is TRUE array of nodes is returned,
   *   If load is FALSE array of nids is returned,
   *   If field is empty, empty array is returned.
   */
  public function getBulletPoints($load = TRUE) {
    if ($fieldBps = $this->getEntity()->get('field_os2web_m_bps')) {
      if ($load) {
        return $fieldBps->referencedEntities();
      }
      else {
        return array_column($fieldBps->getValue(), 'target_id');
      }
    }

    return [];
  }

  /**
   * Returns bullet point having this ESDH ID.
   *
   * @param string $esdhId
   *   Expected ESDH ID of the entity.
   * @param bool $load
   *   If the returned node shall be load. If FALSE, nid is returned.
   *
   * @return \Drupal\Core\Entity\EntityInterface|int|null
   *   Bullet point node, or Bullet point nid.
   *   NULL is nothing is found.
   */
  public function getBulletPointByEsdhId($esdhId, $load = TRUE) {
    // Getting all BPA IDs of this bullet point.
    $bpIds = $this->getBulletPoints(FALSE);

    if (!empty($bpIds)) {
      $query = \Drupal::entityQuery('node')
        ->condition('nid', $bpIds, 'IN')
        ->condition('type', 'os2web_meetings_bp')
        ->condition('field_os2web_m_esdh_id', $esdhId);

      $nids = $query->execute();
      if (!empty($nids)) {
        $nid = reset($nids);
        return ($load) ? Node::load($nid) : $nid;
      }
    }

    return NULL;
  }

  /**
   * Sets addendum of a meeting.
   *
   * @param int $nid
   *   The nid of the meeting to become addendum of.
   * @param bool $save
   *   If meeting needs to be saved right away.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setAddendum($nid, $save = TRUE) {
    $this->getEntity()->set('field_os2web_m_addendum', $nid);
    if ($save) {
      $this->getEntity()->save();
    }
  }

}
