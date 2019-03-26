<?php

namespace Pumukit\ImportBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;

class ImportOpencastService extends ImportCommonService
{
    private $dm;

    private $multimediaObjectProperties = array(
        'id' => 'opencast',
        'link' => 'opencasturl',
        'invert' => 'opencastinvert',
        'language' => 'opencastlanguage',
    );

    private $lowerFields = array('opencastlanguage');

    private $multimediaObjectSetFields = array(
        'duration' => 'setDuration',
    );

    private $seriesProperties = array('id' => 'opencast');

    /**
     * Constructor.
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
    }

    /**
     * Set Opencast in MultimediaObject.
     *
     * @param $opencastArray
     * @param $multimediaObject
     *
     * @return object
     */
    public function setOpencastInMultimediaObject($opencastArray, $multimediaObject)
    {
        $multimediaObject = $this->setOpencastProperties($opencastArray, $this->multimediaObjectProperties, $multimediaObject);
        $multimediaObject = $this->setFields($opencastArray, $this->multimediaObjectSetFields, $multimediaObject);
        $multimediaObject->setNumview($multimediaObject->getNumview() + $this->getOpencastNumview($opencastArray));
        $multimediaObject->setDuration(intval($multimediaObject->getDuration()));

        return $multimediaObject;
    }

    /**
     * Set Opencast in Series.
     *
     * @param $opencastArray
     * @param $series
     *
     * @return mixed
     */
    public function setOpencastInSeries($opencastArray, $series)
    {
        $series = $this->setOpencastProperties($opencastArray, $this->seriesProperties, $series);

        return $series;
    }

    private function setOpencastProperties($opencastArray, $resourceProperties, $resource)
    {
        foreach ($resourceProperties as $field => $property) {
            $value = $this->getOpencastFieldValue($opencastArray, $field);
            if (null != $value) {
                if ('true' === $value) {
                    $resource->setProperty($property, true);
                } elseif ('false' === $value) {
                    $resource->setProperty($property, false);
                } elseif (in_array($property, $this->lowerFields)) {
                    $resource->setProperty($property, strtolower($value));
                } else {
                    $resource->setProperty($property, $value);
                }
            }
        }

        return $resource;
    }

    private function getOpencastFieldValue($opencastArray = array(), $field = '')
    {
        $value = null;
        if (array_key_exists($field, $opencastArray)) {
            if (null != $opencastArray[$field]) {
                $value = $opencastArray[$field];
            }
        }

        return $value;
    }

    private function getOpencastNumview($opencastArray = array())
    {
        $numview = 0;
        if (array_key_exists('numview', $opencastArray)) {
            if ($opencastArray['numview']) {
                $numview = intval($opencastArray['numview']);
            }
        }

        return $numview;
    }
}
