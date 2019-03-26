<?php

namespace Pumukit\ImportBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class ImportOpencastServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $seriesRepo;
    private $factoryService;
    private $importOpencastService;
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
        $this->seriesRepo = $this->dm
            ->getRepository('PumukitSchemaBundle:Series');
        $this->factoryService = $kernel->getContainer()
            ->get('pumukitschema.factory');
        $this->importOpencastService = $kernel->getContainer()
            ->get('pumukit_import.opencast');
        $this->resourcesDir = realpath(__DIR__.'/../Resources/data/xmlfiles');

        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->mmobjRepo = null;
        $this->seriesRepo = null;
        $this->importOpencastService = null;
        $this->factoryService = null;
        $this->resourcesDir = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testSetOpencastInMultimediaObject()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);
        $multimediaObject->setNumview(3);
        $this->dm->persist($multimediaObject);
        $this->dm->flush();

        $xmlFile = $this->resourcesDir.'/opencastinmultimediaobject.xml';
        $xmlArray = $this->importXMLFile($xmlFile);

        $this->importOpencastService->setOpencastInMultimediaObject($xmlArray, $multimediaObject);

        $allMultimediaObjects = $this->mmobjRepo->findAll();
        $this->assertEquals(2, count($allMultimediaObjects));

        $multimediaObject = $this->mmobjRepo->findOneByStatus(MultimediaObject::STATUS_BLOQ);

        $opencastId = 'a93f5411-822b-4a84-a4e4-d6619fe86fbe';
        $this->assertEquals($opencastId, $multimediaObject->getProperty('opencast'));

        $opencastLink = 'http://engage14.pumukit.es/engage/ui/watch.html?id=%id%';
        $this->assertEquals($opencastLink, $multimediaObject->getProperty('opencasturl'));

        $opencastInvert = false;
        $this->assertEquals($opencastInvert, $multimediaObject->getProperty('opencastinvert'));

        $numview = 102 + 3;
        $this->assertEquals($numview, $multimediaObject->getNumview());

        $duration = 950;
        $this->assertEquals($duration, $multimediaObject->getDuration());
    }

    public function testSetOpencastInSeries()
    {
        $series = $this->factoryService->createSeries();

        $xmlFile = $this->resourcesDir.'/opencastinseries.xml';
        $xmlArray = $this->importXMLFile($xmlFile);

        $series = $this->importOpencastService->setOpencastInSeries($xmlArray, $series);

        $allSeries = $this->seriesRepo->findAll();
        $this->assertEquals(1, count($allSeries));

        $series = $allSeries[0];

        $opencastId = '319b6a62-7da1-4681-9752-93f2791c5f3a';
        $this->assertEquals($opencastId, $series->getProperty('opencast'));
    }

    private function importXMLFile($filePath = null)
    {
        $xml = simplexml_load_file($filePath, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (false === $xml) {
            throw new \Exception('Not valid XML file: '.$filePath);
        }

        $xmlArray = json_decode(json_encode($xml, JSON_HEX_TAG), true);

        return $xmlArray;
    }
}
