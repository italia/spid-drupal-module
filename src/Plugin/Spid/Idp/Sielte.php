<?php

namespace Drupal\spid\Plugin\Spid\Idp;

use Drupal\spid\Annotation\Idp;
use Drupal\spid\IdpInterface;

/**
 * Class Sielte
 *
 * @Idp(
 *   id = "spid-idp-sielteid",
 *   label = "Sielte Id"
 * )
 */
class Sielte implements IdpInterface {

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'spid-idp-sielteid';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return 'Sielte Id';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    return [
      'entityId' => 'https://identity.sieltecloud.it',
      'singleSignOnService' => [
        'url' => 'https://identity.sieltecloud.it/simplesaml/saml2/idp/SSO.php',
      ],
      'singleLogoutService' => [
        'url' => 'https://identity.sieltecloud.it/simplesaml/saml2/idp/SLS.php',
      ],
      'x509cert' => 'MIIDczCCAlugAwIBAgIJAMsX0iEKQM6xMA0GCSqGSIb3DQEBCwUAMFAxCzAJBgNVBAYTAklUMQ4wDAYDVQQIDAVJdGFseTEgMB4GA1UEBwwXU2FuIEdyZWdvcmlvIGRpIENhdGFuaWExDzANBgNVBAoMBlNpZWx0ZTAeFw0xNTEyMTQwODE0MTVaFw0yNTEyMTMwODE0MTVaMFAxCzAJBgNVBAYTAklUMQ4wDAYDVQQIDAVJdGFseTEgMB4GA1UEBwwXU2FuIEdyZWdvcmlvIGRpIENhdGFuaWExDzANBgNVBAoMBlNpZWx0ZTCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBANIRlOjM/tS9V9jYjJreqZSctuYriLfPTDgX2XdhWEbMpMpwA9p0bsbLQoC1gP0piLO+qbCsIh9+boPfb4/dLIA7E+Vmm5/+evOtzvjfHG4oXjZK6jo08QwkVV8Bm1jkakJPVZ57QFbyDSr+uBbIMY7CjA2LdgnIIwKN/kSfFhrZUMJ6ZxwegM100X5psfNPSV9WUtgHsvqlIlvydPo2rMm21sg+2d3Vtg8DthNSYRLqgazCc0NTsigrH7niSbJCO0nq/svMX2rSFdh5GFK7/pxT+c3OFWqIR8r+RX4qW+auJqkbTuNRwxV22Sm6r69ZJwV0WspvsVJi+FYqiyoWhgUCAwEAAaNQME4wHQYDVR0OBBYEFCUx063GwUhEFDllwCBe/+jdeW+XMB8GA1UdIwQYMBaAFCUx063GwUhEFDllwCBe/+jdeW+XMAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQELBQADggEBADF94c3JwyBM86QBLeoUZxRYKPniba8B39FfJk0pb+LejKfZMvspOrOFgYQQ9UrS8IFkBX9Xr7/tjRbr2cPwZNjrEZhoq+NfcE09bnaWTyEl1IEKK8TWOupJj9UNVpYXX0LfIRrMwNEzAPQykOaqPOnyHxOCPTY957xXSo3jXOyvugtvPHbd+iliAzUoPm1tgiTKWS+EkQ/e22eFv5NEyT+oHiKovrQ+voPWOIvJVMjiTyxRic8fEnI9zzV0SxWvFvty77wgcYbeEuFZa3iidhojUge8o1uY/JUyQjFxcvvfAgWSIZwdHiNyWaAgwzLPmPCPsvBdR3xrlcDg/9Bd3D0=',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLogo($type = 'png') {
    switch ($type) {
      case 'png':
        return 'spid-idp-sielteid.png';
        break;
      case 'svg':
        return 'spid-idp-sielteid.svg';
        break;
    }

    return '';
  }
}
