<?php

namespace Pumukit\ImportBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Process\Process;
use Pumukit\SchemaBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class ImportMultimediaObjectServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $tagRepo;
    private $factoryService;
    private $importMultimediaObjectService;
    private $resourcesDir;
    private $console;
    private $dataDir;
    private $bundles;

    public function __construct()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->mmobjRepo = $this->dm
            ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->tagRepo = $this->dm
            ->getRepository('PumukitSchemaBundle:Tag');
        $this->factoryService = $kernel->getContainer()
            ->get('pumukitschema.factory');
        $this->importMultimediaObjectService = $kernel->getContainer()
            ->get('pumukit_import.multimediaobject');
        $this->resourcesDir = realpath(__DIR__.'/../Resources/data/xmlfiles');
        $this->console = $kernel->getRootDir().'/../app/console --env=test';
        $this->dataDir = realpath(__DIR__.'/../Resources/data');
        $this->bundles = $kernel->getContainer()->getParameter('kernel.bundles');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Broadcast')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Person')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Role')->remove(array());
        $this->dm->flush();
    }

    public function testSetMultimediaObjects()
    {
        $this->initAllPumukitTags();

        $series = $this->factoryService->createSeries();

        $this->assertEquals(1, count($this->mmobjRepo->findAll()));

        $xmlFile = $this->resourcesDir.'/multimediaobjects.xml';
        $xmlArray = $this->importXMLFile($xmlFile, $series, true);

        $series = $this->importMultimediaObjectService->setMultimediaObjects($xmlArray, $series);

        $this->assertEquals(3, count($this->mmobjRepo->findAll()));
    }

    public function testSetSingleMultimediaObject()
    {
        $this->initAllPumukitTags();

        $series = $this->factoryService->createSeries();

        $this->assertEquals(1, count($this->mmobjRepo->findAll()));

        $xmlFile = $this->resourcesDir.'/singlemultimediaobject.xml';
        $xmlArray = $this->importXMLFile($xmlFile, $series, true);

        $series = $this->importMultimediaObjectService->setMultimediaObjects($xmlArray, $series);

        $this->assertEquals(2, count($this->mmobjRepo->findAll()));

        $multimediaObjects = $this->mmobjRepo->findAll();
        $multimediaObject = $multimediaObjects[1];

        $this->assertEquals(MultimediaObject::STATUS_BLOQ, $multimediaObject->getStatus());

        $this->assertEquals(7, count($multimediaObject->getTags()));
        $this->assertTrue($multimediaObject->containsTagWithCod('ROOT'));
        $this->assertTrue($multimediaObject->containsTagWithCod('PUBCHANNELS'));
        $this->assertTrue($multimediaObject->containsTagWithCod('PUCHWEBTV'));
        $this->assertTrue($multimediaObject->containsTagWithCod('PLACES'));
        $this->assertTrue($multimediaObject->containsTagWithCod('T6-3'));
        $this->assertTrue($multimediaObject->containsTagWithCod('GENRE'));
        $this->assertTrue($multimediaObject->containsTagWithCod('GENRE1'));

        $broadcastRepo = $this->dm->getRepository('PumukitSchemaBundle:Broadcast');
        $broadcast = $broadcastRepo->createQueryBuilder()
            ->field('broadcast_type_id')->equals(Broadcast::BROADCAST_TYPE_PUB)
            ->getQuery()
            ->getSingleResult();
        $this->assertEquals($broadcast, $multimediaObject->getBroadcast());

        $copyright = 'UVIGO-Tv';
        $recordDate = new \Datetime('2011-07-19 14:43:00');
        $publicDate = new \Datetime('2011-07-19 14:43:00');
        $i18nTitle = array('es' => '', 'gl' => 'novo', 'en' => 'new');
        $i18nSubtitle = array('es' => 'subtítulo', 'gl' => 'subtítulo', 'en' => '');
        $i18nKeyword = array('es' => 'llave1,llave2', 'gl' => 'chave1,chave2', 'en' => '');
        $i18nDescription = array('es' => 'descripción', 'gl' => 'descripción', 'en' => '');
        $subseriesTitle = array('es' => 'a', 'gl' => 'a', 'en' => '');
        $subseries = true;
        $email = 'mm@mail.com';
        $pumukit1id = '278';
        $pumukit1rank = '1';

        $this->assertEquals($copyright, $multimediaObject->getCopyright());
        $this->assertEquals($recordDate, $multimediaObject->getRecordDate());
        $this->assertEquals($publicDate, $multimediaObject->getPublicDate());
        $this->assertEquals($i18nTitle, $multimediaObject->getI18nTitle());
        $this->assertEquals($i18nSubtitle, $multimediaObject->getI18nSubtitle());
        $this->assertEquals($i18nKeyword, $multimediaObject->getI18nKeyword());
        $this->assertEquals($i18nDescription, $multimediaObject->getI18nDescription());
        $this->assertEquals($subseriesTitle, $multimediaObject->getProperty('subseriestitle'));
        $this->assertEquals($subseries, $multimediaObject->getProperty('subseries'));
        $this->assertEquals($email, $multimediaObject->getProperty('email'));
        $this->assertEquals($pumukit1id, $multimediaObject->getProperty('pumukit1id'));
        $this->assertEquals($pumukit1rank, $multimediaObject->getProperty('pumukit1rank'));

        $this->assertEquals(1, count($multimediaObject->getPics()));
        $this->assertEquals(5, count($multimediaObject->getTracks()));
        $this->assertEquals(3, count($multimediaObject->getMaterials()));
        $this->assertEquals(2, count($multimediaObject->getLinks()));

        $this->assertEquals(5, count($multimediaObject->getRoles()));
        $this->assertEquals(4, count($multimediaObject->getPeople()));

        $opencastId = 'a93f5411-822b-4a84-a4e4-d6619fe86fbe';
        $this->assertEquals($opencastId, $multimediaObject->getProperty('opencast'));

        $opencastLink = 'http://engage14.pumukit.es/engage/ui/watch.html?id=%id%';
        $this->assertEquals($opencastLink, $multimediaObject->getProperty('opencasturl'));

        $opencastInvert = false;
        $this->assertEquals($opencastInvert, $multimediaObject->getProperty('invert'));

        $numview = 7;
        $this->assertEquals($numview, $multimediaObject->getNumview());

        $duration = 950;
        $this->assertEquals($duration, $multimediaObject->getDuration());
    }

    public function testSetMultimediaObjectPrototype()
    {
        $this->initAllPumukitTags();

        $series = $this->factoryService->createSeries();

        $this->assertEquals(1, count($this->mmobjRepo->findAll()));

        $xmlFile = $this->resourcesDir.'/multimediaobjectprototypes.xml';
        $xmlArray = $this->importXMLFile($xmlFile, $series, false);

        $series = $this->importMultimediaObjectService->setMultimediaObjectPrototype($xmlArray, $series);

        $this->assertEquals(1, count($this->mmobjRepo->findAll()));

        $multimediaObject = $this->mmobjRepo->findPrototype($series);

        $this->assertEquals(MultimediaObject::STATUS_PROTOTYPE, $multimediaObject->getStatus());

        $this->assertEquals(12, count($multimediaObject->getTags()));
        $this->assertTrue($multimediaObject->containsTagWithCod('ROOT'));
        $this->assertTrue($multimediaObject->containsTagWithCod('UNESCO'));
        $this->assertTrue($multimediaObject->containsTagWithCod('U220000'));
        $this->assertTrue($multimediaObject->containsTagWithCod('U230000'));
        $this->assertTrue($multimediaObject->containsTagWithCod('U240000'));
        $this->assertTrue($multimediaObject->containsTagWithCod('U310000'));
        $this->assertTrue($multimediaObject->containsTagWithCod('DIRECTRIZ'));
        $this->assertTrue($multimediaObject->containsTagWithCod('Dscience'));
        $this->assertFalse($multimediaObject->containsTagWithCod('Dtechnical'));
        $this->assertTrue($multimediaObject->containsTagWithCod('PLACES'));
        $this->assertTrue($multimediaObject->containsTagWithCod('T6-3'));
        $this->assertTrue($multimediaObject->containsTagWithCod('GENRE'));
        $this->assertTrue($multimediaObject->containsTagWithCod('GENRE1'));

        $broadcastRepo = $this->dm->getRepository('PumukitSchemaBundle:Broadcast');
        $broadcast = $broadcastRepo->createQueryBuilder()
            ->field('broadcast_type_id')->equals(Broadcast::BROADCAST_TYPE_PUB)
            ->getQuery()
            ->getSingleResult();
        $this->assertEquals($broadcast, $multimediaObject->getBroadcast());

        $this->assertEquals(3, count($multimediaObject->getRoles()));
        $this->assertEquals(2, count($multimediaObject->getPeople()));
    }

    private function importXMLFile($filePath, $multimediaObject, $hasFiles = false)
    {
        $xml = simplexml_load_file($filePath, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($xml === false) {
            throw new \Exception('Not valid XML file: '.$filePath);
        }

        $xmlArray = json_decode(json_encode($xml, JSON_HEX_TAG), true);

        if ($hasFiles) {
            $xmlArray = $this->changeRealpath($xmlArray);
        }

        return $xmlArray;
    }

    private function initAllPumukitTags()
    {
        $pumukitCommand = 'php '.$this->console.' pumukit:init:repo tag --force';

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

    private function changeRealpath($xmlArray = array())
    {
        // NOTE: workaround trick to add realpath to xml and do the test
        // TODO: break into smaller functions
        foreach ($xmlArray as $key => $mms) {
            if (array_key_exists('0', $mms)) {
                foreach ($mms as $mmIndex => $mmArray) {
                    if (array_key_exists('files', $mmArray)) {
                        $tracksArray = $mmArray['files'];
                        foreach ($tracksArray as $trackKey => $tracks) {
                            if (array_key_exists('0', $tracks)) {
                                foreach ($tracks as $trackIndex => $trackArray) {
                                    $fakePath = $trackArray['file'];
                                    $xmlArray[$key][$mmIndex]['files'][$trackKey][$trackIndex]['file'] = str_replace('__realpath__', $this->dataDir, $fakePath);
                                }
                            } else {
                                $trackArray = $tracks;
                                $fakePath = $trackArray['file'];
                                $xmlArray[$key][$mmIndex]['files'][$trackKey]['file'] = str_replace('__realpath__', $this->dataDir, $fakePath);
                            }
                        }
                    }
                }
            } else {
                $tracksArray = $xmlArray[$key]['files'];
                foreach ($tracksArray as $trackKey => $tracks) {
                    if (array_key_exists('0', $tracks)) {
                        foreach ($tracks as $trackIndex => $trackArray) {
                            $fakePath = $trackArray['file'];
                            $xmlArray[$key]['files'][$trackKey][$trackIndex]['file'] = str_replace('__realpath__', $this->dataDir, $fakePath);
                        }
                    } else {
                        $trackArray = $tracks;
                        $fakePath = $trackArray['file'];
                        $xmlArray[$key]['files'][$trackKey]['file'] = str_replace('__realpath__', $this->dataDir, $fakePath);
                    }
                }
            }
        }

        return $xmlArray;
    }
}
