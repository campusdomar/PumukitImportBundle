Pumukit2 full installation
==========================

*This page is updated to the 2.1.x branch*


Steps to follow to import your PuMuKIT1.7 data into PuMuKIT2.1 after a clean [installation of a PuMuKIT v2.1 server](https://github.com/campusdomar/PuMuKIT2-doc/blob/2.1.x/InstallationGuide.md#installation-on-linux-ubuntu-1404)

1.- Make sure you're at 2.1 tag:

```
$ sudo git checkout 2.1
```

2.- Try to download changes from github:

```
$ sudo git pull
```

4.- Install dependencies:

```
$ sudo composer install
```

5.- Configure your `web/storage/` and `web/uploads/` directories to be mounted on your PuMuKIT share if any. Otherwise, follow steps 6 and 7.


6.- Make a symbolink link of `uploads` directory, where `/mnt/nas/pumukit1.7/web/uploads` is the `uploads` directory of your PuMuKIT1.7 server. Make sure the PuMuKIT2 server has access to that directory:

```
$ cd /path/to/pumukit2
$ cd web/
$ rm -rf uploads
$ ln -s /path/to/mount/nas/pumukit1.7/web/uploads
```

7.- Make a symbolink link of `almacen` directory, where `/mnt/nas/pumukit1.7/web/almacen` is the `almacen` directory of your PuMuKIT1.7 server. Make sure the PuMuKIT2 server has access to that directory:

```
$ cd /path/to/pumukit2
$ cd web/
$ ln -s /path/to/mount/nas/pumukit1.7/web/almacen
```

8.- Make sure you have exported your PuMuKIT1.7 data [following the steps on the export script](https://gitlab.teltek.es/mrey/export-scripts/blob/master/README.md).

9.- Clear cache and install assets

```
$ cd /path/to/pumukit2
$ sudo php app/console cache:clear
$ sudo php app/console cache:clear --env=prod --no-debug
$ sudo php app/console assets:install
```

10.- Execute import command

Follow the steps at [Resources/doc/MigrationExecutionGuide.md](https://gitlab.teltek.es/mrey/pumukitimportbundle/blob/master/Resources/doc/MigrationExecutionGuide.md).