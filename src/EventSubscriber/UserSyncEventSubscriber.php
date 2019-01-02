<?php

namespace Drupal\spid\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\spid\Event\SpidEvents;
use Drupal\spid\Event\SpidUserSyncEvent;
use Drupal\spid\SpidService;
use Drupal\user\UserInterface;
use Egulias\EmailValidator\EmailValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber that synchronizes user properties on a user_sync event.
 *
 * This is basic module functionality, partially driven by config options. It's
 * split out into an event subscriber so that the logic is easier to tweak for
 * individual sites. (Set message or not? Completely break off login if an
 * account with the same name is found, or continue with a non-renamed account?
 * etc.)
 */
class UserSyncEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The EntityTypeManager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * A configuration object containing spid settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private $messenger;

  /**
   * Construct a new SpidUserSyncSubscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager service.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typed_data_manager
   *   The typed data manager.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The Translation Manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, TypedDataManagerInterface $typed_data_manager, EmailValidator $email_validator, LoggerInterface $logger, MessengerInterface $messenger, TranslationInterface $translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->emailValidator = $email_validator;
    $this->logger = $logger;
    $this->typedDataManager = $typed_data_manager;
    $this->config = $config_factory->get('spid.settings');
    $this->messenger = $messenger;

    $this->setStringTranslation($translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[SpidEvents::USER_SYNC][] = ['onUserSync'];
    return $events;
  }

  /**
   * Performs actions to synchronize users with Factory data on login.
   *
   * @param \Drupal\spid\Event\SpidUserSyncEvent $event
   *   The event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function onUserSync(SpidUserSyncEvent $event) {
    // If the account is new, we are in the middle of a user save operation;
    // the current user name is 'spid_AUTHNAME' (as set by externalauth) and
    // e-mail is not set yet.
    $account = $event->getAccount();
    $fatal_errors = [];

    // Synchronize username.
    if ($account->isNew()) {
      // Get value from the SPID attribute whose name is configured in the
      // spid module.
      $name = $this->getAttribute('fiscalNumber', $event);
      if ($name && $name != $account->getAccountName()) {
        // Validate the username. This shouldn't be necessary to mitigate
        // attacks; assuming our SPID setup is correct, noone can insert fake
        // data here. It protects against SPID attribute misconfigurations.
        // Invalid names will cancel the login / account creation. The code is
        // copied from user_validate_name().
        $definition = BaseFieldDefinition::create('string')
          ->addConstraint('UserName', []);
        $data = $this->typedDataManager->create($definition);
        $data->setValue($name);
        $violations = $data->validate();
        if ($violations) {
          foreach ($violations as $violation) {
            $fatal_errors[] = $violation->getMessage();
          }
        }

        // Check if the username is not already taken by someone else. For new
        // accounts this can happen if the 'map existing users' setting is off.
        if (!$fatal_errors) {
          $account_search = $this->entityTypeManager->getStorage('user')
            ->loadByProperties(['name' => $name]);
          $existing_account = reset($account_search);
          if (!$existing_account || $account->id() == $existing_account->id()) {
            $account->setUsername($name);
          }
          else {
            if ($account->isNew()) {
              $fatal_errors[] = $this->t('An account with the username @username already exists.', ['@username' => $name]);
            }
            else {
              // We continue and keep the old name. A DSM should be OK here
              // since login only happens interactively. (And we're ignoring
              // the law of dependency injection for this.)
              $this->logger->error('Error updating user name from SPID attribute', ['@username' => $name]);
              $this->messenger->addError($this->t('Error updating user name from SPID attribute', ['@username' => $name]));
            }
          }
        }
      }
    }

    // Synchronize e-mail.
    if ($account->isNew()) {
      $mail = $this->getAttribute('email', $event);
      if ($mail) {
        if ($mail != $account->getEmail()) {
          // Invalid e-mail cancels the login / account creation just like name.
          if ($this->emailValidator->isValid($mail)) {

            $account->setEmail($mail);
            if ($account->isNew()) {
              // Externalauth sets init to a non e-mail value so we will fix it.
              $account->set('init', $mail);
            }
          }
          else {
            $fatal_errors[] = $this->t('Invalid e-mail address @mail', ['@mail' => $mail]);
          }
        }
      }
      elseif ($account->isNew()) {
        // We won't allow new accounts with empty e-mail.
        $fatal_errors[] = $this->t('Email address is not provided in SPID attribute.');
      }
    }

    foreach (SpidService::getSpidAttributes() as $key => $attribute) {
      $this->setFieldValue($event, $account, 'user_' . $key, $key);
    }

    $event->markAccountChanged();

    if ($fatal_errors) {
      // Cancel the whole login process and/or account creation.
      throw new \RuntimeException('Error(s) encountered during SPID attribute synchronization: ' . implode(' // ', $fatal_errors));
    }
  }

  /**
   * Returns the value of a SPID attribute from a SpidUserSyncEvent.
   *
   * @param string $attribute
   *   The SPID attribute to extract.
   * @param \Drupal\spid\Event\SpidUserSyncEvent $event
   *   A SpidUserSyncEvent.
   *
   * @return string
   *   The SPID attribute value.
   */
  public function getAttribute($attribute, SpidUserSyncEvent $event) {
    $attributes = $event->getAttributes();

    return $attributes[$attribute];
  }

  /**
   * Sets the value of a user field to the value of a SPID attribute.
   *
   * @param \Drupal\spid\Event\SpidUserSyncEvent $event
   *   A SpidUserSyncEvent.
   * @param \Drupal\user\UserInterface $account
   *   A user account.
   * @param string $fieldName
   *   The name of the field in the user entity.
   * @param string $attribute
   *   The SPID attribute.
   */
  protected function setFieldValue(SpidUserSyncEvent $event, UserInterface &$account, $fieldName, $attribute) {
    if (($field = $this->config->get($fieldName)) != 'none' && $account->hasField($field)) {
      $account->set($field, $this->getAttribute($attribute, $event));
    }
  }

}
