<?php

namespace Pumukit\ImportBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ImportMaterialServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $importMaterialService;
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
        $this->importMaterialService = $kernel->getContainer()
            ->get("pumukit_import.material");
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

    public function testSetMaterials()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getMaterials()));

        $xmlFile = $this->resourcesDir.'/materials.xml';
        $multimediaObject = $this->importXMLFile($xmlFile, $multimediaObject);

        $this->assertEquals(3, count($multimediaObject->getMaterials()));
    }

    public function testSetSingleMaterial()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getMaterials()));

        $xmlFile = $this->resourcesDir.'/singlematerial.xml';
        $multimediaObject = $this->importXMLFile($xmlFile, $multimediaObject);

        $this->assertEquals(1, count($multimediaObject->getMaterials()));

        $material = $multimediaObject->getMaterials()[0];

        $i18nName = array("es" => "material", "gl" => "material", "en" => "");
        $url = "/uploads/material/Video/3599/zpatron_uno.avs";
        $mimetype = "Avsp";

        $this->assertEquals($i18nName, $material->getI18nName());
        $this->assertEquals($url, $material->getUrl());
        $this->assertEquals($mimetype, $material->getMimeType());
        $this->assertTrue($material->getHide());
    }

    private function importXMLFile($filePath=null, $multimediaObject)
    {
        $xml = simplexml_load_file($filePath, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($xml === false) {
            throw new \Exception("Not valid XML file: ".$filePath);
        }

        $xmlArray = json_decode(json_encode($xml, JSON_HEX_TAG), TRUE);

        $multimediaObject = $this->importMaterialService->setMaterials($xmlArray, $multimediaObject);

        return $multimediaObject;
    }
}