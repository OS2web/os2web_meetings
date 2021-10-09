<?php

namespace Drupal\os2web_meetings\EventSubscriber;

use Drupal\os2web_meetings\Entity\BulletPoint;
use Drupal\os2web_meetings\Entity\BulletPointAttachment;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber subscribing to KernelEvents::REQUEST.
 */
class MeetingContentRedirectSubscriber implements EventSubscriberInterface {

  /**
   * Redirects BP and BPA nodes to meeting node.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The subscribed event.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   */
  public function nodeRedirect(GetResponseEvent $event) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = NULL;
    $routeMatch = \Drupal::routeMatch();
    if ($routeMatch->getRouteName() == 'entity.node.canonical' && $node = $routeMatch->getParameter('node')) {
      $meetingNode = NULL;

      if ($node->getType() == 'os2web_meetings_bp') {
        $bulletPoint = new BulletPoint($node);
        $meetingNode = $bulletPoint->getMeeting();
      }
      elseif ($node->getType() == 'os2web_meetings_bpa') {
        $bpa = new BulletPointAttachment($node);
        $meetingNode = $bpa->getMeeting();
      }

      if ($meetingNode) {
        $response = new RedirectResponse($meetingNode->toUrl()->toString());
        $event->setResponse($response);
        $event->stopPropagation();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['nodeRedirect'];
    return $events;
  }

}
