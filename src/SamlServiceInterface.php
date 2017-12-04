<?php

namespace Drupal\spid;

use Drupal\user\UserInterface;
use Exception;
use OneLogin_Saml2_Error;

/**
 * Governs communication between the SAML toolkit and the IDP / login behavior.
 */
interface SamlServiceInterface {

  /**
   * Show metadata about the local sp. Use this to configure your saml2 IDP
   *
   * @return mixed xml string representing metadata
   * @throws OneLogin_Saml2_Error
   */
  public function getMetadata();

  /**
   * Initiates a SAML2 authentication flow and redirects to the IDP.
   *
   * @param string $return_to
   *   (optional) The path to return the user to after successful processing by
   *   the IDP.
   */
  public function login($idp, $return_to = NULL);

  /**
   * Initiates a SAML2 logout flow and redirects to the IdP.
   *
   * @param null $return_to
   *   (optional) The path to return the user to after successful processing by
   *   the IDP.
   */
  public function logout($return_to = NULL);

  /**
   * Processes a SAML response (Assertion Consumer Service).
   *
   * First checks whether the SAML request is OK, then takes action on the
   * Drupal user (logs in / maps existing / create new) depending on attributes
   * sent in the request and our module configuration.
   *
   * @throws Exception
   */
  public function acs($idp);

  /**
   * Does processing for the Single Logout Service if necessary.
   */
  public function sls();

  /**
   * Synchronizes user data with attributes in the SAML request.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user to synchronize attributes into.
   * @param bool $skip_save
   *   (optional) If TRUE, skip saving the user account.
   */
  public function synchronizeUserAttributes(UserInterface $account, $skip_save = FALSE);

  /**
   * Returns all attributes in a SAML response.
   *
   * This method will return valid data after a response is processed (i.e.
   * after spid->processResponse() is called).
   *
   * @return array
   *   An array with all returned SAML attributes..
   */
  public function getAttributes();

  /**
   * @return array
   */
  public static function getSPIDAttributes();

}
