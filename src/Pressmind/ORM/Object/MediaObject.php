<?php

namespace Pressmind\ORM\Object;

use DateTime;
use Exception;
use Custom\AbstractMediaType;
use Custom\MediaType\Factory;
use Pressmind\DB\Adapter\Pdo;
use Pressmind\HelperFunctions;
use Pressmind\MVC\View;
use Pressmind\ORM\Object\Itinerary\Variant;
use Pressmind\ORM\Object\MediaObject\DataType\Objectlink;
use Pressmind\ORM\Object\Touristic\Booking\Package;
use Pressmind\ORM\Object\Touristic\CheapestPrice;
use Pressmind\ORM\Object\Touristic\Date;
use Pressmind\ORM\Object\Touristic\Option;
use Pressmind\ORM\Object\Touristic\Transport;
use Pressmind\Registry;
use Pressmind\ValueObject\MediaObject\Result\GetByPrettyUrl;
use stdClass;

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

    /**
     * @return string[]
     * @throws Exception
     */
    public function buildPrettyUrls()
    {
        /** @var Pdo $db */
        $db = Registry::getInstance()->get('db');
        $config = Registry::getInstance()->get('config');
        $field = isset($config['data']['media_types_pretty_url'][$this->id_object_type]['field']) ? $config['data']['media_types_pretty_url'][$this->id_object_type]['field'] : ['name' => 'name'];
        $strategy = isset($config['data']['media_types_pretty_url'][$this->id_object_type]['strategy']) ? $config['data']['media_types_pretty_url'][$this->id_object_type]['strategy'] : 'unique';
        $url = $this->name;
        $field_name = $field['name'];
        if($field_name == 'name' || $field_name == 'code') {
            $url = strtolower(HelperFunctions::replaceLatinSpecialChars(trim($this->$field_name)));
        } else {
            $object = $this->data[0];
            if($object->getPropertyDefinition($field_name)['type'] == 'string') {
                if(!empty($object->$field_name)) {
                    $url = strtolower(HelperFunctions::replaceLatinSpecialChars(trim($object->$field_name)));
                }
            }
            if($object->getPropertyDefinition($field_name)['type'] == 'relation') {
                $linked_object_field_name = $field['linked_object_field'];
                $linked_objects = $object->$field_name;
                if(!empty($object->$field_name)) {
                    if(is_array($linked_objects)) {
                        if(get_class($linked_objects[0]) == Objectlink::class) {
                            $objectlink = new MediaObject($linked_objects[0]->id_media_object_link);
                            $url = strtolower(HelperFunctions::replaceLatinSpecialChars(trim($objectlink->data[0]->$linked_object_field_name)));
                        }
                    } else {
                        if(get_class($linked_objects) == Objectlink::class) {
                            $objectlink = new MediaObject($linked_objects->id_media_object_link);
                            $url = strtolower(HelperFunctions::replaceLatinSpecialChars(trim($objectlink->data[0]->$linked_object_field_name)));
                        }
                    }
                }
            }
        }
        $prefix = isset($config['data']['media_types_pretty_url'][$this->id_object_type]['prefix']) ? $config['data']['media_types_pretty_url'][$this->id_object_type]['prefix'] : '/';
        $suffix = isset($config['data']['media_types_pretty_url'][$this->id_object_type]['suffix']) ? $config['data']['media_types_pretty_url'][$this->id_object_type]['suffix'] : '';
        $final_url = $prefix . preg_replace('/\W+/', '-', $url) . $suffix;
        if($strategy == 'unique' || $strategy == 'count-up') {
            /** @var Route[] $existing_routes */
            $existing_routes = $db->fetchAll("SELECT * FROM pmt2core_routes WHERE SUBSTR(REPLACE(route, '" . $suffix . "', ''),1 , LENGTH(REPLACE(route, '$suffix', '')) - 1) = '" . str_replace($suffix, '', $final_url) . "' OR route = '" . $final_url . "'", null, Route::class);
            if(count($existing_routes) > 0) {
                if($strategy == 'unique') {
                    throw new Exception('Route with url ' . $final_url . ' already exists and route-building strategy is set to unique in config.json.');
                }
                /*if($strategy == 'count-up') {
                    $counter = 1;
                    foreach ($existing_routes as $existing_route) {
                        if (substr(str_replace($suffix, '', $existing_route->route), 0, -2) == $final_url || $existing_route->route == $final_url) {
                            //echo $existing_route->id_media_object . ': ' . substr(str_replace($suffix, '', $existing_route->route), 0, -2) . '-' . $counter . $suffix . "\n";
                            $new_route = str_replace($suffix, '', $existing_route->route) . '-' . count($existing_routes) . $suffix;
                            $existing_route->route = $new_route;
                            $existing_route->update();
                            $counter++;
                        }
                    }
                }*/
            }
        }
        return [$final_url];
    }

    public function getPrettyUrl()
    {
        $routes = $this->routes;
        if(!empty($routes)) {
            return $routes[0]->route;
        }
        return null;
    }

    /**
     * @param $route
     * @param $id_object_type
     * @param $visibility = 30
     * @return GetByPrettyUrl[]
     * @throws Exception
     */
    public static function getByPrettyUrl($route, $id_object_type = null, $language = 'de', $visibility = null)
    {
        /** @var Pdo $db */
        $db = Registry::getInstance()->get('db');
        $sql = [];
        $values = [$route, $language];
        $sql[] = "SELECT p2cmo.id, p2cmo.id_object_type, p2cmo.visibility, '" . $language ."' as language FROM pmt2core_media_objects p2cmo INNER JOIN pmt2core_routes p2cr on p2cmo.id = p2cr.id_media_object WHERE";
        $sql[] = "p2cr.route = ?";
        $sql[] = "AND p2cr.language = ?";
        if(!is_null($id_object_type)) {
            $sql[] = "AND p2cr.id_object_type = ?";
            $values[] = $id_object_type;
        }
        if(!is_null($visibility)) {
            $sql[] = "AND pmt2core_media_objects.visibility = ?";
            $values[] = $visibility;
        }
        $result = $db->fetchAll(implode(' ', $sql), $values, GetByPrettyUrl::class);
        return $result;
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

    public function createSearchIndex()
    {
        $config = Registry::getInstance()->get('config');
        if(isset($config['data']['media_types_fulltext_index_fields'])) {
            $complete_fulltext = [];
            $this->_db->delete('pmt2core_fulltext_search', ['id_media_object = ?', $this->getId()]);
            $fulltext[] = [
                'var_name' => 'code',
                'id_media_object' => $this->getId(),
                'fulltext_values' => $this->code
            ];
            $fulltext[] = [
                'var_name' => 'name',
                'id_media_object' => $this->getId(),
                'fulltext_values' => $this->name
            ];
            $fulltext[] = [
                'var_name' => 'tags',
                'id_media_object' => $this->getId(),
                'fulltext_values' => $this->tags
            ];
            foreach ($this->data as $data) {
                foreach ($data->getPropertyDefinitions() as $name => $definition) {
                    $add_to_complete_fulltext = in_array($name, $config['data']['media_types_fulltext_index_fields'][$this->id_object_type]);
                    if ($definition['type'] == 'string') {
                        $fulltext[] = [
                            'var_name' => $name,
                            'id_media_object' => $this->getId(),
                            'fulltext_values' => trim(preg_replace('/\s+/', ' ', strip_tags(str_replace('>', '> ', $data->$name))))
                        ];
                        if ($add_to_complete_fulltext) {
                            $complete_fulltext[] = trim(preg_replace('/\s+/', ' ', strip_tags(str_replace('>', '> ', $data->$name))));
                        }
                    }
                    if ($definition['type'] == 'relation') {
                        $values = [];
                        if ($definition['relation']['class'] == '\\Pressmind\\ORM\\Object\\MediaObject\\DataType\\Categorytree') {
                            foreach ($data->$name as $tree) {
                                $values[] = $tree->item->name;
                            }
                        }
                        if (count($values) > 0) {
                            $fulltext[] = [
                                'var_name' => $name,
                                'id_media_object' => $this->getId(),
                                'fulltext_values' => implode(' ', $values)
                            ];
                            if ($add_to_complete_fulltext) {
                                $complete_fulltext[] = implode(' ', $values);
                            }
                        }
                    }
                }
            }
            $fulltext[] = [
                'var_name' => 'fulltext',
                'id_media_object' => $this->getId(),
                'fulltext_values' => implode(' ', $complete_fulltext)
            ];
            foreach ($fulltext as $fulltext_data) {
                $this->_db->insert('pmt2core_fulltext_search', $fulltext_data);
            }
        }
    }

    /**
     * @param string|null $code
     * @param integer|null $duration
     * @param integer|null $id_booking_package
     * @param string|null $type
     * @return Variant[]
     * @throws Exception
     */
    public function getItineraryVariants($code = null, $duration = null, $id_booking_package = null, $type = null)
    {
        $filters = [
            'id_media_object' => $this->getId()
        ];
        if(!is_null($code)) {
            $filters['code'] = $code;
        }
        if(!is_null($duration)) {
            $filters['booking_package_duration'] = $duration;
        }
        if(!is_null($id_booking_package)) {
            $filters['id_booking_package'] = $id_booking_package;
        }
        if(!is_null($type)) {
            $filters['type'] = $type;
        }
        $variants = Variant::listAll($filters);
        return $variants;
    }
}
