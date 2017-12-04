<?php

namespace Drupal\spid;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\externalauth\ExternalAuth;
use Drupal\spid\Event\spidEvents;
use Drupal\spid\Event\spidUserLinkEvent;
use Drupal\spid\Event\spidUserSyncEvent;
use Drupal\user\UserInterface;
use OneLogin_Saml2_Auth;
use OneLogin_Saml2_Error;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Governs communication between the SAML toolkit and the IDP / login behavior.
 */
class SamlService implements SamlServiceInterface {

  /**
   * A OneLogin_Saml2_Auth object representing the current request state.
   *
   * @var \OneLogin_Saml2_Auth
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
   * @var \Drupal\spid\IdpPluginManager
   */
  private $idpPluginManager;

  /**
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  private $session;

  /**
   * Constructor for Drupal\spid\SamlService.
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
   * @param \Drupal\spid\IdpPluginManager $idpPluginManager
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   */
  public function __construct(ExternalAuth $external_auth, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, EventDispatcherInterface $event_dispatcher, IdpPluginManager $idpPluginManager, Session $session) {
    $this->externalAuth = $external_auth;
    $this->config = $config_factory->get('spid.settings');
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
    $this->eventDispatcher = $event_dispatcher;
    $this->idpPluginManager = $idpPluginManager;
    $this->session = $session;
  }

  /**
   * {@inheritdoc
   */
  public function getMetadata() {
    $settings = $this->getspid()->getSettings();
    $metadata = $settings->getSPMetadata();
    $errors = $settings->validateMetadata($metadata);

    if (empty($errors)) {
      return $metadata;
    }
    else {
      throw new OneLogin_Saml2_Error('Invalid SP metadata: ' . implode(', ', $errors), OneLogin_Saml2_Error::METADATA_SP_INVALID);
    }
  }

  /**
   * {@inheritdoc
   */
  public function login($idp, $return_to = NULL) {
    $this->getspid($idp)->login($return_to);
  }

  /**
   * {@inheritdoc
   */
  public function logout($return_to = NULL) {
    $sessionIndex = $this->session->get('session_index');
    $idp = $this->session->get('idp');

    $this->getspid($idp)
      ->logout($return_to, ['referrer' => $return_to], NULL, $sessionIndex);
  }

  /**
   * {@inheritdoc
   */
  public function acs($idp) {
    // This call can either set an error condition or throw a
    // \OneLogin_Saml2_Error exception, depending on whether or not we are
    // processing a POST request. Don't catch the exception.
    $this->getspid($idp)->processResponse();
    // Now look if there were any errors and also throw.
    $errors = $this->getspid($idp)->getErrors();
    if (!empty($errors)) {
      // We have one or multiple error types / short descriptions, and one
      // 'reason' for the last error.
      throw new RuntimeException('Error(s) encountered during processing of ACS response. Type(s): ' . implode(', ', array_unique($errors)) . '; reason given for last error: ' . $this->getspid($idp)
          ->getLastErrorReason());
    }

    if (!$this->isAuthenticated()) {
      throw new RuntimeException('Could not authenticate.');
    }

    $nameAttribute = 'fiscalNumber';
    $mailAttribute = 'email';

    $account = $this->externalAuth->load($nameAttribute, 'spid');
    if (!$account) {
      $this->logger->debug('No matching local users found for unique SAML ID @saml_id.', ['@saml_id' => $nameAttribute]);

      // Try to link an existing user: first through a custom event handler,
      // then by name, then by e-mail.
      $event = new spidUserLinkEvent($this->getAttributes());
      $this->eventDispatcher->dispatch(spidEvents::USER_LINK, $event);
      $account = $event->getLinkedAccount();

      if (!$account) {
        // The linking by name / e-mail cannot be bypassed at this point
        // because it makes no sense to create a new account from the SAML
        // attributes if one of these two basic properties is already in use.
        // (In this case a newly created and logged-in account would get a
        // cryptic machine name because  synchronizeUserAttributes() cannot
        // assign the proper name while saving.)
        if ($account_search = $this->entityTypeManager->getStorage('user')
          ->loadByProperties(['name' => $nameAttribute])) {
          $account = reset($account_search);
          $this->logger->info('Matching local user @uid found for name @name (as provided in a SAML attribute); associating user and logging in.', [
            '@name' => $nameAttribute,
            '@uid' => $account->id(),
          ]);
        }
        else {
          if ($account_search = $this->entityTypeManager->getStorage('user')
            ->loadByProperties(['mail' => $mailAttribute])) {
            $account = reset($account_search);
            $this->logger->info('Matching local user @uid found for e-mail @mail (as provided in a SAML attribute); associating user and logging in.', [
              '@mail' => $mailAttribute,
              '@uid' => $account->id(),
            ]);
          }
        }
      }

      if ($account) {
        // There is a chance that the following call will not actually link the
        // account (if a mapping to this account already exists from another
        // unique ID). If that happens, it does not matter much to us; we will
        // just log the account in anyway. Next time the same not-yet-linked
        // user logs in, we will again try to link the account in the same way
        // and (falsely) log that we are associating the user.
        $this->externalAuth->linkExistingAccount($nameAttribute, 'spid', $account);
      }
    }

    // If we haven't found an account to link, create one from the SAML
    // attributes.
    if (!$account) {
      // The register() call will save the account. We want to:
      // - add values from the SAML response into the user account;
      // - not save the account twice (because if the second save fails we do
      //   not want to end up with a user account in an undetermined state);
      // - reuse code (i.e. call synchronizeUserAttributes() with its current
      //   signature, which is also done when an existing user logs in).
      // Because of the third point, we are not passing the necessary SAML
      // attributes into register()'s $account_data parameter, but we want to
      // hook into the save operation of the user account object that is
      // created by register(). It seems we can only do this by implementing
      // hook_user_presave() - which calls our synchronizeUserAttributes().
      $account = $this->externalAuth->register($nameAttribute, 'spid');

      $this->externalAuth->userLoginFinalize($account, $nameAttribute, 'spid');
    }
    elseif ($account->isBlocked()) {
      throw new RuntimeException('Requested account is blocked.');
    }
    else {
      // Synchronize the user account with SAML attributes if needed.
      $this->synchronizeUserAttributes($account);

      $this->externalAuth->userLoginFinalize($account, $nameAttribute, 'spid');
    }

    $sessionIndex = $this->spid->getSessionIndex();
    $this->session->set('session_index', $sessionIndex);
    $this->session->set('idp', $idp);
  }

  /**
   * {@inheritdoc
   */
  public function sls() {
    user_logout();
  }

  /**
   * {@inheritdoc
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
   * {@inheritdoc
   */
  public function getAttributes() {
    return $this->getspid()->getAttributes();
  }

  /**
   * {@inheritdoc
   */
  public static function getSPIDAttributes() {
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
   * @return bool if a valid user was fetched from the saml assertion this
   *   request.
   */
  protected function isAuthenticated() {
    return $this->getspid()->isAuthenticated();
  }

  /**
   * Returns an initialized Auth class from the SAML Toolkit.
   *
   * @param $idp
   *
   * @return \OneLogin_Saml2_Auth
   */
  protected function getspid($idp = NULL) {
    if (!isset($this->spid)) {
      $this->spid = new OneLogin_Saml2_Auth($this->reformatConfig($idp, $this->config));
    }

    return $this->spid;
  }

  /**
   * Returns a configuration array as used by the external library.
   *
   * @param $idp
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The module configuration.
   *
   * @return array The library configuration array.
   *   The library configuration array.
   */
  protected function reformatConfig($idp = NULL, ImmutableConfig $config) {
    // Check if we want to load the certificates from a folder. Either folder or
    // cert+key settings should be defined. If both are defined, "folder" is the
    // preferred method and we ignore cert/path values; we don't do more
    // complicated validation like checking whether the cert/key files exist.
    $sp_cert = '';
    $sp_key = '';
    $cert_folder = $config->get('sp_cert_folder');
    if ($cert_folder) {
      // Set the folder so the Simple SAML toolkit knows where to look.
      define('ONELOGIN_CUSTOMPATH', "$cert_folder/");
    }
    else {
      $sp_cert = $config->get('sp_x509_certificate');
      $sp_key = $config->get('sp_private_key');
    }

    $output = [
      'sp' => [
        'entityId' => $config->get('sp_entity_id'),
        'assertionConsumerService' => [
          'url' => Url::fromRoute('spid.saml_controller_acs', [], ['absolute' => TRUE])
            ->toString(),
        ],
        'attributeConsumingService' => [
          'serviceName' => 'Test',
        ],
        'singleLogoutService' => [
          'url' => Url::fromRoute('spid.saml_controller_sls', [], ['absolute' => TRUE])
            ->toString(),
        ],
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
        'x509cert' => $sp_cert,
        'privateKey' => $sp_key,
      ],
      'idp' => [
        'entityId' => 'example',
        'singleSignOnService' => [
          'url' => 'http://www.example.com',
        ],
        'singleLogoutService' => [
          'url' => 'http://www.example.com',
        ],
        'x509cert' => 'xxxyyy',
      ],
      'security' => [
        'authnRequestsSigned' => (bool) $config->get('security_authn_requests_sign'),
        'wantMessagesSigned' => (bool) $config->get('security_messages_sign'),
        'requestedAuthnContext' => (bool) $config->get('security_request_authn_context'),
        'signMetadata' => TRUE,
        'signatureAlgorithm' => \XMLSecurityKey::RSA_SHA256,
        'digestAlgorithm' => \XMLSecurityDSig::SHA256,
      ],
      'strict' => (bool) $config->get('strict'),
    ];

    if ($idp != NULL) {
      /** @var \Drupal\spid\IdpInterface $idpPlugin */
      $idpPlugin = $this->idpPluginManager->createInstance($idp);

      $output['idp'] = $idpPlugin->getConfig();
    }

    $attributesMapping = SamlService::getSPIDAttributes();
    foreach ($config->get('sp_metadata_attributes') as $attribute) {
      if ($attribute !== 0) {
        $output['sp']['attributeConsumingService']['requestedAttributes'][] = [
          'name' => $attribute,
          'nameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:unspecified',
          'friendlyName' => $attributesMapping[$attribute],
        ];
      }
    }

    return $output;
  }

}
