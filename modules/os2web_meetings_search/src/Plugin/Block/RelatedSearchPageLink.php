<?php

namespace Drupal\os2web_meetings_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

/**
 * Provides OS2Web Meeting Search Related search page link.
 * @Block(
 *   id = "os2web_meetings_search_rel_search_page_link",
 *   admin_label = @Translation("OS2Web Meeting Search Related search page link"),
 * )
 */
class RelatedSearchPageLink extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $view_id = \Drupal::routeMatch()->getRouteObject()->getDefault('view_id');
    $display_id = \Drupal::routeMatch()->getRouteObject()->getDefault('display_id');

    $content_view_id = 'os2web_search';
    $meetings_view_id = 'os2web_meetings_search_page';

    if (isset($view_id) && isset($display_id)) {
      $searchQuery = \Drupal::request()->get('sq');

      $url = NULL;
      $urlText = NULL;

      if ($view_id == $content_view_id) {
        $url = Url::fromRoute("view.$meetings_view_id.os2web_meeetings_search_page")->setRouteParameter('sq', $searchQuery);
        $urlText = 'møder';
      }
      elseif ($view_id == $meetings_view_id) {
        $url = Url::fromRoute("view.$content_view_id.os2web_search_page")->setRouteParameter('sq', $searchQuery);
        $urlText = 'sider';
      }

      if ($url) {
        $link = $url->toString();
        $markup = Markup::create(t('Søg altå i <a href=":url">' . $urlText . '</a>', [
          ':url' => $link,
        ]));

        return [
          '#type' => 'markup',
          '#markup' => $markup,
        ];
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $tags[] = 'url.path';
    return $tags;
  }

}
