<?php

namespace Drupal\spid\Plugin\Spid\Idp;

use Drupal\spid\IdpInterface;

/**
 * Class Tim.
 *
 * @Idp(
 *   id = "spid-idp-timid",
 *   label = "Tim ID"
 * )
 */
class Tim implements IdpInterface {

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'spid-idp-timid';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return 'Tim ID';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    return [
      'entityId' => 'https://login.id.tim.it/affwebservices/public/saml2sso',
      'singleSignOnService' => [
        'url' => 'https://login.id.tim.it/affwebservices/public/saml2sso',
      ],
      'singleLogoutService' => [
        'url' => 'https://login.id.tim.it/affwebservices/public/saml2slo',
      ],
      'x509cert' => 'MIIE7jCCA9agAwIBAgIJAIfOQuFIcYGRMA0GCSqGSIb3DQEBCwUAMIGqMQswCQYDVQQGEwJJVDELMAkGA1UECBMCUk0xEDAOBgNVBAcTB1BvbWV6aWExLjAsBgNVBAoTJVRlbGVjb20gSXRhbGlhIFRydXN0IFRlY2hub2xvZ2llcyBzcmwxKDAmBgNVBAsTH1NlcnZpemkgcGVyIGwnaWRlbnRpdGEgZGlnaXRhbGUxIjAgBgNVBAMTGVRJIFRydXN0IFRlY2hub2xvZ2llcyBzcmwwHhcNMTYwMTE4MTAxODA2WhcNMTgwMTE3MTAxODA2WjCBqjELMAkGA1UEBhMCSVQxCzAJBgNVBAgTAlJNMRAwDgYDVQQHEwdQb21lemlhMS4wLAYDVQQKEyVUZWxlY29tIEl0YWxpYSBUcnVzdCBUZWNobm9sb2dpZXMgc3JsMSgwJgYDVQQLEx9TZXJ2aXppIHBlciBsJ2lkZW50aXRhIGRpZ2l0YWxlMSIwIAYDVQQDExlUSSBUcnVzdCBUZWNobm9sb2dpZXMgc3JsMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0mvyKfdJp0YgK7KAdD+sVcVpHcZoBBBFcz0cg8PPdT+2nH0ES09uxHWghcHNg0nJGWPJKaUJ0PWdokKwQ+ahI7RiHI9zufN4G7LlM40ko7heI5Xjv4wCMeJNYM2GY+1l9fS+595882GopALi8MGhTxH3QFvPTDtxj7D0fsKw0DNFk18jcRoLwfc/X0fzyUMBDk6QaZzi5MTjKP5ouHn/CATkW7MRZZOy6CGb6Fic0HyOhB46eFnB2QlRnCzQe1cwzpnfzB/BbtouWe/CFlHtbACbZwXGRKfFJnr3Zj5eYi5aRZDteIXMAj/UmNTP0X0PcI66b5ialTeDXFjgEO1hhwIDAQABo4IBEzCCAQ8wHQYDVR0OBBYEFIqo1nunRisDzjvLkTZx2/VVmXPjMIHfBgNVHSMEgdcwgdSAFIqo1nunRisDzjvLkTZx2/VVmXPjoYGwpIGtMIGqMQswCQYDVQQGEwJJVDELMAkGA1UECBMCUk0xEDAOBgNVBAcTB1BvbWV6aWExLjAsBgNVBAoTJVRlbGVjb20gSXRhbGlhIFRydXN0IFRlY2hub2xvZ2llcyBzcmwxKDAmBgNVBAsTH1NlcnZpemkgcGVyIGwnaWRlbnRpdGEgZGlnaXRhbGUxIjAgBgNVBAMTGVRJIFRydXN0IFRlY2hub2xvZ2llcyBzcmyCCQCHzkLhSHGBkTAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBAQBkgBFZDg34+ER3HaFSmg5I7BUkjyPinMqy1G2k5O2Jlry/e6X8u8QhieKXhMtGq3+XzYHY/QEFmnqOvEdlCeET9PPVb0Mr0mG5vU7lb2MfB+2+Wqg+1Hf8c3ABfDItuJoL95OsbEO4a+3a6EMfaAdSaeTb2+rsfr6R12cSxQcx16430iMhGj4T9GpFp8XH/JSE5XJvIddpNxuzf+/LlbcsMJ5lljitlOYUi7MxLpiqtgl56UD1YkGcezoi2Glb3m3l3a2V4pShh6qROfyd87jLCjqsuBJtlgToSQgMTb4lFv9RBbFD3rjL8gCx55W6kxP1YeclFVPzk/7Xaca29uTJ',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLogo($type = 'png') {
    switch ($type) {
      case 'png':
        return 'spid-idp-timid.png';

      case 'svg':
        return 'spid-idp-timid.svg';
    }

    return '';
  }

}
