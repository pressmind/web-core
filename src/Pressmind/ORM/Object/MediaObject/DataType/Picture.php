<?php

namespace Pressmind\ORM\Object\MediaObject\DataType;
use Exception;
use Pressmind\Image\Download;
use Pressmind\Image\Processor;
use Pressmind\ORM\Object\AbstractObject;
use Pressmind\ORM\Object\MediaObject\DataType\Picture\Derivative;
use Pressmind\ORM\Object\MediaObject\DataType\Picture\Section;
use Pressmind\Registry;

/**
 * Class Plaintext
 * @package Pressmind\ORM\Object\MediaObject\DataType
 * @property integer $id
 * @property integer $id_media_object
 * @property string $section_name
 * @property string $var_name
 * @property string $file_name
 * @property integer $width
 * @property integer $height
 * @property integer $file_size
 * @property string $caption
 * @property string $title
 * @property string $uri
 * @property string $alt
 * @property string $copyright
 * @property integer $sort
 * @property string $tmp_url
 * @property string $path
 * @property string $mime_type
 * @property Derivative[] $derivatives
 * @property Section[] $sections
 */
class Picture extends AbstractObject
{
    protected $_definitions = [
        'class' => [
            'name' => 'Picture',
            'namespace' => '\Pressmind\ORM\MediaObject\DataType',
        ],
        'database' => [
            'table_name' => 'pmt2core_media_object_images',
            'primary_key' => 'id',
            'order_columns' => ['sort' => 'ASC']
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
            'section_name' => [
                'title' => 'section_name',
                'name' => 'section_name',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'language' => [
                'title' => 'section_name',
                'name' => 'section_name',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'var_name'  => [
                'title' => 'var_name',
                'name' => 'var_name',
                'type' => 'string',
                'required' => true,
                'filters' => null,
                'validators' => null,
            ],
            'caption' => [
                'title' => 'caption',
                'name' => 'caption',
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
            'title' => [
                'title' => 'title',
                'name' => 'title',
                'type' => 'string',
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
            'alt' => [
                'title' => 'alt',
                'name' => 'alt',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'copyright' => [
                'title' => 'copyright',
                'name' => 'copyright',
                'type' => 'string',
                'required' => false,
                'filters' => null,
                'validators' => null,
            ],
            'sort' => [
                'title' => 'sort',
                'name' => 'sort',
                'type' => 'integer',
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
                'title' => 'type',
                'name' => 'type',
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
                    'related_id' => 'id_image',
                ],
            ],
            'sections' => [
                'title' => 'sections',
                'name' => 'sections',
                'type' => 'relation',
                'required' => false,
                'filters' => null,
                'validators' => null,
                'relation' => [
                    'type' => 'hasMany',
                    'class' => Section::class,
                    'related_id' => 'id_image',
                    'on_save_related_properties' => [
                        'id' => 'id_image'
                    ],
                ],
            ],
        ]
    ];

    /**
     * @param null $derivativeName
     * @return string
     */
    public function getUri($derivativeName = null) {
        $config = Registry::getInstance()->get('config');
        if(!is_null($derivativeName)) {
            if($derivative = $this->hasDerivative($derivativeName)) {
                $uri = $config['imageprocessor']['image_http_path'] . $derivative->file_name;
                if($config['imageprocessor']['webp_support'] == true && $config['imageprocessor']['derivatives'][$derivativeName]['webp_create'] == true && defined('WEBP_SUPPORT') && WEBP_SUPPORT === true) {
                    $path_info = pathinfo($uri);
                    if(file_exists($derivative->path . DIRECTORY_SEPARATOR . str_replace($path_info['extension'], 'webp', $derivative->file_name))) {
                        $uri = str_replace($path_info['extension'], 'webp', $uri);
                    }
                }
                $uri = is_null($this->path) ? $this->getTmpUri($derivativeName) : $uri;
            } else {
                $uri = is_null($this->path) ? $this->getTmpUri() : $config['imageprocessor']['image_http_path'] . $this->file_name;
            }
        } else {
            $uri = is_null($this->path) ? $this->getTmpUri() : $config['imageprocessor']['image_http_path'] . $this->file_name;
        }
        return $uri;
    }

    /**
     * @param null $derivativeName
     * @return string
     */
    public function getTmpUri($derivativeName = null)
    {
        $height = null;
        $config = Registry::getInstance()->get('config');
        $parsed_query = [];
        $parsed_url = parse_url($this->tmp_url);
        parse_str($parsed_url['query'], $parsed_query);
        if(!is_null($derivativeName)) {
            $parsed_query['w'] = $config['imageprocessor']['derivatives'][$derivativeName]['max_width'];
            $parsed_query['h'] = $config['imageprocessor']['derivatives'][$derivativeName]['max_height'];
        }
        return $parsed_url['scheme'] . '://' . $parsed_url['host'] . $parsed_url['path'] . '?' . http_build_query($parsed_query);
    }

    /**
     * @param $derivativeName
     * @return bool|Derivative
     */
    public function hasDerivative($derivativeName)
    {
        if(is_null($this->derivatives)) {
            return false;
        }
        foreach ($this->derivatives as $derivative) {
            if($derivative->name == $derivativeName) {
                return $derivative;
            }
        }
        return false;
    }

    /**
     * @param string $sectionName
     * @return Section|null
     */
    public function getSection($sectionName) {
        foreach ($this->sections as $section) {
            if($section->section_name == $sectionName) {
                return $section;
            }
        }
        return null;
    }

    /**
     * @param $target_path
     * @param bool $use_cache
     * @throws Exception
     */
    public function downloadOriginal($target_path, $use_cache = true)
    {
        $download_url = $this->tmp_url;
        if($use_cache == false) {
            $download_url .= '&cache=0';
        }
        $downloader = new Download();
        $query = [];
        $url = parse_url($this->tmp_url);
        parse_str($url['query'], $query);
        $filename = $downloader->download($download_url, $target_path, $this->file_name);
        $this->path = $target_path;
        $this->file_name = $filename;
        $this->update();
    }

    /**
     * @throws Exception
     */
    public function removeDerivatives()
    {
        foreach ($this->derivatives as $derivative) {
            $derivative->delete();
        }
    }

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
        $derivative->id_image = $this->getId();
        $derivative->id_media_object = $this->id_media_object;
        $derivative->name = $derivative_config->name;
        $derivative->file_name = pathinfo($path)['filename'] . '.' . pathinfo($path)['extension'];
        $derivative->path = $this->path;
        $derivative->width = $derivative_config->max_width;
        $derivative->height = $derivative_config->max_height;
        $derivative->create();
    }
}
