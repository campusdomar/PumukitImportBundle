<?php

namespace Pumukit\ImportBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesType;
use Pumukit\SchemaBundle\Services\FactoryService;

class ImportSeriesService extends ImportCommonService
{
    private $dm;
    private $seriesTypeRepo;
    private $factoryService;
    private $importMultimediaObjectService;
    private $importPicService;
    private $importOpencastService;

    // TODO To be used in the future
    /* private $attributesRenameFields = array( */
    /*                                         "id" => "setId", */
    /*                                         "rank" => "setRank" */
    /*                                         ); */
    private $attributesRenameFields = array();

    // NOTE 1: fields not filled: secret (filled in construct function), license, locale
    private $seriesRenameFields = array(
        'announce' => 'setAnnounce',
        'publicDate' => 'setPublicDate',
        'title' => 'setI18nTitle',
        'subtitle' => 'setI18nSubtitle',
        'description' => 'setI18nDescription',
        'header' => 'setI18nHeader',
        'footer' => 'setI18nFooter',
        'copyright' => 'setCopyright',
        'keyword' => 'setI18nKeyword',
        'line2' => 'setI18nLine2',
        'display' => 'setHide',
        'sorting' => 'setSorting',
    );

    // NOTE 1: not set automatically (series)
    // NOTE 2: not set (locale, id)
    // NOTE 3: not used (defaultsel)
    private $seriesTypeRenameFields = array(
        'cod' => 'setCod',
        'name' => 'setI18nName',
        'description' => 'setI18nDescription',
    );

    /**
     * Constructor.
     *
     * @param DocumentManager               $documentManager
     * @param FactoryService                $factoryService
     * @param ImportMultimediaObjectService $importMultimediaObjectService
     * @param ImportPicService              $importPicService
     * @param ImportOpencastService         $importOpencastService
     */
    public function __construct(DocumentManager $documentManager, FactoryService $factoryService, ImportMultimediaObjectService $importMultimediaObjectService, ImportPicService $importPicService, ImportOpencastService $importOpencastService)
    {
        $this->dm = $documentManager;
        $this->factoryService = $factoryService;
        $this->importMultimediaObjectService = $importMultimediaObjectService;
        $this->importPicService = $importPicService;
        $this->importOpencastService = $importOpencastService;
        $this->seriesTypeRepo = $this->dm->getRepository('PumukitSchemaBundle:SeriesType');
    }

    /**
     * Set imported series.
     *
     * @param array $xmlArray
     *
     * @return Series $series
     */
    public function setImportedSeries($xmlArray = array())
    {
        $series = $this->factoryService->createSeries();
        foreach ($xmlArray as $fieldName => $fieldValue) {
            if (array_key_exists($fieldName, $this->seriesRenameFields)) {
                if ('announce' === $fieldName) {
                    $fieldValue = ('false' === strtolower($fieldValue)) ? false : true;
                }

                if ('display' === $fieldName) {
                    $fieldValue = ('false' === strtolower($fieldValue)) ? true : false;
                }

                $setField = $this->seriesRenameFields[$fieldName];
                $series = $this->setFieldWithValue($setField, $fieldValue, $series);
            } else {
                switch ($fieldName) {
                    case 'version':
                        $series = $this->setImportInfo($fieldValue, $series);
                        break;
                    case 'id':
                        $series = $this->setFieldProperty('pumukit1id', $fieldValue, $series);
                        break;
                    case 'hash':
                        $series = $this->setFieldProperty('pumukit1magic', $fieldValue, $series);
                        break;
                    case 'pics':
                        $series = $this->importPicService->setPics($fieldValue, $series);
                        break;
                    case 'serialTemplate':
                        $series = $this->setTemplate($fieldValue, $series);
                        break;
                    case 'serialType':
                        $series = $this->setSeriesType($fieldValue, $series);
                        break;
                    case 'mmTemplates':
                        $series = $this->importMultimediaObjectService->setMultimediaObjectPrototype($fieldValue, $series);
                        break;
                    case 'mms':
                        $series = $this->importMultimediaObjectService->setMultimediaObjects($fieldValue, $series);
                        break;
                    case 'opencast':
                        $series = $this->importOpencastService->setOpencastInSeries($fieldValue, $series);
                        break;
                    case 'mail':
                        $series = $this->setEmailProperty($fieldValue, $series);
                        break;
                }
            }
        }
        $series->setPublicDate(new \Datetime($series->getPublicDate()));

        $this->dm->persist($series);
        $this->dm->flush();
        $this->dm->clear(get_class($series));

        return $series;
    }

    private function setFieldProperty($fieldName, $fieldValue, $resource)
    {
        if (null != $fieldValue) {
            $resource->setProperty($fieldName, $fieldValue);
        }

        return $resource;
    }

    private function setImportInfo($fieldValue, $series)
    {
        $date = new \Datetime('now');
        $value = 'Imported with XML version '.$fieldValue.' on date '.$date->format('d-m-Y H:i:s');
        $series = $this->setFieldProperty('import', $value, $series);

        return $series;
    }

    private function setTemplate($fieldValue, $series)
    {
        if (!empty(array_filter($fieldValue))) {
            if (array_key_exists('name', $fieldValue)) {
                $name = $fieldValue['name'];
                if (null != $name) {
                    $series->setProperty('template', $name);
                }
            }
        }

        return $series;
    }

    private function setSeriesType($fieldValue, $series)
    {
        $seriesType = $this->getExistingSeriesType($fieldValue);
        if (null == $seriesType) {
            $seriesType = $this->createSeriesType($fieldValue);
        }

        $series->setSeriesType($seriesType);

        return $series;
    }

    private function getExistingSeriesType($fieldValue = array())
    {
        $seriesType = null;
        $i18nName = null;

        if (array_key_exists('name', $fieldValue)) {
            $i18nName = $fieldValue['name'];
        }

        if (!empty(array_filter($i18nName))) {
            $qb = $this->seriesTypeRepo->createQueryBuilder();
            foreach ($i18nName as $locale => $value) {
                if (null != $value) {
                    $i18nName[$locale] = $value;
                } else {
                    $i18nName[$locale] = '';
                }
                $qb->field('name.'.$locale)->equals($i18nName[$locale]);
            }
            $seriesType = $qb->getQuery()->getSingleResult();
        }

        return $seriesType;
    }

    private function createSeriesType($fieldValue = array())
    {
        $seriesType = new SeriesType();

        $seriesType = $this->setFields($fieldValue, $this->seriesTypeRenameFields, $seriesType);

        $this->dm->persist($seriesType);
        $this->dm->flush();

        return $seriesType;
    }

    private function setEmailProperty($email, $series)
    {
        if (null != $email) {
            $series->setProperty('email', $email);
        }

        return $series;
    }
}
