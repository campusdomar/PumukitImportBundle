# Allow Publication Channels Guide

*This page is updated to the PuMuKIT-import-bundle 2.0.x and to the PuMuKIT 2.4.0*

If you have used ARCA, GoogleVideoSiteMap, iTunesU or YouTubeEDU channels in your PuMuKIT1.7 server, you can still use them in PuMuKIT2.1, except for GoogleVideoSiteMap.

## Allow ARCA Channel

1.- Install the ArcaBundle:

Follow the steps at its [Installation guide](https://github.com/campusdomar/PuMuKIT2/blob/2.1.x/src/Pumukit/ArcaBundle/Resources/doc/InstallationGuide.md).

2.- Enable the import data of the ARCA Channel at `app/config/parameters.yml` file of your PuMuKIT2.1 project:

```
pumukit_import:
    ignore_arca: false
```

## Allow iTunesU Channel

1.- Install the PodcastBundle:

Follow the steps at its [Installation guide](https://github.com/campusdomar/PuMuKIT2/blob/2.1.x/src/Pumukit/PodcastBundle/Resources/doc/InstallationGuide.md).

2.- Enable the import data of the iTunesU Channel at `app/config/parameters.yml` file of your PuMuKIT2.1 project:

```
pumukit_import:
    ignore_itunesu: false
```

## Allow YouTubeEDU Channel

1.- Install the YoutubeBundle:

Follow the steps at its [Installation guide](https://github.com/teltek/PuMuKIT2-youtube-bundle/blob/1.0.x/README.md).

2.- Enable the import data of the YouTubeEDU Channel at `app/config/parameters.yml` file of your PuMuKIT2.1 project:

```
pumukit_import:
    ignore_youtube: false
```