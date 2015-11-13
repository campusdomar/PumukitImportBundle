# Update Guide

*This page is updated to the PuMuKIT 2.1.0*

Steps to update the Import Bundle.

```bash
$ cd /path/to/pumukit2
$ php composer.phar update teltek/pmk2-import-bundle
$ php app/console cache:clear
$ php app/console cache:clear --env=prod --no-debug
$ php app/console assets:install
```