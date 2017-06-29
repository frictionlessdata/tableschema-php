<?php

namespace frictionlessdata\tableschema;

/**
 *  Table Schema which updates the descriptor based on input data.
 */
class InferSchema extends Schema
{
    /**
     * InferSchema constructor.
     *
     * @param null $descriptor optional descriptor object - will be used as an initial descriptor
     * @param bool $lenient    if true - infer just basic types, without strict format requirements
     */
    public function __construct($descriptor = null, $lenient = false)
    {
        $this->descriptor = empty($descriptor) ? (object) ['fields' => []] : $descriptor;
        $this->fieldsInferer = new Fields\FieldsInferrer(null, $lenient);
    }

    /**
     * @return object
     */
    public function descriptor()
    {
        return $this->descriptor;
    }

    /**
     * @param mixed[] $row
     *
     * @return mixed[]
     *
     * @throws Exceptions\FieldValidationException
     */
    public function castRow($row)
    {
        if ($this->isLocked) {
            // schema is locked, no more inferring is needed
            return parent::castRow($row);
        } else {
            // add the row to the inferrer, update the descriptor according to the best inferred fields
            $this->fieldsInferer->addRows([$row]);
            $this->descriptor->fields = [];
            foreach ($this->fieldsInferer->infer() as $fieldName => $inferredField) {
                /* @var Fields\BaseField $inferredField */
                $this->descriptor->fields[] = $inferredField->descriptor();
            }
            $this->castRows = $this->fieldsInferer->castRows();

            return $this->castRows[count($this->castRows) - 1];
        }
    }

    public function lock()
    {
        $this->isLocked = true;

        return $this->castRows;
    }

    protected $isLocked = false;
    protected $fieldsInferer;
    protected $castRows;
}
