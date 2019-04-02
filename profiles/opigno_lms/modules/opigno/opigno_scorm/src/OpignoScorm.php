<?php

namespace Drupal\opigno_scorm;

use Drupal\Core\Database\Connection;
use Drupal\file\Entity\File;

/**
 * Class OpignoScorm.
 */
class OpignoScorm {

  protected $database;

  /**
   * OpignoScorm constructor.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Extract Scorm data from Scorm package.
   */
  public function scormExtract(File $file) {
    $path = \Drupal::service('file_system')->realpath($file->getFileUri());
    $zip = new \ZipArchive();
    $result = $zip->open($path);
    if ($result === TRUE) {
      $extract_dir = 'public://opigno_scorm_extracted/scorm_' . $file->id();
      $zip->extractTo($extract_dir);
      $zip->close();

      // This is a standard: the manifest file will always be here.
      $manifest_file = $extract_dir . '/imsmanifest.xml';

      if (file_exists($manifest_file)) {
        // Prepare the Scorm DB entry.
        $scorm = (object) [
          'fid' => $file->id(),
          'extracted_dir' => $extract_dir,
          'manifest_file' => $manifest_file,
          'manifest_id' => '',
          'metadata' => '',
        ];

        // Parse the manifest file and extract the data.
        $manifest_data = $this->scormExtractManifestData($manifest_file);

        // Get the manifest ID, if it's given.
        if (!empty($manifest_data['manifest_id'])) {
          $scorm->manifest_id = $manifest_data['manifest_id'];
        }

        // If the file contains (global) metadata, serialize it.
        if (!empty($manifest_data['metadata'])) {
          $scorm->metadata = serialize($manifest_data['metadata']);
        }

        // Try saving the SCORM to the DB.
        if ($this->scormSave($scorm)) {
          // Store each SCO.
          if (!empty($manifest_data['scos']['items'])) {
            foreach ($manifest_data['scos']['items'] as $i => $sco_item) {
              $sco = (object) [
                'scorm_id' => $scorm->id,
                'organization' => $sco_item['organization'],
                'identifier' => $sco_item['identifier'],
                'parent_identifier' => $sco_item['parent_identifier'],
                'launch' => $sco_item['launch'],
                'type' => $sco_item['type'],
                'scorm_type' => $sco_item['scorm_type'],
                'title' => $sco_item['title'],
                'weight' => empty($sco_item['weight']) ? $sco_item['weight'] : 0,
                'attributes' => $sco_item['attributes'],
              ];

              if ($this->scormScoSave($sco)) {
                // @todo Store SCO attributes.
              }
              else {
                \Drupal::logger('opigno_scorm')->error('An error occured when saving an SCO.');
              }
            }
          }
          return TRUE;
        }
        else {
          \Drupal::logger('opigno_scorm')->error('An error occured when saving the SCORM package data.');
        }
      }
    }
    else {
      $error = 'none';
      switch ($result) {
        case \ZipArchive::ER_EXISTS:
          $error = 'ER_EXISTS';
          break;

        case \ZipArchive::ER_INCONS:
          $error = 'ER_INCONS';
          break;

        case \ZipArchive::ER_INVAL:
          $error = 'ER_INVAL';
          break;

        case \ZipArchive::ER_NOENT:
          $error = 'ER_NOENT';
          break;

        case \ZipArchive::ER_NOZIP:
          $error = 'ER_NOZIP';
          break;

        case \ZipArchive::ER_OPEN:
          $error = 'ER_OPEN';
          break;

        case \ZipArchive::ER_READ:
          $error = 'ER_READ';
          break;

        case \ZipArchive::ER_SEEK:
          $error = 'ER_SEEK';
          break;
      }
      \Drupal::logger('opigno_scorm')->error("An error occured when unziping the SCORM package data. Error: !error", ['!error' => $error]);
    }

    return FALSE;
  }

  /**
   * Save a SCORM package information.
   *
   * @param object $scorm
   *   Scorm object.
   *
   * @return bool
   *   Save flag.
   *
   * @throws \Exception
   */
  public function scormSave($scorm) {
    $connection = $this->database;
    if (!empty($scorm->id)) {
      return $connection->update('opigno_scorm_packages')
        ->fields((array) $scorm)
        ->condition('id', $scorm->id)
        ->execute();
    }
    else {
      $id = $connection->insert('opigno_scorm_packages')
        ->fields((array) $scorm)
        ->execute();

      $scorm->id = $id;

      return !!$id;
    }
  }

  /**
   * Save a SCO information.
   *
   * @param object $sco
   *   Sco object.
   *
   * @return bool
   *   Sco save flag.
   *
   * @throws \Exception
   */
  public function scormScoSave($sco) {
    $connection = $this->database;
    // The attributes is not a field in the database, but
    // a representation of a table relationship.
    // Cache them here and unset the property for the
    // DB query.
    $attributes = $sco->attributes;
    unset($sco->attributes);

    if (!empty($sco->id)) {
      $res = $connection->update('opigno_scorm_package_scos')
        ->fields((array) $sco)
        ->condition('id', $sco->id)
        ->execute();
    }
    else {
      $id = $connection->insert('opigno_scorm_package_scos')
        ->fields((array) $sco)
        ->execute();

      $sco->id = $id;

      $res = !!$id;
    }

    if ($res && !empty($attributes)) {
      // Remove all old attributes, to prevent duplicates.
      $connection->delete('opigno_scorm_package_sco_attributes')
        ->condition('sco_id', $sco->id)
        ->execute();

      foreach ($attributes as $key => $value) {
        $serialized = 0;
        if (is_array($value) || is_object($value)) {
          $value = serialize($value);
          $serialized = 1;
        }
        elseif (is_bool($value)) {
          $value = (int) $value;
        }

        $connection->insert('opigno_scorm_package_sco_attributes')
          ->fields([
            'sco_id' => $sco->id,
            'attribute' => $key,
            'value' => $value,
            'serialized' => $serialized,
          ])
          ->execute();
      }
    }

    return $res;
  }

  /**
   * Get Scorm data by it's file id.
   *
   * @param \Drupal\file\Entity\File $file
   *   File entity.
   *
   * @return mixed
   *   Scorm data.
   */
  public function scormLoadByFileEntity(File $file) {
    $connection = $this->database;
    return $connection->select('opigno_scorm_packages', 'o')
      ->fields('o', [])
      ->condition('fid', $file->id())
      ->execute()
      ->fetchObject();
  }

  /**
   * Load a SCORM package information.
   *
   * @param int $scorm_id
   *   Scorm ID.
   *
   * @return object|false
   *   SCORM package information.
   */
  public function scormLoadById($scorm_id) {
    $connection = $this->database;
    return $connection->select('opigno_scorm_packages', 'o')
      ->fields('o', [])
      ->condition('id', $scorm_id)
      ->execute()
      ->fetchObject();
  }

  /**
   * Load a SCO information.
   *
   * @param int $sco_id
   *   Sco ID.
   *
   * @return object|false
   *   SCO information.
   */
  public function scormLoadSco($sco_id) {
    $connection = $this->database;
    $sco = $connection->select('opigno_scorm_package_scos', 'o')
      ->fields('o', [])
      ->condition('id', $sco_id)
      ->execute()
      ->fetchObject();

    if ($sco) {
      $sco->attributes = $this->scormLoadScormAttributes($sco->id);
    }

    return $sco;
  }

  /**
   * Extract manifest data from the manifest file.
   *
   * @param string $manifest_file
   *   Path to manifest file.
   *
   * @return array
   *   Manifest data.
   */
  public function scormExtractManifestData($manifest_file) {
    $data = [
      'manifest_id' => '',
    ];

    // Get the XML as a string.
    $manifest_string = file_get_contents($manifest_file);

    // Parse it as an array.
    $parser = new XML2Array();
    $manifest = $parser->parse($manifest_string);
    // The parser returns an array of arrays - skip the first element.
    $manifest = array_shift($manifest);

    // Get the manifest ID, if any.
    if (!empty($manifest['attrs']['IDENTIFIER'])) {
      $data['manifest_id'] = $manifest['attrs']['IDENTIFIER'];
    }
    else {
      $data['manifest_id'] = '';
    }

    // Extract the global metadata information.
    $data['metadata'] = $this->scormExtractManifestMetadata($manifest);

    // Extract the SCOs (course items).
    // Gets the default SCO and a list of all SCOs.
    $data['scos'] = $this->scormExtractManifestScos($manifest);

    // Extract the resources, so we can combine the SCOs and resources.
    $data['resources'] = $this->scormExtractManifestResources($manifest);

    // Combine the resources and SCOs.
    $data['scos']['items'] = $this->scormCombineManifestScoAndResources($data['scos']['items'], $data['resources']);

    return $data;
  }

  /**
   * Helper function to load a SCO attributes.
   *
   * @param int $sco_id
   *   Sco ID.
   *
   * @return array
   *   SCO attributes.
   */
  private function scormLoadScormAttributes($sco_id) {
    $connection = $this->database;
    $attributes = [];

    $result = $connection->select('opigno_scorm_package_sco_attributes', 'o')
      ->fields('o', ['attribute', 'value', 'serialized'])
      ->condition('sco_id', $sco_id)
      ->execute();

    while ($row = $result->fetchObject()) {
      $attributes[$row->attribute] = !empty($row->serialized) ? unserialize($row->value) : $row->value;
    }

    return $attributes;
  }

  /**
   * Extract manifest metadata from the manifest.
   *
   * @param array $manifest
   *   Manifest.
   *
   * @return array
   *   Manifest metadata.
   */
  private function scormExtractManifestMetadata(array $manifest) {
    $metadata = [];
    foreach ($manifest['children'] as $child) {
      if ($child['name'] == 'METADATA') {
        foreach ($child['children'] as $meta) {
          if (isset($meta['tagData'])) {
            $metadata[strtolower($meta['name'])] = $meta['tagData'];
          }
        }
      }
    }
    return $metadata;
  }

  /**
   * Extract scos from the manifest.
   *
   * @param array $manifest
   *   Manifest.
   *
   * @return array
   *   Scos.
   */
  private function scormExtractManifestScos(array $manifest) {
    $items = ['items' => []];
    foreach ($manifest['children'] as $child) {
      if ($child['name'] == 'ORGANIZATIONS') {
        if (!empty($child['attrs']['DEFAULT'])) {
          $items['default'] = $child['attrs']['DEFAULT'];
        }
        else {
          $items['default'] = '';
        }

        if (!empty($child['children']) && is_array($child['children'])) {
          $items['items'] = array_merge($this->scormExtractManifestScosItems($child['children']), $items['items']);
        }
      }
    }
    return $items;
  }

  /**
   * Helper function to recursively extract the manifest SCO items.
   *
   * The data is extracted as a flat array - it contains to hierarchy.
   * Because of this, the items are not extracted in logical order.
   * However, each "level" is given a weight which allows us
   * to know how to organize them.
   *
   * @param array $manifest
   *   Manifest.
   * @param string|int $parent_identifier
   *   Parent identifier.
   * @param string $organization
   *   Organization.
   *
   * @return array
   *   Manifest SCO items.
   */
  private function scormExtractManifestScosItems(array $manifest, $parent_identifier = 0, $organization = '') {
    $items = [];
    $weight = 0;

    foreach ($manifest as $item) {
      if (in_array($item['name'], ['ORGANIZATION', 'ITEM']) && !empty($item['children'])) {
        $attributes = [];
        if (!empty($item['attrs']['IDENTIFIER'])) {
          $identifier = $item['attrs']['IDENTIFIER'];
        }
        else {
          $identifier = uniqid();
        }

        if (!empty($item['attrs']['LAUNCH'])) {
          $launch = $item['attrs']['LAUNCH'];
        }
        else {
          $launch = '';
        }

        if (!empty($item['attrs']['IDENTIFIERREF'])) {
          $resource_identifier = $item['attrs']['IDENTIFIERREF'];
        }
        else {
          $resource_identifier = '';
        }

        if (!empty($item['attrs']['PARAMETERS'])) {
          $attributes['parameters'] = $item['attrs']['PARAMETERS'];
        }

        if (!empty($item['attrs']['TYPE'])) {
          $type = $item['attrs']['TYPE'];
        }
        else {
          $type = '';
        }

        if (!empty($item['attrs']['ADLCP:SCORMTYPE'])) {
          $scorm_type = $item['attrs']['ADLCP:SCORMTYPE'];
        }
        else {
          $scorm_type = '';
        }

        // Find the title, which is also a child node.
        foreach ($item['children'] as $child) {
          if ($child['name'] == 'TITLE') {
            $title = $child['tagData'];
            break;
          }
        }

        // Find any sequencing control modes, which are also child nodes.
        $control_modes = [];
        foreach ($item['children'] as $child) {
          if ($child['name'] == 'IMSSS:SEQUENCING') {
            $control_modes = $this->scormExtractItemSequencingControlModes($child);
            $attributes['objectives'] = $this->scormExtractItemSequencingObjectives($child);
          }
        }

        // Failsafe - we cannot have elements without a title.
        if (empty($title)) {
          $title = 'NO TITLE';
        }

        $items[] = [
          'manifest' => '',
          'organization' => $organization,
          'title' => $title,
          'identifier' => $identifier,
          'parent_identifier' => $parent_identifier,
          'launch' => $launch,
          'resource_identifier' => $resource_identifier,
          'type' => $type,
          'scorm_type' => $scorm_type,
          'weight' => $weight,
          'attributes' => $control_modes + $attributes,
        ];

        // The first item is not an "item",
        // but an "organization" node. This is the organization
        // for the remainder of the tree.
        // Get it, and pass it along, so we know to which organization
        // the SCOs belong.
        if (empty($organization) && $item['name'] == 'ORGANIZATION') {
          $organization = $identifier;
        }

        // Recursively get child items, and merge them to get a flat list.
        $items = array_merge($this->scormExtractManifestScosItems($item['children'], $identifier, $organization), $items);
      }
      $weight++;
    }

    return $items;
  }

  /**
   * Extract the manifest SCO resources.
   *
   * We only extract resource information that is relevant to us.
   * We don't care about references files, dependencies, etc.
   * Only about the href attribute, type and identifier.
   *
   * @param array $manifest
   *   Manifest.
   *
   * @return array
   *   Manifest SCO resources.
   */
  private function scormExtractManifestResources(array $manifest) {
    $items = [];
    foreach ($manifest['children'] as $child) {
      if ($child['name'] == 'RESOURCES') {
        foreach ($child['children'] as $resource) {
          if ($resource['name'] == 'RESOURCE') {
            if (!empty($resource['attrs']['IDENTIFIER'])) {
              $identifier = $resource['attrs']['IDENTIFIER'];
            }
            else {
              $identifier = uniqid();
            }

            if (!empty($resource['attrs']['HREF'])) {
              $href = $resource['attrs']['HREF'];
            }
            else {
              $href = '';
            }

            if (!empty($resource['attrs']['TYPE'])) {
              $type = $resource['attrs']['TYPE'];
            }
            else {
              $type = '';
            }

            if (!empty($resource['attrs']['ADLCP:SCORMTYPE'])) {
              $scorm_type = $resource['attrs']['ADLCP:SCORMTYPE'];
            }
            else {
              $scorm_type = '';
            }

            $items[] = [
              'identifier' => $identifier,
              'href' => $href,
              'type' => $type,
              'scorm_type' => $scorm_type,
            ];
          }
        }
      }
    }
    return $items;
  }

  /**
   * Combine resources and SCO data.
   *
   * Update SCO data to include resource information (if necessary).
   * Return the updated SCO list.
   *
   * @param array $scos
   *   Scos.
   * @param array $resources
   *   Resources.
   *
   * @return array
   *   SCO data.
   */
  private function scormCombineManifestScoAndResources(array $scos, array $resources) {
    if (!empty($scos)) {
      foreach ($scos as &$sco) {
        // If the SCO has a resource identifier ("identifierref"),
        // we need to combine them.
        if (!empty($sco['resource_identifier'])) {
          // Check all resources, and break when the correct one is found.
          foreach ($resources as $resource) {
            if (!empty($resource['identifier']) && $resource['identifier'] == $sco['resource_identifier']) {
              // If the SCO has no launch attribute, get the resource href.
              if (!empty($resource['href']) && empty($sco['launch'])) {
                $sco['launch'] = $resource['href'];
              }

              // Set the SCO type, if available.
              if (!empty($resource['type']) && empty($sco['type'])) {
                $sco['type'] = $resource['type'];
              }

              // Set the SCO scorm type, if available.
              if (!empty($resource['scorm_type']) && empty($sco['scorm_type'])) {
                $sco['scorm_type'] = $resource['scorm_type'];
              }
              break;
            }
          }
        }
      }
    }
    // If scos are empty, but resources exists.
    elseif (!empty($resources)) {
      // Init scos array.
      $scos = [];
      foreach ($resources as $resource) {
        $sco = [];
        // Add lunch key for the sco.
        if (!empty($resource['href'])) {
          $sco['launch'] = $resource['href'];
        }

        // Add type key for the sco.
        if (!empty($resource['type'])) {
          $sco['type'] = $resource['type'];
        }

        // Add scorm type key for the sco.
        if (!empty($resource['scorm_type'])) {
          $sco['scorm_type'] = $resource['scorm_type'];
        }

        // Add scorm type key for the sco.
        if (empty($resource['title']) && !empty($resource['identifier'])) {
          $sco['title'] = $resource['identifier'];
        }
        // Set identifier and default values.
        $sco['identifier'] = $resource['identifier'];
        $sco['parent_identifier'] = 0;
        $sco['weight'] = 0;
        // Add created sco to scos list.
        $scos[] = $sco;
      }
    }
    return $scos;
  }

  /**
   * Extract the manifest SCO item sequencing control modes.
   *
   * This extracts sequencing control modes from an item. Control modes
   * describe how the user can navigate around the course
   * (e.g.: display the tree or not, skip SCOs, etc).
   *
   * @param array $item_manifest
   *   Manifest.
   *
   * @return array
   *   SCO item sequencing control modes.
   */
  private function scormExtractItemSequencingControlModes(array $item_manifest) {
    $defaults = [
      'control_mode_choice' => TRUE,
      'control_mode_flow' => FALSE,
      'control_mode_choice_exit' => TRUE,
      'control_mode_forward_only' => FALSE,
    ];

    $control_modes = [];

    foreach ($item_manifest['children'] as $child) {
      if ($child['name'] == 'IMSSS:CONTROLMODE') {
        // Note: boolean attributes are stored as a strings. PHP does not know
        // how to cast 'false' to FALSE. Use string comparisons to bypass
        // this limitation by PHP. See below.
        if (!empty($child['attrs']['CHOICE'])) {
          $control_modes['control_mode_choice'] = strtolower($child['attrs']['CHOICE']) === 'true';
        }

        if (!empty($child['attrs']['FLOW'])) {
          $control_modes['control_mode_flow'] = strtolower($child['attrs']['FLOW']) === 'true';
        }

        if (!empty($child['attrs']['CHOICEEXIT'])) {
          $control_modes['control_mode_choice_exit'] = strtolower($child['attrs']['CHOICEEXIT']) === 'true';
        }
      }
    }

    return $control_modes + $defaults;
  }

  /**
   * Extract the manifest SCO item sequencing objective.
   *
   * This extracts sequencing objectives from an item.
   * Objectives allow the system to know how to "grade" the SCORM object.
   *
   * @param array $item_manifest
   *   Manifest.
   *
   * @return array
   *   SCO item sequencing objective.
   */
  private function scormExtractItemSequencingObjectives(array $item_manifest) {
    $objectives = [];
    foreach ($item_manifest['children'] as $child) {
      if ($child['name'] == 'IMSSS:OBJECTIVES') {
        foreach ($child['children'] as $child_objective) {
          if (!empty($child_objective['attrs']['OBJECTIVEID'])) {
            $id = $child_objective['attrs']['OBJECTIVEID'];
          }
          else {
            $id = uniqid();
          }

          if ($child_objective['name'] == 'IMSSS:PRIMARYOBJECTIVE') {
            // Note: boolean attributes are stored as a strings.
            // PHP does not know how to cast 'false' to FALSE.
            // Use string comparisons to bypass this limitation by PHP.
            // See below.
            $satisfied_by_measure = FALSE;
            if (!empty($child_objective['attrs']['SATISFIEDBYMEASURE'])) {
              $satisfied_by_measure = strtolower($child_objective['attrs']['SATISFIEDBYMEASURE']) === 'true';
            }

            $objective = [
              'primary' => TRUE,
              'secondary' => FALSE,
              'id' => $id,
              'satisfied_by_measure' => $satisfied_by_measure,
            ];

            foreach ($child_objective['children'] as $primary_obj_child) {
              if ($primary_obj_child['name'] == 'IMSSS:MINNORMALIZEDMEASURE') {
                $objective['min_normalized_measure'] = $primary_obj_child['tagData'];
              }
              elseif ($primary_obj_child['name'] == 'IMSSS:MAXNORMALIZEDMEASURE') {
                $objective['max_normalized_measure'] = $primary_obj_child['tagData'];
              }
            }

            $objectives[] = $objective;
          }
          elseif ($child_objective['name'] == 'IMSSS:OBJECTIVE') {
            $objectives[] = [
              'primary' => FALSE,
              'secondary' => TRUE,
              'id' => $id,
            ];
          }
        }
      }
    }

    return $objectives;
  }

}
