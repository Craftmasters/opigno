<?php

namespace Drupal\opigno_moxtra;

/**
 * Implements Moxtra REST API.
 */
interface MoxtraServiceInterface {

  /**
   * Creates workspace (Moxtra binder).
   *
   * @param int $owner_id
   *   User ID.
   * @param string $name
   *   Workspace name.
   *
   * @return array
   *   Response data.
   */
  public function createWorkspace($owner_id, $name);

  /**
   * Updates workspace (Moxtra binder).
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID.
   * @param string $name
   *   New workspace name.
   *
   * @return array
   *   Response data.
   */
  public function updateWorkspace($owner_id, $binder_id, $name);

  /**
   * Deletes workspace (Moxtra binder).
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID.
   *
   * @return array
   *   Response data.
   */
  public function deleteWorkspace($owner_id, $binder_id);

  /**
   * Sends message to the workspace.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID.
   * @param string $message
   *   Message.
   *
   * @return array
   *   Response data.
   */
  public function sendMessage($owner_id, $binder_id, $message);

  /**
   * Adds users to the workspace.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID.
   * @param int[] $users_ids
   *   Array of the users IDs to add to the workspace.
   *
   * @return array
   *   Response data.
   */
  public function addUsersToWorkspace($owner_id, $binder_id, $users_ids);

  /**
   * Removes users from the workspace.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID.
   * @param int $user_id
   *   ID of the user to remove from the workspace.
   *
   * @return array
   *   Response data.
   */
  public function removeUserFromWorkspace($owner_id, $binder_id, $user_id);

  /**
   * Returns meeting info.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $session_key
   *   Session key of the meeting.
   *
   * @return array
   *   Response data.
   */
  public function getMeetingInfo($owner_id, $session_key);

  /**
   * Creates meeting.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $title
   *   New title of the meeting.
   * @param int $starts
   *   New start date timestamp of the meeting.
   * @param int $ends
   *   New end date timestamp of the meeting.
   *
   * @return array
   *   Response data.
   */
  public function createMeeting($owner_id, $title, $starts, $ends);

  /**
   * Updates meeting.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $session_key
   *   Session key of the meeting.
   * @param string $title
   *   New title of the meeting.
   * @param int $starts
   *   New start date timestamp of the meeting.
   * @param int|null $ends
   *   New end date timestamp of the meeting.
   *
   * @return array
   *   Response data.
   */
  public function updateMeeting($owner_id, $session_key, $title, $starts, $ends = NULL);

  /**
   * Deletes meeting.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $session_key
   *   Session key of the meeting.
   *
   * @return array
   *   Response data.
   */
  public function deleteMeeting($owner_id, $session_key);

  /**
   * Returns meeting files list.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID.
   *
   * @return array
   *   Response data.
   */
  public function getMeetingFilesList($owner_id, $binder_id);

  /**
   * Returns meeting file info.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID.
   * @param string $file_id
   *   File ID.
   *
   * @return array
   *   Response data.
   */
  public function getMeetingFileInfo($owner_id, $binder_id, $file_id);

  /**
   * Returns meeting recording info.
   *
   * @param int $owner_id
   *   User ID.
   * @param string $binder_id
   *   Binder ID.
   *
   * @return array
   *   Response data.
   */
  public function getMeetingRecordingInfo($owner_id, $binder_id);

}
