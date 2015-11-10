<?php

namespace Pumukit\ImportBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Link;

class ImportLinkService extends ImportCommonService
{
    private $linkRenameFields = array(
                                      "name" => "setI18nName",
                                      "url" => "setUrl"
                                      );

    /**
     * Set Links
     *
     * @param array $linksArray
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject
     */
    public function setLinks($linksArray, $multimediaObject)
    {
        foreach ($linksArray as $links) {
            if (array_key_exists("0", $links)) {
                foreach ($links as $linkArray) {
                    $multimediaObject = $this->setLink($linkArray, $multimediaObject);
                }
            } else {
                $linkArray = $links;
                $multimediaObject = $this->setLink($linkArray, $multimediaObject);
            }
        }

        return $multimediaObject;
    }

    /**
     * Set Link
     *
     * @param array $linkArray
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject
     */
    public function setLink($linkArray=array(), $multimediaObject)
    {
        $link = $this->createLink($linkArray);
        $multimediaObject->addLink($link);

        return $multimediaObject;
    }

    private function createLink($linkArray=array())
    {
        $link = new Link();
        $link = $this->setFields($linkArray, $this->linkRenameFields, $link);

        return $link;
    }
}