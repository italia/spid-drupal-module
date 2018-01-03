<?php

namespace Drupal\spid;

/**
 * Base interface definition for Idp plugins.
 *
 * @see plugin_api
 */
interface IdpInterface {

  /**
   * Returns the idp ID.
   *
   * @return string
   *   The idp ID.
   */
  public function getId();

  /**
   * Returns the idp label.
   *
   * @return array
   *   The idp label.
   */
  public function getLabel();

  /**
   * Returns the idp configuration.
   *
   * @return array
   *   The idp configuration.
   */
  public function getConfig();

  /**
   * Returns the idp logo in different formats.
   *
   * @param string $type
   *   The type of the logo, png or svg.
   *
   * @return string
   *   The name of the logo image.
   */
  public function getLogo($type = 'png');

}
