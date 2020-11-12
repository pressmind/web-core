<?php

namespace Pressmind\ORM\Object\MediaObject\DataType\Picture;
use Exception;
use Pressmind\ORM\Object\AbstractObject;
use Pressmind\ORM\Object\MediaObject\DataType\Picture;
use Pressmind\Image\Processor;
use Pressmind\ORM\Object\MediaObject\DataType\Picture\Derivative;

/**
 * Class Section
 * @package Pressmind\ORM\Object\MediaObject\DataType
 * @property integer $id
 * @property integer $id_media_object
 * @property integer $id_image
 * @property string $section_name
 * @property string $file_name
 * @property integer $width
 * @property integer $height
 * @property integer $file_size
 * @property string $uri
 * @property string $tmp_url
 * @property string $path
 * @property string $mime_type
 * @property Derivative[] $derivatives
 */
class Section extends Picture
{
    protected $_definitions = [
        'class' => [
            'name' => 'Section',
            'namespace' => '\Pressmind\ORM\MediaObject\DataType\Picture',
        ],
        'database' => [
            'table_name' => 'pmt2core_media_object_image_sections',
            'primary_key' => 'id',
            'order_columns' => null
        ],
        'properties' => [
            'id' => [
                'title' => 'id',
                'name' => 'id',
                'type' => 'integer',
                'required' => true,
                'filters' => null,
                'validators' => null,
            ],
            'id_media_object' => [
                'title' => 'id_media_object',
                'name' => 'id_media_object',
                'type' => 'integer',
                'required' => true,
                'filters' => null,
                'validators' => null,
            ],
            'id_image' => [
                'title' => 'id_image',
                'name' => 'id_image',
                'type' => 'integer',
                'required' => true,
                'filters' => null,
                'validators' => null,
            ],
            'section_name' => [
                'title' => 'section_name',
                'name' => 'section_name',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'file_name' => [
                'title' => 'file_name',
                'name' => 'file_name',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'width' => [
                'title' => 'width',
                'name' => 'width',
                'type' => 'integer',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'height' => [
                'title' => 'height',
                'name' => 'height',
                'type' => 'integer',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'file_size' => [
                'title' => 'file_size',
                'name' => 'file_size',
                'type' => 'integer',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'uri' => [
                'title' => 'uri',
                'name' => 'uri',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'tmp_url' => [
                'title' => 'tmp_url',
                'name' => 'tmp_url',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'path' => [
                'title' => 'path',
                'name' => 'path',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'mime_type' => [
                'title' => 'mime_type',
                'name' => 'mime_type',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'derivatives' => [
                'title' => 'derivatives',
                'name' => 'derivatives',
                'type' => 'relation',
                'required' => false,
                'filters' => null,
                'validators' => null,
                'relation' => [
                    'type' => 'hasMany',
                    'class' => Derivative::class,
                    'related_id' => 'id_image_section',
                ],
            ]
        ]
    ];

    /**
     * @param Processor\Config $derivative_config
     * @param Processor\AdapterInterface $image_processor
     * @throws Exception
     */
    public function createDerivative($derivative_config, $image_processor)
    {
        $path = $image_processor->process($derivative_config, $this->path . DIRECTORY_SEPARATOR . $this->file_name, $derivative_config->name);
        $webp_processor = new Processor\Adapter\WebPicture();
        $webp_processor->process($derivative_config, $this->path . DIRECTORY_SEPARATOR . $this->file_name, $derivative_config->name);
        $derivative = new Derivative();
        $derivative->id_image_section = $this->getId();
        $derivative->id_media_object = $this->id_media_object;
        $derivative->name = $derivative_config->name;
        $derivative->file_name = pathinfo($path)['filename'] . '.' . pathinfo($path)['extension'];
        $derivative->path = $this->path;
        $derivative->width = $derivative_config->max_width;
        $derivative->height = $derivative_config->max_height;
        //$derivative->uri = $this->uri;
        $derivative->create();
    }
}
