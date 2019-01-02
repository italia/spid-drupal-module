<?php

namespace Drupal\spid\Plugin\Block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\spid\SpidServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with the SPID Smart Button.
 *
 * @Block(
 *   id = "spid_smart_button",
 *   admin_label = @Translation("SPID Smart Button")
 * )
 */
class SpidSmartButton extends SpidButtonBase implements ContainerFactoryPluginInterface {

  /**
   * The Spid Service.
   *
   * @var \Drupal\spid\SpidServiceInterface
   */
  private $spid;

  /**
   * IdpList constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\spid\SpidServiceInterface $spid
   *   The Spid Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SpidServiceInterface $spid) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->spid = $spid;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('spid')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $list = $this->spid->getIdpList();

    return [
      '#theme' => 'spid_smart_button',
      '#idps' => $list,
      '#size' => $this->configuration['size'],
      '#cache' => [
        'tags' => ['idp_list'],
      ],
    ];
  }

}
