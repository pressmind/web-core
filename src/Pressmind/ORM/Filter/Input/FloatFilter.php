<?php


namespace Pressmind\ORM\Filter\Input;
use Pressmind\ORM\Filter\FilterInterface;

class FloatFilter implements FilterInterface
{
    private $_errors = [];

    public function filterValue($pValue)
    {
        return floatval($pValue);
    }

    public function getErrors()
    {
        return $this->_errors;
    }
}
