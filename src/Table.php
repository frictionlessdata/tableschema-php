<?php
namespace frictionlessdata\tableschema;

use frictionlessdata\tableschema\Exceptions\TableRowValidationException;

/**
 * represents a data source which validates against a table schema
 * provides interfaces for validating the data and iterating over it
 * casts all values to their native values according to the table schema
 */
class Table implements \Iterator
{
    /**
     * @param DataSources\DataSourceInterface $dataSource
     * @param Schema $schema
     * @throws Exceptions\DataSourceException
     */
    public function __construct($dataSource, $schema)
    {
        $this->dataSource = $dataSource;
        $this->schema = $schema;
        $this->dataSource->open();
    }

    /**
     * @param DataSources\DataSourceInterface $dataSource
     * @param Schema $schema
     * @param int $numPeekRows
     * @return array of validation errors
     */
    public static function validate($dataSource, $schema, $numPeekRows=10)
    {
        try {
            $table = new static($dataSource, $schema);
        } catch (Exceptions\DataSourceException $e) {
            return [new SchemaValidationError(SchemaValidationError::LOAD_FAILED, $e->getMessage())];
        };
        if ($numPeekRows > 0) {
            $i = 0;
            try {
                foreach ($table as $row) {
                    if (++$i > $numPeekRows) break;
                }
            } catch (Exceptions\DataSourceException $e) {
                return [new TableValidationError(TableValidationError::ROW_VALIDATION_FAILED, [
                    "row" => $i,
                    "error" => $e->getMessage()
                ])];
            } catch (Exceptions\TableRowValidationException $e) {
                return $e->validationErrors;
            }
        }
        return [];
    }

    /**
     * called on each iteration to get the next row
     * depends on order of fields in the schema to match to the order of fields from the data source
     * @return array
     * @throws TableRowValidationException
     */
    public function current() {
        $line = $this->dataSource->getNextLine();
        return $this->filterLine($line);
    }

    // not interesting, standard iterator functions
    // to simplify we prevent rewinding - so you can only iterate once
    public function __destruct() {$this->dataSource->close();}
    public function rewind() {if ($this->currentLine == 0) {$this->currentLine = 1;} else {throw new \Exception("rewind is not supported");}}
    public function key() {return $this->currentLine;}
    public function next() {$this->currentLine++;}
    public function valid() {return !$this->dataSource->isEof();}

    protected $currentLine = 0;
    protected $dataSource;
    protected $schema;

    /**
     * validates the given line against the table schema
     * casts the values to the native representation according to the schema
     * @param array $line
     * @return array
     * @throws TableRowValidationException
     */
    protected function filterLine($line)
    {
        $outLine = [];
        $validationErrors = [];
        foreach ($this->schema->descriptor()->fields as $field) {
            if (isset($line[$field->name])) {
                $value = $line[$field->name];
            } else {
                $value = null;
            }
            if (
                isset($field->type) && $field->type == "string"
                && isset($field->format) && $field->format == "email"
                && strpos($value, "@") === false
            ) {
                $validationErrors[] = new TableValidationError(TableValidationError::ROW_VALIDATION_FAILED, [
                    "row" => $this->currentLine,
                    "col" => $field->name,
                    "val" => $value,
                    "error" => "invalid value for email format"
                ]);
            }
            $outLine[$field->name] = $value;
        }
        if (count($validationErrors) > 0) {
            throw new TableRowValidationException($validationErrors);
        }
        return $outLine;
    }
}