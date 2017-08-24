<?php

namespace Pumukit\ImportBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Tag;
use Pumukit\SchemaBundle\Services\TagService;

class ImportTagService extends ImportCommonService
{
    private $dm;
    private $repo;
    private $opencastTag;
    private $tagService;
    private $locales;

    private $placesMetatagCode = 'Lugares';
    private $metatagCodes = array(
                                  'Directriz' => 'DIRECTRIZ',
                                  'Unesco' => 'UNESCO',
                                  'Lugares' => 'PLACES',
                                  );

    private $directrizTagRenameCodes = array(
                                             'Dciencia' => 'Dscience',
                                             'Djuridicosocial' => 'Dsocial',
                                             'Dsalud' => 'Dhealth',
                                             'Dtecnologia' => 'Dtechnical',
                                             'Dhumanistica' => 'Dhumanities',
                                             );

    private $groundTagRenameFields = array(
                                           'cod' => 'setCod',
                                           'name' => 'setI18nTitle',
                                           );

    private $publicationChannelTagRenameCodes = array(
                                                      'WebTV' => 'PUCHWEBTV',
                                                      'Moodle' => 'PUCHMOODLE',
                                                      'ARCA' => 'PUCHARCA',
                                                      'iTunesU' => 'PUCHPODCAST',
                                                      'YouTubeEDU' => 'PUCHYOUTUBE',
                                                      );

    private $ignoredPublicationChannels = array(
                                                'ARCA' => 'ignore_arca',
                                                'GoogleVideoSiteMap' => 'ignore_google',
                                                'iTunesU' => 'ignore_itunesu',
                                                );

    private $publishingDecisionTagRenameCodes = array(
                                                      'Announce' => 'PUDENEW',
                                                      'Editorial1' => 'PUDEPD1',
                                                      'Editorial2' => 'PUDEPD2',
                                                      'Editorial3' => 'PUDEPD3',
                                                      'Titular' => 'PUDEHEADLINE'
                                                      );

    /**
     * Constructor.
     *
     * @param DocumentManager $documentManager
     * @param TagService      $tagService
     * @param array           $locales
     * @param array           $publicationChannelsToIgnore
     */
    public function __construct(DocumentManager $documentManager, TagService $tagService, $locales = array(), $publicationChannelsToIgnore = array())
    {
        $this->dm = $documentManager;
        $this->tagService = $tagService;
        $this->locales = $locales;
        $this->publicationChannelsToIgnore = $publicationChannelsToIgnore;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:Tag');
    }

    /**
     * Set genre tag.
     *
     * @param array            $genreArray
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject
     */
    public function setGenreTag($genreArray, $multimediaObject)
    {
        $genreTag = $this->getExistingTag($genreArray, 'GENRE');
        if (null == $genreTag) {
            $genreTag = $this->createTag($genreArray, 'GENRE');
        }

        $multimediaObject = $this->addTagToMultimediaObject($genreTag, $multimediaObject);

        return $multimediaObject;
    }

    /**
     * Set ground tags.
     *
     * @param array            $groundsArray
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject
     */
    public function setGroundTags($groundsArray, $multimediaObject)
    {
        foreach ($groundsArray as $grounds) {
            if(is_array($grounds)) {
                if (array_key_exists('0', $grounds)) {
                    foreach ($grounds as $groundArray) {
                        $multimediaObject = $this->setGroundTag($groundArray, $multimediaObject);
                    }
                } else {
                    $groundArray = $grounds;
                    $multimediaObject = $this->setGroundTag($groundArray, $multimediaObject);
                }
            }
        }

        return $multimediaObject;
    }

    private function setGroundTag($groundArray, $multimediaObject)
    {
        $tag = $this->getExistingGroundTag($groundArray);
        if (null == $tag) {
            $tagCode = $this->getGroundTagCode($groundArray);
            if (!array_key_exists('groundType', $groundArray)) {
                throw new \Exception("Trying to add an inexisting tag with code '".$tagCode."' and without tag parent. Please, init pumukit tags.");
            }
            $groundTypeArray = $groundArray['groundType'];
            if (!array_key_exists('name', $groundTypeArray)) {
                throw new \Exception("Trying to add an inexisting tag with code: '".$tagCode."' and without tag parent code. Please, init pumukit tags.");
            }
            $metatagCode = $groundTypeArray['name'];
            if ((null == $metatagCode) || (!array_key_exists($metatagCode, $this->metatagCodes))) {
                throw new \Exception("Trying to add an inexisting tag with code '".$tagCode."' and a not valid parent tag with code '".$metatagCode."'");
            }
            if (($this->placesMetatagCode === $metatagCode) && (array_key_exists($this->placesMetatagCode, $this->metatagCodes))) {
                $tag = $this->createTag($groundArray, $this->metatagCodes[$this->placesMetatagCode], true, true);
            }
        }

        $multimediaObject = $this->addTagToMultimediaObject($tag, $multimediaObject);

        return $multimediaObject;
    }

    /**
     * Set announce tag.
     *
     * @param string           $announce
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject
     */
    public function setAnnounceTag($announce, $multimediaObject)
    {
        if ('true' == $announce) {
            $pudenewTag = $this->repo->findOneByCod('PUDENEW');
            if (null == $pudenewTag) {
                throw new \Exception('Publication Decisions not properly set. Please, init pumukit tags');
            }
            $multimediaObject = $this->addTagToMultimediaObject($pudenewTag, $multimediaObject);
        }

        return $multimediaObject;
    }

    /**
     * Set Publication Channels Tags.
     *
     * @param array            $publicationChannelsArray
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject
     */
    public function setPublicationChannelTags($publicationChannelsArray, $multimediaObject)
    {
        foreach ($publicationChannelsArray as $publicationChannels) {
            if (is_array($publicationChannels)) {
                if (array_key_exists('0', $publicationChannels)) {
                    foreach ($publicationChannels as $publicationChannelArray) {
                        $multimediaObject = $this->setPublicationChannelTag(
                            $publicationChannelArray,
                            $multimediaObject
                        );
                    }
                } else {
                    $publicationChannelArray = $publicationChannels;
                    $multimediaObject = $this->setPublicationChannelTag($publicationChannelArray, $multimediaObject);
                }
            }
        }

        return $multimediaObject;
    }

    /**
     * Set Publication Channel Tag.
     *
     * @param array            $publicationChannelArray
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject
     */
    public function setPublicationChannelTag($publicationChannelArray, $multimediaObject)
    {
        $addTag = $this->getPublicationChannelAddTag($publicationChannelArray);

        if ($addTag) {
            $tag = $this->getExistingPublicationChannelTag($publicationChannelArray);
            if (null == $tag) {
                $name = $this->getTagValue($publicationChannelArray, 'name');
                throw new \Exception("There is no publication channel tag with name '".$name."' to add. Please init Pumukit tags.");
            }
            $multimediaObject = $this->addTagToMultimediaObject($tag, $multimediaObject);
        }

        return $multimediaObject;
    }

    /**
     * Set Publication Channels Tags.
     *
     * @param array            $publishingDecisionsArray
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject
     */
    public function setPublishingDecisionTags($publishingDecisionsArray, $multimediaObject)
    {
        foreach ($publishingDecisionsArray as $publishingDecisions) {
            if (array_key_exists('0', $publishingDecisions)) {
                foreach ($publishingDecisions as $publishingDecisionArray) {
                    $multimediaObject = $this->setPublishingDecisionTag($publishingDecisionArray, $multimediaObject);
                }
            } else {
                $publishingDecisionArray = $publishingDecisions;
                $multimediaObject = $this->setPublishingDecisionTag($publishingDecisionArray, $multimediaObject);
            }
        }

        return $multimediaObject;
    }

    /**
     * Set Publication Channel Tag.
     *
     * @param array            $publishingDecisionArray
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject
     */
    public function setPublishingDecisionTag($publishingDecisionArray, $multimediaObject)
    {
        $tag = $this->getExistingPublishingDecisionTag($publishingDecisionArray);
        if (null == $tag) {
            $name = $this->getTagValue($publishingDecisionArray, 'name');
            throw new \Exception("There is no publishing decision tag with name '".$name."' to add. Please init Pumukit tags.");
        }
        $multimediaObject = $this->addTagToMultimediaObject($tag, $multimediaObject);

        return $multimediaObject;
    }

    private function getExistingTag($tagArray, $prefix = '')
    {
        $tagCode = $this->getTagCode($tagArray, $prefix);
        $tag = $this->repo->findOneByCod(trim($tagCode));

        return $tag;
    }

    private function getExistingGroundTag($tagArray = array())
    {
        $tagCode = $this->getGroundTagCode($tagArray);
        $tag = $this->repo->findOneByCod(trim($tagCode));

        return $tag;
    }

    private function getExistingPublicationChannelTag($tagArray = array())
    {
        $tagCode = $this->getPublicationChannelTagCode($tagArray);
        $tag = $this->repo->findOneByCod(trim($tagCode));

        return $tag;
    }

    private function getExistingPublishingDecisionTag($tagArray = array())
    {
        $tagCode = $this->getPublishingDecisionTagCode($tagArray);
        $tag = $this->repo->findOneByCod(trim($tagCode));

        return $tag;
    }

    private function createTag($tagArray, $prefix = '', $setParent = true, $useCode = false)
    {
        $prefix = trim($prefix);

        $tag = new Tag();

        if ($useCode) {
            $tagCode = $this->getGroundTagCode($tagArray);
        } else {
            $tagCode = $this->getTagCode($tagArray, $prefix);
        }

        $tag->setCod($tagCode);
        if (array_key_exists('name', $tagArray)) {
            if (!empty(array_filter($tagArray['name']))) {
                $i18nTitle = $tagArray['name'];
                foreach ($i18nTitle as $locale => $value) {
                    if (null != $value) {
                        $tag->setTitle($value, $locale);
                    } else {
                        $tag->setTitle('', $locale);
                    }
                }
            }
        }
        $tag->setMetatag(false);
        $tag->setDisplay(true);
        if ($setParent) {
            $metaTag = $this->repo->findOneByCod($prefix);
            if (null == $metaTag) {
                $metaTag = $this->createMetaTag($prefix);
            }
            $tag->setParent($metaTag);
        }
        $tag->setCreated(new \Datetime('now'));

        $this->dm->persist($tag);
        $this->dm->flush();

        return $tag;
    }

    private function createMetaTag($code)
    {
        $rootTag = $this->repo->findOneByCod('ROOT');
        if (null == $rootTag) {
            throw new \Exception('ROOT tag is not created. Please init tags.');
        }

        $metaTag = new Tag();

        $metaTag->setCod($code);
        $metaTag->setMetatag(true);
        $metaTag->setDisplay(true);
        $metaTag->setParent($rootTag);
        foreach ($this->locales as $locale) {
            $metaTag->setTitle($code, $locale);
            $metaTag->setDescription($code, $locale);
        }
        $metaTag->setCreated(new \Datetime('now'));

        $this->dm->persist($metaTag);
        $this->dm->flush();

        return $metaTag;
    }

    private function addTagToMultimediaObject($tag, $multimediaObject)
    {
        if ($tag instanceof Tag) {
            if (!$multimediaObject->containsTag($tag)) {
                $tagAdded = $this->tagService->addTagToMultimediaObject($multimediaObject, $tag->getId(), false);
            }
        } else {
            throw new \Exception('Trying to add a not valid Tag');
        }

        return $multimediaObject;
    }

    /**
     * Get tag code.
     *
     * NOTE: Building 'cod' Tag field
     * - Genre: 'id' and 'cod' are equals in most cases
     * Conclusion: We take name[GENRE] + 'id' for building unique 'cod'
     */
    private function getTagCode($tagArray = array(), $prefix = '')
    {
        if (!array_key_exists('@attributes', $tagArray)) {
            throw new \Exception('Trying to add Tag without code (non exisiting @attributes)');
        }
        $attributes = $tagArray['@attributes'];
        if ((null == $attributes) && (!array_key_exists('id', $attributes))) {
            throw new \Exception('Trying to add Tag without code (non exisiting msyql id)');
        }
        $pumukit1Id = $attributes['id'];
        if (null == $pumukit1Id) {
            throw new \Exception('Trying to add Tag without unique code (null pumukit1 id)');
        }
        if (null == $prefix) {
            throw new \Exception('Trying to add Tag without unique code (null prefix)');
        }
        $tagCode = $prefix.$attributes['id'];

        return $tagCode;
    }

    private function getGroundTagCode($tagArray = array())
    {
        if ((null == $tagArray) && (!array_key_exists('cod', $tagArray))) {
            throw new \Exception('Trying to add Tag without code (non exisiting cod)');
        }
        $tagCode = $tagArray['cod'];
        if (array_key_exists($tagCode, $this->directrizTagRenameCodes)) {
            $tagCode = $this->directrizTagRenameCodes[$tagCode];
        }
        if (null == $tagCode) {
            throw new \Exception('Trying to add Tag without unique code (null cod)');
        }

        return $tagCode;
    }

    private function getPublicationChannelTagCode($tagArray = array())
    {
        $tagCode = $this->getTagValue($tagArray, 'name');

        if (array_key_exists($tagCode, $this->publicationChannelTagRenameCodes)) {
            $tagCode = $this->publicationChannelTagRenameCodes[$tagCode];
        }
        if (null == $tagCode) {
            throw new \Exception('Trying to add Tag without unique code (null cod)');
        }

        return $tagCode;
    }

    private function getPublishingDecisionTagCode($tagArray = array())
    {
        $tagCode = $this->getTagValue($tagArray, 'name');
        if (array_key_exists($tagCode, $this->publishingDecisionTagRenameCodes)) {
            $tagCode = $this->publishingDecisionTagRenameCodes[$tagCode];
        }
        if (null == $tagCode) {
            throw new \Exception('Trying to add Tag without unique code (null cod)');
        }

        return $tagCode;
    }

    private function getPublicationChannelAddTag($publicationChannelArray = array())
    {
        $addTag = false;

        $name = $this->getTagValue($publicationChannelArray, 'name');
        if (array_key_exists($name, $this->ignoredPublicationChannels)) {
            if ($this->publicationChannelsToIgnore[$this->ignoredPublicationChannels[$name]]) {
                return false;
            }
        }

        $status = $this->getTagValue($publicationChannelArray, 'status');
        $enable = $this->getTagValue($publicationChannelArray, 'enable');

        if ((1 == $status) && ('true' == $enable)) {
            $addTag = true;
        }

        return $addTag;
    }

    private function getTagValue($publicationChannelArray = array(), $field = '')
    {
        $value = null;
        if (array_key_exists('@attributes', $publicationChannelArray)) {
            $attributes = $publicationChannelArray['@attributes'];
            if (array_key_exists($field, $attributes)) {
                $value = $attributes[$field];
            }
        }

        return $value;
    }
}
