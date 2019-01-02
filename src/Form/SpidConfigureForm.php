<?php

namespace Drupal\spid\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\spid\MetadataServiceInterface;
use Drupal\spid\SpidService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for spid module settings and IDP/SP info.
 */
class SpidConfigureForm extends ConfigFormBase {

  /**
   * The EntityFieldManager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $fieldManager;

  /**
   * The CacheTags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  private $tagsInvalidator;

  /**
   * The Metadata service.
   *
   * @var \Drupal\spid\MetadataServiceInterface
   */
  private $metadata;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The EntityFieldManager service.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $tags_invalidator
   *   The CacheTags invalidator service.
   * @param \Drupal\spid\MetadataServiceInterface $metadata
   *   The Metadata service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManagerInterface $field_manager, CacheTagsInvalidatorInterface $tags_invalidator, MetadataServiceInterface $metadata) {
    parent::__construct($config_factory);

    $this->fieldManager = $field_manager;
    $this->tagsInvalidator = $tags_invalidator;
    $this->metadata = $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('cache_tags.invalidator'),
      $container->get('spid.metadata')
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
    return 'configure';
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

    $form['service_provider']['sp_entity_id'] = [
      '#default_value' => $config->get('sp_entity_id'),
      '#description' => $this->t('Specifies the identifier to be used to represent the SP.'),
      '#title' => $this->t('Entity ID'),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['service_provider']['sp_cert_file'] = [
      '#default_value' => $config->get('sp_cert_file'),
      '#description' => $this->t('Service provider certificate file. Absolute path.'),
      '#title' => $this->t('Certificate file path'),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['service_provider']['sp_key_file'] = [
      '#default_value' => $config->get('sp_key_file'),
      '#description' => $this->t('Service provider certificate key file. Absolute path.'),
      '#title' => $this->t('Certificate key file path'),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['service_provider']['idp_metadata_folder'] = [
      '#default_value' => $config->get('idp_metadata_folder'),
      '#description' => $this->t('Folder that contains IDP metadata. Absolute path, must end with a trailing slash.'),
      '#title' => $this->t('IDP metadata folder'),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['service_provider']['sp_org_name'] = [
      '#default_value' => $config->get('sp_org_name'),
      '#title' => $this->t('Organization name'),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['service_provider']['sp_org_display_name'] = [
      '#default_value' => $config->get('sp_org_display_name'),
      '#title' => $this->t('Organization display name'),
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['service_provider']['sp_metadata_attributes'] = [
      '#default_value' => $config->get('sp_metadata_attributes'),
      '#description' => $this->t('Choose which attributes we want returned from the Identity Provider. Notice that this list is used to populate the metadata, change this after that the metadata has been submitted to the different idp has no effect.'),
      '#options' => SpidService::getSpidAttributes(),
      '#type' => 'checkboxes',
      '#title' => $this->t('Required attributes'),
    ];

    $form['service_provider']['spid_level'] = [
      '#default_value' => $config->get('spid_level'),
      '#description' => $this->t('Choose the SPID level the user will be authenticated to.'),
      '#options' => SpidService::getSpidLevels(),
      '#type' => 'select',
      '#title' => $this->t('SPID authentication level'),
    ];

    $form['user_info'] = [
      '#title' => $this->t('User field mapping'),
      '#type' => 'fieldset',
    ];

    foreach (SpidService::getSpidAttributes() as $key => $attribute) {
      $this->buildUserMappingFormElement($form, $key, $attribute);
    }

    $form['#attached']['library'][] = 'spid/spid.config';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // fiscalNumber and email are always required.
    $metadataAttributes = $form_state->getValue('sp_metadata_attributes');
    $metadataAttributes['fiscalNumber'] = 'fiscalNumber';
    $metadataAttributes['email'] = 'email';
    $metadataFolder = $form_state->getValue('idp_metadata_folder');

    $this->config('spid.settings')
      ->set('sp_entity_id', $form_state->getValue('sp_entity_id'))
      ->set('sp_cert_file', $form_state->getValue('sp_cert_file'))
      ->set('sp_key_file', $form_state->getValue('sp_key_file'))
      ->set('idp_metadata_folder', $metadataFolder)
      ->set('sp_org_name', $form_state->getValue('sp_org_name'))
      ->set('sp_org_display_name', $form_state->getValue('sp_org_display_name'))
      ->set('sp_metadata_attributes', $metadataAttributes)
      ->set('spid_level', $form_state->getValue('spid_level'));

    foreach (SpidService::getSpidAttributes() as $key => $attribute) {
      $this->config('spid.settings')
        ->set('user_' . $key, $form_state->getValue('user_' . $key));
    }

    $this->config('spid.settings')->save();

    $this->tagsInvalidator->invalidateTags(['idp_list']);

    if ($this->config('spid.settings')
      ->get('idp_metadata_folder') != $metadataFolder) {

      try {
        $this->metadata->downloadMetadata($metadataFolder);
        $this->messenger()
          ->addStatus($this->t('IDPs metadata downloaded to %metadata_folder', ['%metadata_folder' => $metadataFolder]));
      }
      catch (\Exception $e) {
        $this->messenger()
          ->addError($this->t('Error downloading IDPs metadata to %metadata_folder: @err', [
            '%metadata_folder' => $metadataFolder,
            '@err' => $e->getMessage(),
          ]));
      }
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns the list of fields defined for the User entity.
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
