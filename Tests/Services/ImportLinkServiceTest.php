<?php

namespace Pumukit\ImportBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ImportLinkServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $importLinkService;
    private $factoryService;
    private $resourcesDir;

    public function __construct()
    {
        $options = array("environment" => "test");
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
            ->get("doctrine_mongodb")->getManager();
        $this->mmobjRepo = $this->dm
            ->getRepository("PumukitSchemaBundle:MultimediaObject");
        $this->importLinkService = $kernel->getContainer()
            ->get("pumukit_import.link");
        $this->factoryService = $kernel->getContainer()
            ->get("pumukitschema.factory");
        $this->resourcesDir = realpath(__DIR__.'/../Resources/data/xmlfiles');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->flush();
    }

    public function testSetLinks()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getLinks()));

        $xmlFile = $this->resourcesDir.'/links.xml';
        $multimediaObject = $this->importXMLFile($xmlFile, $multimediaObject);

        $this->assertEquals(2, count($multimediaObject->getLinks()));
    }

    public function testSetSingleLink()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getLinks()));

        $xmlFile = $this->resourcesDir.'/singlelink.xml';
        $multimediaObject = $this->importXMLFile($xmlFile, $multimediaObject);

        $this->assertEquals(1, count($multimediaObject->getLinks()));

        $link = $multimediaObject->getLinks()[0];

        $url = "http://external-url.com/link1";
        $i18nName = array("es" => "Enlace uno", "gl" => "Ligazón un", "en" => "Link one");

        $this->assertEquals($url, $link->getUrl());
        $this->assertEquals($i18nName, $link->getI18nName());
    }

    private function importXMLFile($filePath=null, $multimediaObject)
    {
        $xml = simplexml_load_file($filePath, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($xml === false) {
            throw new \Exception("Not valid XML file: ".$filePath);
        }

        $xmlArray = json_decode(json_encode($xml, JSON_HEX_TAG), TRUE);

        $multimediaObject = $this->importLinkService->setLinks($xmlArray, $multimediaObject);

        return $multimediaObject;
    }
}