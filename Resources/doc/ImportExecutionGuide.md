# Import Execution Guide

*This page is updated to the PuMuKIT 2.1.0*

Follow the next steps to import into PuMuKIT2.1 the metadata exported from PuMuKIT1.7

## System requirements

Make sure your PuMuKIT2 server achieve the system requirements

Configure your `web/storage/` and `web/uploads/` directories to be mounted on your PuMuKIT share in a way PuMuKIT1 and PuMuKIT2 servers have access to the multimedia files.

## PuMuKIT1.7 metadata exportation

Make sure you have exported the PuMuKIT1.7 metadata following the [PuMuKIT1-data-export steps](https://github.com/campusdomar/PuMuKIT1-data-export/blob/master/README.md)
and place the `export` directory at an accessible route.

## Connect to your PuMuKIT2.1 server

```bash
$ ssh user@pumukit2.1-server
```

where `user` is your access user and `pumukit2.1-server` your PuMuKIT2.1 host.

## Delete database if any collection was previously added

```
$ mongo
> use pumukit
> db.dropDatabase()
> exit
```

## Init Tags of all installed bundles

Go to root Pumukit2 folder

```
$ cd /path/to/pumukit2
$ php app/console pumukit:init:repo tag --force
```

## Allow Publication Channels

If you have used ARCA, GoogleVideoSiteMap, iTunesU or YouTubeEDU channels in your PuMuKIT1.7 server, you can still use them in PuMuKIT2.1, except for GoogleVideoSiteMap.

If you want to activate one of these publication channels for importing data, follow the steps at [AllowPublicationChannelsGuide.md](AllowPublicationChannelsGuide.md).

## Exec command

Go to root PuMuKIT2 folder and execute the import command, changing ROUTE by the route path where the XML files are (the PuMuKIT1.7 exported files).

```
$ cd /path/to/pumukit2
$ time php app/console import:pumukit:series --data="ROUTE" --force --env=prod | sudo tee app/logs/import.log
```

## Post-import steps

Steps to do manually after the import is done.

### Create Users

Go to root PuMuKIT2 folder and create the same users you had in PuMuKIT1.7.

```
$ cd /path/to/pumukit2
$ php app/console fos:user:create
$ php app/console fos:user:activate
```

To give more permissions than User

```
$ php app/console fos:user:promote
```

Publisher permissions: ROLE_ADMIN
Admin permissions: ROLE_SUPER_ADMIN

### Create Live channels

Go to back-office and create the live channels your had in PuMuKIT1.7.

Route: `/admin/live`
