<?php

namespace Drupal\spid\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an Ipd annotation object.
 *
 * @Annotation
 *
 * @see plugin_api
 */
class Idp extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The idp label.
   *
   * @var string
   */
  public $label;

}
