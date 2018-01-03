<?php

namespace Drupal\spid\Plugin\Spid\Idp;

use Drupal\spid\IdpInterface;

/**
 * Class Aruba.
 *
 * @Idp(
 *   id = "spid-idp-arubaid",
 *   label = "Aruba ID"
 * )
 */
class Aruba implements IdpInterface {

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'spid-idp-arubaid';
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return 'Aruba ID';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig() {
    return [
      'entityId' => 'https://loginspid.aruba.it',
      'singleSignOnService' => [
        'url' => 'https://loginspid.aruba.it/ServiceLoginWelcome',
      ],
      'singleLogoutService' => [
        'url' => 'https://loginspid.aruba.it/ServiceLogoutRequest',
      ],
      'x509cert' => 'MIIExTCCA62gAwIBAgIQIHtEvEhGM77HwqsuvSbi9zANBgkqhkiG9w0BAQsFADBsMQswCQYDVQQGEwJJVDEYMBYGA1UECgwPQXJ1YmFQRUMgUy5wLkEuMSEwHwYDVQQLDBhDZXJ0aWZpY2F0aW9uIEF1dGhvcml0eUIxIDAeBgNVBAMMF0FydWJhUEVDIFMucC5BLiBORyBDQSAyMB4XDTE3MDEyMzAwMDAwMFoXDTIwMDEyMzIzNTk1OVowgaAxCzAJBgNVBAYTAklUMRYwFAYDVQQKDA1BcnViYSBQRUMgc3BhMREwDwYDVQQLDAhQcm9kb3R0bzEWMBQGA1UEAwwNcGVjLml0IHBlYy5pdDEZMBcGA1UEBRMQWFhYWFhYMDBYMDBYMDAwWDEPMA0GA1UEKgwGcGVjLml0MQ8wDQYDVQQEDAZwZWMuaXQxETAPBgNVBC4TCDE2MzQ1MzgzMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAqt2oHJhcp03l73p+QYpEJ+f3jYYj0W0gos0RItZx/w4vpsiKBygaqDNVWSwfo1aPdVDIX13f62O+lBki29KTt+QWv5K6SGHDUXYPntRdEQlicIBh2Z0HfrM7fDl+xeJrMp1s4dsSQAuB5TJOlFZq7xCQuukytGWBTvjfcN/os5aEsEg+RbtZHJR26SbbUcIqWb27Swgj/9jwK+tvzLnP4w8FNvEOrNfR0XwTMNDFrwbOCuWgthv5jNBsVZaoqNwiA/MxYt+gTOMj/o5PWKk8Wpm6o/7/+lWAoxh0v8x9OkbIi+YaFpIxuCcUqsrJJk63x2gHCc2nr+yclYUhsKD/AwIDAQABo4IBLDCCASgwDgYDVR0PAQH/BAQDAgeAMB0GA1UdDgQWBBTKQ3+NPGcXFk8nX994vMTVpba1EzBHBgNVHSAEQDA+MDwGCysGAQQBgegtAQEBMC0wKwYIKwYBBQUHAgEWH2h0dHBzOi8vY2EuYXJ1YmFwZWMuaXQvY3BzLmh0bWwwWAYDVR0fBFEwTzBNoEugSYZHaHR0cDovL2NybC5hcnViYXBlYy5pdC9BcnViYVBFQ1NwQUNlcnRpZmljYXRpb25BdXRob3JpdHlCL0xhdGVzdENSTC5jcmwwHwYDVR0jBBgwFoAU8v9jQBwRQv3M3/FZ9m7omYcxR3kwMwYIKwYBBQUHAQEEJzAlMCMGCCsGAQUFBzABhhdodHRwOi8vb2NzcC5hcnViYXBlYy5pdDANBgkqhkiG9w0BAQsFAAOCAQEAnEw0NuaspbpDjA5wggwFtfQydU6b3Bw2/KXPRKS2JoqGmx0SYKj+L17A2KUBa2c7gDtKXYz0FLT60Bv0pmBN/oYCgVMEBJKqwRwdki9YjEBwyCZwNEx1kDAyyqFEVU9vw/OQfrAdp7MTbuZGFKknVt7b9wOYy/Op9FiUaTg6SuOy0ep+rqhihltYNAAl4L6fY45mHvqa5vvVG30OvLW/S4uvRYUXYwY6KhWvNdDf5CnFugnuEZtHJrVe4wx9aO5GvFLFZ/mQ35C5mXPQ7nIb0CDdLBJdz82nUoLSA5BUbeXAUkfahW/hLxLdhks68/TK694xVIuiB40pvMmJwxIyDA==',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLogo($type = 'png') {
    switch ($type) {
      case 'png':
        return 'spid-idp-arubaid.png';

      case 'svg':
        return 'spid-idp-arubaid.svg';

    }

    return '';
  }

}
