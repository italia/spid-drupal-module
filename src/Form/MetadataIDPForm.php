<?php

namespace Drupal\spid\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\spid\MetadataServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to configure the IDP settings.
 */
class MetadataIDPForm extends FormBase {

  /**
   * The metadata service.
   *
   * @var \Drupal\spid\MetadataServiceInterface
   */
  private $metadata;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\spid\MetadataServiceInterface $metadata
   *   The metadata service.
   */
  public function __construct(MetadataServiceInterface $metadata) {
    $this->metadata = $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('spid.metadata')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spid.metadata_idp';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['update_idp_metadata'] = [
      '#value' => $this->t('Download metadata'),
      '#type' => 'submit',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $metadataFolder = $this->config('spid.settings')
      ->get('idp_metadata_folder');

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

}
