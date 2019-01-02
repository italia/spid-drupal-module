<?php

namespace Drupal\spid;

use Drupal\Component\Serialization\SerializationInterface;
use GuzzleHttp\Client;

/**
 * Class MetadataService.
 */
class MetadataService implements MetadataServiceInterface {

  const IDP_ENDPOINT = 'https://registry.spid.gov.it/assets/data/idp.json';

  /**
   * The HTTP client service.
   *
   * @var \GuzzleHttp\Client
   */
  private $client;

  /**
   * The Json Serialization service.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  private $json;

  /**
   * Constructor for Drupal\spid\MetadataService.
   *
   * @param \GuzzleHttp\Client $client
   *   The HTTP client service.
   * @param \Drupal\Component\Serialization\SerializationInterface $json
   *   The Json Serialization service.
   */
  public function __construct(Client $client, SerializationInterface $json) {
    $this->client = $client;
    $this->json = $json;
  }

  /**
   * {@inheritdoc}
   */
  public function downloadMetadata($dir) {
    $res = $this->client->get(MetadataService::IDP_ENDPOINT);
    if ($res->getStatusCode() == 200) {
      $idps = $this->json->decode($res->getBody()->getContents());

      foreach ($idps['data'] as $idp) {
        $metadata_url = $idp['metadata_url'];
        $ipa_entity_code = $idp['ipa_entity_code'];

        $res = $this->client->get($metadata_url);
        if ($res->getStatusCode() == 200) {
          $xml = $res->getBody()->getContents();

          $file = "$dir/$ipa_entity_code.xml";
          file_put_contents($file, $xml);
        }
      }
    }
  }

}
