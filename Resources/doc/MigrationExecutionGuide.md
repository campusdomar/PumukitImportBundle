# Migration Execution Guide

Follow the next steps to import the metadata exported from PuMuKIT1.7


## Delete database if any collection was previously added


```
$ mongo
> use pumukit
> db.dropDatabase()
> exit
```


## Init Tags of all installed bundles if haven't done previously


Go to root Pumukit2 folder

```
$ cd /path/to/pumukit2
$ php app/console pumukit:init:repo tag --force
```


## Allow Publication Channels

If you have used ARCA, GoogleVideoSiteMap, iTunesU or YouTubeEDU channels in your PuMuKIT1.7 server, you can still use them in PuMuKIT2.1, except for GoogleVideoSiteMap.

If you want to activate one of these publication for importing data, follow the steps at [Resources/doc/AllowPublicationChannelsGuide.md](https://gitlab.teltek.es/mrey/pumukitimportbundle/blob/master/Resources/doc/AllowPublicationChannelsGuide.md).


## Exec command


Go to root PuMuKIT2 folder and execute the import command, changing ROUTE by the route path where the XML files are.

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

Route: /admin/live
