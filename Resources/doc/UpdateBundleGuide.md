# Update Guide

*This page is updated to the pumukit-import-bundle 1.0.x and to the PuMuKIT 2.1.0 or higher*

Steps to update the Import Bundle.

```bash
$ cd /path/to/pumukit
$ php composer.phar update campusdomar/pumukit-import-bundle
$ php app/console cache:clear
$ php app/console cache:clear --env=prod --no-debug
$ php app/console assets:install
```