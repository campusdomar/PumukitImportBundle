# Import Bundle


## *Configuration and execution*


### Install bundles


Before installing any bundle is necessary to login to your GitHub account. You have different ways of doing it. We recommend to use:

```
$ curl -u "username" https://api.github.com
```

For more options, visit: [https://developer.github.com/v3/#authentication](https://developer.github.com/v3/#authentication)


### Delete database if any collection was previously added


```
$ mongo
> use pumukit
> db.dropDatabase()
> exit
```


### Init Tags of all installed bundles if haven't done previously


Go to root Pumukit2 folder

```
$ cd /path/to/pumukit2
$ php app/console pumukit:init:repo tag --force
```

### Check files access


Check the machine where PuMuKIT2 is install, have access to the share where files (tracks) are stored:

```
/mnt/nas/pumukit1.7
```

Check the machine where PuMuKIT2 is install, have access to the share where uploads (materials and pics) are stored:

Go to root Pumukit2 folder

```
$ cd /path/to/pumukit2
$ cd web/
$ rm -rf uploads
$ ln -s /mnt/nas/pumukit1.7/uploads
```


### Exec command


Go to root PuMuKIT2 folder and execute the import command, changing ROUTE by the route path where the XML files are.

```
$ cd /path/to/pumukit2
$ time php app/console import:pumukit:series --data="ROUTE" --force --env=prod | sudo tee app/logs/import.log
```


### Post-import steps


Steps to do manually after the import is done.


#### Create Users

Go to root PuMuKIT2 folder

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


#### Go to back-office and create Live channels

Route: /admin/live


## Clear cache and install assets. Update Import Bundle if necessary.


```
$ cd /path/to/pumukit2
$ php composer.phar update teltek/pmk2-import-bundle
$ php app/console cache:clear
$ php app/console cache:clear --env=prod --no-debug
$ php app/console assets:install
```