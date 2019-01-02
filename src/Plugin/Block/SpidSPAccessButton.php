<?php

namespace Drupal\spid\Plugin\Block;

/**
 * Provides a block with the SPID login button.
 *
 * @Block(
 *   id = "spid_sp_access_button",
 *   admin_label = @Translation("SPID SP A button")
 * )
 */
class SpidSPAccessButton extends SpidButtonBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'spid_sp_access_button',
      '#size' => $this->configuration['size'],
    ];
  }

}
