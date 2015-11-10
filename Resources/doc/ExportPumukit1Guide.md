# Export PuMuKIT1.7 Database Guide

## Steps to export Pumukit1 database and to import it into Pumukit2

1.- Connect to your Pumukit1.7 system:

```
$ ssh user@pumukit1.7-system
```

where `user` is your user and pumukit1.7-system your PuMuKIT1.7 host.

2.- Access the `batch` folder of your PuMuKIT1.7 application directory:

```
$ cd /path/to/pumukit1.7
$ cd batch
```

3.- Git clone this repo:

```
$ git clone URL_OF_THIS_REPO export
```

4.- Create the directory where the exported XML files are going to be placed. E.g.:

```
$ mkdir -p /tmp/export
```

5.- Remove previous exported data if any from the previous directory:

```
$ rm -rf /tmp/export/*
```

6.- Execute script to collect all series MySQL ids and build the final script with created directory:

```
$ cd /path/to/pumukit1.7
$ cd batch/export/
$ php export_all.php /tmp/export/ > script_export_series_bat.sh
```

7.- Execute created script to export all series:

```
$ bash script_export_series_bat.sh
```

8.- Check if all series have been exported successfully:

```
$ cd /tmp/export
$ grep -L "</serial>" *
```

* If there is no output to the previous command, all data is correct.

* If there is any output of type:

```
serialXXXX.xml
```

where XXXX is the Pumukit1 Id of the Series written in 4 digits (with zeros on the left side if needed),
then something went wrong with that specific series.

In this case, it is possible to execute the script for one specific series.
Follow the next steps. First XXXX should be the id without the zeros on the left side,
second XXXX should be the id in full 4 digits format:

```
$ cd /path/to/pumukit1.7/
$ cd batch/export/
$ php export.php XXXX > /tmp/export/serialXXXX.xml
```

Execute again the `grep` command to check the series now has been imported successfully.
Execute it as many times as needed.

If you want to execute the scripts for all series, start at step 5.

When all the series have been imported successfully, move to the next step.


9.- Create a zip file with all the exported series (example with the directory /tmp/export/):

```
$ cd /tmp
$ tar czvf export.tgz export/
$ tar czvf /path/to/pumukit1.7/web/export.tgz export/
```

10.- Exit from the Pumukit1 system:

```
$ exit
```

11.- Connect to the Pumukit2 system:

```
$ ssh user@pumukit2-system
```

where `user` is the user to connect and `pumukit2-system` the IP or name of the PuMuKIT2 system.


12.- Chose a folder where to copy to the export.tgz file previously created and get the export.tgz file.

```
$ cd /path/to/safe/folder
$ wget URL_OF_YOUR_PUMUKIT1.7/export.tgz
```

13.- Remove previous export files if any:

```
$ rm -rf export/
```

14.- Untar the exported file:

```
$ tar zxvf export.tgz
```

This will create a folder named 'export' into the current directory. This full path directory will be used into `import:pumukit:series` command as the ROUTE parameter in the Migration Execution Guide:

```
/path/to/safe/folder/export
```

15.- Exit the Pumukit2 system:

```
$ exit
```

16.- Connect to Pumukit1 system:

```
$ ssh user@pumukit1.7-system
```

17.- Remove the export.tgz created at web folder:

```
$ cd /path/to/pumukit1.7
$ cd web/
$ rm export.tgz
```

18.- Exit the Pumukit1 system:

```
$ exit
```

19.- Connect to the Pumukit2 system:

```
$ ssh user@pumukit2-system
```

20.- Follow the steps of the Migration Execution Guide:

URL link will be available soon.