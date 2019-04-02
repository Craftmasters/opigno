<?php

namespace Drupal\opigno_moxtra;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;

/**
 * Provides a list controller for opigno_moxtra_meeting entity.
 */
class MeetingListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['training'] = $this->t('Related training');
    $header['date'] = $this->t('Date');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\opigno_moxtra\MeetingInterface $entity */
    $row['id'] = $entity->id();
    $row['name'] = $entity->toLink(NULL, 'edit-form');

    $training = $entity->getTraining();
    $row['training'] = isset($training) ? $entity->getTraining()->toLink() : '-';

    $date = $entity->getStartDate();
    $row['date'] = !empty($date) ? $date : '-';

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $operations['score'] = [
      'title' => $this->t('Score'),
      'weight' => 10,
      'url' => Url::fromRoute('opigno_moxtra.score_meeting', ['opigno_moxtra_meeting' => $entity->id()],
        [
          'query' => ['destination' => 'admin/content/moxtra/meeting'],
          'absolute' => TRUE,
        ]),
    ];

    $gid = $entity->getTrainingId();
    if ($gid) {
      $tid = _tft_get_group_tid($gid);
      $elements = _tft_folder_content($tid, TRUE, $gid);

      if ($elements) {
        $recordings_folder_exists = FALSE;
        foreach ($elements as $element) {
          if ($element['name'] == 'Recorded Live Meetings') {
            $tid = $element['id'];
            $recordings_folder_exists = TRUE;
            break;
          }
        }

        if ($recordings_folder_exists) {
          $elements = _tft_folder_content($tid, FALSE, $gid);
          if ($elements) {
            foreach ($elements as $element) {
              if ($element['type'] != 'term') {
                $media = Media::load($element['id']);
                if ($media && $media->get('opigno_moxtra_recording_link')->getValue()) {
                  $operations['download'] = [
                    'title' => $this->t('Download'),
                    'weight' => 11,
                    'url' => Url::fromUri("internal:/tft/download/file/{$media->id()}"),
                  ];
                }
              }
            }
          }
        }
      }
    }

    return $operations;
  }

}
