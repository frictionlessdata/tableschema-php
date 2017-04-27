<?php
namespace frictionlessdata\tableschema\DataSources;

use frictionlessdata\tableschema\Exceptions\DataSourceException;

/**
 * handles reading data from a csv source
 * responsible for finding the header row based on options
 * support skipping rows from the csv
 */
class CsvDataSource extends BaseDataSource
{
    /**
     * @throws DataSourceException
     */
    public function open()
    {
        $this->curRowNum = 0;
        try {
            $this->resource = fopen($this->dataSource, "r");
        } catch (\Exception $e) {
            throw new DataSourceException($e->getMessage());
        }
        $this->headerRow = $this->getOption("headerRow");
        if ($this->headerRow) {
            $headerRowNum = 0;
            $defaultSkipRows = 0;
        } else {
            $defaultSkipRows = $headerRowNum = $this->getOption("headerRowNum", 1);
        }
        $skipRows = $this->getOption("skipRows", $defaultSkipRows);
        if ($skipRows > 0) {
            foreach (range(1, $skipRows) as $i) {
                $row = $this->getRow();
                $this->skippedRows[] = $row;
                if ($i == $headerRowNum) {
                    $this->headerRow = $row;
                }
            }
        }
        if (!$this->headerRow) {
            throw new DataSourceException("Failed to get header row");
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
     * @throws DataSourceException
     */
    public function getNextLine()
    {
        $row = $this->getRow();
        $colNum = 0;
        $obj = [];
        foreach ($this->headerRow as $fieldName) {
            $obj[$fieldName] = $row[$colNum++];
        }
        return $obj;
    }

    /**
     * @return bool
     * @throws DataSourceException
     */
    public function isEof()
    {
        try {
            return feof($this->resource);
        } catch (\Exception $e) {
            throw new DataSourceException($e->getMessage(), $this->curRowNum);
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

    protected $resource;
    protected $headerRow;
    protected $skippedRows;
    protected $curRowNum;

    /**
     * @return array
     * @throws DataSourceException
     */
    protected function getRow()
    {
        $this->curRowNum++;
        try {
            return fgetcsv($this->resource);
        } catch (\Exception $e) {
            throw new DataSourceException($e->getMessage(), $this->curRowNum);
        }
    }
}
