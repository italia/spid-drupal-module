# Description

The SPID authentication module allows users to authenticate against the Italian SPID system.

# Installation

Install the module as usual.

# Usage

[WIP]

## Configuration

* Navigate to `/admin/config/people/spid` and fill the form with all the data related to your environment
* Place the the *SPID SP Access Button* block somewhere in you layout
* Add a logout link to the user menu (or the menu you want) that points to /spid/logout

## Test with the test Identity Provider

* configure and start testenv2 (TODO: add documentation)
* install module as usual
* generate certificates
* configure module (`/admin/config/people/spid`)
* copy idp metadata to configured `IDP metadata folder`
* generate SP metadata
* copy SP metadata to testenv2
* restart testenv2

# Security

This module requires `italia/spid-php-lib` **0.36 or later**. Earlier releases,
including the `v0.35` this module used to pin, are affected by an authentication
bypass that lets an attacker obtain a session with an identity of their choosing.
Run `composer show italia/spid-php-lib` to check what is actually installed, and
update `composer.lock`, not only `composer.json`.

**Account linking.** When a SPID identity logs in for the first time and no local
account is linked to it yet, the module can log in as an existing local account
instead of creating a new one. This gives whoever holds that SPID identity the
permissions of that account, and the values it is matched on come from the SAML
response, so:

* the option is **off by default** (`link_existing_accounts`, under *User field
  mapping* in the module settings);
* the Drupal user name is **never** matched against an attribute supplied by the
  Identity Provider. Matching the `fiscalNumber` against the user name is what
  allowed a forged response to be turned into a login as an administrator;
* an account with administrative privileges is **never** linked automatically —
  user 1, an account holding a role flagged as admin, or one holding
  `administer permissions`, `administer users` or
  `administer site configuration`. This applies to the `SpidEvents::USER_LINK`
  event handler's result too.

The Identity Provider is selected by name from the metadata in the configured
folder; `/spid/login/{idp}` rejects anything that is not in that list. Every
`*.xml` in the metadata folder is a trust anchor: only metadata you have verified
belongs there, and the directory should not be writable by the web server.
