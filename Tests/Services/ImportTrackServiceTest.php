<?php

namespace Pumukit\ImportBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ImportTrackServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $importTrackService;
    private $factoryService;
    private $resourcesDir;
    private $dataDir;

    public function setUp()
    {
        $options = array('environment' => 'test');
        $kernel = static::createKernel($options);
        $kernel->boot();

        $this->dm = $kernel->getContainer()
            ->get('doctrine_mongodb')->getManager();
        $this->mmobjRepo = $this->dm
            ->getRepository('PumukitSchemaBundle:MultimediaObject');
        $this->importTrackService = $kernel->getContainer()
            ->get('pumukit_import.track');
        $this->factoryService = $kernel->getContainer()
            ->get('pumukitschema.factory');
        $this->resourcesDir = realpath(__DIR__.'/../Resources/data/xmlfiles');
        $this->dataDir = realpath(__DIR__.'/../Resources/data');

        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->flush();
    }

    public function tearDown()
    {
        $this->dm->close();
        $this->dm = null;
        $this->mmobjRepo = null;
        $this->importTrackService = null;
        $this->factoryService = null;
        $this->resourcesDir = null;
        $this->dataDir = null;
        gc_collect_cycles();
        parent::tearDown();
    }

    public function testSetTracks()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTracks()));

        $xmlFile = $this->resourcesDir.'/tracks.xml';
        $multimediaObject = $this->importXMLFile($xmlFile, $multimediaObject);

        $this->assertEquals(6, count($multimediaObject->getTracks()));

        $numview = 102;
        $this->assertEquals($numview, $multimediaObject->getNumview());
    }

    public function testSetSingleTrack()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($multimediaObject->getTracks()));

        $xmlFile = $this->resourcesDir.'/singletrack.xml';
        $multimediaObject = $this->importXMLFile($xmlFile, $multimediaObject);

        $this->assertEquals(1, count($multimediaObject->getTracks()));

        $track = $multimediaObject->getTracks()[0];

        $pumukit1Id = 'pumukit1id:9652';
        $profile = 'profile:MASTER-H264';
        $master = 'master';
        $tags = array($pumukit1Id, $profile, $master);

        $fakePath = '__realpath__/videos/track01.mp4';
        $path = str_replace('__realpath__', $this->dataDir, $fakePath);
        $language = 'es';
        $description = array('es' => 'Vídeo 1', 'gl' => 'O vídeo un', 'en' => '');
        $numview = 20;

        $this->assertEquals($tags, $track->getTags());
        $this->assertEquals($path, $track->getPath());
        $this->assertEquals($language, $track->getLanguage());
        $this->assertEquals($description, $track->getI18nDescription());
        $this->assertNull($track->getUrl());
        $this->assertFalse($track->getOnlyAudio());
        $this->assertTrue($track->getHide());
        $this->assertEquals($numview, $multimediaObject->getNumview());
    }

    private function importXMLFile($filePath, $multimediaObject)
    {
        $xml = simplexml_load_file($filePath, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (false === $xml) {
            throw new \Exception('Not valid XML file: '.$filePath);
        }

        $xmlArray = json_decode(json_encode($xml, JSON_HEX_TAG), true);

        $xmlArray = $this->changeRealpath($xmlArray);

        $multimediaObject = $this->importTrackService->setTracks($xmlArray, $multimediaObject);

        return $multimediaObject;
    }

    /**
     * Change realpath.
     *
     * Workaround trick to add realpath to xml and do the test
     */
    private function changeRealpath($xmlArray = array())
    {
        foreach ($xmlArray as $key => $tracks) {
            if (array_key_exists('0', $tracks)) {
                foreach ($tracks as $index => $trackArray) {
                    $fakePath = $trackArray['file'];
                    $xmlArray[$key][$index]['file'] = str_replace('__realpath__', $this->dataDir, $fakePath);
                }
            } else {
                $trackArray = $tracks;
                $fakePath = $trackArray['file'];
                $xmlArray[$key]['file'] = str_replace('__realpath__', $this->dataDir, $fakePath);
            }
        }

        return $xmlArray;
    }
}
