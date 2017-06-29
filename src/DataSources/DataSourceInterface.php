<?php

namespace frictionlessdata\tableschema\DataSources;

/**
 * interface for getting tabular data from different data sources.
 */
interface DataSourceInterface
{
    /**
     * @param mixed $dataSource implementation dependant data source (e.g. file name / url)
     * @param array $options    implementation dependant options
     */
    public function __construct($dataSource, $options);

    /**
     * open and prepare the data source for reading.
     */
    public function open();

    /**
     * get the next line of data form the data source
     * should match field name to field value.
     *
     * @return array
     */
    public function getNextLine();

    /**
     * @return bool
     */
    public function isEof();

    public function close();
}
