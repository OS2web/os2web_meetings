<?php

namespace Drupal\os2web_meetings_print\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\os2web_pagebuilder\Form\SettingsForm;

/**
 * Provides a 'OS2Web Meetings Document Download' block.
 *
 * @Block(
 *   id = "os2web_meetings_print_downment_download",
 *   admin_label = @Translation("OS2Web Meeting Document download")
 * )
 */
class MeetingDocumentDownload extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = NULL;

    /** @var \Drupal\node\NodeInterface $node */
    $node = \Drupal::routeMatch()->getParameter('node');

    if ($node && $node->bundle() == 'os2web_meetings_meeting') {
      $block = [
        '#markup' => $this->getMarkup($node),
      ];
    }

    return $block;
  }

  /**
   * Make block links markup.
   *
   * @param NodeInterface $meeting
   *   Meeting node.
   *
   * @return string
   *   Rendered HTML.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  private function getMarkup(NodeInterface $meeting) {
    $link_builder = \Drupal::service('printable.link_builder');
    $links = $link_builder->buildLinks($meeting);

    /** @var \Drupal\Core\Url $pdfUrl */
    $pdfUrl = $links['pdf']['url'];
    $pdfLink = Link::fromTextAndUrl(t('Download samlet dokument'), $pdfUrl)->toString();

    $output = '<span class="file file--mime-application-pdf file--application-pdf">' . $pdfLink . '</span>';

//    $output = '<ul class="related-links">';
//
//    /** @var \Drupal\node\NodeInterface $node */
//    foreach ($related_nodes as $node) {
//      $output .= '<li>';
//      $output .= $node->toLink()->toString();
//      $output .= '</li>';
//    }
//    $output .= '</ul>';

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url.path'];
  }

}
