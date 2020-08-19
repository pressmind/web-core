<?php


namespace Pressmind\Search\Condition;


use stdClass;

class Category implements ConditionInterface
{

    /**
     * @var string
     */
    private $_var_name;

    /**
     * @var array
     */
    private $_item_ids = [];

    /**
     * @var array
     */
    private $_values = [];

    /**
     * @var int
     */
    private $_sort = 3;

    /**
     * @var string
     */
    private $_combine_operator;

    /**
     * Category constructor.
     * @param string $pVarName
     * @param array $pItemIds
     * @param string $pCombineOperator
     */
    public function __construct($pVarName, $pItemIds, $pCombineOperator = 'OR')
    {
        $this->_var_name = $pVarName;
        $this->_item_ids = $pItemIds;
        $this->_combine_operator = $pCombineOperator;
    }

    /**
     * @return string
     */
    public function getSQL()
    {
        $item_id_strings = [];
        $term_counter = 0;
        foreach ($this->_item_ids as $item_id) {
            $term_counter++;
            $item_id_strings[] = $this->_var_name . '.id_item = :' . $this->_var_name . $term_counter;
            $this->_values[':' . $this->_var_name . $term_counter] = $item_id;
        }
        $sql = $this->_var_name . ".var_name = '" . $this->_var_name . "' AND (" . implode(' ' . $this->_combine_operator . ' ', $item_id_strings) . ")";
        return $sql;
    }

    /**
     * @return int
     */
    public function getSort()
    {
        return $this->_sort;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->_values;
    }

    /**
     * @return string|null
     */
    public function getJoins()
    {
        return 'INNER JOIN pmt2core_media_object_tree_items ' . $this->_var_name . ' ON pmt2core_media_objects.id = ' . $this->_var_name . '.id_media_object';
    }

    /**
     * @return string|null
     */
    public function getAdditionalFields()
    {
        return null;
    }

    /**
     * @param string $pVarName
     * @param array $pItemIds
     * @param string $pCombineOperator
     * @return Category
     */
    public static function create($pVarName, $pItemIds, $pCombineOperator = 'OR')
    {
        $object = new self($pVarName, $pItemIds, $pCombineOperator);
        return $object;
    }

    /**
     * @param stdClass $config
     */
    public function setConfig($config) {
        $this->_var_name = $config->var_name;
        $this->_item_ids = $config->item_ids;
        $this->_combine_operator = $config->combine_operator;
    }
}
