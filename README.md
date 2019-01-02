# Description

The SPID authentication module allows users to authenticate against the Italian SPID system.

# Installation

Install the module as usual.

# Usage
 
[WIP]

## Configuration

* Navigate to `/admin/config/people/spid` and fill the form with all the data related to your environment
* Place the the *SPID SP Access Button* block (or the new *SPID smart button* block) somewhere in you layout
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
