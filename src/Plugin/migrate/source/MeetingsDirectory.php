<?php

namespace Drupal\os2web_meetings\Plugin\migrate\source;

use Drupal\Core\File\FileSystemInterface;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\source\Url;
use Drupal\node\Entity\Node;
use Drupal\os2web_meetings\Entity\BulletPoint;
use Drupal\os2web_meetings\Entity\Committee;
use Drupal\os2web_meetings\Entity\Location;
use Drupal\os2web_meetings\Entity\Meeting;
use Drupal\os2web_meetings\Form\SettingsForm;
use Drupal\os2web_meetings\MeetingsDirectoryInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;

/**
 * Source plugin for retrieving data via URLs.
 */
abstract class MeetingsDirectory extends Url implements MeetingsDirectoryInterface {

  /**
   * Array of committees IDs that are allowed to be imported.
   *
   * @var array
   */
  protected $committeesWhitelist;

  /**
   * If closed agenda is allowed to be imported.
   *
   * @var bool
   */
  protected $importClosedAgenda;

  /**
   * Closed bullet point title prefix.
   *
   * @var string
   */
  protected $closedBulletPointTitlePrefix;

  /**
   * Standard content for closed bullet points.
   *
   * @var string
   */
  protected $closedBulletPointBodyText;

  /**
   * If missing agendas meeting nodes need to be unpublished.
   *
   * @var bool
   */
  protected $unpublishMissingAgendas;

  /**
   * If enclosures shall be processed as attachments.
   *
   * @var bool
   */
  protected $processEnclosuresAsAttachments;

  /**
   * List of meetings that will be unpublished after the import process.
   *
   * @var array
   *   Structured as [
   *     'agenda_esdh_id' => loaded meeting node,
   *     ...
   *   ]
   */
  protected $unpublishScheduledMeetings;

  /**
   * List of HTML tags that needs to be clear (style attribute removed).
   *
   * @var array
   */
  protected $clearHtmlTagsList;

  /**
   * If replace multiple concurrent non-breakable-space with a single one.
   *
   * @var bool
   */
  protected $replaceMultipleNbsp;

  /**
   * If replace empty Paragraphs with br-tag.
   *
   * @var bool
   */
  protected $replaceEmptyParagraphs;

  /**
   * Maximum allowed amount of sequential br-tags.
   *
   * @var int
   */
  protected $maxSequentialBr;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    // Code below is almost identical copy from "migrate_directory" module with
    // very few modifications.
    // Always get UNIX paths, skipping . and .., key as filename, and follow
    // links.
    $flags = \FilesystemIterator::UNIX_PATHS |
      \FilesystemIterator::SKIP_DOTS |
      \FilesystemIterator::KEY_AS_FILENAME |
      \FilesystemIterator::FOLLOW_SYMLINKS;

    // Recurse through the directory.
    $path = $this->getMeetingsManifestPath();
    $files = new \RecursiveDirectoryIterator($path, $flags);

    // A filter could be added here if necessary.
    if (!empty($configuration['pattern'])) {
      $pattern = $configuration['pattern'];

      $filter = new \RecursiveCallbackFilterIterator($files, function ($current, $key, $iterator) use ($pattern) {

        // Get the current item's name.
        /** @var \SplFileInfo $current */
        $filename = $current->getFilename();

        if ($current->isDir()) {
          // Always descend into directories.
          return TRUE;
        }

        // Match the filename against the pattern.
        return preg_match($pattern, $filename) === 1;
      });
    }
    else {
      $filter = $files;
    }

    // Get an iterator of our iterator...
    $iterator = new \RecursiveIteratorIterator($filter);
    // ...because we need to get the path and filename of each item...
    /** @var \SplFileInfo $fileinfo */
    $urls = [];
    foreach ($iterator as $fileinfo) {
      $urls[] = $fileinfo->getPathname();
    }

    $configuration['urls'] = $urls;

    // Save committees whitelist.
    $settingFormConfig = \Drupal::config(SettingsForm::$configName);
    $committeesWhitelistSettings = $settingFormConfig->get('committee_whitelist');
    $this->committeesWhitelist = !empty($committeesWhitelistSettings) ? explode(',', $committeesWhitelistSettings) : [];

    $this->importClosedAgenda = $settingFormConfig->get('import_closed_agenda');
    $this->closedBulletPointTitlePrefix = $settingFormConfig->get('closed_bp_title_prefix');
    $this->closedBulletPointBodyText = $settingFormConfig->get('closed_bp_body_text');
    $this->unpublishMissingAgendas = $settingFormConfig->get('unpublish_missing_agendas');
    $this->processEnclosuresAsAttachments = $settingFormConfig->get('process_enclosures_as_attachments');
    $this->clearHtmlTagsList = str_getcsv($settingFormConfig->get('clear_html_tags_list'));
    $this->replaceMultipleNbsp = $settingFormConfig->get('replace_multiple_nbsp');
    $this->replaceEmptyParagraphs = $settingFormConfig->get('replace_empty_paragraphs');
    $this->maxSequentialBr = $settingFormConfig->get('max_sequential_br') ?? 1;
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * {@inheritDoc}
   */
  public function prepareRow(Row $row) {
    // Always settings URL as empty array, so that this value is not used for
    // row hashing.
    $row->setSourceProperty('urls', []);

    $source = $row->getSource();

    $agendaId = $this->convertAgendaIdToCanonical($source);
    $row->setSourceProperty('agenda_id', $agendaId);
    // Removing meeting from a list of meeting scheduled to be unpublished.
    if ($this->unpublishMissingAgendas) {
      unset($this->unpublishScheduledMeetings[$agendaId]);
    }

    $result = parent::prepareRow($row);
    // If this row is to be skipped, return the result right away.
    if (!$result) {
      return $result;
    }
    // TODO: meeting skipping, meeting updating (agenda->referat etc)
    // Check if the current meeting needs creating updating.
    if (!$row->getIdMap() || $row->needsUpdate() || $this->aboveHighwater($row) || $this->rowChanged($row)) {
      print_r(PHP_EOL . 'Importing meeting: ' . $agendaId . PHP_EOL);

      // Setting meeting source ID.
      $row->setDestinationProperty('field_os2web_m_source', $this->getPluginId());

      $meetingDirectoryPath = $row->getSourceProperty('directory_path');

      // Process agenda access.
      $agendaAccessCanonical = $this->convertAgendaAccessToCanonical($source);
      // Skipping closed agendas.
      if (!$this->importClosedAgenda && $agendaAccessCanonical != MeetingsDirectoryInterface::AGENDA_ACCESS_OPEN) {
        return FALSE;
      }
      // Process committee.
      $committeeCanonical = $this->convertCommitteeToCanonical($source);

      // Skip if committee is not whitelisted.
      if (!empty($this->committeesWhitelist)) {
        if (!in_array($committeeCanonical['id'], $this->committeesWhitelist)) {
          return FALSE;
        }
      }
      $committeeTarget = $this->processCommittee($committeeCanonical);
      $row->setSourceProperty('committee_target', $committeeTarget);
      $meeting = Meeting::loadByEsdhId($agendaId);

      // Process agenda type.
      $agendaTypeCanonical = $this->convertAgendaTypeToCanonical($source);
      $row->setSourceProperty('agenda_type', $agendaTypeCanonical);

      // Skip Draft/Kladde status.
      if ($agendaTypeCanonical == MeetingsDirectory::AGENDA_TYPE_KLADDE) {
        return FALSE;
      }

      // Process start date.
      $startDateCanonical = $this->convertStartDateToCanonical($source);
      $row->setSourceProperty('meeting_start_date', $startDateCanonical);

      // Process end date.
      $endDateCanonical = $this->convertEndDateToCanonical($source);
      $row->setSourceProperty('meeting_end_date', $endDateCanonical);

      // Process agenda document.
      $agendaDocumentCanonical = $this->convertAgendaDocumentToCanonical($source);
      $agendaDocumentTarget = $this->processAgendaDocument($agendaDocumentCanonical, $meetingDirectoryPath);
      $row->setSourceProperty('agenda_document', $agendaDocumentTarget);

      // Process location.
      $locationCanonical = $this->convertLocationToCanonical($source);
      if (!empty($locationCanonical)) {
        $locationTarget = $this->processLocation($locationCanonical);
        $row->setSourceProperty('location_target', $locationTarget);
      }

      // Process bullet points.
      $bulletPointsCanonical = $this->convertBulletPointsToCanonical($source);
      $bulletPointTargets = $this->processBulletPoints($bulletPointsCanonical, $meetingDirectoryPath, $meeting);
      $row->setSourceProperty('bullet_points_targets', $bulletPointTargets);

      // Process participants.
      $participantsCanonical = $this->convertParticipantToCanonical($source);
      if (!empty($participantsCanonical['participants'])) {
        $row->setSourceProperty('participants', implode(',', $participantsCanonical['participants']));
      }
      if (!empty($participantsCanonical['participants_canceled'])) {
        $row->setSourceProperty('cancel_participants', implode(',', $participantsCanonical['participants_canceled']));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preImport(MigrateImportEvent $event) {
    if ($this->unpublishMissingAgendas) {
      // Collect all meetings agenda ESDH ID, and schedule them for
      // unpublishing.
      // The meetings will be removed from that list if they are present in
      // prepareRow.
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'os2web_meetings_meeting');
      $query->condition('status', 1);
      $query->condition('field_os2web_m_source', $this->getPluginId());
      $nids = $query->execute();

      $meetingNodes = Node::loadMultiple($nids);

      $meetings = [];

      foreach ($meetingNodes as $meetingNode) {
        $meeting = new Meeting($meetingNode);
        $meetings[$meeting->getEsdhId()] = $meeting->getEntity();
      }

      $this->unpublishScheduledMeetings = $meetings;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postImport(MigrateImportEvent $event) {
    if ($this->unpublishMissingAgendas) {
      // Unpublish all meeting node that weren't removed from a list.
      // It means there was no manifest for them.
      if (!empty($this->unpublishScheduledMeetings)) {
        foreach ($this->unpublishScheduledMeetings as $unpublishScheduledMeeting) {
          $unpublishScheduledMeeting->setPublished(FALSE);
          $unpublishScheduledMeeting->save();
        }
      }
    }
  }

  /**
   * Handles the agenda document creation.
   *
   * @param array $agendaDocumentCanonical
   *   Agenda document data in a caninical format.
   * @param string $directoryPath
   *   Directory of meeting XML file.
   *
   * @return array
   *   Array of agenda document, like entity reference targets:
   *   [
   *     0 => [
   *       'target_id' => 1,
   *     ],
   *   ]
   *
   * @see \Drupal\os2web_meetings\Plugin\migrate\source\MeetingsDirectory::convertAgendaDocumentToCanonical()
   */
  protected function processAgendaDocument(array $agendaDocumentCanonical, $directoryPath) {
    $uri = $directoryPath . '/' . $agendaDocumentCanonical['uri'];
    $title = $agendaDocumentCanonical['title'];

    $documentFile = $this->createFileCopyAsManaged($uri, $title);

    return ($documentFile) ? ['target_id' => $documentFile->id()] : [];
  }

  /**
   * Handles finding the committee or creating it.
   *
   * @param array $committeeCanonical
   *   Committee in canonical format.
   *
   * @return array
   *   Array of entity reference target:
   *   [
   *     'target_id' => 1,
   *   ]
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see \Drupal\os2web_meetings\Plugin\migrate\source\MeetingsDirectory::convertCommitteeToCanonical()
   */
  protected function processCommittee(array $committeeCanonical) {
    $id = $committeeCanonical['id'];
    $name = $committeeCanonical['name'];

    /** @var \Drupal\os2web_meetings\Entity\Committee $committee */
    $committee = Committee::loadByEsdhId($id);

    if (!$committee) {
      $committee = Term::create([
        'vid' => 'os2web_m_committee',
        'name' => $name,
        'field_os2web_m_esdh_id' => $id,
      ]);
    }
    else {
      $committee = $committee->getEntity();
      $committee->setName($name);
    }

    $committee->save();

    return [
      'target_id' => $committee->id(),
    ];
  }

  /**
   * Handles finding the committee or creating it.
   *
   * @param array $locationCanonical
   *   Committee incanonical format.
   *
   * @return array
   *   Array of entity reference target:
   *   [
   *     'target_id' => 1,
   *   ]
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see \Drupal\os2web_meetings\Plugin\migrate\source\MeetingsDirectory::convertLocationToCanonical()
   */
  protected function processLocation(array $locationCanonical) {
    $id = $locationCanonical['id'];
    $name = $locationCanonical['name'];

    /** @var \Drupal\os2web_meetings\Entity\Location $location */
    $location = Location::loadByEsdhId($id);

    if (!$location) {
      $location = Term::create([
        'vid' => 'os2web_m_location',
        'name' => $name,
        'field_os2web_m_esdh_id' => $id,
      ]);
    }
    else {
      $location = $location->getEntity();
      $location->setName($name);
    }

    $location->save();

    return [
      'target_id' => $location->id(),
    ];
  }

  /**
   * Handles finding the bullet points or creating them.
   *
   * @param array $bulletPoints
   *   Bullet points in canonical format.
   * @param string $directoryPath
   *   Directory of meeting XML file.
   * @param \Drupal\os2web_meetings\Entity\Meeting|null $meeting
   *   Parent meeting.
   *
   * @return array
   *   Array of bullet point nodes, like entity reference targets:
   *   [
   *     0 => [
   *       'target_id' => 1,
   *     ],
   *     ...
   *   ]
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   *
   * @see \Drupal\os2web_meetings\Plugin\migrate\source\MeetingsDirectory::convertBulletPointsToCanonical()
   */
  protected function processBulletPoints(array $bulletPoints, $directoryPath, $meeting = NULL) {
    $bulletPointsTargets = [];
    $settingFormConfig = \Drupal::config(SettingsForm::$configName);

    $textBeforeBpNumber = $settingFormConfig->get('text_before_bp_number');
    $dotAfterBpNumber = $settingFormConfig->get('dot_after_bp_number');

    foreach ($bulletPoints as $bulletPoint) {
      $id = $bulletPoint['id'];
      $number = $bulletPoint['number'];
      $title = $bulletPoint['title'];
      // If access is not set explicitly, consider it as open.
      $access = $bulletPoint['access'] ?? TRUE;
      $attachments = $bulletPoint['attachments'];
      $enclosures = $bulletPoint['enclosures'];
      $enclosure_targets = [];
      $bpa_targets = [];

      /** @var \Drupal\node\NodeInterface $bp */
      $bp = NULL;
      if ($meeting) {
        $bp = $meeting->getBulletPointByEsdhId($id);
      }

      if (!$bp) {
        $bp = Node::create([
          'type' => 'os2web_meetings_bp',
          'field_os2web_m_esdh_id' => $id,
          'title' => $title,
        ]);
        try {
          $bp->save();
        }
        catch (\Exception $e) {
          \Drupal::logger('os2web_meeting')->warning($this->t('Cannot save BP: %error', ['%error' => $e->getMessage()]));
          continue;
        }
      }
      else {
        $bp->setTitle($title);
      }

      if ($this->importClosedAgenda || $access === TRUE) {
        // Processing enclosures.
        if ($this->processEnclosuresAsAttachments) {
          $attachments = array_merge($attachments, $enclosures);
          $enclosure_targets = [];
        }
        else {
          $enclosure_targets = $this->processEnclosures($enclosures, $directoryPath);
        }

        // Processing attachments.
        $os2webBp = new BulletPoint($bp);
        $bpa_targets = $this->processAttachments($attachments, $directoryPath, $os2webBp);
      }
      else {
        $title = $bp->getTitle();
        if ($titlePrefix = $this->closedBulletPointTitlePrefix) {
          $bp->setTitle($titlePrefix . ' ' . $title);
        }
        // Set closed bullet point text.
        $bp->set('body', $this->closedBulletPointBodyText);
      }

      // Setting fields.
      if (isset($number)) {
        $title = $bp->getTitle();

        $titlePrefix = '';

        // Adding text before number.
        if (!empty($textBeforeBpNumber)) {
          $titlePrefix = "$textBeforeBpNumber ";
        }

        // Adding number.
        $titlePrefix .= $number;

        // Adding dot after number.
        if ($dotAfterBpNumber) {
          $titlePrefix .= ".";
        }

        $bp->setTitle("$titlePrefix $title");
      }
      $bp->set('field_os2web_m_bp_enclosures', $enclosure_targets);
      $bp->set('field_os2web_m_bp_bpas', $bpa_targets);
      $bp->set('field_os2web_m_bp_closed', ['value' => !$access]);

      try {
        $bp->save();

        $bulletPointsTargets[] = [
          'target_id' => $bp->id(),
        ];
      }
      catch (\Exception $e) {
        \Drupal::logger('os2web_meeting')->warning($this->t('Cannot save BP: %error', ['%error' => $e->getMessage()]));
      }
    }

    // TODO think about deleting the BPs.
    return $bulletPointsTargets;
  }

  /**
   * Handles finding the bullets or creating them.
   *
   * @param array $enclosures
   *   Bullet point enclosures in canonical format.
   * @param string $directoryPath
   *   Directory of meeting XML file.
   * @param \Drupal\os2web_meetings\Entity\BulletPoint|null $bulletPoint
   *   Parent bullet point.
   *
   * @return array
   *   Array of bullet point attachment nodes, like entity reference targets:
   *   [
   *     0 => [
   *       'target_id' => 1,
   *     ],
   *     ...
   *   ]
   *
   * @see \Drupal\os2web_meetings\Plugin\migrate\source\MeetingsDirectory::convertAttachmentsToCanonical()
   */
  protected function processEnclosures(array $enclosures, $directoryPath, $bulletPoint = NULL) {
    $enclosureTargets = [];

    foreach ($enclosures as $enclosure) {
      $title = $enclosure['title'];
      $uri = $enclosure['uri'];
      // If access is not set explicitly, consider it as open.
      $access = $enclosure['access'] ?? TRUE;

      // Handling closed content.
      if (!$this->importClosedAgenda && $access === FALSE) {
        continue;
      }

      $enclosure = NULL;

      if ($bulletPoint) {
        $enclosure = $bulletPoint->getEnclosureByName($title);
      }

      // Creating enclosure file.
      if (!$enclosure && $uri) {
        $absoluteUri = $directoryPath . '/' . $uri;
        $enclosure = $this->createFileCopyAsManaged($absoluteUri);
      }

      if ($enclosure) {
        $enclosureTargets[] = [
          'target_id' => $enclosure->id(),
          'description' => $title,
        ];
      }
    }

    // TODO think about deleting the enclosures.
    return $enclosureTargets;
  }

  /**
   * Handles finding the bullets or creating them.
   *
   * @param array $attachments
   *   Bullet point attachments in canonical format.
   * @param string $directoryPath
   *   Directory of meeting XML file.
   * @param \Drupal\os2web_meetings\Entity\BulletPoint $bulletPoint
   *   Parent bullet point.
   *
   * @return array
   *   Array of bullet point attachment nodes, like entity reference targets:
   *   [
   *     0 => [
   *       'target_id' => 1,
   *     ],
   *     ...
   *   ]
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see \Drupal\os2web_meetings\Plugin\migrate\source\MeetingsDirectory::convertAttachmentsToCanonical()
   */
  protected function processAttachments(array $attachments, $directoryPath, BulletPoint $bulletPoint) {
    $bpaTargets = [];
    foreach ($attachments as $attachment) {
      $id = $attachment['id'];
      $title = $attachment['title'];
      $body = $this->cleanHtml($attachment['body']);
      $body = $this->fixImagePaths($body, $directoryPath);
      $uri = $attachment['uri'] ?? NULL;

      // If access is not set explicitly, consider it as open.
      $access = $attachment['access'] ?? TRUE;

      // Handling closed content.
      if (!$this->importClosedAgenda && $access === FALSE) {
        continue;
      }

      $bpa = $bulletPoint->getBulletPointAttachmentByEsdhId($id);

      if (!$bpa) {
        $bpa = Node::create([
          'type' => 'os2web_meetings_bpa',
          'field_os2web_m_esdh_id' => $id,
          'title' => $title,
        ]);
      }
      else {
        $bpa->setTitle($title);
      }

      // Setting fields.
      $bpa->set('body', ['value' => $body, 'format' => 'wysiwyg_tekst']);

      // Handling attachment file.
      if ($uri) {
        $absoluteUri = $directoryPath . '/' . $uri;
        $attachmentFile = $this->createFileCopyAsManaged($absoluteUri);
        if ($attachmentFile) {
          $bpa->set('field_os2web_m_bpa_file', ['target_id' => $attachmentFile->id()]);
        }
      }

      try {
        $bpa->save();

        $bpaTargets[] = [
          'target_id' => $bpa->id(),
        ];
      }
      catch (\Exception $e) {
        \Drupal::logger('os2web_meeting')->warning($this->t('Cannot save BPA: %error', ['%error' => $e->getMessage()]));
      }
    }

    // TODO think about deleting the BPAs.
    return $bpaTargets;
  }

  /**
   * Creates a copy of the file and adds it to file_managed.
   *
   * The file copy will always have name as original_file_name.pdf =>
   * original_file_name_0.pdf.
   * In this way maximum number of copies is 1 at all times.
   * Any new file copy would replace the existing one.
   *
   * @param string $uri
   *   Uri to the file.
   * @param string|null $title
   *   The desired name of the new file.
   *
   * @return \Drupal\file\FileInterface|false
   *   Managed file or FALSE.
   */
  protected function createFileCopyAsManaged($uri, $title = NULL) {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    $basename = $file_system->basename($uri);
    $dirname = dirname($uri);

    // A URI or path may already have a trailing slash or look like
    // "public://".
    if (substr($dirname, -1) == '/') {
      $separator = '';
    }
    else {
      $separator = '/';
    }

    // Making sure that name does not contain extension.
    $pos = strrpos($basename, '.');
    if ($pos !== FALSE) {
      $name = substr($basename, 0, $pos);
      $ext = substr($basename, $pos);
    }
    else {
      $name = $basename;
      $ext = '';
    }
    $original_name = $name . $ext;
    // If the desired title is provided, use it. Otherwise take the original
    // title and concat '_0'.
    if ($title) {
      $name = $title;
    }
    else {
      $name .= '_0';
    }

    // We always create copy in the same way. In this way maximum number of
    // copies is 1 at all times. Any new file copy would replace the existing
    // one.
    $copyUri = $dirname . $separator . $name . $ext;

    $managedFile = FALSE;
    $settingFormConfig = \Drupal::config(SettingsForm::$configName);
    $createFilesCopy = $settingFormConfig->get('create_files_copy');
    try {
      if ($createFilesCopy) {
        $unmanagedFilePath = $file_system->copy($uri, $copyUri, FileSystemInterface::EXISTS_REPLACE);

        $data = file_get_contents($unmanagedFilePath);
        $managedFile = file_save_data($data, $unmanagedFilePath, FileSystemInterface::EXISTS_REPLACE);
      }
      else {
        $current_user = \Drupal::currentUser();
        $managedFile = File::create([
          'uid' => $current_user->id(),
          'filename' => $original_name,
          'uri' => $uri,
          'status' => 1,
        ]);
        $managedFile->save();
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('os2web_meeting')->warning($e->getMessage());
    }

    return $managedFile;
  }

  /**
   * Wrapper function for making HTML cleaner.
   *
   * @param string $html
   *   Input HTML text.
   *
   * @return string
   *   Cleaned HTML.
   */
  protected function cleanHtml($html) {
    $html = $this->clearHtmlTags($html);

    if ($this->replaceMultipleNbsp) {
      // Replace multiple &nbsp; tags with single one.
      $html = preg_replace('/(\x{00A0}|&nbsp;){2,}/u', '&nbsp;', $html);
    }

    if ($this->replaceEmptyParagraphs) {
      // Replace all <p></p> and <p>&nbsp;</p> tags with <br/>.
      $html = preg_replace('#(<p( )*?>((<span>)*?)(|&nbsp;)((<\/span>)*?)<\/p>\s*)+#i', '<br/>', $html);
    }

    $replaceThreshold = $this->maxSequentialBr + 1;
    // Replace multiple <br> tags, if their sequential number is above limit.
    // Counts also <br>, <br/> and <br />. Case insensitive.
    $html = preg_replace('/(\<br\>|\<br\/\>|\<br\ \/\>){' . $replaceThreshold . ',}/i', '', $html);

    return $html;
  }

  /**
   * Removes the style attributes from the tags in provided HTML string.
   *
   * @param string $html
   *   The HTML string.
   *
   * @return string
   *   HTML string with tag style attributes removed.
   */
  protected function clearHtmlTags($html) {
    if (!empty($this->clearHtmlTagsList)) {
      foreach ($this->clearHtmlTagsList as $tag) {
        $tag = trim($tag);

        if (empty($tag)) {
          continue;
        }

        // Removing tag style attribute.
        preg_match_all('#<' . $tag . '(.*?)>#is', $html, $matches);
        foreach ($matches[0] as $match) {
          $filtered_tag = preg_replace('#\sstyle="(.*?)"#is', "", $match);
          $html = str_replace($match, $filtered_tag, $html);
        }

      }
    }

    return $html;
  }

  /**
   * Converts relative image paths to absolute.
   *
   * If image is located in a private directory it will be copied to a public
   * directory 'os2web_meeting_images';
   *
   * @param string $html
   *   The HTML string.
   * @param string $meetingDirectoryPath
   *   Path to the meeting directory.
   *
   * @return string
   *   HTML string with image paths converted to absolute.
   */
  protected function fixImagePaths($html, $meetingDirectoryPath) {
    /** @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager */
    $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');

    preg_match_all('/src="([^"]+)"/', $html, $matches);

    foreach ($matches[1] as $path) {
      $relativeUri = $meetingDirectoryPath . "/" . $path;
      $imageRealUrl = NULL;

      $scheme = $stream_wrapper_manager::getScheme($relativeUri);

      // The image is in the private scheme, copy it to public directory.
      if ($scheme == 'private') {
        $relativeUri = $this->copyPrivateImageToPublic($relativeUri);
      }

      // If file does not exist, remove image path.
      if (!file_exists($relativeUri)) {
        $html = str_replace($path, "", $html);
      }
      else {
        $imageRealUrl = file_create_url($relativeUri);
        $html = str_replace($path, $imageRealUrl, $html);
      }
    }

    $html = preg_replace('/<img([^>]+)src=""([^>]*)>/', "", $html);
    return $html;
  }

  /**
   * Copies the file from private directory to a public one.
   *
   * Public directory name is 'os2web_meetings_images', and the file destination
   * directory structure mimics the file source directory structure.
   *
   * If file is located in private://sbsys/Meeting 1/Agenda 1/Images/file.jpg,
   * it wil be copied to
   * public://os2web_meetings_images/sbsys/Meeting 1/Agenda 1/Images/file.jpg.
   *
   * @param string $sourceUri
   *   Relative uri of the source file.
   *
   * @return string|null
   *   Relative uri of the destination file.
   *   NULL if file could not be copied for some reasons.
   */
  protected function copyPrivateImageToPublic($sourceUri) {
    $destinationUri = NULL;

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    $copyDestination = str_replace('private://', 'public://os2web_meetings_images/', $sourceUri);

    // Forcing destination folders to be created.
    $copyDestinationDir = $file_system->dirname($copyDestination);
    if ($file_system->prepareDirectory($copyDestinationDir, FileSystemInterface::CREATE_DIRECTORY)) {
      // Copying the file to public directory.
      try {
        $destinationUri = $file_system->copy($sourceUri, $copyDestination);
      }
      catch (\Exception $e) {
        \Drupal::logger('os2web_meetings')
          ->warning(t('Image cannot be copied from @source to @destination. Message: @message', [
            '@source' => $sourceUri,
            '@destination' => $copyDestination,
            '@message' => $e->getMessage(),
          ]));
      }
    }
    else {
      \Drupal::logger('os2web_meetings')
        ->warning(t('Image cannot be copied from @source to @destination. Destination folder cannot be created', [
          '@source' => $sourceUri,
          '@destination' => $copyDestination,
        ]));
    }

    return $destinationUri;
  }

}
