<?php

namespace Drupal\tft\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TFTController.
 */
class TFTController extends ControllerBase {

  /**
   * Format a folder or file link.
   *
   * @return array
   *   Render array with the formatted link
   */
  protected function link($item, $mime, $type = NULL) {
    if ($mime == 'folder') {
      return [
        'data' => [
          '#type' => 'link',
          '#title' => $item['name'],
          '#url' => Url::fromUri("internal:#term/{$item['id']}"),
          '#attributes' => [
            'class' => 'folder-folder-link',
            'id' => "tid-{$item['id']}",
          ],
        ],
      ];
    }
    elseif ($type && $type == 'record') {
      // Moxtra recording file.
      // Get the filefield icon.
      $icon_class = file_icon_class($mime);

      return [
        'data' => [
          '#type' => 'link',
          '#title' => $item['name'],
          '#url' => Url::fromUri("internal:/tft/download/file/{$item['id']}"),
          '#attributes' => [
            'class' => "file $icon_class",
            'target' => '_blank',
          ],
        ],
      ];
    }
    else {
      $media = Media::load($item['id']);
      $view_builder = \Drupal::entityManager()->getViewBuilder($media->getEntityTypeId());
      return [
        'data' => [
          $view_builder->view($media),
        ],
      ];
    }
  }

  /**
   * Return an <ul> with links for the current folder.
   *
   * @return array
   *   The render array
   */
  protected function operation_links($type, $id, $media = NULL, $gid = NULL) {
    $links = [];
    /** @var \Drupal\Core\TempStore\PrivateTempStore $tempstore */
    $tempstore = \Drupal::service('user.private_tempstore')->get('tft');
    $query = 'destination=' . $tempstore->get('q');

    switch ($type) {
      case 'folder':
        /** @var \Drupal\group\Entity\GroupInterface $group */
        $group = Group::load($gid);
        $user = $this->currentUser();
        $edit = FALSE;

        // Hide edit link if the user has no access.
        if ($user->hasPermission(TFT_ADD_TERMS)
          || ($group && $group->hasPermission(TFT_ADD_TERMS, $user))) {
          $edit = TRUE;
          $links[] = [
            '#type' => 'link',
            '#title' => $this->t("edit"),
            '#url' => Url::fromUri("internal:/tft/term/edit/$id?" . $query),
            '#attributes' => [
              'class' => 'ops-link term-edit-link',
            ],
          ];
        }

        if ($user->hasPermission(TFT_DELETE_TERMS)
          || ($group && $group->hasPermission(TFT_DELETE_TERMS, $user))) {
          if ($edit) {
            $links[] = [
              '#markup' => ' | ',
            ];
          }

          $links[] = [
            '#type' => 'link',
            '#title' => $this->t("delete"),
            '#url' => Url::fromUri("internal:/tft/term/delete/$id?" . $query),
            '#attributes' => [
              'class' => 'ops-link term-edit-link',
            ],
          ];
        }
        break;

      case 'file':
        /** @var \Drupal\media\Entity\Media $media */
        if ($media->access('update')) {
          $links[] = [
            '#type' => 'link',
            '#title' => $this->t("edit"),
            '#url' => Url::fromUri("internal:/media/$id/edit?" . $query),
            '#attributes' => [
              'class' => 'ops-link node-edit-link',
            ],
          ];

          $links[] = [
            '#markup' => ' | ',
          ];
        }

        $links[] = [
          '#type' => 'link',
          '#title' => $this->t("more info"),
          '#url' => Url::fromUri("internal:/media/$id"),
          '#attributes' => [
            'class' => 'ops-link',
          ],
        ];
        break;
    }

    return [
      'data' => $links,
    ];
  }

  /**
   * Returns folder content.
   *
   * @return array
   *   The folder content
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function get_content($tid, $gid = NULL) {
    $content = [];

    $elements = _tft_folder_content($tid, FALSE, $gid);

    foreach ($elements as $element) {
      if ($element['type'] == 'term') {
        $content[] = [
          $this->link($element, 'folder'),
          '',
          '',
          $this->t("Folder"),
          $this->operation_links('folder', $element['id'], NULL, $gid),
        ];
      }
      else {
        /** @var \Drupal\media\Entity\Media $media */
        $media = Media::load($element['id']);
        $user = $media->getOwner();
        $fids = $media->get('tft_file')->getValue();
        if (!empty($fids)) {
          $fid = reset($fids)['target_id'];
          $file = File::load($fid);

          $file_name = $file->getFilename();
          $file_name_parts = explode('.', $file_name);
          $file_extension = end($file_name_parts);

          $content[] = [
            $this->link($element, $file->getMimeType()),
            $user->getDisplayName(),
            date('d/m/Y H:i', $media->getChangedTime()),
            $this->t('@type file', [
              '@type' => strtoupper($file_extension),
            ]),
            $this->operation_links('file', $element['id'], $media, $gid),
          ];
        }
        elseif (!empty($link = $media->get('opigno_moxtra_recording_link')->getValue())) {
          $content[] = [
            $this->link($element, 'video/mp4', 'record'),
            $user->getDisplayName(),
            date('d/m/Y H:i', $media->getChangedTime()),
            $this->t('MP4 file'),
            $this->operation_links('file', $element['id'], $media, $gid),
          ];
        }
      }
    }

    // Fix error in jquery.tablesorter if table is empty.
    if (empty($elements)) {
      $content[] = [
        '',
        '',
        '',
        '',
        '',
      ];
    }

    return $content;
  }

  /**
   * Render the add file and add folder links.
   */
  protected function add_content_links($tid = 0, $gid = NULL) {
    $items = [];

    $tempstore = \Drupal::service('user.private_tempstore')->get('tft');
    $add_file_query = ['destination' => $tempstore->get('q')];
    $add_term_query = ['destination' => $tempstore->get('q')];

    // Do we have a tid ?
    if ($tid) {
      $add_file_query['tid'] = $tid;
      $add_term_query['parent'] = $tid;

      if (!$gid) {
        $gid = _tft_get_group_gid($tid);
      }
    }

    $group = Group::load($gid);
    $user = $this->currentUser();

    // Can the user create files ?
    if ($user->hasPermission('create media')) {
      // Can they add files in this context ?
      if ($user->hasPermission(TFT_ADD_FILE)
        || ($group && $group->hasPermission(TFT_ADD_FILE, $user))) {
        $query = UrlHelper::buildQuery(array_reverse($add_file_query));
        $items[] = [
          '#wrapper_attributes' => [
            'class' => 'folder-add-content-link',
          ],
          '#type' => 'link',
          '#title' => $this->t("Add a file"),
          '#url' => Url::fromUri("internal:/media/add/tft_file?$query"),
          '#attributes' => [
            'id' => 'add-child-file',
          ],
        ];
      }
    }

    // Can the user add terms anywhere, only under Group or never ?
    if ($user->hasPermission(TFT_ADD_TERMS)
      || ($group && $group->hasPermission(TFT_ADD_TERMS, $user))) {
      $query = UrlHelper::buildQuery(array_reverse($add_term_query));
      $items[] = [
        '#wrapper_attributes' => [
          'class' => 'folder-add-content-link',
        ],
        '#type' => 'link',
        '#title' => $this->t("Add a folder"),
        '#url' => Url::fromUri("internal:/tft/term/add?$query"),
        '#attributes' => [
          'id' => 'add-child-folder',
        ],
      ];
    }

    return [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#attributes' => [
        'id' => 'folder-add-content-links',
      ],
      '#items' => $items,
    ];
  }

  /**
   * Get the folder content in HTML table form.
   *
   * @return array
   *   The render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function content_table($tid, $gid = NULL) {
    $headers = [
      [
        'id' => 'table-th-name',
        'data' => $this->t('Name'),
      ],
      [
        'id' => 'table-th-loaded-by',
        'data' => $this->t('Loaded by'),
      ],
      [
        'id' => 'table-th-date',
        'data' => $this->t('Last modified'),
      ],
      [
        'id' => 'table-th-type',
        'data' => $this->t('Type'),
      ],
      [
        'id' => 'table-th-ops',
        'data' => $this->t('Operations'),
      ],
    ];

    return [
      [
        '#type' => 'table',
        '#header' => $headers,
        '#rows' => $this->get_content($tid, $gid),
      ],
      $this->add_content_links($tid, $gid),
    ];
  }

  /**
   * Return an <ul> with links for the current folder.
   *
   * @return array
   *   The render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function get_folder_operation_links($tid, $gid = NULL) {
    $items = [];

    // First link: got to parent.
    $parent_tid = _tft_get_parent_tid($tid);

    /** @var \Drupal\Core\TempStore\PrivateTempStore $tempstore */
    $tempstore = \Drupal::service('user.private_tempstore')->get('tft');
    $root_tid = $tempstore->get('root_tid');
    $query = 'destination=' . $tempstore->get('q');

    $disabled = FALSE;

    if ($parent_tid > -1 && $tid != $root_tid) {
      if (!_tft_term_access($parent_tid)) {
        $disabled = TRUE;
      }
    }
    else {
      $disabled = TRUE;
    }

    $class = $disabled ? 'disabled' : '';
    $fragment = $disabled ? '#' : "#term/$parent_tid";

    $items[] = [
      '#wrapper_attributes' => [
        'id' => 'tft-back',
        'class' => 'folder-menu-ops-link first',
      ],
      '#type' => 'link',
      '#title' => t("parent folder"),
      '#url' => Url::fromUri("internal:$fragment"),
      '#attributes' => [
        'class' => $class,
        'id' => 'tft-back-link',
      ],
    ];

    // Third link: reorder child terms.
    $uri = "/tft/terms/reorder/$tid?$query";
    $group = Group::load($gid);
    $user = $this->currentUser();

    if ($user->hasPermission(TFT_REORDER_TERMS)
      || ($group && $group->hasPermission(TFT_REORDER_TERMS, $user))) {
      $items[] = [
        '#wrapper_attributes' => [
          'id' => 'manage-folders',
          'class' => 'folder-menu-ops-link',
        ],
        '#type' => 'link',
        '#title' => $this->t("reorder elements"),
        '#url' => Url::fromUri('internal:' . $uri),
      ];
    }

    return [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#attributes' => [
        'class' => 'tabs primary',
        'id' => 'folder-menu-ops-links',
      ],
      '#items' => $items,
    ];
  }

  /**
   * File explorer.
   */
  protected function tft($tid = 'all', $gid = NULL) {
    if ($tid == 'all' || !(int) $tid) {
      if ($this->currentUser()->hasPermission(TFT_ACCESS_FULL_TREE)) {
        $tid = 0;
      }
      else {
        throw new AccessDeniedHttpException();
      }
    }

    // Check if the user has access to this tree.
    if (!_tft_term_access($tid)) {
      throw new AccessDeniedHttpException();
    }

    if ($tid) {
      $term = Term::load($tid);
      $name = $term->getName();
    }
    else {
      $name = $this->t("Root");
    }

    /** @var \Drupal\Core\TempStore\PrivateTempStore $tempstore */
    $tempstore = \Drupal::service('user.private_tempstore')->get('tft');

    // Store the URL query. Need the current path for some AJAX callbacks.
    $tempstore->set('q', \Drupal::service('path.current')->getPath());

    // Store the current term tid.
    $tempstore->set('root_tid', $tid);

    $path = drupal_get_path('module', 'tft');

    return [
      // Get the themed title bar.
      [
        '#theme' => 'tft_folder_menu',
        '#name' => $name,
        '#path' => $path,
        '#ops_links' => $this->get_folder_operation_links($tid, $gid),
      ],
      // Prepare the folder content area.
      [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'folder-content-container',
        ],
        'content' => $this::content_table($tid, $gid),
      ],
      // Add CSS and Javascript files.
      '#attached' => [
        'library' => [
          'tft/tft',
        ],
        'drupalSettings' => [
          'tftDirectory' => $path,
        ],
      ],
    ];
  }

  /**
   * Downloads file.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
   *   Response.
   */
  public function downloadFile(MediaInterface $media) {
    if (!empty($fids = $media->get('tft_file')->getValue())) {
      $fid = reset($fids)['target_id'];
      $file = File::load($fid);

      if (!$file) {
        throw new NotFoundHttpException();
      }

      if (!($file->access('view') && $file->access('download'))) {
        throw new AccessDeniedHttpException();
      }

      // Let other modules provide headers and control access to the file.
      $headers = $this->moduleHandler()->invokeAll('file_download', [$file->getFileUri()]);
      if (in_array(-1, $headers) || empty($headers)) {
        throw new AccessDeniedHttpException();
      }

      $file_name = $file->getFilename();
      $headers = [
        'Content-Type' => $file->getMimeType(),
        'Content-Disposition' => 'attachment; filename="' . $file_name . '"',
      ];

      if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
        $headers['Cache-Control'] = 'must-revalidate, post-check=0, pre-check=0';
        $headers['Pragma'] = 'public';
      }
      else {
        $headers['Pragma'] = 'no-cache';
      }

      return new BinaryFileResponse($file->getFileUri(), 200, $headers);
    }
    elseif (!empty($link = $media->get('opigno_moxtra_recording_link')->getValue())) {
      if (\Drupal::hasService('opigno_moxtra.opigno_api')) {
        /** @var \Drupal\opigno_moxtra\OpignoServiceInterface $opigno_api */
        $opigno_api = \Drupal::service('opigno_moxtra.opigno_api');
        $token = $opigno_api->getToken($media->getOwnerId());
        $url = $link[0]['uri'] . "&access_token=$token";
        return new TrustedRedirectResponse($url);
      }
    }

    throw new NotFoundHttpException();
  }

  /**
   * Returns directory list.
   *
   * @param \Drupal\taxonomy\TermInterface|null $taxonomy_term
   *   Term.
   *
   * @return array
   *   Render array.
   */
  public function listDirectory(TermInterface $taxonomy_term = NULL) {
    $tid = isset($taxonomy_term) ? $taxonomy_term->id() : 'all';
    return $this->tft($tid);
  }

  /**
   * Returns group list.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   *
   * @return array
   *   Render array.
   */
  public function listGroup(GroupInterface $group) {
    $tid = _tft_get_group_tid($group->id());

    if (!$tid) {
      return [
        '#markup' => $this->t("No term was found for this group ! Please contact your system administrator."),
      ];
    }

    return $this->tft($tid, $group->id());
  }

  /**
   * Returns folder access flag.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Access flag.
   */
  public function accessAjaxGetFolder(AccountInterface $account) {
    $tid = $_GET['tid'];
    $term = Term::load($tid);
    if (isset($term) && $term->access('view', $account)) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Returns folder.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Response.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function ajaxGetFolder() {
    $tid = $_GET['tid'];
    $gid = _tft_get_group_gid($tid);
    $renderer = \Drupal::service('renderer');

    $data = $this->content_table($tid, $gid);
    $ops_links = $this->get_folder_operation_links($tid, $gid);

    return new JsonResponse([
      'data' => $renderer->renderRoot($data),
      'parent' => _tft_get_parent_tid($tid, $gid),
      'ops_links' => $renderer->renderRoot($ops_links),
    ]);
  }

}
