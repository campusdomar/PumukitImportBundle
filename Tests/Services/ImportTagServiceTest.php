<?php

namespace Pumukit\ImportBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Pumukit\SchemaBundle\Document\Tag;
use Symfony\Component\Process\Process;

class ImportTagServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $tagRepo;
    private $importTagService;
    private $factoryService;
    private $resourcesDir;
    private $console;
    private $bundles;

    public function __construct()
    {
        $options = array("environment" => "test");
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
            ->get("doctrine_mongodb")->getManager();
        $this->mmobjRepo = $this->dm
            ->getRepository("PumukitSchemaBundle:MultimediaObject");
        $this->tagRepo = $this->dm
            ->getRepository("PumukitSchemaBundle:Tag");
        $this->importTagService = $kernel->getContainer()
            ->get("pumukit_import.tag");
        $this->factoryService = $kernel->getContainer()
            ->get("pumukitschema.factory");
        $this->resourcesDir = realpath(__DIR__.'/../Resources/data/xmlfiles');
        $this->console = $kernel->getRootDir() . "/../app/console --env=test";
        $this->bundles = $kernel->getContainer()->getParameter('kernel.bundles');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')->remove(array());
        $this->dm->flush();
    }

    public function testSetGenreTag()
    {
        $rootTag = $this->initRootTag();

        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTags()));

        $xmlFile = $this->resourcesDir.'/genretag.xml';
        $xmlArray = $this->importXMLFile($xmlFile);
        $multimediaObject = $this->importTagService->setGenreTag($xmlArray, $multimediaObject);

        $this->assertEquals(5, count($this->tagRepo->findAll()));
        $this->assertEquals(5, count($multimediaObject->getTags()));

        $tagCodes = array();
        foreach ($multimediaObject->getTags() as $tag) {
            $tagCodes[] = $tag->getCod();
        }
        $codes = array("GENRE22", "GENRE", "ROOT");
        $this->assertEquals($codes, $tagCodes);

        $genre22Tag = $this->tagRepo->findOneByCod("GENRE22");
        $i18nTitle = array("es" => "Matterhorn", "gl" => "", "en" => "Matterhorn");
        $this->assertEquals($i18nTitle, $genre22Tag->getI18nTitle());
    }

    public function testSetPrecinctTag()
    {
        $rootTag = $this->initRootTag();

        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTags()));

        $xmlFile = $this->resourcesDir.'/precincttag.xml';
        $xmlArray = $this->importXMLFile($xmlFile);
        $multimediaObject = $this->importTagService->setPrecinctTag($xmlArray, $multimediaObject);

        $this->assertEquals(4, count($this->tagRepo->findAll()));
        $this->assertEquals(4, count($multimediaObject->getTags()));

        $tagCodes = array();
        foreach ($multimediaObject->getTags() as $tag) {
            $tagCodes[] = $tag->getCod();
        }
        $codes = array("PLACE0001PRECINCT01", "PLACE0001", "PLACE", "ROOT");
        $this->assertEquals($codes, $tagCodes);

        $precinct1Tag = $this->tagRepo->findOneByCod("PLACE0001PRECINCT01");

        $i18nTitle = array("es" => "", "gl" => "otros", "en" => "otros");
        $comment = array("es" => "Recinto por defecto", "gl" => "", "en" => "Recinto por defecto");

        $this->assertEquals($i18nTitle, $precinct1Tag->getI18nTitle());
        $this->assertEquals($comment, $precinct1Tag->getProperty("comment"));

        $place1Tag = $this->tagRepo->findOneByCod("PLACE0001");

        $i18nTitle = array("es" => "", "gl" => "Otros", "en" => "Otros");
        $address = array("es" => "Universidad de Vigo", "gl" => "Universidade de Vigo", "en" => "");
        $geographicalcoordinates = "42.1723237,-8.6930793,15z";

        $this->assertEquals($i18nTitle, $place1Tag->getI18nTitle());
        $this->assertEquals($address, $place1Tag->getProperty("address"));
        $this->assertEquals($geographicalcoordinates, $place1Tag->getProperty("geographicalcoordinates"));
    }

    public function testSetGroundTags()
    {
        $this->initAllPumukitTags();

        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTags()));

        $xmlFile = $this->resourcesDir.'/groundtags.xml';
        $xmlArray = $this->importXMLFile($xmlFile);
        $multimediaObject = $this->importTagService->setGroundTags($xmlArray, $multimediaObject);

        $this->assertEquals(7, count($multimediaObject->getTags()));

        $tagCodes = array();
        foreach ($multimediaObject->getTags() as $tag) {
            $tagCodes[] = $tag->getCod();
        }
        $codes = array("ROOT", "U250000", "UNESCO", "U240000", "Dtechnical", "DIRECTRIZ", "Dscience");
        $this->assertEquals($codes, $tagCodes);
    }

    public function testSetSingleGroundTag()
    {
        $this->initAllPumukitTags();

        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTags()));

        $xmlFile = $this->resourcesDir.'/singlegroundtag.xml';
        $xmlArray = $this->importXMLFile($xmlFile);
        $multimediaObject = $this->importTagService->setGroundTags($xmlArray, $multimediaObject);

        $this->assertEquals(3, count($multimediaObject->getTags()));

        $tagCodes = array();
        foreach ($multimediaObject->getTags() as $tag) {
            $tagCodes[] = $tag->getCod();
        }
        $codes = array("U220000", "UNESCO", "ROOT");
        $this->assertEquals($codes, $tagCodes);
    }

    public function testSetAnnounceTag()
    {
        $this->initAllPumukitTags();

        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTags()));

        $multimediaObject = $this->importTagService->setAnnounceTag("false", $multimediaObject);

        $this->assertEquals(0, count($multimediaObject->getTags()));

        $multimediaObject = $this->importTagService->setAnnounceTag("true", $multimediaObject);

        $this->assertEquals(3, count($multimediaObject->getTags()));

        $tagCodes = array();
        foreach ($multimediaObject->getTags() as $tag) {
            $tagCodes[] = $tag->getCod();
        }
        $codes = array("PUDENEW", "PUBDECISIONS", "ROOT");
        $this->assertEquals($codes, $tagCodes);
    }

    public function testSetPublicationChannelTags()
    {
        $this->initAllPumukitTags();

        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTags()));

        $xmlFile = $this->resourcesDir.'/publicationchanneltags.xml';
        $xmlArray = $this->importXMLFile($xmlFile);
        $multimediaObject = $this->importTagService->setPublicationChannelTags($xmlArray, $multimediaObject);

        $this->assertEquals(6, count($multimediaObject->getTags()));

        $tagCodes = array();
        foreach ($multimediaObject->getTags() as $tag) {
            $tagCodes[] = $tag->getCod();
        }
        $codes = array("PUCHWEBTV", "PUBCHANNELS", "ROOT", "PUCHARCA");
        $this->assertEquals($codes, $tagCodes);
    }

    public function testSetPublishingDecisionTags()
    {
        $this->initAllPumukitTags();

        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTags()));

        $xmlFile = $this->resourcesDir.'/publishingdecisiontags.xml';
        $xmlArray = $this->importXMLFile($xmlFile);
        $multimediaObject = $this->importTagService->setPublishingDecisionTags($xmlArray, $multimediaObject);

        $this->assertEquals(8, count($multimediaObject->getTags()));

        $tagCodes = array();
        foreach ($multimediaObject->getTags() as $tag) {
            $tagCodes[] = $tag->getCod();
        }
        $codes = array("PUDENEW", "PUBDECISIONS", "ROOT", "PUDEAUTO", "PUDEMAINCONF", "PUDEPROMO", "PUDEPRESS", "PUDEIMPACT");
        $this->assertEquals($codes, $tagCodes);
    }

    public function testSetSinglePublishingDecisionTag()
    {
        $this->initAllPumukitTags();

        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTags()));

        $xmlFile = $this->resourcesDir.'/singlepublishingdecisiontag.xml';
        $xmlArray = $this->importXMLFile($xmlFile);
        $multimediaObject = $this->importTagService->setPublishingDecisionTags($xmlArray, $multimediaObject);

        $this->assertEquals(3, count($multimediaObject->getTags()));

        $tagCodes = array();
        foreach ($multimediaObject->getTags() as $tag) {
            $tagCodes[] = $tag->getCod();
        }
        $codes = array("PUDEPROMO", "PUBDECISIONS", "ROOT");
        $this->assertEquals($codes, $tagCodes);
    }

    private function importXMLFile($filePath=null)
    {
        $xml = simplexml_load_file($filePath, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($xml === false) {
            throw new \Exception("Not valid XML file: ".$filePath);
        }

        $xmlArray = json_decode(json_encode($xml, JSON_HEX_TAG), TRUE);

        return $xmlArray;
    }

    private function initRootTag()
    {
        $rootTag = new Tag();
        $rootTag->setCod("ROOT");
        $rootTag->setDisplay(true);
        $rootTag->setMetatag(true);

        $this->dm->persist($rootTag);
        $this->dm->flush();

        return $rootTag;
    }

    private function initAllPumukitTags()
    {
        $pumukitCommand = "php " . $this->console . " pumukit:init:repo tag --force";

        $outPumukit = $this->executeCommand($pumukitCommand);
    }

    private function executeCommand($command)
    {
        $process = new Process($command);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $process->getOutput();
    }
}