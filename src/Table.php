<?php

namespace frictionlessdata\tableschema;

use frictionlessdata\tableschema\DataSources\CsvDataSource;
use frictionlessdata\tableschema\Exceptions\DataSourceException;

/**
 * represents a data source which validates against a table schema
 * provides interfaces for validating the data and iterating over it
 * casts all values to their native values according to the table schema.
 */
class Table implements \Iterator
{
    public $csvDialect;

    /**
     * @param DataSources\DataSourceInterface $dataSource
     * @param Schema                          $schema
     * @param object                          $csvDialect
     *
     * @throws Exceptions\DataSourceException
     */
    public function __construct($dataSource, $schema = null, $csvDialect = null)
    {
        $this->csvDialect = new CsvDialect($csvDialect);
        if (!is_a($dataSource, 'frictionlessdata\\tableschema\\DataSources\\BaseDataSource')) {
            // TODO: more advanced data source detection
            $dataSource = new CsvDataSource($dataSource);
        }
        if (is_a($dataSource, 'frictionlessdata\\tableschema\\DataSources\\CsvDataSource')) {
            $dataSource->setCsvDialect($this->csvDialect);
        }
        $this->dataSource = $dataSource;
        if (!is_a($schema, 'frictionlessdata\\tableschema\\Schema')) {
            if ($schema) {
                $schema = new Schema($schema);
            } else {
                $schema = new InferSchema();
            }
        }
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
    public static function validate($dataSource, $schema, $numPeekRows = 10, $csvDialect = null)
    {
        try {
            $table = new static($dataSource, $schema, $csvDialect);
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

    public function schema($numPeekRows = 10)
    {
        $this->ensureInferredSchema($numPeekRows);

        return $this->schema;
    }

    public function headers($numPeekRows = 10)
    {
        $this->ensureInferredSchema($numPeekRows);

        return array_keys($this->schema->fields());
    }

    public function read($options = null)
    {
        $options = array_merge([
            'keyed' => true,
            'extended' => false,
            'cast' => true,
            'limit' => null,
        ], $options ? $options : []);
        $rows = [];
        $rowNum = 0;
        if ($options['extended']) {
            $headers = $this->headers($options['limit'] ? $options['limit'] : null);
        }
        if (!$options['cast']) {
            $this->dataSource->open();
            while (!$this->dataSource->isEof()) {
                $row = $this->dataSource->getNextLine();
                if ($options['extended']) {
                    $rows[] = [$rowNum, $headers, array_values($row)];
                } else {
                    $rows[] = $row;
                }
                if ($options['limit'] && $options['limit'] > 0 && $rowNum + 1 >= $options['limit']) {
                    break;
                }
                ++$rowNum;
            }
        } else {
            foreach ($this as $row) {
                if ($options['extended']) {
                    $rows[] = [$rowNum, $headers, array_values($row)];
                } else {
                    $rows[] = $row;
                }
                if ($options['limit'] && $options['limit'] > 0 && $rowNum + 1 >= $options['limit']) {
                    break;
                }
                ++$rowNum;
            }
        }

        return $rows;
    }

    public function save($outputDataSource)
    {
        return $this->dataSource->save($outputDataSource);
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
        if (count($this->castRows) > 0) {
            $row = array_shift($this->castRows);
        } else {
            $row = $this->schema->castRow($this->dataSource->getNextLine());
            foreach ($this->schema->fields() as $field) {
                if ($field->unique()) {
                    $fieldName = $field->name();
                    $value = $row[$fieldName];

                    if (
                        array_key_exists($fieldName, $this->uniqueFieldValues)
                        && in_array($value, $this->uniqueFieldValues[$fieldName], false)
                    ) {
                        throw new DataSourceException('field must be unique', $this->currentLine);
                    }

                    $this->uniqueFieldValues[$fieldName][] = $value;
                }
            }

            $pkRowValues = [];

            foreach ($this->schema->primaryKey() as $key) {
                $value = $row[$key];

                if (null === $value) {
                    throw new DataSourceException('value for '.$key.' field cannot be null because it is part of the primary key', $this->currentLine);
                }

                $pkRowValues[$key] = $value;
            }

            if ([] !== $pkRowValues) {
                if (in_array($pkRowValues, $this->primaryKeyValues, false)) {
                    throw new DataSourceException('duplicate row for the primary key '.implode('/', array_keys($pkRowValues)), $this->currentLine);
                }

                $this->primaryKeyValues[] = $pkRowValues;
            }
        }

        return $row;
    }

    // not interesting, standard iterator functions
    // to simplify we prevent rewinding - so you can only iterate once
    public function __destruct()
    {
        $this->dataSource->close();
    }

    public function rewind()
    {
        if (0 == $this->currentLine) {
            $this->currentLine = 1;
        } elseif (0 == count($this->castRows)) {
            $this->currentLine = 1;
            $this->dataSource->open();
        }
    }

    public function key()
    {
        return $this->currentLine - count($this->castRows);
    }

    public function next()
    {
        if (0 == count($this->castRows)) {
            ++$this->currentLine;
        }
    }

    public function valid()
    {
        return count($this->castRows) > 0 || !$this->dataSource->isEof();
    }

    protected $currentLine = 0;
    protected $dataSource;
    protected $schema;
    protected $uniqueFieldValues;
    protected $primaryKeyValues = [];
    protected $castRows = [];

    protected function isInferSchema()
    {
        return is_a($this->schema, 'frictionlessdata\\tableschema\\InferSchema');
    }

    protected function ensureInferredSchema($numPeekRows = 10)
    {
        if ($this->isInferSchema() && 0 == count($this->schema->fields())) {
            // need to fetch some rows first
            if ($numPeekRows > 0) {
                $i = 0;
                foreach ($this as $row) {
                    if (++$i > $numPeekRows) {
                        break;
                    }
                }
                // these rows will be returned by next current() call
                $this->castRows = $this->schema->lock();
            }
        }
    }
}
