<?php

namespace frictionlessdata\tableschema;

use frictionlessdata\tableschema\Exceptions\DataSourceException;

/**
 * represents a data source which validates against a table schema
 * provides interfaces for validating the data and iterating over it
 * casts all values to their native values according to the table schema.
 */
class Table implements \Iterator
{
    /**
     * @param DataSources\DataSourceInterface $dataSource
     * @param Schema                          $schema
     *
     * @throws Exceptions\DataSourceException
     */
    public function __construct($dataSource, $schema)
    {
        $this->dataSource = $dataSource;
        $this->schema = $schema;
        $this->dataSource->open();
        $this->uniqueFieldValues = [];
    }

    /**
     * @param DataSources\DataSourceInterface $dataSource
     * @param Schema                          $schema
     * @param int                             $numPeekRows
     *
     * @return array of validation errors
     */
    public static function validate($dataSource, $schema, $numPeekRows = 10)
    {
        try {
            $table = new static($dataSource, $schema);
        } catch (Exceptions\DataSourceException $e) {
            return [new SchemaValidationError(SchemaValidationError::LOAD_FAILED, $e->getMessage())];
        }
        if ($numPeekRows > 0) {
            $i = 0;
            try {
                foreach ($table as $row) {
                    if (++$i > $numPeekRows) {
                        break;
                    }
                }
            } catch (Exceptions\DataSourceException $e) {
                // general error in getting the next row from the data source
                return [new SchemaValidationError(SchemaValidationError::ROW_VALIDATION, [
                    'row' => $i,
                    'error' => $e->getMessage(),
                ])];
            } catch (Exceptions\FieldValidationException $e) {
                // validation error in one of the fields
                return array_map(function ($validationError) use ($i) {
                    return new SchemaValidationError(SchemaValidationError::ROW_FIELD_VALIDATION, [
                        'row' => $i + 1,
                        'field' => $validationError->extraDetails['field'],
                        'error' => $validationError->extraDetails['error'],
                        'value' => $validationError->extraDetails['value'],
                    ]);
                }, $e->validationErrors);
            }
        }

        return [];
    }

    /**
     * called on each iteration to get the next row
     * does validation and casting on the row.
     *
     * @return mixed[]
     *
     * @throws Exceptions\FieldValidationException
     * @throws Exceptions\DataSourceException
     */
    public function current()
    {
        $row = $this->schema->castRow($this->dataSource->getNextLine());
        foreach ($this->schema->fields() as $field) {
            if ($field->unique()) {
                if (!array_key_exists($field->name(), $this->uniqueFieldValues)) {
                    $this->uniqueFieldValues[$field->name()] = [];
                }
                $value = $row[$field->name()];
                if (in_array($value, $this->uniqueFieldValues[$field->name()])) {
                    throw new DataSourceException('field must be unique', $this->currentLine);
                } else {
                    $this->uniqueFieldValues[$field->name()][] = $value;
                }
            }
        }

        return $row;
    }

    // not interesting, standard iterator functions
    // to simplify we prevent rewinding - so you can only iterate once
    // @codingStandardsIgnoreStart
    public function __destruct()
    {
        $this->dataSource->close();
    }

    public function rewind()
    {
        if ($this->currentLine == 0) {
            $this->currentLine = 1;
        } else {
            throw new \Exception('rewind is not supported');
        }
    }

    public function key()
    {
        return $this->currentLine;
    }

    public function next()
    {
        ++$this->currentLine;
    }

    public function valid()
    {
        return !$this->dataSource->isEof();
    }

    // @codingStandardsIgnoreEnd

    protected $currentLine = 0;
    protected $dataSource;
    protected $schema;
    protected $uniqueFieldValues;
}
