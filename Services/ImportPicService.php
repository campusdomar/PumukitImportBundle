<?php

namespace Pumukit\ImportBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Pic;

class ImportPicService
{
    private $dm;

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
     * Set pics.
     *
     * @param array  $picsArray
     * @param object $resource
     *
     * @return object $resource
     */
    public function setPics($picsArray, $resource)
    {
        foreach ($picsArray as $pics) {
            if (is_array($pics)) {
                if (array_key_exists('0', $pics)) {
                    foreach ($pics as $picArray) {
                        $resource = $this->setPic($picArray, $resource);
                    }
                } else {
                    $picArray = $pics;
                    $resource = $this->setPic($picArray, $resource);
                }
            }
        }

        return $resource;
    }

    private function setPic($picArray, $resource)
    {
        if (is_array($picArray) && array_key_exists('url', $picArray)) {
            if (null != $picArray['url']) {
                $pic = new Pic();
                $pic->setUrl($picArray['url']);
                $resource->addPic($pic);
            }
        }

        return $resource;
    }
}
