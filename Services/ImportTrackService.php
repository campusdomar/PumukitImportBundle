<?php

namespace Pumukit\ImportBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Track;
use Pumukit\InspectionBundle\Services\InspectionFfprobeService;

class ImportTrackService
{
    private $dm;
    private $inspectionService;

    private $displayProfiles = array(
        'x264-mp4',
        'mp3',
        'mp4',
        'm4a',
        'broadcast-mp4',
        'broadcast-mp4a',
        'master_emitible',
        'master_trimming_emitible',
        'mp4_screencast',
        'broadcast-mp4',
        'broadcast-mp4a',
        'mp4_720p',
        'video_h264',
    );

    private $renameLanguages = array('ls' => 'lse');

    private $prefixProperty = array('uuid');
    private $prefix = 'pumukit1';

    /**
     * Constructor.
     *
     * @param DocumentManager          $documentManager
     * @param InspectionFfprobeService $inspectionService
     */
    public function __construct(DocumentManager $documentManager, InspectionFfprobeService $inspectionService)
    {
        $this->dm = $documentManager;
        $this->inspectionService = $inspectionService;
    }

    /**
     * Set Tracks.
     *
     * @param $tracksArray
     * @param $multimediaObject
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function setTracks($tracksArray, $multimediaObject)
    {
        foreach ($tracksArray as $tracks) {
            if (array_key_exists('0', $tracks)) {
                foreach ($tracks as $trackArray) {
                    $multimediaObject = $this->setTrack($trackArray, $multimediaObject);
                }
            } else {
                $trackArray = $tracks;
                $multimediaObject = $this->setTrack($trackArray, $multimediaObject);
            }
        }

        $this->updateType($multimediaObject);

        return $multimediaObject;
    }

    private function setTrack($trackArray, $multimediaObject)
    {
        $pumukit1Id = $this->getPumukit1Id($trackArray);
        $pathEnd = $this->getTrackPath($trackArray);
        $profileName = $this->getTrackProfile($trackArray);

        if ((null == $pathEnd) || (null == $profileName)) {
            throw new \Exception('Trying to add Track with null path or null profile name');
        }

        $track = new Track();
        if ($pumukit1Id) {
            $track->addTag('pumukit1id:'.$pumukit1Id);
        }
        $track->addTag('profile:'.$profileName);

        $auxPosition = strpos(strtolower($profileName), 'master');
        if ((0 <= $auxPosition) && (false !== $auxPosition)) {
            $track->addTag('master');
            $track->addTag('ENCODED_PUCHWEBTV');
        }

        if ($this->getProfileDisplay($profileName)) {
            $track->addTag('display');
            $track->addTag('html5');
            $track->addTag('podcast');
        }

        $track->setHide($this->getTrackHide($trackArray));

        $description = $this->getTrackDescription($trackArray);
        if (!empty($description)) {
            foreach ($description as $locale => $value) {
                if (null != $value) {
                    $track->setDescription($value, $locale);
                } else {
                    $track->setDescription('', $locale);
                }
            }
        }
        $language = $this->getTrackLanguage($trackArray);
        $track->setLanguage($language);

        $track->setPath($pathEnd);

        $url = $this->getTrackUrl($trackArray);
        if (null != $url) {
            $track->setUrl($url);
        }

        $onlyAudio = $this->getTrackOnlyAudio($trackArray);
        $track->setOnlyAudio($onlyAudio);

        $download = $this->getDownloadTrack($trackArray);
        $track->setAllowDownload($download);

        $this->inspectionService->autocompleteTrack($track);

        $numViews = $this->getTrackNumview($trackArray);
        $track->setNumview($numViews);
        $multimediaObject->setNumview($multimediaObject->getNumview() + $numViews);

        $multimediaObject->addTrack($track);

        if (isset($trackArray['properties'])) {
            $this->setProperties($trackArray['properties'], $track);
        }

        return $multimediaObject;
    }

    private function getPumukit1Id($trackArray = array())
    {
        $pumukit1Id = null;
        if (array_key_exists('@attributes', $trackArray)) {
            $attributes = $trackArray['@attributes'];
            if (array_key_exists('id', $attributes)) {
                $pumukit1Id = $attributes['id'];
            }
        }

        return $pumukit1Id;
    }

    private function getTrackPath($trackArray = array())
    {
        $path = null;
        if (array_key_exists('file', $trackArray)) {
            $path = $trackArray['file'];
        }

        return $path;
    }

    private function getTrackProfile($trackArray = array())
    {
        $profileName = null;
        if (array_key_exists('perfil', $trackArray)) {
            $profileArray = $trackArray['perfil'];
            if (array_key_exists('name', $profileArray)) {
                $profileName = $profileArray['name'];
            }
        }

        return trim($profileName);
    }

    private function getTrackLanguage($trackArray = array())
    {
        $language = null;
        if (array_key_exists('language', $trackArray)) {
            $languageArray = $trackArray['language'];
            if (array_key_exists('cod', $languageArray)) {
                $code = strtolower($languageArray['cod']);
                if (array_key_exists($code, $this->renameLanguages)) {
                    $language = $this->renameLanguages[$code];
                } else {
                    $language = $code;
                }
            }
        }

        return $language;
    }

    private function getTrackDescription($trackArray = array())
    {
        $description = array();
        if (array_key_exists('description', $trackArray)) {
            $descriptionArray = $trackArray['description'];
            $descriptionArray = array_filter($descriptionArray);
            if (!empty($descriptionArray)) {
                $description = $descriptionArray;
            }
        }

        return $description;
    }

    private function getProfileDisplay($profileName = '')
    {
        $display = false;
        if (in_array($profileName, $this->displayProfiles)) {
            $display = true;
        }

        return $display;
    }

    private function getTrackHide($trackArray = array())
    {
        $hide = true;
        if (array_key_exists('display', $trackArray)) {
            if ('true' == $trackArray['display']) {
                $hide = false;
            }
        }

        return $hide;
    }

    private function getTrackUrl($trackArray = array())
    {
        $url = null;
        if (array_key_exists('url', $trackArray)) {
            if (null != $trackArray['url']) {
                $url = $trackArray['url'];
            }
        }

        return $url;
    }

    private function getTrackOnlyAudio($trackArray)
    {
        $onlyAudio = false;
        if (array_key_exists('audio', $trackArray)) {
            if ((null != $trackArray['audio']) && ('1' == $trackArray['audio'])) {
                $onlyAudio = true;
            }
        }

        return $onlyAudio;
    }

    private function getDownloadTrack($trackArray)
    {
        $download = false;
        if (array_key_exists('download', $trackArray)) {
            if ((null != $trackArray['download']) && ('true' == $trackArray['download'])) {
                $download = true;
            }
        }

        return $download;
    }

    private function getTrackNumview($trackArray = array())
    {
        $numview = 0;
        if (array_key_exists('numview', $trackArray)) {
            if ($trackArray['numview']) {
                $numview = intval($trackArray['numview']);
            }
        }

        return $numview;
    }

    public function setProperties($propertiesArray, $track)
    {
        foreach ($propertiesArray as $key => $value) {
            if (!empty($value)) {
                if (in_array($key, $this->prefixProperty)) {
                    $key = $this->prefix.$key;
                }
                $track->setProperty($key, $value);
            }
        }

        return $track;
    }

    private function getTracksType($tracks)
    {
        foreach ($tracks as $track) {
            if (!$track->isOnlyAudio()) {
                return MultimediaObject::TYPE_VIDEO;
            }
        }

        return MultimediaObject::TYPE_AUDIO;
    }

    private function updateType($multimediaObject)
    {
        $multimediaObject = $event->getMultimediaObject();

        if ($multimediaObject->getProperty('opencast')) {
            $multimediaObject->setType(MultimediaObject::TYPE_VIDEO);
        } elseif ($multimediaObject->getProperty('externalplayer')) {
            $multimediaObject->setType(MultimediaObject::TYPE_EXTERNAL);
        } elseif ($displayTracks = $multimediaObject->getTracksWithTag('display')) {
            $multimediaObject->setType($this->getTracksType($displayTracks));
        } elseif ($masterTracks = $multimediaObject->getTracksWithTag('master')) {
            $multimediaObject->setType($this->getTracksType($masterTracks));
        } elseif ($otherTracks = $multimediaObject->getTracks()) {
            $multimediaObject->setType($this->getTracksType($otherTracks));
        } else {
            $multimediaObject->setType(MultimediaObject::TYPE_UNKNOWN);
        }

        $this->dm->flush();
    }
}
