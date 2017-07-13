<?php

namespace frictionlessdata\tableschema\DataSources;

use frictionlessdata\tableschema\Exceptions\DataSourceException;

/**
 * the data source parameter provided to the constructor should be an array of values
 * those values are then returned on each iteration.
 */
class NativeDataSource extends BaseDataSource
{
    public function open()
    {
        // no opening is needed for native data source
    }

    /**
     * @return array
     *
     * @throws DataSourceException
     */
    public function getNextLine()
    {
        return $this->dataSource[$this->curRowNum++];
    }

    /**
     * @return bool
     *
     * @throws DataSourceException
     */
    public function isEof()
    {
        return $this->curRowNum >= count($this->dataSource);
    }

    public function save($output)
    {
        // no point in saving for native data source
    }

    protected $curRowNum = 0;
}
