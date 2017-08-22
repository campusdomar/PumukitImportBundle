<?php

namespace Pumukit\ImportBundle\Services;

class ImportCommonService
{
    /**
     * Set Field with value.
     *
     * @param string       $setField
     * @param string|array $fieldValue
     * @param object       $resource
     *
     * @return object $resource
     */
    protected function setFieldWithValue($setField, $fieldValue, $resource)
    {
        if ('true' === $fieldValue) {
            $resource->$setField(true);
        } elseif ('false' === $fieldValue) {
            $resource->$setField(false);
        } elseif (null != $fieldValue) {
            if ('array' === gettype($fieldValue)) {
                if (!empty(array_filter($fieldValue))) {
                    if (0 === strpos($setField, 'setI18n')) {
                        $setLocaleField = str_replace('I18n', '', $setField);
                        foreach ($fieldValue as $locale => $value) {
                            if (null != $value) {
                                if (is_array($value) && empty(trim($value[0]))) {
                                    $resource->$setLocaleField('', $locale);
                                } else {
                                    $resource->$setLocaleField($value, $locale);
                                }
                            } else {
                                $resource->$setLocaleField('', $locale);
                            }
                        }
                    } else {
                        $resource->$setField($fieldValue);
                    }
                }
            } else {
                $resource->$setField($fieldValue);
            }
        }

        return $resource;
    }

    /**
     * Set fields.
     *
     * @param array  $xmlArray
     * @param array  $setFields
     * @param object $resource
     *
     * @return object $resource
     */
    protected function setFields($xmlArray, $setFields, $resource)
    {
        foreach ($xmlArray as $field => $value) {
            if (array_key_exists($field, $setFields)) {
                $setField = $setFields[$field];
                $resource = $this->setFieldWithValue($setField, $value, $resource);
            }
        }

        return $resource;
    }

    /**
     * Set attributes
     * (id, rank).
     *
     * @param array  $attributes
     * @param array  $attributesSetFields
     * @param object $resource
     *
     * @return object $resource
     */
    protected function setAttributes($attributes, $attributesSetFields, $resource)
    {
        foreach ($attributes as $field => $value) {
            if (array_key_exists($field, $attributesSetFields)) {
                $setField = $attributesSetFields[$field];
                $resource = $this->setFieldWithValue($setField, $value, $resource);
            }
        }

        return $resource;
    }
}
