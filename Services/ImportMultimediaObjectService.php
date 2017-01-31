<?php

namespace Pumukit\ImportBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\YoutubeBundle\Document\Youtube;
use Pumukit\SchemaBundle\Services\FactoryService;
use Pumukit\SchemaBundle\Services\TagService;

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
        'id' => 'pumukit1id',
        'rank' => 'pumukit1rank',
    );

    private $multimediaObjectRenameFields = array(
        'statusId' => 'setStatus',
        'copyright' => 'setLicense',
        'recordDate' => 'setRecordDate',
        'publicDate' => 'setPublicDate',
        'title' => 'setI18nTitle',
        'subtitle' => 'setI18nSubtitle',
        'keyword' => 'setI18nKeyword',
        'description' => 'setI18nDescription',
        'line2' => 'setI18nLine2',
    );

    private $prototypeRenameFields = array(
        'copyright' => 'setLicense',
        'recordDate' => 'setRecordDate',
        'publicDate' => 'setPublicDate',
        'title' => 'setI18nTitle',
        'subtitle' => 'setI18nSubtitle',
        'keyword' => 'setI18nKeyword',
        'description' => 'setI18nDescription',
        'line2' => 'setI18nLine2',
    );

    private $prefixProperty = array('dep', 'precinct_id', 'mm_person_mail');
    private $propertiesAsDateTime = array('offDate');
    private $isYoutubeProperty = 'youtube';
    private $prefix = 'pumukit1';

    /**
     * ImportMultimediaObjectService constructor.
     *
     * @param DocumentManager                $documentManager
     * @param FactoryService                 $factoryService
     * @param TagService                     $tagService
     * @param ImportBroadcastService         $importBroadcastService
     * @param ImportEmbeddedBroadcastService $importEmbeddedBroadcastService
     * @param ImportTagService               $importTagService
     * @param ImportMaterialService          $importMaterialService
     * @param ImportTrackService             $importTrackService
     * @param ImportLinkService              $importLinkService
     * @param ImportPeopleService            $importPeopleService
     * @param ImportPicService               $importPicService
     * @param ImportOpencastService          $importOpencastService
     */
    public function __construct(
        DocumentManager $documentManager,
        FactoryService $factoryService,
        TagService $tagService,
        ImportBroadcastService $importBroadcastService,
        ImportEmbeddedBroadcastService $importEmbeddedBroadcastService,
        ImportTagService $importTagService,
        ImportMaterialService $importMaterialService,
        ImportTrackService $importTrackService,
        ImportLinkService $importLinkService,
        ImportPeopleService $importPeopleService,
        ImportPicService $importPicService,
        ImportOpencastService $importOpencastService
    ) {
        $this->dm = $documentManager;
        $this->factoryService = $factoryService;
        $this->tagService = $tagService;
        $this->importBroadcastService = $importBroadcastService;
        $this->importEmbeddedBroadcastService = $importEmbeddedBroadcastService;
        $this->importTagService = $importTagService;
        $this->importMaterialService = $importMaterialService;
        $this->importTrackService = $importTrackService;
        $this->importLinkService = $importLinkService;
        $this->importPeopleService = $importPeopleService;
        $this->importPicService = $importPicService;
        $this->importOpencastService = $importOpencastService;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->tagRepo = $this->dm->getRepository('PumukitSchemaBundle:Tag');
        $this->youtubeRepo = $this->dm->getRepository('PumukitYoutubeBundle:Youtube');
    }

    /**
     * Set multimedia object prototype.
     *
     * @param array  $seriesTemplatesArray
     * @param Series $series
     *
     * @return Series $series
     */
    public function setMultimediaObjectPrototype($prototypesArray, $series)
    {
        foreach ($prototypesArray as $prototypes) {
            // There should be only 1 mmTemplate
            if (array_key_exists('0', $prototypes)) {
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
     * Set multimedia objects.
     *
     * @param array  $mmsArray
     * @param Series $series
     *
     * @return Series $series
     */
    public function setMultimediaObjects($mmsArray, $series)
    {
        foreach ($mmsArray as $mms) {
            if (array_key_exists('0', $mms)) {
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
     * Set multimedia object.
     *
     * @param array  $mmArray
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
            if (is_array($fieldValue) && 1 == count($fieldValue) && isset($fieldValue[0]) && '' == trim(
                    $fieldValue[0]
                )
            ) {
                continue;
            }

            if (array_key_exists($fieldName, $this->multimediaObjectRenameFields)) {
                $setField = $this->multimediaObjectRenameFields[$fieldName];
                $multimediaObject = $this->setFieldWithValue($setField, $fieldValue, $multimediaObject);
            } else {
                switch ($fieldName) {
                    case '@attributes':
                        $multimediaObject = $this->setAttributesProperties(
                            $fieldValue,
                            $this->attributesSetProperties,
                            $multimediaObject
                        );
                        break;
                    case 'broadcast':
                        $multimediaObject = $this->importBroadcastService->setBroadcast($fieldValue, $multimediaObject);
                        break;
                    case 'embedded_broadcast':
                        $multimediaObject = $this->importEmbeddedBroadcastService->setEmbeddedBroadcast($fieldValue, $multimediaObject);
                        break;
                    case 'genre':
                        $multimediaObject = $this->importTagService->setGenreTag($fieldValue, $multimediaObject);
                        break;
                    case 'mmGrounds':
                        $multimediaObject = $this->importTagService->setGroundTags($fieldValue, $multimediaObject);
                        break;
                    case 'announce':
                        $multimediaObject = $this->importTagService->setAnnounceTag($fieldValue, $multimediaObject);
                        break;
                    case 'mmPics':
                        $multimediaObject = $this->importPicService->setPics($fieldValue, $multimediaObject);
                        break;
                    case 'mmPersons':
                        $multimediaObject = $this->importPeopleService->setPeople($fieldValue, $multimediaObject);
                        break;
                    case 'files':
                        $multimediaObject = $this->importTrackService->setTracks($fieldValue, $multimediaObject);
                        break;
                    case 'materials':
                        $multimediaObject = $this->importMaterialService->setMaterials($fieldValue, $multimediaObject);
                        break;
                    case 'links':
                        $multimediaObject = $this->importLinkService->setLinks($fieldValue, $multimediaObject);
                        break;
                    case 'publicationChannels':
                        $multimediaObject = $this->importTagService->setPublicationChannelTags(
                            $fieldValue,
                            $multimediaObject
                        );
                        break;
                    case 'publishingDecisions':
                        $multimediaObject = $this->importTagService->setPublishingDecisionTags(
                            $fieldValue,
                            $multimediaObject
                        );
                        break;
                    case 'opencast':
                        $multimediaObject = $this->importOpencastService->setOpencastInMultimediaObject(
                            $fieldValue,
                            $multimediaObject
                        );
                        break;
                    case 'subserialTitle':
                        $multimediaObject = $this->setSubseriesTitleProperty($fieldValue, $multimediaObject);
                        break;
                    case 'subserial':
                        $multimediaObject = $this->setSubseriesProperty($fieldValue, $multimediaObject);
                        break;
                    case 'mail':
                        $multimediaObject = $this->setEmailProperty($fieldValue, $multimediaObject);
                        break;
                    case 'properties':
                        $multimediaObject = $this->setProperties($fieldValue, $multimediaObject);
                        break;
                }
            }
        }



        $aPubChannel = array('PUCHWEBTV','PUCHMOODLE','PUCHYOUTUBE');
        if($multimediaObject->containsAnyTagWithCodes($aPubChannel)) {
            $multimediaObject->setStatus(MultimediaObject::STATUS_PUBLISHED);
        } else {
            $multimediaObject->setStatus(intval($multimediaObject->getStatus()));
        }

        $multimediaObject->setRecordDate(new \Datetime($multimediaObject->getRecordDate()));
        $multimediaObject->setPublicDate(new \Datetime($multimediaObject->getPublicDate()));

        $this->dm->persist($multimediaObject);
        $this->dm->flush();
        $this->dm->clear(get_class($multimediaObject));

        return $series;
    }

    /**
     * Update multimedia object prototype.
     *
     * @param array  $mmArray
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
                    case '@attributes':
                        $prototype = $this->setAttributesProperties(
                            $fieldValue,
                            $this->attributesSetProperties,
                            $prototype
                        );
                        break;
                    case 'broadcast':
                        $prototype = $this->importBroadcastService->setBroadcast($fieldValue, $prototype);
                        break;
                    case 'genre':
                        $prototype = $this->importTagService->setGenreTag($fieldValue, $prototype);
                        break;
                    case 'mmTemplateGrounds':
                        $prototype = $this->importTagService->setGroundTags($fieldValue, $prototype);
                        break;
                    case 'announce':
                        $prototype = $this->importTagService->setAnnounceTag($fieldValue, $prototype);
                        break;
                    case 'mmTemplatePersons':
                        $prototype = $this->importPeopleService->setPeople($fieldValue, $prototype);
                        break;
                    case 'subserialTitle':
                        $prototype = $this->setSubseriesTitleProperty($fieldValue, $prototype);
                        break;
                    case 'subserial':
                        $prototype = $this->setSubseriesProperty($fieldValue, $prototype);
                        break;
                    case 'mail':
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
                    $subseriesTitleArray[$locale] = '';
                }
            }
            $multimediaObject->setProperty('subseriestitle', $subseriesTitleArray);
        }

        return $multimediaObject;
    }

    private function setSubseriesProperty($subseries, $multimediaObject)
    {
        if ('true' == $subseries) {
            $multimediaObject->setProperty('subseries', true);
        } elseif ('false' == $subseries) {
            $multimediaObject->setProperty('subseries', false);
        }

        return $multimediaObject;
    }

    private function setEmailProperty($email, $multimediaObject)
    {
        if (null != $email) {
            $multimediaObject->setProperty('email', $email);
        }

        return $multimediaObject;
    }

    private function setAttributesProperties($attributes, $attributesSetProperties, $resource)
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

    private function setProperties($properties, $multimediaObject)
    {
        if (is_array($properties)) {
            foreach ($properties as $key => $property) {
                if (!empty($property)) {
                    if (in_array($key, $this->prefixProperty)) {
                        $key = $this->prefix.$key;
                    }

                    if (in_array($key, $this->propertiesAsDateTime)) {
                        $multimediaObject->setPropertyAsDateTime($key, $property);
                    } else {
                        $multimediaObject->setProperty($key, $property);
                    }

                    if (strpos($key, $this->isYoutubeProperty) !== false) {
                        $this->addYoutube($key, $property, $multimediaObject);
                    }
                }
            }
        }

        return $multimediaObject;
    }

    private function addYoutube($key, $property, $multimediaObject)
    {
        switch ($key) {
            case 'youtube_id':
                $youtube = new Youtube();
                $youtube->setMultimediaObjectId($multimediaObject->getId());
                $youtube->setYoutubeId($property);
                $youtube->setPlaylists(array());
                $youtube->setForce(false);
                $youtube->setMultimediaObjectUpdateDate(new \DateTime('now'));
                $youtube->setSyncMetadataDate(new \DateTime('now'));

                $this->dm->persist($youtube);
                $multimediaObject->setProperty('youtube', $youtube->getId());
                $this->dm->persist($multimediaObject);

                break;
            case 'youtube_link':
                $youtube = $this->youtubeRepo->find($multimediaObject->getProperty('youtube'));
                $youtube->setLink($property);
                $multimediaObject->setProperty('youtubeurl', $property);
                break;
            case 'youtube_embed':
                $youtube = $this->youtubeRepo->find($multimediaObject->getProperty('youtube'));

                $property = $property['iframe']['@attributes'];
                $embed = '<iframe class="'.$property['class'].'" type="'.$property['type'].'" width="'.$property['width'].'" height="'.$property['height'].'" src="'.$property['src'].'" frameborder="'.$property['frameborder'].'"></iframe>';
                $youtube->setEmbed($embed);
                $multimediaObject->setProperty('youtube_embed', $embed);
                $this->dm->persist($multimediaObject);
                break;
            case 'youtube_status':
                $youtube = $this->youtubeRepo->find($multimediaObject->getProperty('youtube'));
                if ($youtube) {
                    $youtube->setStatus($property);
                }
                break;
        }

        return $multimediaObject;
    }
}
