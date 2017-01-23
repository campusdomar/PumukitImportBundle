<?php

namespace Pumukit\ImportBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Person;
use Pumukit\SchemaBundle\Document\Role;
use Pumukit\SchemaBundle\Services\PersonService;

class ImportPeopleService extends ImportCommonService
{
    private $dm;
    private $personService;
    private $roleRepo;
    private $personRepo;

    private $attributesSetFields = array(
                                         'rank' => 'setRank',
                                         );

    // NOTE 1: check unique cod
    private $roleRenameFields = array(
                                      'cod' => 'setCod',
                                      'xml' => 'setXml',
                                      'display' => 'setDisplay',
                                      'name' => 'setI18nName',
                                      'text' => 'setI18nText',
                                      );

    private $personRenameFields = array(
                                        'name' => 'setName',
                                        'email' => 'setEmail',
                                        'web' => 'setWeb',
                                        'phone' => 'setPhone',
                                        'honorific' => 'setI18nHonorific',
                                        'firm' => 'setI18nFirm',
                                        'post' => 'setI18nPost',
                                        'bio' => 'setI18nBio',
                                        );

    private $roleRenameOldValue = array('propi' => 'owner', 'old' => 'expired_owner');

    /**
     * Constructor.
     *
     * @param DocumentManager $documentManager
     * @param PersonService   $personService
     */
    public function __construct(DocumentManager $documentManager, PersonService $personService)
    {
        $this->dm = $documentManager;
        $this->personService = $personService;
        $this->roleRepo = $this->dm->getRepository('PumukitSchemaBundle:Role');
        $this->personRepo = $this->dm->getRepository('PumukitSchemaBundle:Person');
    }

    /**
     * Set People.
     *
     * @param array            $peopleArray
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject
     */
    public function setPeople($peopleArray, $multimediaObject)
    {
        foreach ($peopleArray as $roles) {
            if (array_key_exists('0', $roles)) {
                foreach ($roles as $roleArray) {
                    $multimediaObject = $this->setPeopleWithRole($roleArray, $multimediaObject);
                }
            } else {
                $roleArray = $roles;
                $multimediaObject = $this->setPeopleWithRole($roleArray, $multimediaObject);
            }
        }

        return $multimediaObject;
    }

    /**
     * Set People With Role.
     *
     * @param array            $roleArray
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject
     */
    public function setPeopleWithRole($roleArray, $multimediaObject)
    {
        $role = $this->getExistingRole($roleArray);
        if (null == $role) {
            $role = $this->createRole($roleArray);
        }

        $multimediaObject = $this->addPeopleInRole($roleArray, $multimediaObject, $role);

        return $multimediaObject;
    }

    private function getExistingRole($roleArray = array())
    {
        $roleCode = $this->getRoleCode($roleArray);
        $role = $this->roleRepo->findOneByCod($roleCode);

        return $role;
    }

    private function getRoleCode($roleArray)
    {
        if ((null == $roleArray) && (!array_key_exists('cod', $roleArray))) {
            throw new \Exception('Trying to add Role without code (non exisiting cod)');
        }

        if (array_key_exists($roleArray['cod'], $this->roleRenameOldValue)) {
            $roleCode = $this->roleRenameOldValue[$roleArray['cod']];
        } else {
            $roleCode = $roleArray['cod'];
        }
        if (null == $roleCode) {
            throw new \Exception('Trying to add Role without unique code (null cod)');
        }

        return $roleCode;
    }

    private function createRole($roleArray)
    {
        $role = new Role();
        foreach ($roleArray as $fieldName => $fieldValue) {
            if (array_key_exists($fieldName, $this->roleRenameFields)) {
                $setField = $this->roleRenameFields[$fieldName];
                $role = $this->setFieldWithValue($setField, $fieldValue, $role);
            } else {
                switch ($fieldName) {
                case '@attributes':
                    $role = $this->setAttributes($fieldValue, $this->attributesSetFields, $role);
                    break;
                }
            }
        }

        $this->dm->persist($role);
        $this->dm->flush();

        return $role;
    }

    private function addPeopleInRole($roleArray, $multimediaObject, $role)
    {
        if (array_key_exists('persons', $roleArray)) {
            $peopleArray = $roleArray['persons'];
            foreach ($peopleArray as $people) {
                if (array_key_exists('0', $people)) {
                    foreach ($people as $personArray) {
                        $multimediaObject = $this->addPersonInRole($personArray, $role, $multimediaObject);
                    }
                } else {
                    $personArray = $people;
                    $multimediaObject = $this->addPersonInRole($people, $role, $multimediaObject);
                }
            }
        }

        return $multimediaObject;
    }

    private function addPersonInRole($personArray, $role, $multimediaObject)
    {
        $person = $this->getExistingPerson($personArray);
        if (null == $person) {
            $person = $this->createPerson($personArray);
        }
        if (!$multimediaObject->containsPersonWithRole($person, $role)) {
            $multimediaObject->addPersonWithRole($person, $role);
            $role->increaseNumberPeopleInMultimediaObject();
            $this->dm->persist($role);
        }

        return $multimediaObject;
    }

    private function getExistingPerson($personArray = array())
    {
        $fields = $this->getValuableFields($personArray);
        $person = $this->getOnePersonByFields($fields);

        return $person;
    }

    private function createPerson($personArray = array())
    {
        $person = new Person();
        $person = $this->setFields($personArray, $this->personRenameFields, $person);

        if(array_key_exists('properties', $personArray)) {
            $person->setProperties($personArray['properties']);
        }
        $this->dm->persist($person);
        $this->dm->flush();

        return $person;
    }

    private function getValuableFields($resourceArray = array())
    {
        $fields = array();
        foreach ($resourceArray as $fieldName => $fieldValue) {
            if ('@attributes' === $fieldName) {
                continue;
            } elseif ('true' === $fieldValue) {
                $fields[$fieldName] = true;
            } elseif ('false' === $fieldValue) {
                $fields[$fieldName] = false;
            } elseif (null != $fieldValue) {
                if ('array' === gettype($fieldValue)) {
                    if (!empty(array_filter($fieldValue))) {
                        foreach ($fieldValue as $locale => $value) {
                            if (null == $value) {
                                $fieldValue[$locale] = '';
                            }
                        }
                        $fields[$fieldName] = $fieldValue;
                    }
                } else {
                    $fields[$fieldName] = $fieldValue;
                }
            }
        }

        return $fields;
    }

    private function getOnePersonByFields($fields = array())
    {
        $person = null;
        if (!empty($fields)) {
            $qb = $this->personRepo->createQueryBuilder();
            foreach ($fields as $field => $value) {
                if ('array' === gettype($value)) {
                    foreach ($value as $locale => $val) {
                        $qb->field($field.'.'.$locale)->equals($val);
                    }
                } else {
                    $qb->field($field)->equals($value);
                }
            }
            $person = $qb->getQuery()->getSingleResult();
        }

        return $person;
    }
}
