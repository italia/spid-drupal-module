<?php

namespace Drupal\spid\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\spid\SpidServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to configure the SP settings.
 */
class MetadataSPForm extends FormBase {

  /**
   * The spid service.
   *
   * @var \Drupal\spid\SpidServiceInterface
   */
  private $spid;

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\spid\SpidServiceInterface $spid
   *   The spid service.
   */
  public function __construct(SpidServiceInterface $spid) {
    $this->spid = $spid;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('spid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spid.metadata_sp';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $metadata = $this->spid->getMetadata();

    $form['metadata'] = [
      '#default_value' => $metadata,
      '#description' => $this->t('Use this metadata to register the Service Provider with AgID.'),
      '#rows' => 50,
      '#title' => $this->t('Metadata'),
      '#type' => 'textarea',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
