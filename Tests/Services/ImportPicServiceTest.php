<?php

namespace Pumukit\ImportBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ImportPicServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $importPicService;
    private $factoryService;
    private $resourcesDir;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->mmobjRepo = $this->dm
            ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->importPicService = $kernel->getContainer()
            ->get('pumukit_import.pic');
        $this->factoryService = $kernel->getContainer()
            ->get('pumukitschema.factory');
        $this->resourcesDir = realpath(__DIR__.'/../Resources/data/xmlfiles');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->flush();
    }

    public function testSetPics()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($series->getPics()));
        $this->assertEquals(0, count($multimediaObject->getPics()));

        $seriesPicsXmlFile = $this->resourcesDir.'/seriespics.xml';
        $series = $this->importXMLFile($seriesPicsXmlFile, $series);

        $this->assertEquals(2, count($series->getPics()));

        $mmPicsXmlFile = $this->resourcesDir.'/mmpics.xml';
        $multimediaObject = $this->importXMLFile($mmPicsXmlFile, $multimediaObject);

        $this->assertEquals(2, count($multimediaObject->getPics()));
    }

    public function testSetSinglePic()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($series->getPics()));
        $this->assertEquals(0, count($multimediaObject->getPics()));

        $seriesPicsXmlFile = $this->resourcesDir.'/singleseriespic.xml';
        $series = $this->importXMLFile($seriesPicsXmlFile, $series);

        $seriesPics = $series->getPics();
        $this->assertEquals(1, count($seriesPics));

        $seriesPicUrl = '/uploads/pic/Serial/409/Captura_de_pantalla_2014-12-24_a_la_s__14.15.36.png';
        $this->assertEquals($seriesPicUrl, $seriesPics[0]->getUrl());

        $mmPicsXmlFile = $this->resourcesDir.'/singlemmpic.xml';
        $multimediaObject = $this->importXMLFile($mmPicsXmlFile, $multimediaObject);

        $mmPics = $multimediaObject->getPics();
        $this->assertEquals(1, count($mmPics));

        $mmPicUrl = '/uploads/pic/Serial/429/Video/3601/plasticos.jpg';
        $this->assertEquals($mmPicUrl, $mmPics[0]->getUrl());
    }

    private function importXMLFile($filePath, $resource)
    {
        $xml = simplexml_load_file($filePath, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            throw new \Exception('Not valid XML file: '.$filePath);
        }

        $xmlArray = json_decode(json_encode($xml, JSON_HEX_TAG), true);

        $resource = $this->importPicService->setPics($xmlArray, $resource);

        return $resource;
    }
}
