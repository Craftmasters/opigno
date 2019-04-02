<?php

namespace Drupal\opigno_ilt\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\group\Entity\Group;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ILTController.
 */
class ILTController extends ControllerBase {

  /**
   * Returns response for the autocompletion.
   *
   * @param \Drupal\group\Entity\Group $group
   *   Group object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function membersAutocomplete(Group $group) {
    $matches = [];
    $search = \Drupal::request()->query->get('q');
    if (!isset($search)) {
      $search = '';
    }

    if (isset($group)) {
      $training_members = $group->getMembers();
      $training_users = array_map(function ($member) {
        /** @var \Drupal\group\GroupMembership $member */
        return $member->getUser();
      }, $training_members);
      foreach ($training_users as $user) {
        /** @var \Drupal\user\UserInterface $user */
        $id = $user->id();
        $name = $user->getDisplayName();
        $label = $this->t("@name (User #@id)", [
          '@name' => $name,
          '@id' => $id,
        ]);

        $matches[] = [
          'value' => $label,
          'label' => $label,
          'type' => 'user',
          'id' => 'user_' . $id,
          'name' => $name,
        ];
      }

      /** @var \Drupal\group\Entity\Group[] $classes */
      $classes = $group->getContentEntities('subgroup:opigno_class');
      foreach ($classes as $class) {
        $id = $class->id();
        $name = $class->label();
        $label = $this->t("@name (Group #@id)", [
          '@name' => $name,
          '@id' => $id,
        ]);

        $matches[] = [
          'value' => $label,
          'label' => $label,
          'type' => 'group',
          'id' => 'class_' . $id,
          'name' => $name,
        ];
      }

      $search = strtoupper($search);
      $matches = array_filter($matches, function ($match) use ($search) {
        $name = strtoupper($match['name']);
        return strpos($name, $search) !== FALSE;
      });

      usort($matches, function ($match1, $match2) {
        return strcasecmp($match1['name'], $match2['name']);
      });
    }

    return new JsonResponse($matches);
  }

}
