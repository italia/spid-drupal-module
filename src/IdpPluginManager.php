<?php

namespace Drupal\spid;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\spid\Annotation\Idp;

/**
 * Plugin type manager for Idp plugins.
 *
 * @see \Drupal\devel\Annotation\Idp
 * @see \Drupal\devel\DevelDumperInterface
 * @see \Drupal\devel\DevelDumperBase
 * @see plugin_api
 */
class IdpPluginManager extends DefaultPluginManager {

  /**
   * Constructs a IdpPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/Spid/Idp', $namespaces, $module_handler, IdpInterface::class, Idp::class);
    $this->setCacheBackend($cache_backend, 'spid_idp_plugins');
    $this->alterInfo('spid_idp_info');
  }

}
