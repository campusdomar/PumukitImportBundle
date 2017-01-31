<?php

namespace Pumukit\ImportBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;

class ImportEmbeddedBroadcastService extends ImportCommonService
{
    private $dm;
    private $repo;

    /**
     * ImportEmbeddedBroadcastService constructor.
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:EmbeddedBroadcast');
    }

    /**
     * @param $aEmbeddedBroadcast
     * @param $multimediaObject
     *
     * @return mixed
     */
    public function setEmbeddedBroadcast($aEmbeddedBroadcast, $multimediaObject)
    {
        $oEmbeddedBroadcast = $this->getExistingEmbeddedBroadcast($aEmbeddedBroadcast);
        if (null == $oEmbeddedBroadcast) {
            $oEmbeddedBroadcast = $this->createBroadcast($aEmbeddedBroadcast);
        }

        $multimediaObject = $this->setEmbeddedBroadcastToMultimediaObject($oEmbeddedBroadcast, $multimediaObject);

        return $multimediaObject;
    }

    private function getExistingEmbeddedBroadcast($aEmbeddedBroadcast)
    {
        $broadcast = null;
        $name = null;

        if (array_key_exists('name', $aEmbeddedBroadcast)) {
            $name = $aEmbeddedBroadcast['name'];
        }

        if (null != $name) {
            $broadcast = $this->repo->findOneByName($name);
        }

        return $broadcast;
    }

    private function createBroadcast($aEmbeddedBroadcast)
    {
        $oEmbeddedBroadcast = new EmbeddedBroadcast();

        if (array_key_exists('name', $aEmbeddedBroadcast)) {
            $oEmbeddedBroadcast->setName($aEmbeddedBroadcast['name']);
        }

        if (array_key_exists('type', $aEmbeddedBroadcast)) {
            $oEmbeddedBroadcast->setType($aEmbeddedBroadcast['@attributes']['type']);
        }

        if (array_key_exists('groups', $aEmbeddedBroadcast)) {
            foreach ($aEmbeddedBroadcast['groups'] as $sGroup) {
                $oEmbeddedBroadcast->addGroup($sGroup);
            }
        }

        if (array_key_exists('password', $aEmbeddedBroadcast)) {
            $oEmbeddedBroadcast->setPassword($aEmbeddedBroadcast['password']);
        }

        $this->dm->persist($oEmbeddedBroadcast);
        $this->dm->flush();

        return $oEmbeddedBroadcast;
    }

    private function setEmbeddedBroadcastToMultimediaObject($oEmbeddedBroadcast, $multimediaObject)
    {
        if ((null != $oEmbeddedBroadcast) && ($oEmbeddedBroadcast instanceof EmbeddedBroadcast)) {
            $multimediaObject->setEmbeddedBroadcast($oEmbeddedBroadcast);
        }

        return $multimediaObject;
    }
}
