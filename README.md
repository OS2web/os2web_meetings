# OS2Web Meetings Drupal module

## Module purpose

The aim of this module is to import meetings from various ESDH providers and expose this content to be used in Drupal.

The module itself only provides the abstract implementation of the importer, the actual one need extend the abstract one and implement missing methods.

## How does it work

Meetings are being imported from so called agenda or manifest files, that are provided by specific ESDH provider.

There are multiple implementations of ESDH (Acadre, SBSYS, Edoc etc), each one has its structure and way of storing meeting information.

OS2Web Meetings module provides a canonical/single way of importing meetings, assuming that meetings are coming in an unified format.
Each meeting is then processed and eventually imported in the system.

It is a responsibility of specific ESDH provider module to provide meeting in a canonical format.

Import is handled via Migrate API, which is part of the Drupal 8 core functionality.

## Additional settings
Settings are available under ```admin/config/content/os2web-meetings```
* **Import closed agenda** - If closed agenda will be imported, otherwise the closed content is skipped.
* **Whitelist of the committees** - If committee is not whitelisted, its meetings will be ignored.
* **Unpublish missing agendas** - If this plugin missing agendas will be unpublished. DO NOT use this setting if you are planning to import agendas in with max limit.
* **Clear HTML tags** - Comma-separated list of HTML tags, which style attribute shall be removed during import (it will remove only style HTML attribute of a given tag).

## Install

Module is available to download via composer.
```
composer require os2web/os2web_meetings
drush en os2web_meetings
```

## Import process

The import process is described for each of the ESDH provider meeting plugins individually.

## Update
Updating process for OS2Web Meetings module is similar to usual Drupal 8 module.
Use Composer's built-in command for listing packages that have updates available:

```
composer outdated os2web/os2web_meetings
```

## Automated testing and code quality
See [OS2Web testing and CI information](https://github.com/OS2Web/docs#testing-and-ci)

## Contribution

Project is opened for new features and os course bugfixes.
If you have any suggestion or you found a bug in project, you are very welcome
to create an issue in github repository issue tracker.
For issue description there is expected that you will provide clear and
sufficient information about your feature request or bug report.

### Code review policy
See [OS2Web code review policy](https://github.com/OS2Web/docs#code-review)

### Git name convention
See [OS2Web git name convention](https://github.com/OS2Web/docs#git-guideline)
