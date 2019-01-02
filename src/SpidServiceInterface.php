<?php

namespace Drupal\spid;

use Drupal\user\UserInterface;

/**
 * Governs communication between the PHP SPID lib and the IDP / login behavior.
 */
interface SpidServiceInterface {

  /**
   * Initiates a SPID authentication flow and redirects to the IDP.
   *
   * @param string $idp
   *   The IDP to login to.
   * @param int $level
   *   A SPID level.
   * @param string $redirectTo
   *   (optional) The path to return the user to after successful processing by
   *   the IDP.
   */
  public function login($idp, $level, $redirectTo = NULL);

  /**
   * Processes a SPID response (Assertion Consumer Service).
   *
   * First checks whether the SPID request is OK, then takes action on the
   * Drupal user (logs in / maps existing / create new) depending on attributes
   * sent in the request and our module configuration.
   */
  public function acs();

  /**
   * Initiates a SPID logout flow and redirects to the IdP.
   *
   * @param string $return_to
   *   (optional) The path to return the user to after successful processing by
   *   the IDP.
   */
  public function logout($return_to = NULL);

  /**
   * Does processing for the Single Logout Service if necessary.
   */
  public function slo();

  /**
   * Show metadata about the local sp. Use this to configure your SPID IDP.
   *
   * @return string
   *   The xml string representing metadata.
   */
  public function getMetadata();

  /**
   * Returns an array of available IDPs.
   *
   * @return array
   *   An array of available IDPs.
   */
  public function getIdpList();

  /**
   * Synchronizes user data with attributes in the SPID request.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user to synchronize attributes into.
   * @param bool $skip_save
   *   (optional) If TRUE, skip saving the user account.
   */
  public function synchronizeUserAttributes(UserInterface $account, $skip_save = FALSE);

  /**
   * Returns all attributes in a SPID response.
   *
   * This method will return valid data after a response is processed (i.e.
   * after spid->processResponse() is called).
   *
   * @return array
   *   An array with all returned SPID attributes..
   */
  public function getAttributes();

  /**
   * Returns true if the Testenv is enabled.
   *
   * @return bool
   *   True if the Testenv is enabled.
   */
  public function isTestenvEnabled();

  /**
   * Returns an array of available SPID attributes.
   *
   * @return array
   *   An array of available SPID attributes.
   */
  public static function getSpidAttributes();

  /**
   * Returns an array of available SPID levels.
   *
   * @return array
   *   An array of available SPID levels.
   */
  public static function getSpidLevels();

}
