<?php

namespace Drupal\spid\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\spid\IdpPluginManager;
use Drupal\spid\SamlService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for spid module settings and IDP/SP info.
 */
class SpidConfigureForm extends ConfigFormBase {

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $fieldManager;

  /**
   * The idp plugin manager.
   *
   * @var \Drupal\spid\IdpPluginManager
   */
  private $idpPluginManager;

  /**
   * The tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  private $tagsInvalidator;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager service.
   * @param \Drupal\spid\IdpPluginManager $idp_plugin_manager
   *   The idp plugin manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $tags_invalidator
   *   The tags invalidator service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $field_manager, IdpPluginManager $idp_plugin_manager, CacheTagsInvalidatorInterface $tags_invalidator) {
    parent::__construct($config_factory);

    $this->fieldManager = $field_manager;
    $this->idpPluginManager = $idp_plugin_manager;
    $this->tagsInvalidator = $tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.spid_idp'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'spid.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spid_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('spid.settings');

    $form['service_provider'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Service Provider configuration'),
    ];

    $form['service_provider']['config_info'] = [
      '#theme' => 'item_list',
      '#items' => [
        $this->t('Metadata URL: @url', [
          '@url' => Url::fromRoute('spid.saml_controller_metadata', [], ['absolute' => TRUE])
            ->toString(),
        ]),
        $this->t('Assertion Consumer Service: @url', [
          '@url' => Url::fromRoute('spid.saml_controller_acs', [], ['absolute' => TRUE])
            ->toString(),
        ]),
        $this->t('Single Logout Service: @url', [
          '@url' => Url::fromRoute('spid.saml_controller_sls', [], ['absolute' => TRUE])
            ->toString(),
        ]),
      ],
      '#empty' => [],
      '#list_type' => 'ul',
    ];

    $form['service_provider']['sp_entity_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity ID'),
      '#description' => $this->t('Specifies the identifier to be used to represent the SP.'),
      '#default_value' => $config->get('sp_entity_id'),
    ];

    $cert_folder = $config->get('sp_cert_folder');
    $sp_x509_certificate = $config->get('sp_x509_certificate');
    $sp_private_key = $config->get('sp_private_key');

    $form['service_provider']['sp_cert_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of configuration to save for the certificates'),
      '#required' => TRUE,
      '#options' => [
        'folder' => $this->t('Folder name'),
        'fields' => $this->t('Cert/key value'),
      ],
      // Prefer folder over certs, like SamlService::reformatConfig(), but if
      // both are empty then default to folder here.
      '#default_value' => $cert_folder || (!$sp_x509_certificate && !$sp_private_key) ? 'folder' : 'fields',
    ];

    $form['service_provider']['sp_x509_certificate'] = [
      '#type' => 'textarea',
      '#title' => $this->t('x509 Certificate'),
      '#description' => $this->t('Public x509 certificate of the SP. No line breaks or BEGIN CERTIFICATE or END CERTIFICATE lines.'),
      '#default_value' => $config->get('sp_x509_certificate'),
      '#states' => [
        'visible' => [
          [':input[name="sp_cert_type"]' => ['value' => 'fields']],
        ],
      ],
    ];

    $form['service_provider']['sp_private_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Private Key'),
      '#description' => $this->t('Private key for SP. No line breaks or BEGIN CERTIFICATE or END CERTIFICATE lines.'),
      '#default_value' => $config->get('sp_private_key'),
      '#states' => [
        'visible' => [
          [':input[name="sp_cert_type"]' => ['value' => 'fields']],
        ],
      ],
    ];

    $form['service_provider']['sp_cert_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Certificate folder'),
      '#description' => $this->t('Set the path to the folder containing a /certs subfolder and the /certs/sp.key (private key) and /certs/sp.crt (public cert) files. The names of the subfolder and files are mandated by the external SAML Toolkit library.'),
      '#default_value' => $cert_folder,
      '#states' => [
        'visible' => [
          [':input[name="sp_cert_type"]' => ['value' => 'folder']],
        ],
      ],
    ];

    $form['service_provider']['sp_metadata_attributes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Required attributes'),
      '#description' => $this->t('Choose which attributes we want returned from the Identity Provider. Notice that this list is used to populate the metadata, change this after that the metadata has been submitted to the different idp has no effect.'),
      '#default_value' => $config->get('sp_metadata_attributes'),
      '#options' => SamlService::getSpidAttributes(),
    ];

    $form['identity_provider'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Identity Provider configuration'),
    ];

    $plugins = $this->idpPluginManager->getDefinitions();
    $idpList = [];
    /** @var \Drupal\spid\IdpInterface $plugin */
    foreach ($plugins as $plugin) {
      $idpList[$plugin['id']] = $plugin['label'];
    }

    $form['identity_provider']['idp_list'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled Identity Providers'),
      '#description' => $this->t('Choose which Identity providers to enable.'),
      '#default_value' => $config->get('idp_list'),
      '#options' => $idpList,
    ];

    $form['identity_provider']['idp_test'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Test Identity Provider data'),
      '#states' => [
        'visible' => [
          [':input[name="idp_list[spid-testenv-identityserver]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['identity_provider']['idp_test']['idp_test_entityid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity ID'),
      '#description' => $this->t('The entity ID of the test Identity Provider.'),
      '#default_value' => $config->get('idp_test_entityid'),
      '#states' => [
        'required' => [
          [':input[name="idp_list[spid-testenv-identityserver]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['identity_provider']['idp_test']['idp_test_sso'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Single sign-on service'),
      '#description' => $this->t('The single sign-on url of the test Identity Provider.'),
      '#default_value' => $config->get('idp_test_sso'),
      '#states' => [
        'required' => [
          [':input[name="idp_list[spid-testenv-identityserver]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['identity_provider']['idp_test']['idp_test_slo'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Single logout service'),
      '#description' => $this->t('The single logout url of the test Identity Provider.'),
      '#default_value' => $config->get('idp_test_slo'),
      '#states' => [
        'required' => [
          [':input[name="idp_list[spid-testenv-identityserver]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['identity_provider']['idp_test']['idp_test_x509cert'] = [
      '#type' => 'textarea',
      '#title' => $this->t('x509 Certificate'),
      '#description' => $this->t('The x509cert certificate of the test Identity Provider.'),
      '#default_value' => $config->get('idp_test_x509cert'),
      '#states' => [
        'required' => [
          [':input[name="idp_list[spid-testenv-identityserver]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['user_info'] = [
      '#title' => $this->t('User field mapping'),
      '#type' => 'fieldset',
    ];

    foreach (SamlService::getSpidAttributes() as $key => $attribute) {
      $this->buildUserMappingFormElement($form, $key, $attribute);
    }

    $form['security'] = [
      '#title' => $this->t('Security Options'),
      '#type' => 'fieldset',
    ];

    $form['security']['strict'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Strict mode'),
      '#description' => $this->t('In strict mode, any validation failures or unsigned SAML messages which are requested to be signed (according to your settings) will cause the SAML conversation to be terminated. In production environments, this <em>must</em> be set.'),
      '#default_value' => $config->get('strict'),
    ];

    $form['security']['security_authn_requests_sign'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sign authentication requests'),
      '#description' => $this->t('Requests sent to the Single sign-on Service of the Identity Provider will include a signature.'),
      '#default_value' => $config->get('security_authn_requests_sign'),
    ];

    $form['security']['security_messages_sign'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Request messages to be signed'),
      '#description' => $this->t('Response messages from the Identity Provider are expected to be signed.'),
      '#default_value' => $config->get('security_messages_sign'),
      '#states' => [
        'disabled' => [
          ':input[name="strict"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['security']['security_request_authn_context'] = [
      '#type' => 'select',
      '#title' => $this->t('SPID authentication level'),
      '#options' => [
        'https://www.spid.gov.it/SpidL1' => 'SpidL1',
        'https://www.spid.gov.it/SpidL2' => 'SpidL2',
        'https://www.spid.gov.it/SpidL3' => 'SpidL3',
      ],
      '#default_value' => $config->get('security_request_authn_context'),
    ];

    $form['#attached']['library'][] = 'spid/spid_config';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // @TODO: Validate cert. Might be able to just openssl_x509_parse().

    // Validate certs folder. Don't allow the user to save an empty folder; if
    // they want to save incomplete config data, they can switch to 'fields'.
    $sp_cert_type = $form_state->getValue('sp_cert_type');
    $sp_cert_folder = $this->fixFolderPath($form_state->getValue('sp_cert_folder'));
    if ($sp_cert_type == 'folder') {
      if (empty($sp_cert_folder)) {
        $form_state->setErrorByName('sp_cert_folder', $this->t('@name field is required.', ['@name' => $form['service_provider']['sp_cert_folder']['#title']]));
      }
      elseif (!file_exists($sp_cert_folder . '/certs/sp.key') || !file_exists($sp_cert_folder . '/certs/sp.crt')) {
        $form_state->setErrorByName('sp_cert_folder', $this->t('The Certificate folder does not contain the required certs/sp.key or certs/sp.crt files.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Only store variables related to the sp_cert_type value. (If the user
    // switched from fields to folder, the cert/key values always get cleared
    // so no unused security sensitive data gets saved in the database.)
    $sp_cert_type = $form_state->getValue('sp_cert_type');
    $sp_x509_certificate = '';
    $sp_private_key = '';
    $sp_cert_folder = '';
    if ($sp_cert_type == 'folder') {
      $sp_cert_folder = $this->fixFolderPath($form_state->getValue('sp_cert_folder'));
    }
    else {
      $sp_x509_certificate = $form_state->getValue('sp_x509_certificate');
      $sp_private_key = $form_state->getValue('sp_private_key');
    }

    // fiscalNumber and email are always required.
    $metadataAttributes = $form_state->getValue('sp_metadata_attributes');
    $metadataAttributes['fiscalNumber'] = 'fiscalNumber';
    $metadataAttributes['email'] = 'email';

    $this->config('spid.settings')
      ->set('sp_entity_id', $form_state->getValue('sp_entity_id'))
      ->set('sp_x509_certificate', $sp_x509_certificate)
      ->set('sp_private_key', $sp_private_key)
      ->set('sp_cert_folder', $sp_cert_folder)
      ->set('sp_metadata_attributes', $metadataAttributes)
      ->set('idp_list', $form_state->getValue('idp_list'))
      ->set('idp_test_entityid', $form_state->getValue('idp_test_entityid'))
      ->set('idp_test_sso', $form_state->getValue('idp_test_sso'))
      ->set('idp_test_slo', $form_state->getValue('idp_test_slo'))
      ->set('idp_test_x509cert', $form_state->getValue('idp_test_x509cert'))
      ->set('security_authn_requests_sign', $form_state->getValue('security_authn_requests_sign'))
      ->set('security_messages_sign', $form_state->getValue('security_messages_sign'))
      ->set('security_request_authn_context', $form_state->getValue('security_request_authn_context'))
      ->set('strict', $form_state->getValue('strict'));

    foreach (SamlService::getSpidAttributes() as $key => $attribute) {
      $this->config('spid.settings')
        ->set('user_' . $key, $form_state->getValue('user_' . $key));
    }

    $this->config('spid.settings')->save();

    $this->tagsInvalidator->invalidateTags(['idp_list']);
  }

  /**
   * Remove trailing slash from a folder name, to unify config values.
   *
   * @param string $path
   *   A filesystem path.
   *
   * @return string
   *   The fixed filesystem path.
   */
  protected function fixFolderPath($path) {
    if ($path) {
      $path = rtrim($path, '/');
    }
    return $path;
  }

  /**
   * Returns the list of fields defined for the User entity.
   *
   * @return array
   *   The list of fields defined for the User entity.
   */
  protected function getUserFields() {
    $definitions = $this->fieldManager->getFieldDefinitions('user', 'user');

    $fields = ['none' => $this->t('- None -')];
    /** @var \Drupal\Core\Field\BaseFieldDefinition $definition */
    foreach ($definitions as $name => $definition) {
      if (strpos($name, 'field_') === 0) {
        $fields[$name] = $definition->getLabel();
      }
    }

    return $fields;
  }

  /**
   * Builds the form element for a single attribute mapping.
   *
   * @param array $form
   *   The config form.
   * @param string $key
   *   The form element key.
   * @param string $title
   *   The form element title.
   */
  protected function buildUserMappingFormElement(array &$form, $key, $title) {
    $config = $this->config('spid.settings');

    $form['user_info']['user_' . $key] = [
      '#type' => 'select',
      '#title' => $title,
      '#options' => $this->getUserFields(),
      '#default_value' => $config->get('user_' . $key),
    ];
  }

}
