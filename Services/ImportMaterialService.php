<?php

namespace Pumukit\ImportBundle\Services;

use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Material;

class ImportMaterialService extends ImportCommonService
{
    private $materialRenameFields = array(
                                          'name' => 'setI18nName',
                                          'url' => 'setUrl',
                                          'size' => 'setSize',
                                          );

    private $renameLanguages = array('ls' => 'lse');

    private $mattypeSetFields = array(
                      'type' => 'setMimeType',
                      );

    /**
     * Set Materials.
     *
     * @param array            $materialsArray
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject
     */
    public function setMaterials($materialsArray, $multimediaObject)
    {
        foreach ($materialsArray as $materials) {
            if (is_array($materials) and array_key_exists('0', $materials)) {
                foreach ($materials as $materialArray) {
                    $multimediaObject = $this->setMaterial($materialArray, $multimediaObject);
                }
            } else {
                $materialArray = $materials;
                $multimediaObject = $this->setMaterial($materialArray, $multimediaObject);
            }
        }

        return $multimediaObject;
    }

    /**
     * Set Material.
     *
     * @param array            $materialArray
     * @param MultimediaObject $multimediaObject
     *
     * @return MultimediaObject
     */
    public function setMaterial($materialArray, $multimediaObject)
    {
        $material = $this->createMaterial($materialArray);
        $multimediaObject->addMaterial($material);

        return $multimediaObject;
    }

    private function createMaterial($materialArray = array())
    {
        $material = new Material();
        $material = $this->setFields($materialArray, $this->materialRenameFields, $material);
        $material = $this->completeMaterialFields($materialArray, $material);

        return $material;
    }

    private function completeMaterialFields($materialArray, $material)
    {
        $display = $this->getDisplay($materialArray);
        $material->setHide(!$display);

        $mattype = $this->getMattype($materialArray);
        foreach ($this->mattypeSetFields as $field => $setField) {
            $value = $this->getFieldFromMattype($field, $mattype);
            if (null != $value) {
                $material->$setField($value);
            }
        }

        $language = $this->getMaterialLanguage($materialArray);
        $material->setLanguage($language);

        return $material;
    }

    private function getMaterialLanguage($materialArray = array())
    {
        $language = null;
        if (array_key_exists('language', $materialArray)) {
            $languageArray = $materialArray['language'];
            if (array_key_exists('cod', $languageArray)) {
                $code = strtolower($languageArray['cod']);
                if (array_key_exists($code, $this->renameLanguages)) {
                    $language = $this->renameLanguages[$code];
                } else {
                    $language = $code;
                }
            }
        }
        return $language;
    }

    private function getDisplay($materialArray)
    {
        $display = true;
        if (array_key_exists('display', $materialArray)) {
            $displayString = $materialArray['display'];
            switch ($displayString) {
            case 'true':
                $display = true;
                break;
            case 'false':
                $display = false;
                break;
            }
        }

        return $display;
    }

    private function getMattype($materialArray)
    {
        $mattype = array();
        if (array_key_exists('mattype', $materialArray)) {
            $mattype = $materialArray['mattype'];
        }

        return $mattype;
    }

    private function getFieldFromMattype($field = '', $mattype = array())
    {
        $value = '';
        if (array_key_exists($field, $mattype)) {
            $value = $mattype[$field];
        }

        return $value;
    }
}
