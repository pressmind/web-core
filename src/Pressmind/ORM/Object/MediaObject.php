<?php

namespace Pressmind\ORM\Object;

use DateTime;
use Exception;
use Custom\AbstractMediaType;
use Custom\MediaType\Factory;
use Pressmind\HelperFunctions;
use Pressmind\MVC\View;
use Pressmind\ORM\Object\Touristic\Booking\Package;
use Pressmind\ORM\Object\Touristic\CheapestPrice;
use Pressmind\ORM\Object\Touristic\Date;
use Pressmind\ORM\Object\Touristic\Option;
use Pressmind\ORM\Object\Touristic\Transport;
use Pressmind\Registry;

/**
 * Class MediaObject
 * @property integer $id
 * @property integer $id_pool
 * @property integer $id_object_type
 * @property string $name
 * @property string $code
 * @property string $tags
 * @property integer $visibility
 * @property integer $state
 * @property DateTime $valid_from
 * @property DateTime $valid_to
 * @property integer $id_client
 * @property integer $hidden
 * @property AbstractMediaType[] $data
 * @property Package[] $booking_packages
 * @property Route[] $routes
 */
class MediaObject extends AbstractObject
{
    /**
     * @var Transport[]
     */
    private $_all_available_transports;

    /**
     * @var Date[]
     */
    private $_all_available_dates;

    protected $_dont_use_autoincrement_on_primary_key = true;
    protected $_definitions = [
        'class' =>
            [
                'name' => 'MediaObject',
                'namespace' => '\Pressmind\ORM\Object'
            ],
        'database' =>
            [
                'table_name' => 'pmt2core_media_objects',
                'primary_key' => 'id',
            ],
        'properties' =>
            [
                'id' =>
                    [
                        'title' => 'Id',
                        'name' => 'id',
                        'type' => 'integer',
                        'required' => true,
                        'validators' =>
                            [
                                0 =>
                                    [
                                        'name' => 'maxlength',
                                        'params' => 22,
                                    ],
                            ],
                        'filters' => NULL,
                    ],
                'id_pool' =>
                    [
                        'title' => 'Id_pool',
                        'name' => 'id_pool',
                        'type' => 'integer',
                        'required' => true,
                        'validators' =>
                            [
                                0 =>
                                    [
                                        'name' => 'maxlength',
                                        'params' => 22,
                                    ],
                            ],
                        'filters' => NULL,
                    ],
                'id_object_type' =>
                    [
                        'title' => 'Id_object_type',
                        'name' => 'id_object_type',
                        'type' => 'integer',
                        'required' => true,
                        'validators' =>
                            [
                                0 =>
                                    [
                                        'name' => 'maxlength',
                                        'params' => 22,
                                    ],
                            ],
                        'filters' => NULL,
                    ],
                'name' =>
                    [
                        'title' => 'Name',
                        'name' => 'name',
                        'type' => 'string',
                        'required' => true,
                        'validators' =>
                            [
                                0 =>
                                    [
                                        'name' => 'maxlength',
                                        'params' => 255,
                                    ],
                            ],
                        'filters' => NULL,
                    ],
                'code' =>
                    [
                        'title' => 'Code',
                        'name' => 'code',
                        'type' => 'string',
                        'required' => false,
                        'validators' =>
                            [
                                0 =>
                                    [
                                        'name' => 'maxlength',
                                        'params' => 255,
                                    ],
                            ],
                        'filters' => NULL,
                    ],
                'tags' =>
                    [
                        'title' => 'Tags',
                        'name' => 'tags',
                        'type' => 'string',
                        'required' => false,
                        'validators' => NULL,
                        'filters' => NULL,
                    ],
                'visibility' =>
                    [
                        'title' => 'Visibility',
                        'name' => 'visibility',
                        'type' => 'integer',
                        'required' => true,
                        'validators' =>
                            [
                                0 =>
                                    [
                                        'name' => 'maxlength',
                                        'params' => 11,
                                    ],
                            ],
                        'filters' => NULL,
                    ],
                'state' =>
                    [
                        'title' => 'State',
                        'name' => 'state',
                        'type' => 'integer',
                        'required' => true,
                        'validators' =>
                            [
                                0 =>
                                    [
                                        'name' => 'maxlength',
                                        'params' => 11,
                                    ],
                            ],
                        'filters' => NULL,
                    ],
                'valid_from' =>
                    [
                        'title' => 'Valid_from',
                        'name' => 'valid_from',
                        'type' => 'datetime',
                        'required' => false,
                        'validators' => NULL,
                        'filters' => NULL,
                    ],
                'valid_to' =>
                    [
                        'title' => 'Valid_to',
                        'name' => 'valid_to',
                        'type' => 'datetime',
                        'required' => false,
                        'validators' => NULL,
                        'filters' => NULL,
                    ],
                'id_client' =>
                    [
                        'title' => 'Id_client',
                        'name' => 'id_client',
                        'type' => 'integer',
                        'required' => true,
                        'validators' =>
                            [
                                0 =>
                                    [
                                        'name' => 'maxlength',
                                        'params' => 22,
                                    ],
                            ],
                        'filters' => NULL,
                    ],
                'hidden' =>
                    [
                        'title' => 'Hidden',
                        'name' => 'hidden',
                        'type' => 'integer',
                        'required' => true,
                        'validators' =>
                            [
                                0 =>
                                    [
                                        'name' => 'maxlength',
                                        'params' => 1,
                                    ],
                            ],
                        'filters' => NULL,
                    ],
                'routes' => [
                    'title' => 'routes',
                    'name' => 'routes',
                    'type' => 'relation',
                    'relation' => [
                        'type' => 'hasMany',
                        'related_id' => 'id_media_object',
                        'class' => Route::class,
                    ],
                    'required' => false,
                    'validators' => null,
                    'filters' => null
                ],
                'data' => [
                    'title' => 'data',
                    'name' => 'data',
                    'type' => 'relation',
                    'relation' => [
                        'from_factory' => true,
                        'factory_parameters' => array(
                            'id_object_type'
                        ),
                        'factory_method' => 'createById',
                        'type' => 'hasMany',
                        'related_id' => 'id_media_object',
                        'class' => Factory::class,
                        'filters' => null
                    ],
                    'required' => false,
                    'validators' => null,
                    'filters' => null
                ],
                'booking_packages' => [
                    'title' => 'booking_packages',
                    'name' => 'booking_packages',
                    'type' => 'relation',
                    'relation' => [
                        'type' => 'hasMany',
                        'related_id' => 'id_media_object',
                        'class' => Package::class,
                        'filters' => null
                    ],
                    'required' => false,
                    'validators' => null,
                    'filters' => null
                ],
            ]
    ];

    /**
     * @param string $template
     * @param string $language
     * @param object $custom_data
     * @return false|string
     * @throws Exception
     */
    public function render($template, $language = 'de', $custom_data = null) {
        $config = Registry::getInstance()->get('config');
        $media_type_name = $config['data']['media_types'][$this->id_object_type];
        $data = HelperFunctions::findObjectInArray($this->data, 'language', $language);
        $booking_packages = $this->booking_packages;
        $media_object = $this;
        $script_path = $config['view_scripts']['base_path'] . DIRECTORY_SEPARATOR . ucfirst($media_type_name) . '_' . ucfirst($template);
        $view = new View($script_path);
        return $view->render([
            'data' => $data,
            'booking_packages' => $booking_packages,
            'media_object' => $media_object,
            'custom_data' => $custom_data
        ]);
        /*$template_file = HelperFunctions::buildPathString(
            [
                BASE_PATH,
                'src',
                'Pressmind',
                'Custom',
                'Templates',
                ucfirst($media_type_name) . '_' . ucfirst($template) . '.php'
            ]
        );
        if(!file_exists($template_file)) {
            throw new Exception('Template file ' . $template_file . ' does not exist');
        }
        ob_start();
        require($template_file);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;*/
    }

    /**
     * Convenient static function to get a MediaObject by code (as defined in pressmind)
     * @param string $code
     * @return array
     * @throws Exception
     */
    public static function getByCode($code)
    {
        $object = new self();
        return $object->loadAll('code = ' . $code);
    }

    /**
     * @param \Pressmind\Search\CheapestPrice $filters
     * @return CheapestPriceSpeed
     * @throws Exception
     */
    public function getCheapestPrice($filters = null)
    {
        $CheapestPrice = $this->getCheapestPrices($filters);
        return empty($CheapestPrice[0]) ? null : $CheapestPrice[0];
    }

    /**
     * @param null $filters
     * @return CheapestPriceSpeed[]
     * @throws Exception
     */
    public function getCheapestPrices($filters = null)
    {
        //print_r($filters);
        $now = new DateTime();
        $where = "id_media_object = " . $this->getId() . " AND price_total > 0 AND date_departure > '" . $now->format('Y-m-d H:i:s') . "'";
        if(!is_null($filters)) {
            if(!is_null($filters->duration_from) && !is_null($filters->duration_to)) {
                $where .= ' AND duration BETWEEN ' . $filters->duration_from . ' AND ' . $filters->duration_to;
            }
            if(!is_null($filters->date_from) && !is_null($filters->date_to)) {
                $where .= " AND date_departure BETWEEN '" . $filters->date_from->format('Y-m-d H:i:s') . "' AND '" . $filters->date_to->format('Y-m-d H:i:s') . "'";
            }
        }
        $cheapest_prices = CheapestPriceSpeed::listAll($where . ' AND option_occupancy = 2', ['price_total' => 'ASC', 'date_departure' => 'ASC']);
        if(empty($cheapest_prices)) {
            $cheapest_prices = CheapestPriceSpeed::listAll($where . ' AND option_occupancy = 1', ['price_total' => 'ASC', 'date_departure' => 'ASC']);
        }
        if(empty($cheapest_prices)) {
            $cheapest_prices = CheapestPriceSpeed::listAll($where, ['price_total' => 'ASC', 'date_departure' => 'ASC', 'date_departure' => 'ASC']);
        }
        return $cheapest_prices;
    }

    public function getPrettyUrl()
    {
        $name = HelperFunctions::replaceLatinSpecialChars(trim(strtolower($this->name)));
        return preg_replace('/\W+/', '-', $name);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function insertCheapestPrice()
    {
        $booking_packages = $this->booking_packages;
        $result = [];
        foreach ($booking_packages as $booking_package) {
            foreach ($booking_package->dates as $date) {
                $transport_pairs = count($date->transports) > 0 ? [] : [null];
                foreach ($date->transports as $transport) {
                    if (!isset($transport_pairs[$transport->code])) {
                        $transport_pairs[$transport->code] = array();
                    }
                    $transport_pairs[$transport->code][$transport->way] = $transport;
                }
                if($booking_package->price_mix == 'date_housing') {
                    $options = $date->getHousingOptions();
                }
                if($booking_package->price_mix == 'date_sightseeing') {
                    $options = $date->getSightseeings();
                }
                if($booking_package->price_mix == 'date_extra') {
                    $options = $date->getExtras();
                }
                if($booking_package->price_mix == 'date_ticket') {
                    $options = $date->getTickets();
                }
                foreach ($options as $option) {
                    foreach ($transport_pairs as $transport_pair) {
                        if(!is_null($transport_pair)) {
                            $transport_price = $transport_pair[1]->price + (isset($transport_pair[2]) ? $transport_pair[2]->price : 0);
                        } else {
                            $transport_price = null;
                        }
                        $cheapestPriceSpeed = new CheapestPriceSpeed();
                        $cheapestPriceSpeed->id_media_object = $this->getId();
                        $cheapestPriceSpeed->id_booking_package = $booking_package->getId();
                        $cheapestPriceSpeed->id_housing_package = $option->id_housing_package;
                        $cheapestPriceSpeed->id_date = $date->getId();
                        $cheapestPriceSpeed->id_option = $option->getId();
                        $cheapestPriceSpeed->id_transport_1 = !is_null($transport_pair) ? $transport_pair[1]->id : null;
                        $cheapestPriceSpeed->id_transport_2 = !is_null($transport_pair) && isset($transport_pair[2]) ? $transport_pair[2]->id : null;
                        $cheapestPriceSpeed->duration = $booking_package->duration;
                        $cheapestPriceSpeed->date_departure = $date->departure;
                        $cheapestPriceSpeed->date_arrival = $date->arrival;
                        $cheapestPriceSpeed->option_name = $option->name;
                        $cheapestPriceSpeed->option_code = $option->code;
                        $cheapestPriceSpeed->option_board_type = $option->board_type;
                        $cheapestPriceSpeed->option_occupancy = $option->occupancy;
                        $cheapestPriceSpeed->option_occupancy_min = $option->occupancy_min;
                        $cheapestPriceSpeed->option_occupancy_max = $option->occupancy_max;
                        $cheapestPriceSpeed->price_transport_total = $transport_price;
                        $cheapestPriceSpeed->price_transport_1 = !is_null($transport_pair) ? $transport_pair[1]->price : null;
                        $cheapestPriceSpeed->price_transport_2 = !is_null($transport_pair) && isset($transport_pair[2]) ? $transport_pair[2]->price : null;
                        $cheapestPriceSpeed->price_mix = $booking_package->price_mix;
                        $cheapestPriceSpeed->price_option = $option->price;
                        $cheapestPriceSpeed->price_option_pseudo = $option->price_pseudo;
                        $cheapestPriceSpeed->price_regular_before_discount = $option->price;
                        $cheapestPriceSpeed->price_total = $option->price + $transport_price;
                        $cheapestPriceSpeed->transport_code = !is_null($transport_pair) ? $transport_pair[1]->code : null;
                        $cheapestPriceSpeed->transport_type = !is_null($transport_pair) ? $transport_pair[1]->type : null;
                        $cheapestPriceSpeed->transport_1_way = !is_null($transport_pair) ? $transport_pair[1]->way : null;
                        $cheapestPriceSpeed->transport_2_way = !is_null($transport_pair) && isset($transport_pair[2]) ? $transport_pair[2]->way : null;
                        $cheapestPriceSpeed->transport_1_description = !is_null($transport_pair) ? $transport_pair[1]->description : null;
                        $cheapestPriceSpeed->transport_2_description = !is_null($transport_pair) && isset($transport_pair[2]) ? $transport_pair[2]->description : null;
                        $cheapestPriceSpeed->state = 1;
                        $cheapestPriceSpeed->infotext = null;
                        $cheapestPriceSpeed->earlybird_discount = null;
                        $cheapestPriceSpeed->earlybird_discount_date_to = null;
                        $cheapestPriceSpeed->earlybird_discount_f = null;
                        $cheapestPriceSpeed->earlybird_discount_date_to_f = null;
                        $cheapestPriceSpeed->id_option_auto_book = null;
                        $cheapestPriceSpeed->id_option_required_group = null;
                        $cheapestPriceSpeed->id_start_point_option = null;
                        $cheapestPriceSpeed->id_origin = null;
                        $cheapestPriceSpeed->id_startingpoint = null;
                        $cheapestPriceSpeed->create();
                        $result[] = $cheapestPriceSpeed->toStdClass();
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @return Date[]
     * @throws Exception
     */
    public function getAllAvailableDates()
    {
        if(empty($this->_all_available_dates)) {
            $now = new DateTime();
            $this->_all_available_dates = Date::listAll(['id_media_object' => $this->getId(), 'departure' => ['>=', $now->format('Y-m-d H:i:s')]]);
        }
        return $this->_all_available_dates;
    }

    /**
     * @return Transport[]
     * @throws Exception
     */
    public function getAllAvailableTransports()
    {
        if(empty($this->_all_available_transports)) {
            foreach ($this->getAllAvailableDates() as $date) {
                foreach ($date->transports as $transport) {
                    if ($transport->way == 1) {
                        $this->_all_available_transports[] = $transport;
                    }
                }
            }
        }
        return $this->_all_available_transports;
    }

    /**
     * @param $tagName
     * @return mixed|null
     * @throws Exception
     */
    public function getValueByTagName($tagName) {
        /**@var ObjectdataTag[] $possible_columns**/
        $possible_columns = ObjectdataTag::listAll(['id_object_type' => $this->id_object_type, 'tag_name' => $tagName]);
        if(count($possible_columns) > 0) {
            $column_name = $possible_columns[0]->objectdata_column_name;
            return($this->data[0]->$column_name);
        }
        return null;
    }
}
