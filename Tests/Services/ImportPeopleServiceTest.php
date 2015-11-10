<?php

namespace Pumukit\ImportBundle\Tests\Services;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ImportPeopleServiceTest extends WebTestCase
{
    private $dm;
    private $mmobjRepo;
    private $roleRepo;
    private $personRepo;
    private $importPeopleService;
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
        $this->roleRepo = $this->dm
            ->getRepository("PumukitSchemaBundle:Role");
        $this->personRepo = $this->dm
            ->getRepository("PumukitSchemaBundle:Person");
        $this->importPeopleService = $kernel->getContainer()
            ->get("pumukit_import.people");
        $this->factoryService = $kernel->getContainer()
            ->get("pumukitschema.factory");
        $this->resourcesDir = realpath(__DIR__.'/../Resources/data/xmlfiles');
    }

    public function setUp()
    {
        $this->dm->getDocumentCollection('PumukitSchemaBundle:MultimediaObject')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Series')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Person')->remove(array());
        $this->dm->getDocumentCollection('PumukitSchemaBundle:Role')->remove(array());
        $this->dm->flush();
    }

    public function testSetPeople()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($this->roleRepo->findAll()));
        $this->assertEquals(0, count($this->personRepo->findAll()));

        $this->assertEquals(0, count($multimediaObject->getRoles()));
        $this->assertEquals(0, count($multimediaObject->getPeople()));

        $xmlFile = $this->resourcesDir.'/people.xml';
        $multimediaObject = $this->importXMLFile($xmlFile, $multimediaObject);

        $this->assertEquals(5, count($this->roleRepo->findAll()));
        $this->assertEquals(4, count($this->personRepo->findAll()));

        $this->assertEquals(5, count($multimediaObject->getRoles()));
        $this->assertEquals(4, count($multimediaObject->getPeople()));
    }

    public function testSetSingleRole()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($this->roleRepo->findAll()));
        $this->assertEquals(0, count($this->personRepo->findAll()));

        $this->assertEquals(0, count($multimediaObject->getRoles()));
        $this->assertEquals(0, count($multimediaObject->getPeople()));

        $xmlFile = $this->resourcesDir.'/singlerole.xml';
        $multimediaObject = $this->importXMLFile($xmlFile, $multimediaObject);

        $this->assertEquals(1, count($this->roleRepo->findAll()));
        $this->assertEquals(4, count($this->personRepo->findAll()));

        $this->assertEquals(1, count($multimediaObject->getRoles()));
        $this->assertEquals(4, count($multimediaObject->getPeople()));
    }

    public function testSetSinglePerson()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($this->roleRepo->findAll()));
        $this->assertEquals(0, count($this->personRepo->findAll()));

        $this->assertEquals(0, count($multimediaObject->getRoles()));
        $this->assertEquals(0, count($multimediaObject->getPeople()));

        $xmlFile = $this->resourcesDir.'/singleperson.xml';
        $multimediaObject = $this->importXMLFile($xmlFile, $multimediaObject);

        $this->assertEquals(5, count($this->roleRepo->findAll()));
        $this->assertEquals(4, count($this->personRepo->findAll()));

        $this->assertEquals(5, count($multimediaObject->getRoles()));
        $this->assertEquals(4, count($multimediaObject->getPeople()));
    }

    public function testSetSingleRoleSinglePerson()
    {
        $series = $this->factoryService->createSeries();
        $multimediaObject = $this->factoryService->createMultimediaObject($series);

        $this->assertEquals(0, count($this->roleRepo->findAll()));
        $this->assertEquals(0, count($this->personRepo->findAll()));

        $this->assertEquals(0, count($multimediaObject->getRoles()));
        $this->assertEquals(0, count($multimediaObject->getPeople()));

        $xmlFile = $this->resourcesDir.'/singlerolesingleperson.xml';
        $multimediaObject = $this->importXMLFile($xmlFile, $multimediaObject);

        $this->assertEquals(1, count($this->roleRepo->findAll()));
        $this->assertEquals(1, count($this->personRepo->findAll()));

        $this->assertEquals(1, count($multimediaObject->getRoles()));
        $this->assertEquals(1, count($multimediaObject->getPeople()));

        $role = $multimediaObject->getRoles()[0];

        $code = "actor";
        $xml = "actor";
        $i18nName = array("es" => "Actor", "gl" => "", "en" => "Actor");
        $i18nText = array("es" => "Texto del actor", "gl" => "", "en" => "Actor text");

        $this->assertEquals($code, $role->getCod());
        $this->assertEquals($xml, $role->getXml());
        $this->assertTrue($role->getDisplay());
        $this->assertEquals($i18nName, $role->getI18nName());
        $this->assertEquals($i18nText, $role->getI18nText());

        $person = $multimediaObject->getPeople()[0];

        $name = "Isabel Riveiro Alarcón";
        $email = "isabel.riveiro@mail.com";
        $web = "http://www.isabelriveiro.com";
        $phone = "654321789";
        $i18nHonorific = array("es" => "", "gl" => "Sra.", "en" => "Mrs.");
        $i18nFirm = array("es" => "Instituto Español de Oceanografía", "gl" => "", "en" => "");
        $i18nPost = array("es" => "Investigadora", "gl" => "", "en" => "");
        $i18nBio = array("es" => "", "gl" => "A biografía de Isabel", "en" => "Isabel biography");

        $this->assertEquals($name, $person->getName());
        $this->assertEquals($email, $person->getEmail());
        $this->assertEquals($web, $person->getWeb());
        $this->assertEquals($phone, $person->getPhone());
        $this->assertEquals($i18nHonorific, $person->getI18nHonorific());
        $this->assertEquals($i18nFirm, $person->getI18nFirm());
        $this->assertEquals($i18nPost, $person->getI18nPost());
        $this->assertEquals($i18nBio, $person->getI18nBio());
    }

    private function importXMLFile($filePath=null, $multimediaObject)
    {
        $xml = simplexml_load_file($filePath, "SimpleXMLElement", LIBXML_NOCDATA);
        if ($xml === false) {
            throw new \Exception("Not valid XML file: ".$filePath);
        }

        $xmlArray = json_decode(json_encode($xml, JSON_HEX_TAG), TRUE);

        $multimediaObject = $this->importPeopleService->setPeople($xmlArray, $multimediaObject);

        return $multimediaObject;
    }
}