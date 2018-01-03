<?php

namespace Drupal\spid\Plugin\Spid\Idp;

use Drupal\spid\IdpInterface;

/**
 * Class Infocert.
 *
 * @Idp(
 *   id = "spid-idp-infocertid",
 *   label = "Infocert ID"
 * )
 */
class Infocert implements IdpInterface {

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'spid-idp-infocertid';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return 'Infocert ID';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    return [
      'entityId' => 'https://identity.infocert.it',
      'singleSignOnService' => [
        'url' => 'https://identity.infocert.it/spid/samlsso',
      ],
      'singleLogoutService' => [
        'url' => 'https://identity.infocert.it/spid/samlslo',
      ],
      'x509cert' => 'MIIGbDCCBVSgAwIBAgIDA+76MA0GCSqGSIb3DQEBCwUAMIGGMQswCQYDVQQGEwJJVDEVMBMGA1UECgwMSU5GT0NFUlQgU1BBMRswGQYDVQQLDBJFbnRlIENlcnRpZmljYXRvcmUxFDASBgNVBAUTCzA3OTQ1MjExMDA2MS0wKwYDVQQDDCRJbmZvQ2VydCBTZXJ2aXppIGRpIENlcnRpZmljYXppb25lIDIwHhcNMTYwMTEyMDkyNDI4WhcNMTkwMTEyMDAwMDAwWjCBsTEUMBIGA1UELhMLMDc5NDUyMTEwMDYxDzANBgkqhkiG9w0BCQEWADEUMBIGA1UEBRMLMDc5NDUyMTEwMDYxHTAbBgNVBAMMFGlkZW50aXR5LmluZm9jZXJ0Lml0MRQwEgYDVQQLDAtJbmZvQ2VydCBJRDEhMB8GA1UECgwYSW5mb0NlcnQgU3BBLzA3OTQ1MjExMDA2MQ0wCwYDVQQHDARSb21hMQswCQYDVQQGEwJJVDCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBALDysrpnXB+it94LSuAmOgyFDilZ8nuSEVOFl1PX/HtgK3W25B/tqJBsyZwrAIXxg5XHYd3+i7bFoBjuduzfqhvSv9WYCVtggsz5a3sbOpU54DaOLgoCmd4nIsINwKzCmT1UNXBGjS+Xt5F3lV+v2Ayr4rAsPnkE2084BLmwcIX3w7+rx/Nd+/5HfaAMaORICYinUIvbZ5e/plUj87s1YEpep/DcC0uMFE66jFrcnHVOeHCrDh+tAZAiGew4BVJjLr0hfS4ZeaE43TJlHb00GZNfpfzGcOPbzWlSB5iF/cZbTRHmPsn0gALfpPNViniFBVqSaoywZwvkFosrehRUCNkCAwEAAaOCArQwggKwMBMGA1UdJQQMMAoGCCsGAQUFBwMCMCUGA1UdEgQeMByBGmZpcm1hLmRpZ2l0YWxlQGluZm9jZXJ0Lml0MGUGA1UdIAReMFwwWgYGK0wkAQEIMFAwTgYIKwYBBQUHAgIwQgxASW5mb0NlcnQgU3BBIFNTTCwgU01JTUUgYW5kIGRpZ2l0YWwgc2lnbmF0dXJlIENsaWVudCBDZXJ0aWZpY2F0ZTA3BggrBgEFBQcBAQQrMCkwJwYIKwYBBQUHMAGGG2h0dHA6Ly9vY3NwLnNjLmluZm9jZXJ0Lml0LzCB7AYDVR0fBIHkMIHhMDSgMqAwhi5odHRwOi8vY3JsLmluZm9jZXJ0Lml0L2NybHMvc2Vydml6aTIvQ1JMMDEuY3JsMIGooIGloIGihoGfbGRhcDovL2xkYXAuaW5mb2NlcnQuaXQvY24lM0RJbmZvQ2VydCUyMFNlcnZpemklMjBkaSUyMENlcnRpZmljYXppb25lJTIwMiUyMENSTDAxLG91JTNERW50ZSUyMENlcnRpZmljYXRvcmUsbyUzRElORk9DRVJUJTIwU1BBLEMlM0RJVD9jZXJ0aWZpY2F0ZVJldm9jYXRpb25MaXN0MA4GA1UdDwEB/wQEAwIEsDCBswYDVR0jBIGrMIGogBTpNppkKVKhWv5ppMSDt4B9D2oSeKGBjKSBiTCBhjELMAkGA1UEBhMCSVQxFTATBgNVBAoMDElORk9DRVJUIFNQQTEbMBkGA1UECwwSRW50ZSBDZXJ0aWZpY2F0b3JlMRQwEgYDVQQFEwswNzk0NTIxMTAwNjEtMCsGA1UEAwwkSW5mb0NlcnQgU2Vydml6aSBkaSBDZXJ0aWZpY2F6aW9uZSAyggECMB0GA1UdDgQWBBTi8mIRU4ue/0lKSfv4gSQhoZQvozANBgkqhkiG9w0BAQsFAAOCAQEAUCXyjmfzxmyVQbK4cf79zj5qMZVAAjDMTR1UGFcS2IibICh3S3Uf22lPGQfm+MX9tiweETW7fBLW6lrR2ofXBz/FfU98A/AA9GZDrbGhBxoc+RoqkHVYRqEuXOq6z3X9DuvsdsfKeO3p4eXbXlCcxD2PP5fFqcZxx1WZ1HRamiGk9fMN1iT3aPa3q7TfRD6W6+XgafjXieZ8bCa1FGIfapbqsWa91jdn4xiJpbmTTq1/Zjs5RCZYzmMEV9rSuSVgFtONb8+xKC4ohMVxAUw2yZHwd4dDyBLkapuaWkzhW939+gjeoKz04Ds2C52d/kln7ehdu9LkzvRI6UAEpAYLgg==',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLogo($type = 'png') {
    switch ($type) {
      case 'png':
        return 'spid-idp-infocertid.png';

      case 'svg':
        return 'spid-idp-infocertid.svg';

    }

    return '';
  }

}
