<?php

namespace Drupal\opigno_module\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\opigno_module\Entity\OpignoAnswerInterface;

/**
 * Class OpignoAnswerController.
 *
 *  Returns responses for Answer routes.
 *
 * @package Drupal\opigno_module\Controller
 */
class OpignoAnswerController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Answer  revision.
   *
   * @param int $opigno_answer_revision
   *   The Answer  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionShow($opigno_answer_revision) {
    $opigno_answer = $this->entityManager()->getStorage('opigno_answer')->loadRevision($opigno_answer_revision);
    $view_builder = $this->entityManager()->getViewBuilder('opigno_answer');

    return $view_builder->view($opigno_answer);
  }

  /**
   * Page title callback for a Answer  revision.
   *
   * @param int $opigno_answer_revision
   *   The Answer  revision ID.
   *
   * @return string
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionPageTitle($opigno_answer_revision) {
    $opigno_answer = $this->entityManager()->getStorage('opigno_answer')->loadRevision($opigno_answer_revision);
    return $this->t('Revision of %title from %date', ['%title' => $opigno_answer->label(), '%date' => format_date($opigno_answer->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Answer .
   *
   * @param \Drupal\opigno_module\Entity\OpignoAnswerInterface $opigno_answer
   *   A Answer  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionOverview(OpignoAnswerInterface $opigno_answer) {
    $account = $this->currentUser();
    $langcode = $opigno_answer->language()->getId();
    $langname = $opigno_answer->language()->getName();
    $languages = $opigno_answer->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $opigno_answer_storage = $this->entityManager()->getStorage('opigno_answer');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $opigno_answer->label()]) : $this->t('Revisions for %title', ['%title' => $opigno_answer->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all answer revisions") || $account->hasPermission('administer answer entities')));
    $delete_permission = (($account->hasPermission("delete all answer revisions") || $account->hasPermission('administer answer entities')));

    $rows = [];

    $vids = $opigno_answer_storage->revisionIds($opigno_answer);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\opigno_module\Entity\OpignoAnswer $revision */
      $revision = $opigno_answer_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $opigno_answer->getRevisionId()) {
          $link = $this->l($date, new Url('entity.opigno_answer.revision', ['opigno_answer' => $opigno_answer->id(), 'opigno_answer_revision' => $vid]));
        }
        else {
          $link = $opigno_answer->link($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute('entity.opigno_answer.translation_revert', [
                'opigno_answer' => $opigno_answer->id(),
                'opigno_answer_revision' => $vid,
                'langcode' => $langcode,
              ]) :
              Url::fromRoute('entity.opigno_answer.revision_revert', [
                'opigno_answer' => $opigno_answer->id(),
                'opigno_answer_revision' => $vid,
              ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.opigno_answer.revision_delete', ['opigno_answer' => $opigno_answer->id(), 'opigno_answer_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['opigno_answer_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
