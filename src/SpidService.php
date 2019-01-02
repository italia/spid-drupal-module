<?php

namespace Drupal\spid;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\externalauth\ExternalAuth;
use Drupal\spid\Event\SpidEvents;
use Drupal\spid\Event\SpidUserLinkEvent;
use Drupal\spid\Event\SpidUserSyncEvent;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Italia\Spid\Sp;

/**
 * Governs communication between the PHP SPID lib and the IDP / login behavior.
 */
class SpidService implements SpidServiceInterface {

  /**
   * The spid-php-lib Saml class.
   *
   * @var \Italia\Spid\Spid\Saml
   */
  protected $spid;

  /**
   * The ExternalAuth service.
   *
   * @var \Drupal\externalauth\ExternalAuth
   */
  protected $externalAuth;

  /**
   * A configuration object containing spid settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The Session service.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  private $session;

  /**
   * Constructor for Drupal\spid\SpidService.
   *
   * @param \Drupal\externalauth\ExternalAuth $external_auth
   *   The ExternalAuth service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager service.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   The Session service.
   */
  public function __construct(ExternalAuth $external_auth, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, EventDispatcherInterface $event_dispatcher, Session $session) {
    $this->externalAuth = $external_auth;
    $this->config = $config_factory->get('spid.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->eventDispatcher = $event_dispatcher;
    $this->session = $session;

    $sp_base_url = $this->config->get('sp_entity_id');

    $settings = [
      'sp_entityid' => $sp_base_url,
      'sp_key_file' => $this->config->get('sp_key_file'),
      'sp_cert_file' => $this->config->get('sp_cert_file'),
      'sp_assertionconsumerservice' => [
        $sp_base_url . '/spid/acs',
      ],
      'sp_singlelogoutservice' => [
        [$sp_base_url . '/spid/slo', 'POST'],
      ],
      'sp_org_name' => $this->config->get('sp_org_name'),
      'sp_org_display_name' => $this->config->get('sp_org_display_name'),
      'idp_metadata_folder' => $this->config->get('idp_metadata_folder'),
      'accepted_clock_skew_seconds' => 3600,
    ];

    foreach ($this->config->get('sp_metadata_attributes') as $key => $attribute) {
      if ($attribute !== 0) {
        $settings['sp_attributeconsumingservice'][0][] = $key;
      }
    }

    $this->spid = new Sp($settings, NULL, FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getMetadata() {
    try {
      $metadata = $this->spid->getSPMetadata();
      return $metadata;
    }
    catch (\Exception $e) {
      return sprintf("<error>%s</error>", $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function login($idp, $level, $redirectTo = NULL) {
    $this->spid->login($idp, 0, 0, $level, $redirectTo);
  }

  /**
   * {@inheritdoc}
   */
  public function acs() {
    try {
      if ($this->spid->isAuthenticated()) {
        $attributes = $this->getAttributes();

        $nameAttribute = 'fiscalNumber';
        $mailAttribute = 'email';

        $authName = $attributes[$nameAttribute];
        $account = $this->externalAuth->load($authName[0], 'spid');
        if (!$account) {
          $this->logger->debug('No matching local users found for unique SPID ID @spid_id.', ['@spid_id' => $authName[0]]);

          // Try to link an existing user: first through a custom event handler,
          // then by name, then by e-mail.
          $event = new SpidUserLinkEvent($attributes);
          $this->eventDispatcher->dispatch(spidEvents::USER_LINK, $event);
          $account = $event->getLinkedAccount();

          if (!$account) {
            // The linking by name / e-mail cannot be bypassed at this point
            // because it makes no sense to create a new account from the SPID
            // attributes if one of these two basic properties is already in
            // use. (In this case a newly created and logged-in account would
            // get a cryptic machine name because  synchronizeUserAttributes()
            // cannot assign the proper name while saving.)
            if ($account_search = $this->entityTypeManager->getStorage('user')
              ->loadByProperties(['name' => $authName[0]])) {
              $account = reset($account_search);
              $this->logger->info('Matching local user @uid found for name @name (as provided in a SPID attribute); associating user and logging in.', [
                '@name' => $authName[0],
                '@uid' => $account->id(),
              ]);
            }
            else {
              if ($account_search = $this->entityTypeManager->getStorage('user')
                ->loadByProperties(['mail' => $mailAttribute])) {
                $account = reset($account_search);
                $this->logger->info('Matching local user @uid found for e-mail @mail (as provided in a SPID attribute); associating user and logging in.', [
                  '@mail' => $mailAttribute,
                  '@uid' => $account->id(),
                ]);
              }
            }
          }

          if ($account) {
            // There is a chance that the following call will not actually link
            // the account (if a mapping to this account already exists from
            // another unique ID). If that happens, it does not matter much to
            // us; we will just log the account in anyway. Next time the same
            // not-yet-linked user logs in, we will again try to link the
            // account in the same way and (falsely) log that we are associating
            // the user.
            $this->externalAuth->linkExistingAccount($authName[0], 'spid', $account);
          }
        }

        // If we haven't found an account to link, create one from the SPID
        // attributes.
        if (!$account) {
          // The register() call will save the account. We want to:
          // - add values from the SPID response into the user account;
          // - not save the account twice (because if the second save fails we
          //   do not want to end up with a user account in an undetermined
          //   state);
          // - reuse code (i.e. call synchronizeUserAttributes() with its
          //   current signature, which is also done when an existing user logs
          //   in).
          // Because of the third point, we are not passing the necessary SPID
          // attributes into register()'s $account_data parameter, but we want
          // to hook into the save operation of the user account object that is
          // created by register(). It seems we can only do this by implementing
          // hook_user_presave() - which calls our synchronizeUserAttributes().
          $account = $this->externalAuth->register($authName[0], 'spid');

          $this->externalAuth->userLoginFinalize($account, $authName[0], 'spid');
        }
        elseif ($account->isBlocked()) {
          throw new \RuntimeException('Requested account is blocked.');
        }
        else {
          // Synchronize the user account with SPID attributes if needed.
          $this->synchronizeUserAttributes($account);

          $this->externalAuth->userLoginFinalize($account, $authName[0], 'spid');
        }
      }
      else {
        throw new \RuntimeException('Could not authenticate.');
      }
    }
    catch (\Exception $e) {
      \drupal::logger('spid')->error($e->getMessage());
      throw new \RuntimeException('Could not authenticate.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function logout($return_to = NULL) {
    $this->spid->logout(0);
  }

  /**
   * {@inheritdoc}
   */
  public function slo() {
    user_logout();
  }

  /**
   * {@inheritdoc}
   */
  public function getIdpList() {
    return $this->spid->getIdpList();
  }

  /**
   * {@inheritdoc}
   */
  public function synchronizeUserAttributes(UserInterface $account, $skip_save = FALSE) {
    // Dispatch a user_sync event.
    $event = new spidUserSyncEvent($account, $this->getAttributes());
    $this->eventDispatcher->dispatch(spidEvents::USER_SYNC, $event);

    if (!$skip_save && $event->isAccountChanged()) {
      $account->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributes() {
    return $this->spid->getAttributes();
  }

  /**
   * {@inheritdoc}
   */
  public function isTestenvEnabled() {
    foreach ($this->getIdpList() as $key => $idp) {
      if ($key == "test") {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSpidAttributes() {
    $t = \Drupal::translation();
    return [
      'fiscalNumber' => $t->translate('Fiscal number'),
      'name' => $t->translate('Name'),
      'familyName' => $t->translate('Family name'),
      'gender' => $t->translate('Gender'),
      'idCard' => $t->translate('Id card'),
      'expirationDate' => $t->translate('Expiration date'),
      'dateOfBirth' => $t->translate('Date of birth'),
      'placeOfBirth' => $t->translate('Place of birth'),
      'countyOfBirth' => $t->translate('Country of birth'),
      'digitalAddress' => $t->translate('Digital address'),
      'email' => $t->translate('Email'),
      'mobilePhone' => $t->translate('Mobile phone'),
      'companyName' => $t->translate('Company name'),
      'registeredOffice' => $t->translate('Registered office'),
      'ivaCode' => $t->translate('Iva code'),
      'spidCode' => $t->translate('SPID code'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function getSpidLevels() {
    $t = \Drupal::translation();
    return [
      1 => $t->translate('Level 1'),
      2 => $t->translate('Level 2'),
      3 => $t->translate('Level 3'),
    ];
  }

}
