<?php

namespace Pumukit\ImportBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Broadcast;

class ImportBroadcastServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $broadcastRepo;
    private $importBroadcastService;
    private $factoryService;
    private $resourcesDir;

    public function setUp()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->mmobjRepo = $this->dm
            ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->broadcastRepo = $this->dm
            ->getRepository('PumukitSchemaBundle:Broadcast');
        $this->importBroadcastService = $kernel->getContainer()
            ->get('pumukit_import.broadcast');
        $this->factoryService = $kernel->getContainer()
            ->get('pumukitschema.factory');
        $this->resourcesDir = realpath(__DIR__.'/../Resources/data/xmlfiles');

        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Broadcast')->remove(array());
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->mmobjRepo = null;
        $this->broadcastRepo = null;
        $this->importBroadcastService = null;
        $this->factoryService = null;
        $this->resourcesDir = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testSetBroadcast()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($this->broadcastRepo->findAll()));

        $xmlFile = $this->resourcesDir.'/broadcast.xml';
        $multimediaObject = $this->importXMLFile($xmlFile, $multimediaObject);

        $broadcasts = $this->broadcastRepo->findAll();
        $this->assertEquals(1, count($broadcasts));

        $this->assertEquals($multimediaObject->getBroadcast(), $broadcasts[0]);

        $broadcast = $multimediaObject->getBroadcast();

        $type = Broadcast::BROADCAST_TYPE_COR;
        $name = 'broadcast_name';
        $passwd = 'broadcast_password';
        $defaultSel = false;
        $i18nDescription = array('es' => 'Difusión en la Universidad',
                                 'gl' => 'Difusión na Universidade',
                                 'en' => '',
                                 );

        $this->assertEquals($type, $broadcast->getBroadcastTypeId());
        $this->assertEquals($name, $broadcast->getName());
        $this->assertEquals($passwd, $broadcast->getPasswd());
        $this->assertFalse($broadcast->getDefaultSel());
        $this->assertEquals($i18nDescription, $broadcast->getI18nDescription());
    }

    private function importXMLFile($filePath, $multimediaObject)
    {
        $xml = simplexml_load_file($filePath, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (false === $xml) {
            throw new \Exception('Not valid XML file: '.$filePath);
        }

        $xmlArray = json_decode(json_encode($xml, JSON_HEX_TAG), true);

        $multimediaObject = $this->importBroadcastService->setBroadcast($xmlArray, $multimediaObject);

        return $multimediaObject;
    }
}
