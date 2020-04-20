<?php

namespace Drupal\os2web_meetings\Entity;

use Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException;
use Drupal\node\NodeInterface;

/**
 * Abstract wrapper for OS2Web meeting Nodes.
 *
 * Allows to perform commonly used procedures in a more efficient way.
 */
abstract class Os2webNodeBase {

  /**
   * The actual node entity.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $entity;

  /**
   * Os2webBase constructor.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   The node.
   *
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   */
  public function __construct(NodeInterface $entity) {
    if ($entity->getType() == $this->getEntityType()) {
      $this->entity = $entity;
    }
    else {
      throw new UnsupportedEntityTypeDefinitionException(sprintf('Expected entity of type "%s", "%s" given', $this->getEntityType(), $entity->getType()));
    }
  }

  /**
   * Returns original node entity.
   *
   * @return \Drupal\node\NodeInterface
   *   Original node.
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   * Returns original node entity id.
   *
   * @return int
   *   Original node id.
   */
  public function id() {
    return $this->entity->id();
  }

  /**
   * Saves the original node entity.
   *
   * @return int
   *   Output of EntityInterface save function.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see \Drupal\Core\Entity\EntityInterface::save()
   */
  public function save() {
    return $this->entity->save();
  }

  /**
   * Return original entity expected type.
   *
   * @return string
   *   Original entity expected type.
   */
  abstract public function getEntityType();

  /**
   * Loads OS2Web Entity by 'field_os2web_m_esdh_id' field.
   *
   * @param string $esdhId
   *   ESDH ID.
   *
   * @return \Drupal\os2web_meetings\Entity\Os2webNodeBase|null
   *   OS2Web Entity or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  abstract public static function loadByEsdhId($esdhId);

  /**
   * Gets ESDH ID of an entity.
   */

  /**
   * Gets ESDH ID of an entity.
   *
   * @return string
   *   Entity ESDH ID.
   */
  public function getEsdhId() {
    return $this->getEntity()->get('field_os2web_m_esdh_id')->value;
  }

}
