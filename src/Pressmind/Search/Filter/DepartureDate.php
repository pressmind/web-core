<?php


namespace Pressmind\Search\Filter;


use Pressmind\DB\Adapter\Pdo;
use Pressmind\Registry;
use Pressmind\Search;
use Pressmind\ValueObject\Search\Filter\Result\DateRange;
use Pressmind\ValueObject\Search\Filter\Result\MinMax;
use stdClass;

class DepartureDate implements FilterInterface
{

    /**
     * @var Search
     */
    private $_search;


    public function __construct($search = null)
    {
        $this->setSearch($search);
    }

    /**
     * @return Search
     */
    public function getSearch()
    {
        return $this->_search;
    }

    /**
     * @param Search $search
     * @return void
     */
    public function setSearch($search)
    {
        $this->_search = $search;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        /** @var Pdo $db */
        $db = Registry::getInstance()->get('db');
        $earliest_departure_date = null;
        $latest_departure_date = null;
        /** @var Search\Condition\DateRange $daterange_condition */
        if($daterange_condition = $this->_search->getCondition(Search\Condition\DateRange::class)) {
            $earliest_departure_date = $daterange_condition->dateFrom;
            $latest_departure_date = $daterange_condition->dateTo;
            $date_range = new DateRange();
            $date_range->from = $earliest_departure_date;
            $date_range->to = $latest_departure_date;
            return $date_range;
        } else {
            $results = $this->_search->getResults();
            $media_object_ids = [];
            foreach ($results as $result) {
                $media_object_ids[] = $result->id;
            }
            $dates = $db->fetchAll("SELECT date_departure from pmt2core_cheapest_price_speed WHERE id_media_object in(" . implode(',', $media_object_ids) . ") ORDER BY date_departure ASC");
            if (count($dates) > 0) {
                $counter = count($dates) - 1;
                $earliest_departure_date = \DateTime::createFromFormat('Y-m-d H:i:s', $dates[0]->date_departure);
                $latest_departure_date = \DateTime::createFromFormat('Y-m-d H:i:s', $dates[$counter]->date_departure);
                $date_range = new DateRange();
                $date_range->from = $earliest_departure_date;
                $date_range->to = $latest_departure_date;
                return $date_range;
            }
        }
        return null;
    }

    /**
     * @param $search
     * @return DepartureDate
     */
    public static function create($search) {
        return new self($search);
    }

    /**
     * @param stdClass $config
     * @return mixed
     */
    public function setConfig($config)
    {
        // TODO: Implement setConfig() method.
    }
}
