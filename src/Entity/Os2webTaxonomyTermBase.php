<?php

namespace Drupal\os2web_meetings\Entity;

use Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException;
use Drupal\taxonomy\TermInterface;

/**
 * Abstract wrapper for OS2Web meeting terms.
 *
 * Allows to perform commonly used procedures in a more efficient way.
 */
abstract class Os2webTaxonomyTermBase {

  /**
   * The actual term entity.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $entity;

  /**
   * Os2webBase constructor.
   *
   * @param \Drupal\taxonomy\TermInterface $entity
   *   The term.
   *
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   */
  public function __construct(TermInterface $entity) {
    if ($entity->bundle() == $this->bundle()) {
      $this->entity = $entity;
    }
    else {
      throw new UnsupportedEntityTypeDefinitionException(sprintf('Expected entity of type "%s", "%s" given', $this->bundle(), $entity->bundle()));
    }
  }

  /**
   * Returns original term entity.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   Original term.
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
   * Saves the original term entity.
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
   * Return original bundle of the term.
   *
   * @return string
   *   Original entity bundle.
   */
  abstract public function bundle();

  /**
   * Loads OS2Web Entity by 'field_os2web_m_esdh_id' field.
   *
   * @param string $esdhId
   *   ESDH ID.
   *
   * @return \Drupal\os2web_meetings\Entity\Os2webTaxonomyTermBase|null
   *   OS2Web Entity or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  abstract public static function loadByEsdhId($esdhId);

}
