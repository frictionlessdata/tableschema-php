<?php

namespace frictionlessdata\tableschema\Fields;

use frictionlessdata\tableschema\Exceptions\FieldValidationException;

class FieldsInferrer
{
    /**
     * @param null|array $rows optional initial rows to infer by, each row is an array of field name => field value
     */
    public function __construct($rows = null, $lenient = false)
    {
        $this->lenient = $lenient;
        if (!empty($rows)) {
            $this->addRows($rows);
        }
    }

    /**
     * add rows and updates the fieldsPopularity array - to make the inferred fields more accurate.
     *
     * @param $rows
     *
     * @throws FieldValidationException
     */
    public function addRows($rows)
    {
        foreach ($rows as $row) {
            $this->inputRows[] = $row;
            $inferredRow = $this->inferRow($row);
            foreach ($this->getFieldNames() as $fieldName) {
                /** @var BaseField $inferredField */
                $inferredField = $inferredRow[$fieldName];
                $inferredFieldType = $inferredField->getInferIdentifier($this->lenient);
                if (!array_key_exists($fieldName, $this->fieldsPopularity)) {
                    $this->fieldsPopularity[$fieldName] = [];
                    $this->fieldsPopularityObjects[$fieldName] = [];
                }
                if (!array_key_exists($inferredFieldType, $this->fieldsPopularity[$fieldName])) {
                    $this->fieldsPopularity[$fieldName][$inferredFieldType] = 0;
                    $this->fieldsPopularityObjects[$fieldName][$inferredFieldType] = $inferredField;
                }
                ++$this->fieldsPopularity[$fieldName][$inferredFieldType];
                arsort($this->fieldsPopularity[$fieldName]);
            }
        }
    }

    /**
     * return the best inferred fields along with the best value casting according to the rows received so far.
     *
     * @return array field name => inferred field object
     *
     * @throws FieldValidationException
     */
    public function infer()
    {
        $bestInferredFields = [];
        foreach ($this->fieldsPopularity as $fieldName => $fieldTypesPopularity) {
            $bestInferredFields[$fieldName] = $this->inferField($fieldName, $fieldTypesPopularity);
        }

        return $bestInferredFields;
    }

    /**
     * returns all the input rows got so far with the best cast value for each field.
     *
     * @return array of arrays of field name => best cast value
     */
    public function castRows()
    {
        return $this->castRows;
    }

    protected $inputRows = [];
    protected $castRows = [];
    protected $fieldsPopularity = [];
    protected $fieldsPopularityObjects = [];
    protected $lenient;

    /**
     * infer field objects for the given row
     * raises exception if fails to infer a field.
     *
     * @param $row array field name => value to infer by
     *
     * @return array field name => inferred field object
     *
     * @throws FieldValidationException
     */
    protected function inferRow($row)
    {
        $rowFields = [];
        foreach ($row as $k => $v) {
            $rowFields[$k] = FieldsFactory::infer($v, (object) ['name' => $k], $this->lenient);
        }

        return $rowFields;
    }

    /**
     * @return array
     */
    protected function getFieldNames()
    {
        // we assume csv file where all rows have the same column positions
        // so we can use the first row to get the field names
        return array_keys($this->inputRows[0]);
    }

    /**
     * finds the best inferred fields for the given field name according to the popularity
     * also updates the castRows array with the latest cast values.
     *
     * @param $fieldName
     * @param $fieldTypesPopularity
     *
     * @return BaseField|null
     */
    protected function inferField($fieldName, $fieldTypesPopularity)
    {
        // the $fieldTypesPopularity array is already sorted with most popular fields first
        $inferredField = null;
        foreach (array_keys($fieldTypesPopularity) as $inferredFieldType) {
            /** @var BaseField $inferredField */
            $inferredField = $this->fieldsPopularityObjects[$fieldName][$inferredFieldType];
            try {
                $rowNum = 0;
                foreach ($this->inputRows as $inputRow) {
                    if (!array_key_exists($rowNum, $this->castRows)) {
                        $this->castRows[$rowNum] = [];
                    }
                    $this->castRows[$rowNum][$fieldName] = $inferredField->castValue($inputRow[$fieldName]);
                    ++$rowNum;
                }
                break;
            } catch (FieldValidationException $e) {
                // a row failed validation for this field type, will continue to the next one according to popularity
                continue;
            }
        }

        return $inferredField;
    }
}
