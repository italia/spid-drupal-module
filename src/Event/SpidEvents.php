<?php

namespace Drupal\spid\Event;

/**
 * Defines events for the spid module.
 *
 * @see \Drupal\spid\Event\SpidUserSyncEvent
 */
final class SpidEvents {

  /**
   * Name of the event fired when searching for a user to match SAML attributes.
   *
   * The event allows modules to link existing user accounts to an authname
   * through the externalauth mechanism (see externalauth module).
   * The event listener method receives a Drupal\spid\Event\SpidUserLinkEvent
   * instance. If it finds an account to link, it should call the event's
   * linkAccount() method.
   *
   * @Event
   *
   * @see \Drupal\spid\Event\SpidUserLinkEvent
   *
   * @var string
   */
  const USER_LINK = 'spid.user_link';

  /**
   * Name of the event fired when a user is synchronized from SAML attributes.
   *
   * The event allows modules to synchronize user account values with SAML
   * attributes passed by the IdP in the authentication response. Basic required
   * properties (username, email) are already synchronized. The event listener
   * method receives a \Drupal\spid\Event\SpidUserSyncEvent instance. If
   * it changes the account, it should call the event's markAccountChanged()
   * method rather than saving the account by itself.
   *
   * The event is fired after the SP / SAML library validates the IdP's
   * authentication response but before the Drupal user is logged in. An event
   * subscriber may throw an exception to prevent the login.
   *
   * @Event
   *
   * @see \Drupal\spid\Event\SpidUserSyncEvent
   *
   * @var string
   */
  const USER_SYNC = 'spid.user_sync';

}
