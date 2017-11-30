<?php

namespace frictionlessdata\tableschema\DataSources;

use frictionlessdata\tableschema\Exceptions\DataSourceException;
use frictionlessdata\tableschema\CsvDialect;

/**
 * handles reading data from a csv source
 * responsible for finding the header row based on options
 * support skipping rows from the csv.
 */
class CsvDataSource extends BaseDataSource
{
    /** @var CsvDialect */
    public $csvDialect;

    public function setCsvDialect($csvDialect)
    {
        $this->csvDialect = $csvDialect;
    }

    /**
     * @throws DataSourceException
     */
    public function open()
    {
        $this->curRowNum = 0;
        if (!$this->csvDialect) {
            $this->setCsvDialect(new CsvDialect());
        }
        try {
            $this->resource = fopen($this->dataSource, 'r');
        } catch (\Exception $e) {
            throw new DataSourceException($e->getMessage());
        }
        $this->headerRow = $this->getOption('headerRow');
        if ($this->headerRow) {
            // specifically set header row - will not skip any rows
            $headerRowNum = 0;
            $defaultSkipRows = 0;
        } else {
            // skip rows according to headerRowNum which is 1 by default
            $defaultSkipRows = $headerRowNum = $this->getOption('headerRowNum', 1);
        }
        /*
         * RFC4180:
         * - The last record in the file may or may not have an ending line break.
         * - Each line should contain the same number of fields throughout the file.
         *
         * Tabular Data requirements
         * - File encoding must be either UTF-8 (the default) or include encoding property
         * - If the CSV differs from this or the RFC in any other way regarding dialect
         *   (e.g. line terminators, quote charactors, field delimiters),
         *   the Tabular Data Resource MUST contain a dialect property describing its dialect.
         *   The dialect property MUST follow the CSV Dialect specification.
         */
        $skipRows = $this->getOption('skipRows', $defaultSkipRows);
        if ($skipRows > 0) {
            // either specifically set skipRows, or as required for the header row
            foreach (range(1, $skipRows) as $i) {
                $row = $this->getRow();
                $this->skippedRows[] = $row;
                if ($i == $headerRowNum) {
                    $this->headerRow = $row;
                }
            }
        }
        if (!$this->headerRow || $this->headerRow == ['']) {
            throw new DataSourceException('Failed to get header row');
        }
    }

    /**
     * @return array
     */
    public function getSkippedRows()
    {
        return $this->skippedRows;
    }

    /**
     * @return array
     *
     * @throws DataSourceException
     */
    public function getNextLine()
    {
        $row = $this->nextRow;
        if ($row === null) {
            if (!$this->resource) {
                $this->open();
            }
            if ($this->isEof()) {
                throw new \Exception('EOF');
            }
            $row = $this->nextRow;
            if ($row === null) {
                throw new \Exception('cannot get valid row, but isEof returns false');
            }
        }
        $this->nextRow = null;
        $colNum = 0;
        $obj = [];
        if (count($row) != count($this->headerRow)) {
            throw new DataSourceException('Invalid row: '.implode(', ', $row));
        }
        foreach ($this->headerRow as $fieldName) {
            $obj[$fieldName] = $row[$colNum];
            ++$colNum;
        }

        return $obj;
    }

    /**
     * @return bool
     *
     * @throws DataSourceException
     */
    public function isEof()
    {
        if ($this->nextRow) {
            return false;
        } else {
            try {
                $eof = feof($this->resource);
            } catch (\Exception $e) {
                throw new DataSourceException($e->getMessage(), $this->curRowNum);
            }
            if ($eof) {
                return true;
            } else {
                $this->nextRow = $this->getRow();
                if (!$this->nextRow || $this->nextRow === ['']) {
                    try {
                        $eof = feof($this->resource);
                    } catch (\Exception $e) {
                        throw new DataSourceException($e->getMessage(), $this->curRowNum);
                    }
                    if ($eof) {
                        // RFC4180: The last record in the file may or may not have an ending line break.
                        return true;
                    } else {
                        throw new DataSourceException('invalid csv file', $this->curRowNum);
                    }
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * @throws DataSourceException
     */
    public function close()
    {
        try {
            fclose($this->resource);
        } catch (\Exception $e) {
            throw new DataSourceException($e->getMessage(), $this->curRowNum);
        }
    }

    public function save($outputDataSource)
    {
        $file = fopen($outputDataSource, 'w');
        fputcsv($file, $this->headerRow);
        while (!$this->isEof()) {
            fputcsv($file, array_values($this->getNextLine()));
        }
        fclose($file);
    }

    protected $resource;
    protected $headerRow;
    protected $skippedRows;
    protected $curRowNum;
    protected $nextRow;

    /**
     * @return array
     *
     * @throws DataSourceException
     */
    protected function getRow($continueRow = null)
    {
        ++$this->curRowNum;
        try {
            $line = fgets($this->resource);
        } catch (\Exception $e) {
            throw new DataSourceException($e->getMessage(), $this->curRowNum);
        }

        $row = $this->csvDialect->parseRow($line, $continueRow);
        if (count($row) > 0 && is_a($row[count($row) - 1], 'frictionlessdata\\tableschema\\ContinueEnclosedField')) {
            return $this->getRow($row);
        } else {
            return $row;
        }
    }
}
