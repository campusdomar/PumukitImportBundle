<?php

namespace Pumukit\ImportBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Pic;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TagService;
use Pumukit\ImportBundle\Services\ImportBroadcastService;
use Pumukit\ImportBundle\Services\ImportTagService;
use Pumukit\ImportBundle\Services\ImportMaterialService;
use Pumukit\ImportBundle\Services\ImportTrackService;
use Pumukit\ImportBundle\Services\ImportLinkService;
use Pumukit\ImportBundle\Services\ImportPeopleService;
use Pumukit\ImportBundle\Services\ImportPicService;
use Pumukit\ImportBundle\Services\ImportOpencastService;

class ImportMultimediaObjectService extends ImportCommonService
{
    private $dm;
    private $repo;
    private $tagRepo;
    private $factoryService;
    private $tagService;
    private $importBroadcastService;
    private $importTagService;
    private $importMaterialService;
    private $importTrackService;
    private $importLinkService;
    private $importPeopleService;
    private $importPicService;
    private $importOpencastService;

    private $attributesSetProperties = array(
                                         "id" => "pumukit1id",
                                         "rank" => "pumukit1rank"
                                         );

    private $multimediaObjectRenameFields = array(
                                                  "statusId" => "setStatus",
                                                  "copyright" => "setCopyright",
                                                  "recordDate" => "setRecordDate",
                                                  "publicDate" => "setPublicDate",
                                                  "title" => "setI18nTitle",
                                                  "subtitle" => "setI18nSubtitle",
                                                  "keyword" => "setI18nKeyword",
                                                  "description" => "setI18nDescription",
                                                  "line2" => "setI18nLine2"
                                                  );

    private $prototypeRenameFields = array(
                                           "copyright" => "setCopyright",
                                           "recordDate" => "setRecordDate",
                                           "publicDate" => "setPublicDate",
                                           "title" => "setI18nTitle",
                                           "subtitle" => "setI18nSubtitle",
                                           "keyword" => "setI18nKeyword",
                                           "description" => "setI18nDescription",
                                           "line2" => "setI18nLine2"
                                           );

    /**
     * Constructor
     *
     * @param DocumentManager         $documentManager
     * @param FactoryService          $factoryService
     * @param TagService              $tagService
     * @param ImportBroadcastService  $importBroadcastService
     * @param ImportTagService        $importTagService
     * @param ImportMaterialService   $importMaterialService
     * @param ImportTrackService      $importTrackService
     * @param ImportLinkService       $importLinkService
     * @param ImportPeopleService     $importPeopleService
     * @param ImportPicService        $importPicService
     * @param ImportOpencastService   $importOpencastService
     */
    public function __construct(DocumentManager $documentManager, FactoryService $factoryService, TagService $tagService, ImportBroadcastService $importBroadcastService, ImportTagService $importTagService, ImportMaterialService $importMaterialService, ImportTrackService $importTrackService, ImportLinkService $importLinkService, ImportPeopleService $importPeopleService, ImportPicService $importPicService, ImportOpencastService $importOpencastService)
    {
        $this->dm = $documentManager;
        $this->factoryService = $factoryService;
        $this->tagService = $tagService;
        $this->importBroadcastService = $importBroadcastService;
        $this->importTagService = $importTagService;
        $this->importMaterialService = $importMaterialService;
        $this->importTrackService = $importTrackService;
        $this->importLinkService = $importLinkService;
        $this->importPeopleService = $importPeopleService;
        $this->importPicService = $importPicService;
        $this->importOpencastService = $importOpencastService;
        $this->repo = $this->dm->getRepository("PumukitSchemaBundle:MultimediaObject");
        $this->tagRepo = $this->dm->getRepository("PumukitSchemaBundle:Tag");
    }

    /**
     * Set multimedia object prototype
     *
     * @param array $seriesTemplatesArray
     * @param Series $series
     *
     * @return Series $series
     */
    public function setMultimediaObjectPrototype($prototypesArray, $series)
    {
        foreach ($prototypesArray as $prototypes) {
            // There should be only 1 mmTemplate
            if (array_key_exists("0", $prototypes)) {
                foreach ($prototypes as $prototypeArray) {
                    $series = $this->updateMultimediaObjectPrototype($prototypeArray, $series);
                }
            } else {
                $prototypeArray = $prototypes;
                $series = $this->updateMultimediaObjectPrototype($prototypeArray, $series);
            }
        }

        return $series;
    }

    /**
     * Set multimedia objects
     *
     * @param array $mmsArray
     * @param Series $series
     *
     * @return Series $series
     */
    public function setMultimediaObjects($mmsArray, $series)
    {
        foreach ($mmsArray as $mms) {
            if (array_key_exists("0", $mms)) {
                foreach ($mms as $mmArray) {
                    $series = $this->setMultimediaObject($mmArray, $series);
                }
            } else {
                $mmArray = $mms;
                $series = $this->setMultimediaObject($mmArray, $series);
            }
        }

        return $series;
    }

    /**
     * Set multimedia object
     *
     * @param array $mmArray
     * @param Series $series
     *
     * @return Series $series
     */
    public function setMultimediaObject($mmArray, $series)
    {
        $multimediaObject = new MultimediaObject();
        $multimediaObject->setSeries($series);
        $multimediaObject->setNumview(0);
        $this->dm->persist($multimediaObject);
        $this->dm->persist($series);
        $this->dm->flush();
        foreach ($mmArray as $fieldName => $fieldValue) {
            if (array_key_exists($fieldName, $this->multimediaObjectRenameFields)) {
                $setField = $this->multimediaObjectRenameFields[$fieldName];
                $multimediaObject = $this->setFieldWithValue($setField, $fieldValue, $multimediaObject);
            } else {
                switch ($fieldName) {
                case "@attributes":
                    $multimediaObject = $this->setAttributesProperties($fieldValue, $this->attributesSetProperties, $multimediaObject);
                    break;
                case "broadcast":
                    $multimediaObject = $this->importBroadcastService->setBroadcast($fieldValue, $multimediaObject);
                    break;
                case "genre":
                    $multimediaObject = $this->importTagService->setGenreTag($fieldValue, $multimediaObject);
                    break;
                case "mmGrounds":
                    $multimediaObject = $this->importTagService->setGroundTags($fieldValue, $multimediaObject);
                    break;
                case "announce":
                    $multimediaObject = $this->importTagService->setAnnounceTag($fieldValue, $multimediaObject);
                    break;
                case "mmPics":
                    $multimediaObject = $this->importPicService->setPics($fieldValue, $multimediaObject);
                    break;
                case "mmPersons":
                    $multimediaObject = $this->importPeopleService->setPeople($fieldValue, $multimediaObject);
                    break;
                case "files":
                    $multimediaObject = $this->importTrackService->setTracks($fieldValue, $multimediaObject);
                    break;
                case "materials":
                    $multimediaObject = $this->importMaterialService->setMaterials($fieldValue, $multimediaObject);
                    break;
                case "links":
                    $multimediaObject = $this->importLinkService->setLinks($fieldValue, $multimediaObject);
                    break;
                case "publicationChannels":
                    $multimediaObject = $this->importTagService->setPublicationChannelTags($fieldValue, $multimediaObject);
                    break;
                case "publishingDecisions":
                    $multimediaObject = $this->importTagService->setPublishingDecisionTags($fieldValue, $multimediaObject);
                    break;
                case "opencast":
                    $multimediaObject = $this->importOpencastService->setOpencastInMultimediaObject($fieldValue, $multimediaObject);
                    break;
                case "subserialTitle":
                    $multimediaObject = $this->setSubseriesTitleProperty($fieldValue, $multimediaObject);
                    break;
                case "subserial":
                    $multimediaObject = $this->setSubseriesProperty($fieldValue, $multimediaObject);
                    break;
                case "mail":
                    $multimediaObject = $this->setEmailProperty($fieldValue, $multimediaObject);
                    break;
                }
            }
        }

        $multimediaObject->setStatus(intval($multimediaObject->getStatus()));
        $multimediaObject->setRecordDate(new \Datetime($multimediaObject->getRecordDate()));
        $multimediaObject->setPublicDate(new \Datetime($multimediaObject->getPublicDate()));

        $this->dm->persist($multimediaObject);
        $this->dm->flush();
        $this->dm->clear(get_class($multimediaObject));

        return $series;
    }

    /**
     * Update multimedia object prototype
     *
     * @param array $mmArray
     * @param Series $series
     *
     * @return Series $series
     */
    public function updateMultimediaObjectPrototype($mmArray, $series)
    {
        $prototype = $this->repo->findPrototype($series);

        foreach ($mmArray as $fieldName => $fieldValue) {
            if (array_key_exists($fieldName, $this->prototypeRenameFields)) {
                $setField = $this->prototypeRenameFields[$fieldName];
                $prototype = $this->setFieldWithValue($setField, $fieldValue, $prototype);
            } else {
                switch ($fieldName) {
                case "@attributes":
                    $prototype = $this->setAttributesProperties($fieldValue, $this->attributesSetProperties, $prototype);
                    break;
                case "broadcast":
                    $prototype = $this->importBroadcastService->setBroadcast($fieldValue, $prototype);
                    break;
                case "genre":
                    $prototype = $this->importTagService->setGenreTag($fieldValue, $prototype);
                    break;
                case "mmTemplateGrounds":
                    $prototype = $this->importTagService->setGroundTags($fieldValue, $prototype);
                    break;
                case "announce":
                    $prototype = $this->importTagService->setAnnounceTag($fieldValue, $prototype);
                    break;
                case "mmTemplatePersons":
                    $prototype = $this->importPeopleService->setPeople($fieldValue, $prototype);
                    break;
                case "subserialTitle":
                    $prototype = $this->setSubseriesTitleProperty($fieldValue, $prototype);
                    break;
                case "subserial":
                    $prototype = $this->setSubseriesProperty($fieldValue, $prototype);
                    break;
                case "mail":
                    $prototype = $this->setEmailProperty($fieldValue, $prototype);
                    break;
                }
            }
        }

        $prototype->setRecordDate(new \Datetime($prototype->getRecordDate()));
        $prototype->setPublicDate(new \Datetime($prototype->getPublicDate()));

        $this->dm->persist($prototype);
        $this->dm->flush();

        return $series;
    }

    private function setSubseriesTitleProperty($subseriesTitleArray, $multimediaObject)
    {
        if (!empty(array_filter($subseriesTitleArray))) {
            foreach ($subseriesTitleArray as $locale => $value) {
                if (null == $value) {
                    $subseriesTitleArray[$locale] = "";
                }
            }
            $multimediaObject->setProperty("subseriestitle", $subseriesTitleArray);
        }

        return $multimediaObject;
    }

    private function setSubseriesProperty($subseries, $multimediaObject)
    {
        if ("true" == $subseries) {
            $multimediaObject->setProperty("subseries", true);
        } elseif ("false" == $subseries) {
            $multimediaObject->setProperty("subseries", false);
        }

        return $multimediaObject;
    }

    private function setEmailProperty($email, $multimediaObject)
    {
        if (null != $email) {
            $multimediaObject->setProperty("email", $email);
        }

        return $multimediaObject;
    }

    private function setAttributesProperties($attributes=array(), $attributesSetProperties=array(), $resource)
    {
        foreach ($attributes as $field => $value) {
            if (array_key_exists($field, $attributesSetProperties)) {
                $property = $attributesSetProperties[$field];
                if (null != $value) {
                    $resource->setProperty($property, $value);
                }
            }
        }

        return $resource;
    }
}