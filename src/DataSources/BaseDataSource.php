<?php
namespace frictionlessdata\tableschema\DataSources;

/**
 * base data source class with some common functionality
 */
abstract class BaseDataSource implements DataSourceInterface
{
    public function __construct($dataSource, $options=null)
    {
        $this->dataSource = $dataSource;
        $this->options = empty($options) ? (object)[] : $options;
    }

    public function open() {

    }

    abstract public function getNextLine();
    abstract public function isEof();

    public function close()
    {

    }

    protected $dataSource;
    protected $options;

    protected function getOption($name, $default=null)
    {
        if (isset($this->options->{$name})) {
            return $this->options->{$name};
        } else {
            return $default;
        }
    }
}
