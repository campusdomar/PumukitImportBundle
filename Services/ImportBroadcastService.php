<?php

namespace Pumukit\ImportBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Broadcast;

class ImportBroadcastService extends ImportCommonService
{
    private $dm;
    private $repo;

    private $broadcastRenameFields = array(
                                           'name' => 'setName',
                                           'passwd' => 'setPasswd',
                                           );
    private $broadcastTypeValues = array(
                                         'pub' => Broadcast::BROADCAST_TYPE_PUB,
                                         'pri' => Broadcast::BROADCAST_TYPE_PRI,
                                         'cor' => Broadcast::BROADCAST_TYPE_COR,
                                         );

    /**
     * Constructor.
     *
     * @param DocumentManager $documentManager
     */
    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        $this->repo = $this->dm->getRepository('PumukitSchemaBundle:Broadcast');
    }

    /**
     * Set broadcast.
     *
     * @param array            $broadcastArray
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject $multimediaObject
     */
    public function setBroadcast($broadcastArray, $multimediaObject)
    {
        $broadcast = $this->getExistingBroadcast($broadcastArray);
        if (null == $broadcast) {
            $broadcast = $this->createBroadcast($broadcastArray);
        }

        $multimediaObject = $this->setBroadcastToMultimediaObject($broadcast, $multimediaObject);

        return $multimediaObject;
    }

    private function getExistingBroadcast($broadcastArray)
    {
        $broadcast = null;
        $name = null;

        if (array_key_exists('name', $broadcastArray)) {
            $name = $broadcastArray['name'];
        }

        if (null != $name) {
            $broadcast = $this->repo->findOneByName($name);
        }

        return $broadcast;
    }

    private function createBroadcast($broadcastArray)
    {
        $broadcast = new Broadcast();

        $broadcast = $this->setName($broadcastArray, $broadcast);
        $broadcast = $this->setPasswd($broadcastArray, $broadcast);
        $broadcast = $this->setDescription($broadcastArray, $broadcast);
        $broadcast = $this->setBroadcastTypeIdAndDefaultSel($broadcastArray, $broadcast);

        $this->dm->persist($broadcast);
        $this->dm->flush();

        return $broadcast;
    }

    private function setBroadcastTypeIdAndDefaultSel($broadcastArray, $broadcast)
    {
        if (array_key_exists('broadcastType', $broadcastArray)) {
            $broadcastType = $broadcastArray['broadcastType'];
            if ($broadcastType) {
                if (array_key_exists('name', $broadcastType)) {
                    $typeId = $broadcastType['name'];
                    if (array_key_exists($typeId, $this->broadcastTypeValues)) {
                        $broadcastTypeId = $this->broadcastTypeValues[$typeId];
                        $broadcast->setBroadcastTypeId($broadcastTypeId);
                    } else {
                        throw new \Exception('Not valid Broadcast type id '.$value);
                    }
                }
                if (array_key_exists('defaultsel', $broadcastType)) {
                    $defaultsel = $broadcastType['defaultsel'];
                    $broadcast = $this->setFieldWithValue('setDefaultSel', $defaultsel, $broadcast);
                }
            }
        }

        return $broadcast;
    }

    private function setName($broadcastArray, $broadcast)
    {
        if (array_key_exists('name', $broadcastArray)) {
            $name = $broadcastArray['name'];
            $broadcast = $this->setFieldWithValue('setName', $name, $broadcast);
        }

        return $broadcast;
    }

    private function setPasswd($broadcastArray, $broadcast)
    {
        if (array_key_exists('passwd', $broadcastArray)) {
            $passwd = $broadcastArray['passwd'];
            $broadcast = $this->setFieldWithValue('setPasswd', $passwd, $broadcast);
        }

        return $broadcast;
    }

    private function setDescription($broadcastArray, $broadcast)
    {
        $i18nDescription = array();

        if (array_key_exists('description', $broadcastArray)) {
            $i18nDescription = $broadcastArray['description'];
            if (!empty(array_filter($i18nDescription))) {
                foreach ($i18nDescription as $locale => $value) {
                    if (null != $value) {
                        $broadcast->setDescription($value, $locale);
                    } else {
                        $broadcast->setDescription('', $locale);
                    }
                }
            }
        }

        return $broadcast;
    }

    private function setBroadcastToMultimediaObject($broadcast, $multimediaObject)
    {
        if ((null != $broadcast) && ($broadcast instanceof Broadcast)) {
            $multimediaObject->setBroadcast($broadcast);
        }

        return $multimediaObject;
    }
}
