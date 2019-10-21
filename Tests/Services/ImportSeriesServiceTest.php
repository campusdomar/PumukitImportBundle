<?php

namespace Pumukit\ImportBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Process\Process;
use Pumukit\ImportBundle\Document\Broadcast;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class ImportSeriesServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $seriesRepo;
    private $tagRepo;
    private $factoryService;
    private $importSeriesService;
    private $resourcesDir;
    private $console;
    private $dataDir;
    private $bundles;

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
        $this->tagRepo = $this->dm
            ->getRepository('PumukitSchemaBundle:Tag');
        $this->factoryService = $kernel->getContainer()
            ->get('pumukitschema.factory');
        $this->importSeriesService = $kernel->getContainer()
            ->get('pumukit_import.series');
        $this->resourcesDir = realpath(__DIR__.'/../Resources/data/xmlfiles');
        $this->console = $kernel->getRootDir().'/../app/console --env=test';
        $this->dataDir = realpath(__DIR__.'/../Resources/data');
        $this->bundles = $kernel->getContainer()->getParameter('kernel.bundles');

        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Broadcast')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Tag')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Person')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Role')->remove(array());
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->mmobjRepo = null;
        $this->seriesRepo = null;
        $this->tagRepo = null;
        $this->factoryService = null;
        $this->importSeriesService = null;
        $this->resourcesDir = null;
        $this->console = null;
        $this->dataDir = null;
        $this->bundles = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testSetImportedSeries()
    {
        $this->initAllPumukitTags();

        $xmlFile = $this->resourcesDir.'/series.xml';
        $xmlArray = $this->importXMLFile($xmlFile);

        $series = $this->importSeriesService->setImportedSeries($xmlArray);
        $series = $this->seriesRepo->find($series->getId());

        $this->assertEquals(2, count($this->mmobjRepo->findAll()));

        $prototype = $this->mmobjRepo->findPrototype($series);

        $this->assertEquals(MultimediaObject::STATUS_PROTOTYPE, $prototype->getStatus());

        $this->assertEquals(12, count($prototype->getTags()));
        $this->assertTrue($prototype->containsTagWithCod('ROOT'));
        $this->assertTrue($prototype->containsTagWithCod('UNESCO'));
        $this->assertTrue($prototype->containsTagWithCod('U220000'));
        $this->assertTrue($prototype->containsTagWithCod('U230000'));
        $this->assertTrue($prototype->containsTagWithCod('U240000'));
        $this->assertTrue($prototype->containsTagWithCod('U310000'));
        $this->assertTrue($prototype->containsTagWithCod('DIRECTRIZ'));
        $this->assertTrue($prototype->containsTagWithCod('Dscience'));
        $this->assertFalse($prototype->containsTagWithCod('Dtechnical'));
        $this->assertTrue($prototype->containsTagWithCod('PLACES'));
        $this->assertTrue($prototype->containsTagWithCod('T6-3'));
        $this->assertTrue($prototype->containsTagWithCod('GENRE'));
        $this->assertTrue($prototype->containsTagWithCod('GENRE1'));
        $this->assertFalse($prototype->containsTagWithCod('CHANNELS'));
        $this->assertFalse($prototype->containsTagWithCod('CHSONAR'));

        $broadcastRepo = $this->dm->getRepository('PumukitSchemaBundle:Broadcast');
        $broadcast = $broadcastRepo->createQueryBuilder()
            ->field('broadcast_type_id')->equals(Broadcast::BROADCAST_TYPE_PUB)
            ->getQuery()
            ->getSingleResult();
        $this->assertEquals($broadcast, $prototype->getBroadcast());

        $copyright = 'UVIGO-TV';
        $publicDate = new \DateTime('2015-04-17 09:12:00');
        $i18nTitle = array('es' => '', 'gl' => 'Título da serie', 'en' => 'Series Title');
        $i18nSubtitle = array('es' => 'Subtítulo', 'gl' => '', 'en' => 'Subtitle');
        $i18nKeyword = array('es' => 'llave1,llave2', 'gl' => 'chave1,chave2', 'en' => 'key1,key2');
        $i18nDescription = array(
                                 'es' => 'Descripción de la serie.',
                                 'gl' => 'Descripción da serie.',
                                 'en' => '',
                                 );
        $i18nHeader = array('es' => 'Cabecera', 'gl' => '', 'en' => 'Header');
        $i18nFooter = array('es' => 'Pie', 'gl' => 'Pe', 'en' => '');
        $i18nLine2 = array('es' => 'Línea 2', 'gl' => 'Liña 2', 'en' => '');
        $email = 'series@mail.com';
        $template = 'date';
        $pumukit1id = '481';
        $pumukit1magic = 'aab3238922bcc25a6f606eb525ffdc56';

        $this->assertEquals($copyright, $series->getCopyright());
        $this->assertEquals($publicDate, $series->getPublicDate());
        $this->assertEquals($i18nTitle, $series->getI18nTitle());
        $this->assertEquals($i18nSubtitle, $series->getI18nSubtitle());
        $this->assertEquals($i18nKeyword, $series->getI18nKeyword());
        $this->assertEquals($i18nDescription, $series->getI18nDescription());
        $this->assertEquals($i18nHeader, $series->getI18nHeader());
        $this->assertEquals($i18nFooter, $series->getI18nFooter());
        $this->assertEquals($i18nLine2, $series->getI18nLine2());
        $this->assertEquals($email, $series->getProperty('email'));
        $this->assertEquals($template, $series->getProperty('template'));
        $this->assertEquals($pumukit1id, $series->getProperty('pumukit1id'));
        $this->assertEquals($pumukit1magic, $series->getProperty('pumukit1magic'));

        $this->assertEquals(3, count($prototype->getRoles()));
        $this->assertEquals(2, count($prototype->getPeople()));

        $multimediaObject = $this->mmobjRepo->findOneByStatus(MultimediaObject::STATUS_BLOQ);

        $this->assertEquals(MultimediaObject::STATUS_BLOQ, $multimediaObject->getStatus());

        $this->assertEquals(7, count($multimediaObject->getTags()));
        $this->assertTrue($multimediaObject->containsTagWithCod('ROOT'));
        $this->assertTrue($multimediaObject->containsTagWithCod('PUBCHANNELS'));
        $this->assertTrue($multimediaObject->containsTagWithCod('PUCHWEBTV'));
        $this->assertTrue($multimediaObject->containsTagWithCod('PLACES'));
        $this->assertTrue($multimediaObject->containsTagWithCod('T6-3'));
        $this->assertTrue($multimediaObject->containsTagWithCod('GENRE'));
        $this->assertTrue($multimediaObject->containsTagWithCod('GENRE1'));
        $this->assertFalse($multimediaObject->containsTagWithCod('UNESCO'));
        $this->assertFalse($multimediaObject->containsTagWithCod('U220000'));
        $this->assertFalse($multimediaObject->containsTagWithCod('U230000'));
        $this->assertFalse($multimediaObject->containsTagWithCod('U240000'));
        $this->assertFalse($multimediaObject->containsTagWithCod('U310000'));
        $this->assertFalse($multimediaObject->containsTagWithCod('DIRECTRIZ'));
        $this->assertFalse($multimediaObject->containsTagWithCod('Dscience'));
        $this->assertFalse($multimediaObject->containsTagWithCod('Dtechnical'));
        $this->assertFalse($multimediaObject->containsTagWithCod('CHANNELS'));
        $this->assertFalse($multimediaObject->containsTagWithCod('CHSONAR'));

        $broadcastRepo = $this->dm->getRepository('PumukitSchemaBundle:Broadcast');
        $broadcast = $broadcastRepo->createQueryBuilder()
            ->field('broadcast_type_id')->equals(Broadcast::BROADCAST_TYPE_PUB)
            ->getQuery()
            ->getSingleResult();
        $this->assertEquals($broadcast, $multimediaObject->getBroadcast());

        $this->assertEquals(1, count($multimediaObject->getPics()));
        $this->assertEquals(5, count($multimediaObject->getTracks()));
        $this->assertEquals(3, count($multimediaObject->getMaterials()));
        $this->assertEquals(2, count($multimediaObject->getLinks()));

        $this->assertEquals(5, count($multimediaObject->getRoles()));
        $this->assertEquals(4, count($multimediaObject->getPeople()));

        $opencastId = 'opencast_id';
        $this->assertEquals($opencastId, $multimediaObject->getProperty('opencast'));

        $opencastLink = 'http://engage.opencast.es/engage/ui/watch.html?id=%id%';
        $this->assertEquals($opencastLink, $multimediaObject->getProperty('opencasturl'));

        $opencastInvert = false;
        $this->assertEquals($opencastInvert, $multimediaObject->getProperty('invert'));

        $numview = 4;
        $this->assertEquals($numview, $multimediaObject->getNumview());

        $duration = 950;
        $this->assertEquals($duration, $multimediaObject->getDuration());

        $opencastIdSeries = 'opencast_id';
        $this->assertEquals($opencastIdSeries, $series->getProperty('opencast'));
    }

    private function importXMLFile($filePath = null)
    {
        $xml = simplexml_load_file($filePath, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (false === $xml) {
            throw new \Exception('Not valid XML file: '.$filePath);
        }

        $xmlArray = json_decode(json_encode($xml, JSON_HEX_TAG), true);

        $xmlArray = $this->changeRealpath($xmlArray);

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
        $mmsArray = $xmlArray['mms'];
        foreach ($mmsArray as $key => $mms) {
            if (array_key_exists('0', $mms)) {
                foreach ($mms as $mmIndex => $mmArray) {
                    if (array_key_exists('files', $mmArray)) {
                        $tracksArray = $mmArray['files'];
                        foreach ($tracksArray as $trackKey => $tracks) {
                            if (array_key_exists('0', $tracks)) {
                                foreach ($tracks as $trackIndex => $trackArray) {
                                    $fakePath = $trackArray['file'];
                                    $xmlArray['mms'][$key][$mmIndex]['files'][$trackKey][$trackIndex]['file'] = str_replace('__realpath__', $this->dataDir, $fakePath);
                                }
                            } else {
                                $trackArray = $tracks;
                                $fakePath = $trackArray['file'];
                                $xmlArray['mms'][$key][$mmIndex]['files'][$trackKey]['file'] = str_replace('__realpath__', $this->dataDir, $fakePath);
                            }
                        }
                    }
                }
            } else {
                $tracksArray = $xmlArray['mms'][$key]['files'];
                foreach ($tracksArray as $trackKey => $tracks) {
                    if (array_key_exists('0', $tracks)) {
                        foreach ($tracks as $trackIndex => $trackArray) {
                            $fakePath = $trackArray['file'];
                            $xmlArray['mms'][$key]['files'][$trackKey][$trackIndex]['file'] = str_replace('__realpath__', $this->dataDir, $fakePath);
                        }
                    } else {
                        $trackArray = $tracks;
                        $fakePath = $trackArray['file'];
                        $xmlArray['mms'][$key]['files'][$trackKey]['file'] = str_replace('__realpath__', $this->dataDir, $fakePath);
                    }
                }
            }
        }

        return $xmlArray;
    }
}
