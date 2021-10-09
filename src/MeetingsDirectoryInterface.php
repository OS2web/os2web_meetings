<?php

namespace Drupal\os2web_meetings;

use Drupal\migrate\Event\ImportAwareInterface;

/**
 * Interface for MeetingDirectory source plugin.
 */
interface MeetingsDirectoryInterface extends ImportAwareInterface {

  /**
   * Agenda type Dagsorden.
   *
   * @var string
   */
  const AGENDA_TYPE_DAGSORDEN = 'Dagsorden';

  /**
   * Agenda type Referat.
   *
   * @var string
   */
  const AGENDA_TYPE_REFERAT = 'Referat';

  /**
   * Agenda type Kladde.
   *
   * @var string
   */
  const AGENDA_TYPE_KLADDE = 'Kladde';

  /**
   * Agenda access Open.
   *
   * @var int
   */
  const AGENDA_ACCESS_OPEN = 1;

  /**
   * Agenda access Closed.
   *
   * @var int
   */
  const AGENDA_ACCESS_CLOSED = 2;

  /**
   * Provides a path to meeting manifests.
   *
   * @return string
   *   Path to meeting manifests.
   */
  public function getMeetingsManifestPath();

  /**
   * Convert the agenda access to canonical format.
   *
   * This method intended to be implemented by ESDH plugin.
   *
   * @param array $source
   *   Raw array values from ESDH provider.
   *
   * @return int
   *   Agenda access as integer.
   */
  public function convertAgendaAccessToCanonical(array $source);

  /**
   * Convert the agenda type to canonical format.
   *
   * This method intended to be implemented by ESDH plugin.
   *
   * @param array $source
   *   Raw array values from ESDH provider.
   *
   * @return string
   *   Agenda type as string.
   */
  public function convertAgendaTypeToCanonical(array $source);

  /**
   * Convert the meeting start date to canonical format.
   *
   * This method intended to be implemented by ESDH plugin.
   *
   * @param array $source
   *   Raw array values from ESDH provider.
   *
   * @return int
   *   Start date as timestamp in UTC.
   */
  public function convertStartDateToCanonical(array $source);

  /**
   * Convert the meeting end date to canonical format.
   *
   * This method intended to be implemented by ESDH plugin.
   *
   * @param array $source
   *   Raw array values from ESDH provider.
   *
   * @return int
   *   Start date as timestamp in UTC.
   */
  public function convertEndDateToCanonical(array $source);

  /**
   * Convert the agenda document to canonical format.
   *
   * This method intended to be implemented by ESDH plugin.
   *
   * @param array $source
   *   Raw array values from ESDH provider.
   *
   * @return mixed
   *   Agenda document in canonical format:
   *   [
   *     'title' => 'Title of the document'
   *     'uri' => [relative path to file],
   *   ]
   */
  public function convertAgendaDocumentToCanonical(array $source);

  /**
   * Convert the committee raw data from ESDH into a canonical format.
   *
   * This method intended to be implemented by ESDH plugin.
   *
   * @param array $source
   *   Raw array values from ESDH provider.
   *
   * @return mixed
   *   Committee in canonical format:
   *   [
   *     'id' => '123'
   *     'name' => 'Name of the committee',
   *   ]
   */
  public function convertCommitteeToCanonical(array $source);

  /**
   * Convert the location raw data from ESDH into a canonical format.
   *
   * This method intended to be implemented by ESDH plugin.
   *
   * @param array $source
   *   Raw array values from ESDH provider.
   *
   * @return mixed
   *   Committee in canonical format:
   *   [
   *     'id' => 123'
   *     'name' => 'Name of the location',
   *   ]
   */
  public function convertLocationToCanonical(array $source);

  /**
   * Convert the bullet point raw data from ESDH into a canonical format.
   *
   * This method intended to be implemented by ESDH plugin.
   *
   * @param array $source
   *   Raw array values from ESDH provider.
   *
   * @return mixed
   *   Array of bullet points in canonical format:
   *   [
   *     0 => [
   *       'id' => 123,
   *       'number' => 1,
   *       'title' => 'Bullet point title',
   *       'access' => TRUE/FALSE, // TRUE is default
   *       'attachments' => [
   *           0 => [
   *             'id' => '456'
   *             'title' => 'Bullet title',
   *             'body' => 'Bullet body',
   *             'access' => TRUE/FALSE,
   *           ],
   *       ],
   *       'enclosures' => [
   *          0 => [
   *             'id' => '456'
   *             'title' => 'Bullet title',
   *             'uri' => [relative path to file],
   *             'access' => TRUE/FALSE,
   *          ],
   *       ],
   *     ],
   *     ...
   *   ]
   */
  public function convertBulletPointsToCanonical(array $source);

  /**
   * Convert the attachment raw data from ESDH into a canonical format.
   *
   * This method intended to be implemented by ESDH plugin.
   *
   * @param array $source
   *   Raw array values from ESDH provider.
   * @param bool $access
   *   Access boolean argument.
   *
   * @return mixed
   *   Array of attachments in canonical format:
   *   [
   *     0 => [
   *       'id' => 123,
   *       'title' => 'Attachment title',
   *       'body' => 'Attachment text', // can be empty
   *       'access' => TRUE/FALSE, // TRUE is default
   *     ],
   *     ...
   *   ]
   */
  public function convertAttachmentsToCanonical(array $source, $access = TRUE);

  /**
   * Convert the enclosure raw data from ESDH into a canonical format.
   *
   * This method intended to be implemented by ESDH plugin.
   *
   * @param array $source
   *   Raw array values from ESDH provider.
   *
   * @return mixed
   *   Array of attachments in canonical format:
   *   [
   *     0 => [
   *       'id' => 123,
   *       'title' => 'Enclosure title',
   *       'uri' => [meeting directory relative path to file],
   *       'access' => TRUE/FALSE, // TRUE is default
   *     ],
   *     ...
   *   ]
   */
  public function convertEnclosuresToCanonical(array $source);

  /**
   * Convert the agenda participants to canonical format.
   *
   * This method intended to be implemented by ESDH plugin.
   *
   * @param array $source
   *   Raw array values from ESDH provider.
   *
   * @return string
   *   Agenda type as string.
   */
  public function convertParticipantToCanonical(array $source);

  /**
   * Convert the agenda id to canonical format.
   *
   * This method intended to be implemented by ESDH plugin.
   *
   * @param array $source
   *   Raw array values from ESDH provider.
   *
   * @return string
   *   Agenda type as string.
   */
  public function convertAgendaIdToCanonical(array $source);

}
