<?php

namespace Drupal\spid\Event;

use Drupal\user\UserInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Wraps a spid user sync event for event listeners.
 */
class SpidUserLinkEvent extends Event {

  /**
   * The Drupal user account to link.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The SAML attributes received from the IDP.
   *
   * Single values are typically represented as one-element arrays.
   *
   * @var array
   */
  protected $attributes;

  /**
   * Constructs a samlouth user link event object.
   *
   * @param array $attributes
   *   The SAML attributes received from the IDP.
   */
  public function __construct(array $attributes) {
    $this->attributes = $attributes;
  }

  /**
   * Gets the Drupal user account to link.
   *
   * @return \Drupal\user\UserInterface
   *   The Drupal user account.
   */
  public function getLinkedAccount() {
    return $this->account;
  }

  /**
   * Sets the Drupal user account to link.
   *
   * @param \Drupal\user\UserInterface $account
   *   The Drupal user account.
   */
  public function setLinkedAccount(UserInterface $account) {
    $this->account = $account;
  }

  /**
   * Gets the SAML attributes.
   *
   * @return array
   *   The SAML attributes received from the IDP.
   */
  public function getAttributes() {
    return $this->attributes;
  }

}
