# Description

Allows users to authenticate against the Italian
[SPID](https://www.spid.gov.it/) system.

This module is based on the
[SAML Authentication module](https://www.drupal.org/project/samlauth) and it use
a slightly modified version of the 
[Onelogin PHP-SAML library](https://github.com/onelogin/php-saml) that allows a
super easy setup of a SAML Service Provider.

# Installation

To download the Onelogin PHP-SAML library library and apply the patch the
preferred method is to use Composer. In the main composer.json of your website
add:

```
"cweagans/composer-patches": "~1.0",
"onelogin/php-saml": "~2.5"
``` 

in the *require* section and then:

```
"extra": {
  "patches": {
    "onelogin/php-saml": {
      "Compatibility with the Italian SPID system": "https://www.drupal.org/files/issues/onelogin-php-saml-for-spid.patch"
    }
  }
},
```

as a root element.

After running *composer update* you can enable the module as usual (using Drush,
Drupal console or with the Web UI).

# Usage
 
The module is pre-configured with the data from all supported Identity Provider
and with the data of the [test Identity Provider](https://idp.spid.gov.it:8080).

## Configuration

* Navigate to `/admin/config/people/spid` and fill the form with all the data related to your environment
* Place the the *SPID login button* block somewhere in you layout and allow the access only to anonymous users
* Add a logout link to the user menu (or the menu you want) that points to /saml/logout

## Test with the test Identity Provider

The module expose the SAML metadata at the url `/saml/metadata`, copy the
relevant values in the form [here](https://idp.spid.gov.it:8080) and click save.
Then in the module configuration enable the Test idp in the *Enabled identity
providers* section. You can now login to you site with the users listed
[here](https://idp.spid.gov.it:8080/#/publicusers). 
