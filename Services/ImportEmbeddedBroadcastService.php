<?php

namespace Pumukit\ImportBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\EmbeddedBroadcast;
use Pumukit\SchemaBundle\Services\GroupService;
use Pumukit\SchemaBundle\Document\Group;

class ImportEmbeddedBroadcastService extends ImportCommonService
{
    private $dm;
    private $repo;
    private $groupRepo;
    private $groupService;

    /**
     * ImportEmbeddedBroadcastService constructor.
     *
     * @param DocumentManager $documentManager
     * @param GroupService    $groupService
     */
    public function __construct(DocumentManager $documentManager, GroupService $groupService)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:EmbeddedBroadcast');
        $this->groupRepo = $this->dm->getRepository('PumukitSchemaBundle:Group');
        $this->groupService = $groupService;
    }

    /**
     * @param $aEmbeddedBroadcast
     * @param $multimediaObject
     *
     * @return mixed
     */
    public function setEmbeddedBroadcast($aEmbeddedBroadcast, $multimediaObject)
    {
        $oEmbeddedBroadcast = $this->createBroadcast($aEmbeddedBroadcast);

        $multimediaObject = $this->setEmbeddedBroadcastToMultimediaObject($oEmbeddedBroadcast, $multimediaObject);

        return $multimediaObject;
    }

    private function createBroadcast($aEmbeddedBroadcast)
    {
        $oEmbeddedBroadcast = new EmbeddedBroadcast();

        if (array_key_exists('name', $aEmbeddedBroadcast)) {
            $oEmbeddedBroadcast->setName($aEmbeddedBroadcast['name']);
        }

        if (array_key_exists('@attributes', $aEmbeddedBroadcast)) {
            if (array_key_exists('type', $aEmbeddedBroadcast['@attributes'])) {
                $oEmbeddedBroadcast->setType($aEmbeddedBroadcast['@attributes']['type']);
            }
        }

        if (array_key_exists('groups', $aEmbeddedBroadcast)) {
            foreach ($aEmbeddedBroadcast['groups'] as $sGroup) {
                if (!is_array($sGroup)) {
                    $group = $this->groupRepo->findOneBy(array('key' => $sGroup));
                    if (!$group) {
                        $group = new Group();
                        $group->setKey($sGroup);
                        $group->setName($sGroup);
                        $group->setOrigin('pmk1');
                        $this->groupService->create($group);
                    }
                    $oEmbeddedBroadcast->addGroup($group);
                }
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
