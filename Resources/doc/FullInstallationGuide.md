Pumukit2 full installation
==========================

Steps to update http://pumukit2-migracion.campusdomar.es

1.- Make sure you're at 2.1.x branch:

```
$ sudo git checkout 2.1.x
```

2.- Try to download changes from github:

```
$ sudo git pull
```

4.- Install dependencies:

```
$ sudo composer install
```

5.- Configure your `web/storage/` and `web/uploads/` directories to be mounted on your PuMuKIT share.


7.- Make a symbolink link of uploads directory:

```
$ sudo rm -rf web/uploads
$ sudo ln -s /mnt/nas/uploads web/uploads
```

8.- Clear cache and install assets

```
$ cd /path/to/pumukit2
$ sudo php app/console cache:clear
$ sudo php app/console cache:clear --env=prod --no-debug
$ sudo php app/console assets:install
```

9.- Execute import command

Follow the steps at [Resources/doc/MigrationExecutionGuide.md](https://gitlab.teltek.es/pumukit2/pumukitimportbundle/blob/master/Resources/doc/MigrationExecutionGuide.md).