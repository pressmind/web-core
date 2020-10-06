<?php


namespace Pressmind\ORM\Object\Itinerary\Variant\Step;


use Pressmind\ORM\Object\AbstractObject;
use Pressmind\ORM\Object\Itinerary\Variant\Step\Section\Content;

/**
 * Class Section
 * @package Pressmind\ORM\Object\Itinerary\Step
 * @property integer $id
 * @property integer $id_step
 * @property string $id_section
 * @property string $name
 * @property string $varname
 * @property string $language
 * @property Content $content
 */
class Section extends AbstractObject
{
    protected $_definitions = [
        'class' => [
            'name' => 'Section',
            'namespace' => 'Pressmind\ORM\Object\Itinerary\Variant\Step'
        ],
        'database' => [
            'table_name' => 'pmt2core_itinerary_step_sections',
            'primary_key' => 'id'
        ],
        'properties' => [
            'id' => [
                'title' => 'id',
                'name' => 'id',
                'type' => 'integer',
                'required' => false,
                'validators' => null,
                'filters' => null
            ],
            'id_step' => [
                'title' => 'id_step',
                'name' => 'id_step',
                'type' => 'integer',
                'required' => false,
                'validators' => null,
                'filters' => null
            ],
            'id_section' => [
                'title' => 'id_section',
                'name' => 'id_section',
                'type' => 'string',
                'required' => false,
                'validators' => null,
                'filters' => null
            ],
            'name' => [
                'title' => 'name',
                'name' => 'name',
                'type' => 'string',
                'required' => false,
                'validators' => null,
                'filters' => null
            ],
            'varname' => [
                'title' => 'varname',
                'name' => 'varname',
                'type' => 'string',
                'required' => false,
                'validators' => null,
                'filters' => null
            ],
            'language' => [
                'title' => 'language',
                'name' => 'language',
                'type' => 'string',
                'required' => false,
                'validators' => null,
                'filters' => null
            ],
            'content' => [
                'title' => 'content',
                'name' => 'content',
                'type' => 'relation',
                'relation' => [
                    'type' => 'belongsTo',
                    'related_id' => 'id_section',
                    'class' => Content::class,
                    'filters' => null
                ],
                'required' => false,
                'validators' => null,
                'filters' => null
            ]
        ]
    ];
}
