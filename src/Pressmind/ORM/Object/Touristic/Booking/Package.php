<?php

namespace Pressmind\ORM\Object\Touristic\Booking;

use DateTime;
use Exception;
use Pressmind\ORM\Object\AbstractObject;
use Pressmind\ORM\Object\CheapestPriceSpeed;
use Pressmind\ORM\Object\Itinerary\Variant;
use Pressmind\ORM\Object\Touristic\Date;
use Pressmind\ORM\Object\Touristic\Insurance;
use Pressmind\ORM\Object\Touristic\Option;
use Pressmind\ORM\Object\Touristic\Pickupservice;
use Pressmind\ORM\Object\Touristic\SeasonalPeriod;

/**
 * Class TouristicBookingPackage
 * @property integer $id
 * @property integer $id_media_object
 * @property string $name
 * @property float $duration
 * @property integer $order
 * @property string $url
 * @property string $text
 * @property string $price_mix
 * @property integer $id_pickupservice
 * @property integer $id_insurance_group
 * @property integer $ibe_type
 * @property string $product_type_ibe
 * @property integer $id_origin
 * @property Pickupservice $pickupservice
 * @property Insurance\Group $insurance_group
 * @property Date[] $dates
 * @property SeasonalPeriod[] $seasonal_periods
 * @property Variant[] $itinerary_variants
 * @property \Pressmind\ORM\Object\Touristic\Housing\Package[] $housing_packages
 * @property Option[] $sightseeings
 * @property Option[] $tickets
 * @property Option[] $extras
 */
class Package extends AbstractObject
{

    protected $_dont_use_autoincrement_on_primary_key = true;

    protected $_definitions = array(
        'class' =>
            array(
                'name' => self::class,
            ),
        'database' =>
            array(
                'table_name' => 'pmt2core_touristic_booking_packages',
                'primary_key' => 'id',
            ),
        'properties' =>
            array(
                'id' =>
                    array(
                        'title' => 'Id',
                        'name' => 'id',
                        'type' => 'integer',
                        'required' => true,
                        'validators' =>
                            array(
                                0 =>
                                    array(
                                        'name' => 'maxlength',
                                        'params' => 22,
                                    ),
                            ),
                        'filters' => NULL,
                    ),
                'id_media_object' =>
                    array(
                        'title' => 'Id_media_object',
                        'name' => 'id_media_object',
                        'type' => 'integer',
                        'required' => true,
                        'validators' =>
                            array(
                                0 =>
                                    array(
                                        'name' => 'maxlength',
                                        'params' => 22,
                                    ),
                            ),
                        'filters' => NULL,
                    ),
                'name' =>
                    array(
                        'title' => 'Name',
                        'name' => 'name',
                        'type' => 'string',
                        'required' => false,
                        'validators' => NULL,
                        'filters' => NULL,
                    ),
                'duration' =>
                    array(
                        'title' => 'Duration',
                        'name' => 'duration',
                        'type' => 'float',
                        'required' => true,
                        'validators' => NULL,
                        'filters' => NULL,
                    ),
                'order' =>
                    array(
                        'title' => 'Order',
                        'name' => 'order',
                        'type' => 'integer',
                        'required' => true,
                        'validators' =>
                            array(
                                0 =>
                                    array(
                                        'name' => 'maxlength',
                                        'params' => 11,
                                    ),
                            ),
                        'filters' => NULL,
                    ),
                'url' =>
                    array(
                        'title' => 'Url',
                        'name' => 'url',
                        'type' => 'string',
                        'required' => false,
                        'validators' => NULL,
                        'filters' => NULL,
                    ),
                'text' =>
                    array(
                        'title' => 'Text',
                        'name' => 'text',
                        'type' => 'string',
                        'required' => false,
                        'validators' => NULL,
                        'filters' => NULL,
                    ),
                'price_mix' =>
                    array(
                        'title' => 'Price_mix',
                        'name' => 'price_mix',
                        'type' => 'string',
                        'required' => true,
                        'validators' =>
                            array(
                                0 =>
                                    array(
                                        'name' => 'maxlength',
                                        'params' => 255,
                                    ),
                            ),
                        'filters' => NULL,
                    ),
                'id_pickupservice' =>
                    array(
                        'title' => 'Id_pickupservice',
                        'name' => 'id_pickupservice',
                        'type' => 'integer',
                        'required' => false,
                        'validators' =>
                            array(
                                0 =>
                                    array(
                                        'name' => 'maxlength',
                                        'params' => 22,
                                    ),
                            ),
                        'filters' => NULL,
                    ),
                'id_insurance_group' =>
                    array(
                        'title' => 'Id_insurance_group',
                        'name' => 'id_insurance_group',
                        'type' => 'integer',
                        'required' => false,
                        'validators' =>
                            array(
                                0 =>
                                    array(
                                        'name' => 'maxlength',
                                        'params' => 22,
                                    ),
                            ),
                        'filters' => NULL,
                    ),
                'ibe_type' =>
                    array(
                        'title' => 'Ibe_type',
                        'name' => 'ibe_type',
                        'type' => 'integer',
                        'required' => false,
                        'validators' =>
                            array(
                                0 =>
                                    array(
                                        'name' => 'maxlength',
                                        'params' => 11,
                                    ),
                            ),
                        'filters' => NULL,
                    ),
                'product_type_ibe' =>
                    array(
                        'title' => 'Product_type_ibe',
                        'name' => 'product_type_ibe',
                        'type' => 'string',
                        'required' => false,
                        'validators' =>
                            array(
                                0 =>
                                    array(
                                        'name' => 'maxlength',
                                        'params' => 255,
                                    ),
                            ),
                        'filters' => NULL,
                    ),
                'id_origin' =>
                    array(
                        'title' => 'Id_origin',
                        'name' => 'id_origin',
                        'type' => 'integer',
                        'required' => false,
                        'validators' =>
                            array(
                                0 =>
                                    array(
                                        'name' => 'maxlength',
                                        'params' => 22,
                                    ),
                            ),
                        'filters' => NULL,
                    ),
                'pickupservice' => array(
                    'title' => 'Pickupservice',
                    'name' => 'pickupservice',
                    'type' => 'relation',
                    'relation' => array(
                        'type' => 'hasOne',
                        'related_id' => 'id_pickupservice',
                        'class' => Pickupservice::class
                    ),
                    'required' => false,
                    'validators' => null,
                    'filters' => null
                ),
                'insurance_group' => array(
                    'title' => 'Insurance Group',
                    'name' => 'insurance_group',
                    'type' => 'relation',
                    'relation' => array(
                        'type' => 'hasOne',
                        'related_id' => 'id_insurance_group',
                        'class' => Insurance\Group::class
                    ),
                    'required' => false,
                    'validators' => null,
                    'filters' => null
                ),
                'dates' => array(
                    'title' => 'Dates',
                    'name' => 'dates',
                    'type' => 'relation',
                    'relation' => array(
                        'type' => 'hasMany',
                        'related_id' => 'id_booking_package',
                        'class' => Date::class,
                        /*'filters' => array(
                            'departure' => array(
                                '>',
                                'CURRENT_DATE'
                            )
                        ),*/
                        'order_columns' => array(
                            'departure' => 'ASC'
                        )
                    ),
                    'required' => false,
                    'validators' => null,
                    'filters' => null
                ),
                'seasonal_periods' => array(
                    'title' => 'seasonal_periods',
                    'name' => 'seasonal_periods',
                    'type' => 'relation',
                    'relation' => array(
                        'type' => 'hasMany',
                        'related_id' => 'id_booking_package',
                        'class' => SeasonalPeriod::class
                    ),
                    'required' => false,
                    'validators' => null,
                    'filters' => null
                ),
                'itinerary_variants' => array(
                    'title' => 'itinerary_variants',
                    'name' => 'itinerary_variants',
                    'type' => 'relation',
                    'relation' => array(
                        'type' => 'hasMany',
                        'related_id' => 'id_booking_package',
                        'class' => Variant::class
                    ),
                    'required' => false,
                    'validators' => null,
                    'filters' => null
                ),
                'housing_packages' => array(
                    'title' => 'Housing Packages',
                    'name' => 'housing_packages',
                    'type' => 'relation',
                    'relation' => array(
                        'type' => 'hasMany',
                        'related_id' => 'id_booking_package',
                        'class' => '\\Pressmind\\ORM\\Object\\Touristic\\Housing\\Package'
                    ),
                    'required' => false,
                    'validators' => null,
                    'filters' => null
                ),
                'sightseeings' => array(
                    'title' => 'sightseeings',
                    'name' => 'sightseeings',
                    'type' => 'relation',
                    'relation' => array(
                        'type' => 'hasMany',
                        'related_id' => 'id_booking_package',
                        'class' => '\\Pressmind\\ORM\\Object\\Touristic\\Option',
                        'filters' => array(
                            'type' => 'sightseeing'
                        )
                    ),
                    'required' => false,
                    'validators' => null,
                    'filters' => null
                ),
                'tickets' => array(
                    'title' => 'tickets',
                    'name' => 'tickets',
                    'type' => 'relation',
                    'relation' => array(
                        'type' => 'hasMany',
                        'related_id' => 'id_booking_package',
                        'class' => '\\Pressmind\\ORM\\Object\\Touristic\\Option',
                        'filters' => array(
                            'type' => 'ticket'
                        )
                    ),
                    'required' => false,
                    'validators' => null,
                    'filters' => null
                ),
                'extras' => array(
                    'title' => 'extras',
                    'name' => 'extras',
                    'type' => 'relation',
                    'relation' => array(
                        'type' => 'hasMany',
                        'related_id' => 'id_booking_package',
                        'class' => '\\Pressmind\\ORM\\Object\\Touristic\\Option',
                        'filters' => array(
                            'type' => 'extra'
                        )
                    ),
                    'required' => false,
                    'validators' => null,
                    'filters' => null
                ),
            ),
    );

    /**
     * @return mixed
     * @throws Exception
     */
    public function getCheapestPrice()
    {
        $now = new DateTime();
        $where = "id_booking_package = " . $this->getId() . " AND price_total > 0 AND date_departure > '" . $now->format('Y-m-d H:i:s') . "'";
        $cheapest_price = CheapestPriceSpeed::listAll($where . ' AND option_occupancy = 2', ['price_total' => 'ASC']);
        if(empty($cheapest_price)) {
            $cheapest_price = CheapestPriceSpeed::listAll($where . ' AND option_occupancy = 1', ['price_total' => 'ASC']);
        }
        if(empty($cheapest_price)) {
            $cheapest_price = CheapestPriceSpeed::listAll($where, ['price_total' => 'ASC']);
        }
        return $cheapest_price[0];
    }
}
