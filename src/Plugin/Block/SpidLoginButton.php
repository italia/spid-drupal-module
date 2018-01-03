<?php

namespace Drupal\spid\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\spid\IdpPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with the SPID login button.
 *
 * @Block(
 *   id = "spid_login_button",
 *   admin_label = @Translation("SPID login button")
 * )
 */
class SpidLoginButton extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The idp plugin manager.
   *
   * @var \Drupal\spid\IdpPluginManager
   */
  private $idpPluginManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * IdpList constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\spid\IdpPluginManager $idp_plugin_manager
   *   The idp plugin manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, IdpPluginManager $idp_plugin_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->idpPluginManager = $idp_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('plugin.manager.spid_idp'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'size' => 'medium',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->isAnonymous());
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Button size'),
      '#default_value' => $this->configuration['size'],
      '#options' => [
        'small' => $this->t('Small'),
        'medium' => $this->t('Medium'),
        'large' => $this->t('Large'),
        'xlarge' => $this->t('Extra large'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['size'] = $form_state->getValue('size');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $plugins = $this->configFactory->get('spid.settings')
      ->get('idp_list');
    $imagesPath = $this->moduleHandler->getModule('spid')
      ->getPath() . "/assets/img";

    $idps = [];
    foreach ($plugins as $plugin) {
      if ($plugin !== 0) {
        /** @var \Drupal\spid\IdpInterface $idp */
        $idp = $this->idpPluginManager->createInstance($plugin);
        $idps[] = [
          '#theme' => 'spid_idp',
          '#idp' => $idp,
          '#imagesPath' => $imagesPath,
        ];
      }
    }

    return [
      '#theme' => 'spid_idps',
      '#idps' => $idps,
      '#imagesPath' => $imagesPath,
      '#size' => $this->configuration['size'],
      '#cache' => [
        'tags' => ['idp_list'],
      ],
    ];
  }

}
