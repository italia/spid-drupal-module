<?php

namespace Drupal\spid\Plugin\Spid\Idp;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\spid\IdpInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TestIdp.
 *
 * @Idp(
 *   id = "spid-testenv-identityserver",
 *   label = "Test idp"
 * )
 */
class TestIdp implements IdpInterface, ContainerFactoryPluginInterface {

  /**
   * The immutable configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * TestIdp constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('spid.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'spid-testenv-identityserver';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return 'Test idp';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    return [
      'entityId' => $this->config->get('idp_test_entityid'),
      'singleSignOnService' => [
        'url' => $this->config->get('idp_test_sso'),
      ],
      'singleLogoutService' => [
        'url' => $this->config->get('idp_test_slo'),
      ],
      'x509cert' => $this->config->get('idp_test_x509cert'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLogo($type = 'png') {
    switch ($type) {
      case 'png':
        return 'spid-idp-testid.png';

      case 'svg':
        return 'spid-idp-testid.svg';

    }

    return '';
  }

}
