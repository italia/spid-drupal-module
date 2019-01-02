<?php

namespace Drupal\spid;

/**
 * Interface MetadataServiceInterface.
 */
interface MetadataServiceInterface {

  /**
   * Download and save IDPs from registry.spid.gov.it.
   *
   * @param string $dir
   *   The folder to save IDPs metadata to.
   */
  public function downloadMetadata($dir);

}
