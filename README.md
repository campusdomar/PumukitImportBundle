Documentation
=============

For contribution to the documentation you can find it on [Resources/doc](Resources/doc).

Installation
============

Before installing any bundle is necessary to login to your GitHub account. You have different ways of doing it. We recommend to use:

```
$ curl -u "username" https://api.github.com
```

For more options, visit: [https://developer.github.com/v3/#authentication](https://developer.github.com/v3/#authentication)


Steps 1 and 2 requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Setp 1: Introduce repository in the root project composer.json
---------------------------------------------------------

Open a command console, enter your project directory and execute the
following command to add this repo:

```bash
$ composer config repositories.pumukitimportbundle vcs https://github.com/campusdomar/PuMuKIT2-import-bundle
```

Step 2: Download the Bundle
---------------------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```bash
$ composer require campusdomar/pmk2-import-bundle dev-master
```

Step 3: Install the Bundle
--------------------------

Install the bundle by executing the following line command. This command updates the Kernel to enable the bundle (app/AppKernel.php) and loads the routing (app/config/routing.yml) to add the bundle routes\
.

```bash
$ cd /path/to/pumukit2/
$ php app/console pumukit:install:bundle Pumukit/ImportBundle/PumukitImportBundle
```

Step 4: Execute the scripts
---------------------------

Follow the steps at [Resources/doc/ImportExecutionGuide.md](Resources/doc/ImportExecutionGuide.md).