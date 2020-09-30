<?php

namespace Pressmind;

use Custom\MediaType\Factory;
use Pressmind\DB\Adapter\Pdo;
use Pressmind\Log\Writer;
use Pressmind\ORM\Object\AbstractObject;
use Pressmind\ORM\Object\CategoryTree\Item;
use Pressmind\ORM\Object\MediaObject;
use Pressmind\ORM\Object\Route;
use Pressmind\ORM\Object\Touristic\Startingpoint\Option;
use Pressmind\REST\Client;
use \DirectoryIterator;
use \Exception;
use stdClass;

// additional use statements for postImportImageProcessor()
use Error;
use Pressmind\Image\Download;
use Pressmind\Image\Processor\Adapter\Factory as ImageFactory;
use Pressmind\Image\Processor\Config;
use Pressmind\ORM\Object\MediaObject\DataType\Picture;
use Pressmind\ORM\Object\MediaObject\DataType\Picture\Derivative;

/**
 * Class Importer
 * @package Pressmind
 */
class Import
{

    /**
     * @var Client
     */
    private $_client;

    /**
     * @var string
     */
    private $_tmp_import_folder = 'import_ids';

    /**
     * @var array
     */
    private $_log = [];

    /**
     * @var array
     */
    private $_errors = [];

    /**
     * @var array
     */
    private $_current_touristic_data_to_import = [];

    /**
     * @var array
     */
    private $_current_media_object_data_to_import = [];

    private $_import_type = null;

    /**
     * @var array
     */
    private $_touristic_object_map = [
        'touristic_booking_packages' => '\Booking\Package',
        'touristic_dates' => '\Date',
        'touristic_seasonal_periods' => '\SeasonalPeriod',
        'touristic_transports' => '\Transport',
        'touristic_booking_earlybirds' => '\Booking\Earlybird',
        'touristic_housing_packages' => '\Housing\Package',
        'touristic_housing_packages_description_links' => '\Housing\Package\DescriptionLink',
        'touristic_option_descriptions' => '\Option\Description',
        'touristic_options' => '\Option',
        'touristic_startingpoint_options' => '\Startingpoint\Option',
        'touristic_startingpoint_options_zip_ranges' => '\Startingpoint\Option\ZipRange',
        'touristic_startingpoints' => '\Startingpoint',
        'touristic_insurance_groups' => '\Insurance\Group',
        'touristic_insurance_to_group' => '\Insurance\InsuranceToGroup',
        'touristic_insurances' => '\Insurance',
        'touristic_insurances_price_tables' => '\Insurance\PriceTable',
    ];

    /**@var array**/
    private $_touristic_object_field_map = [
        'touristic_booking_packages' => [
            'id_insurances_groups' => 'id_insurance_group'
        ]
    ];

    /**
     * @var float
     */
    private $_start_time;

    /**
     * @var float
     */
    private $_overall_start_time;

    /**
     * @var array
     */
    private $_imported_ids = [];

    /**
     * Importer constructor.
     * @param string $importType
     * @throws Exception
     */
    public function __construct($importType = 'fullimport')
    {
        $this->_start_time = microtime(true);
        $this->_overall_start_time = microtime(true);
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::__construct()', Writer::OUTPUT_FILE, 'import.log');
        $this->_client = new Client();
        $this->_import_type = $importType;
    }

    /**
     * @param integer|null $id_pool
     * @throws Exception
     */
    public function import($id_pool = null)
    {
        $conf = Registry::getInstance()->get('config');
        $allowed_object_types = array_keys($conf['data']['media_types']);
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::import()', Writer::OUTPUT_FILE, 'import.log');
        $params = [
            'id_media_object_type' => implode(',', $allowed_object_types)
        ];
        if (!is_null($id_pool)) {
            $params['id_pool'] = intval($id_pool);
        }
        $this->_importIds(0, $params);
        $this->_importMediaObjectsFromFolder();
        $this->removeOrphans();
    }

    /**
     * @param int $startIndex
     * @param array $params
     * @param int $numItems
     * @throws Exception
     */
    private function _importIds($startIndex, $params, $numItems = 50)
    {
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importIds()', Writer::OUTPUT_FILE, 'import.log');
        $params['startIndex'] = $startIndex;
        $params['numItems'] = $numItems;
        $response = $this->_client->sendRequest('Text', 'search', $params);
        $tmp_import_folder = APPLICATION_PATH . DIRECTORY_SEPARATOR . $this->_tmp_import_folder;
        if(!is_dir($tmp_import_folder)) {
            mkdir($tmp_import_folder);
        }
        foreach ($response->result as $item) {
            file_put_contents($tmp_import_folder . DIRECTORY_SEPARATOR . $item->id_media_object, print_r($item, true));
        }
        if (count($response->result) >= $numItems && $startIndex < $response->count) {
            $nextStartIndex = $startIndex + $numItems;
            $this->_importIds($nextStartIndex, $params, $numItems);
        }
    }

    /**
     * @throws Exception
     */
    private function _importMediaObjectsFromFolder()
    {
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectsFromFolder()', Writer::OUTPUT_FILE, 'import.log');
        $dir = new DirectoryIterator(APPLICATION_PATH . DIRECTORY_SEPARATOR . $this->_tmp_import_folder);
        foreach ($dir as $file_info) {
            if (!$file_info->isDot()) {
                $id_media_object = $file_info->getFilename();
                if ($this->importMediaObject($id_media_object)) {
                    unlink($file_info->getPathname());
                    $this->_imported_ids[] = $id_media_object;
                }
            }
        }

        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . 'Fullimport finished', Writer::OUTPUT_BOTH, 'import.log');
    }

    /**
     * @throws Exception
     */
    public function removeOrphans()
    {
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Finding and removing Orphans', Writer::OUTPUT_BOTH, 'import.log');
        $conf = Registry::getInstance()->get('config');
        $allowed_object_types = array_keys($conf['data']['media_types']);
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::removeOrphans()', Writer::OUTPUT_FILE, 'import.log');
        $params = [
            'id_media_object_type' => implode(',', $allowed_object_types)
        ];
        $this->_importIds(0, $params);
        $dir = new DirectoryIterator(APPLICATION_PATH . DIRECTORY_SEPARATOR . $this->_tmp_import_folder);
        foreach ($dir as $file_info) {
            if (!$file_info->isDot()) {
                $id_media_object = $file_info->getFilename();
                unlink($file_info->getPathname());
                $this->_imported_ids[] = $id_media_object;
            }
        }
        $this->_findAndRemoveOrphans();
    }

    /**
     * @throws Exception
     */
    private function _findAndRemoveOrphans()
    {
        /** @var Pdo $db */
        $db = Registry::getInstance()->get('db');
        $existing_media_objects = $db->fetchAll("SELECT id FROM pmt2core_media_objects");
        foreach($existing_media_objects as $media_object) {
            if(!in_array($media_object->id, $this->_imported_ids)) {
                $media_object_to_remove = new MediaObject($media_object->id);
                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Found Orphan: ' . $media_object->id . ' -> deleting ...', Writer::OUTPUT_BOTH, 'import.log');
                try {
                    $media_object_to_remove->delete(true);
                    $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Orphan: ' . $media_object->id . ' deleted', Writer::OUTPUT_BOTH, 'import.log');
                } catch (Exception $e) {
                    $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Deletion of Orphan ' . $media_object->id . ' failed: ' . $e->getMessage(), Writer::OUTPUT_FILE, 'import_error.log');
                    $this->_errors[] = 'Deletion of Orphan ' . $media_object->id . '): failed: ' . $e->getMessage();
                }
            }
        }
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Finding and removing Orphans done', Writer::OUTPUT_BOTH, 'import.log');
    }

    /**
     * @param $media_object_ids
     * @throws Exception
     */
    public function importMediaObjectsFromArray($media_object_ids, $import_linked_objects = true)
    {
        foreach ($media_object_ids as $media_object_id) {
            $this->importMediaObject($media_object_id, $import_linked_objects);
        }
    }

    /**
     * @param int $id_media_object
     * @return bool
     * @throws Exception
     */
    public function importMediaObject($id_media_object, $import_linked_objects = true)
    {
        $id_media_object = intval($id_media_object);
        $db = Registry::getInstance()->get('db');
        $this->_start_time = microtime(true);
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . '--------------------------------------------------------------------------------', Writer::OUTPUT_FILE, 'import.log');
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObject(' . $id_media_object . ')', Writer::OUTPUT_FILE, 'import.log');
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObject(' . $id_media_object . '): REST Request started', Writer::OUTPUT_BOTH, 'import.log');
        try {
            $response = $this->_client->sendRequest('Text', 'getById', ['ids' => $id_media_object, 'withTouristicData' => 1, 'withDynamicData' => 1]);
        } catch (Exception $e) {
            $response = null;
        }
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObject(' . $id_media_object . '): REST Request done', Writer::OUTPUT_BOTH, 'import.log');
        $import_error = false;
        if (is_array($response) && count($response) > 0) {
            $this->_start_time = microtime(true);
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObject(' . $id_media_object . '): parsing data', Writer::OUTPUT_FILE, 'import.log');
            if (is_a($response[0]->touristic, 'stdClass')) {
                $starting_point_ids = $this->_importMediaObjectTouristicData($response[0]->touristic, $id_media_object);
            }
            if (is_array($response[0]->data)) {
                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Deleting media_object_files', Writer::OUTPUT_FILE, 'import.log');
                $db->delete('pmt2core_media_object_files', ['id_media_object = ?', $id_media_object]);

                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Deleting media_object_geodata', Writer::OUTPUT_FILE, 'import.log');
                $db->delete('pmt2core_media_object_geodata', ['id_media_object = ?', $id_media_object]);

                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Deleting media_object_images', Writer::OUTPUT_FILE, 'import.log');
                $db->delete('pmt2core_media_object_images', ['id_media_object = ?', $id_media_object]);

                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Deleting media_object_image_derivatives', Writer::OUTPUT_FILE, 'import.log');
                $db->delete('pmt2core_media_object_image_derivatives', ['id_media_object = ?', $id_media_object]);

                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Deleting media_object_image_sections', Writer::OUTPUT_FILE, 'import.log');
                $db->delete('pmt2core_media_object_image_sections', ['id_media_object = ?', $id_media_object]);

                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Deleting media_object_links', Writer::OUTPUT_FILE, 'import.log');
                $db->delete('pmt2core_media_object_links', ['id_media_object = ?', $id_media_object]);

                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Deleting media_object_object_links', Writer::OUTPUT_FILE, 'import.log');
                $db->delete('pmt2core_media_object_object_links', ['id_media_object = ?', $id_media_object]);

                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Deleting media_object_tables', Writer::OUTPUT_FILE, 'import.log');
                $db->delete('pmt2core_media_object_tables', ['id_media_object = ?', $id_media_object]);

                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Deleting media_object_key_value', Writer::OUTPUT_FILE, 'import.log');
                $db->delete('pmt2core_media_object_key_value', ['id_media_object = ?', $id_media_object]);

                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Deleting media_object_files', Writer::OUTPUT_FILE, 'import.log');
                $db->delete('pmt2core_media_object_files', ['id_media_object = ?', $id_media_object]);

                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Deleting media_object_tree_items', Writer::OUTPUT_FILE, 'import.log');
                $db->delete('pmt2core_media_object_tree_items', ['id_media_object = ?', $id_media_object]);

                $category_tree_ids = $this->_importMediaObjectData($response[0], $id_media_object, $import_linked_objects);
            }
            if(!empty($starting_point_ids)) {
                $this->_importMediaObjectTouristicStartingPointOptions($starting_point_ids);
            }
            if(!empty($category_tree_ids)) {
                $this->_importCategoryTrees($category_tree_ids);
            }
            $media_object = new ORM\Object\MediaObject();
            $media_object->id = $response[0]->id_media_object;
            $media_object->id_pool = $response[0]->id_pool;
            $media_object->id_object_type = $response[0]->id_media_objects_data_type;
            $media_object->id_client = 348;
            $media_object->name = $response[0]->name;
            $media_object->code = $response[0]->code;
            $media_object->tags = $response[0]->tags;
            $media_object->visibility = $response[0]->visibility;
            $media_object->state = $response[0]->state;
            $media_object->valid_from = $response[0]->valid_from;
            $media_object->valid_to = $response[0]->valid_to;
            try {
                $old_media_object = new MediaObject();
                $old_media_object->read($response[0]->id_media_object);
                $old_media_object->delete();
                unset($old_media_object);
            } catch (Exception $e) {
                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Deleting old object failed', Writer::OUTPUT_FILE, 'import.log');
            }
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Creating media object', Writer::OUTPUT_FILE, 'import.log');
            try {
                $media_object->create();
            } catch (Exception $e) {
                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Creating media object failed: ' . $e->getMessage(), Writer::OUTPUT_FILE, 'import_error.log');
                $this->_errors[] = 'Importer::importMediaObject(' . $id_media_object . '):  Creating media object failed: ' . $e->getMessage();
            }
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Deleting Route entries', Writer::OUTPUT_FILE, 'import.log');
            $db->delete('pmt2core_routes', ['id_media_object = ?', $media_object->getId()]);
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Inserting Route entries', Writer::OUTPUT_FILE, 'import.log');
            $media_object->setReadRelations(true);
            $media_object->readRelations();
            try {
                $urls = $media_object->buildPrettyUrls();
                foreach ($urls as $url) {
                    $route = new Route();
                    $route->id_media_object = $media_object->getId();
                    $route->id_object_type = $media_object->id_object_type;
                    $route->route = $url;
                    $route->language = 'de';
                    $route->create();
                }
            } catch (Exception $e) {
                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Creating routes failed: ' . $e->getMessage(), Writer::OUTPUT_FILE, 'import_error.log');
                $this->_errors[] = 'Importer::importMediaObject(' . $id_media_object . '):  Creating routes failed: ' . $e->getMessage();
            }
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Routes updated', Writer::OUTPUT_FILE, 'import.log');
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Deleting CheapestPriceSpeed entries', Writer::OUTPUT_FILE, 'import.log');
            /**@var Pdo $db**/
            $db->delete('pmt2core_cheapest_price_speed', ['id_media_object = ?', $media_object->getId()]);
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Inserting CheapestPriceSpeed entries', Writer::OUTPUT_FILE, 'import.log');
            $media_object->insertCheapestPrice();
            $media_object->createSearchIndex();
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  CheapestPriceSpeed table updated', Writer::OUTPUT_FILE, 'import.log');
            if ($import_error == true) {
                $this->_revertCurrentImport();
            }
            $this->_current_touristic_data_to_import = [];
            $this->_current_media_object_data_to_import = [];
            unset($media_object);
            unset($response);
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Objects removed from heap', Writer::OUTPUT_FILE, 'import.log');
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . '--------------------------------------------------------------------------------', Writer::OUTPUT_FILE, 'import.log');
            $overall_time_elapsed = number_format(microtime(true) - $this->_overall_start_time, 4) . ' sec';
            $this->_log[] = Writer::write($overall_time_elapsed, Writer::OUTPUT_FILE, 'import.log');
            return ($import_error == false);
        } else {
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '): RestClient-Request for Media Object ID: ' . $id_media_object . ' failed', Writer::OUTPUT_FILE, 'import_error.log');
            $this->_errors[] = 'Importer::importMediaObject(' . $id_media_object . '): RestClient-Request for Media Object ID: ' . $id_media_object . ' failed';
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . '--------------------------------------------------------------------------------', Writer::OUTPUT_FILE, 'import.log');
        }
        return false;
    }

    /**
     * @param string $touristic_object_name
     * @param integer $id_media_object
     * @throws Exception
     */
    private function _delete_old_touristic_data($touristic_object_name, $id_media_object)
    {
        if(isset($this->_touristic_object_map[$touristic_object_name])) {
            /** @var Pdo $db * */
            $db = Registry::getInstance()->get('db');
            $class_name = '\Pressmind\ORM\Object\Touristic' . $this->_touristic_object_map[$touristic_object_name];
            /**@var AbstractObject $touristic_object * */
            $touristic_object = new $class_name();
            if ($touristic_object->hasProperty('id_media_object')) {
                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_delete_old_touristic_data(' . $touristic_object_name . ', ' . $id_media_object . '): deleting ' . $touristic_object_name . ' data for media_object: ' . $id_media_object, Writer::OUTPUT_FILE, 'import.log');
                $db->delete($touristic_object->getDbTableName(), ['id_media_object = ?', $id_media_object]);
            }
            unset($touristic_object);
        }
    }

    /**
     * @param array $touristic_data
     * @param integer $id_media_object
     * @return array
     * @throws Exception
     */
    private function _importMediaObjectTouristicData($touristic_data, $id_media_object)
    {
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicData(' . $id_media_object . '): parsing touristic data', Writer::OUTPUT_BOTH, 'import.log');
        $this->_current_touristic_data_to_import = [];
        $linked_media_object_ids = [];
        foreach ($touristic_data as $touristic_object_name => $touristic_objects) {
            $this->_delete_old_touristic_data($touristic_object_name, $id_media_object);
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicData(' . $id_media_object . '): Mapping ' . $touristic_object_name, Writer::OUTPUT_FILE, 'import.log');
            if (count($touristic_objects) == 0) {
                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicData(' . $id_media_object . '): ' . $touristic_object_name . ' does not contain any data, skipping.', Writer::OUTPUT_FILE, 'import.log');
            }
            if(isset($this->_touristic_object_map[$touristic_object_name])) {
                foreach ($touristic_objects as $touristic_object) {
                    $class_name = '\Pressmind\ORM\Object\Touristic' . $this->_touristic_object_map[$touristic_object_name];
                    foreach ($touristic_object as $key => $value) {
                        if(isset($this->_touristic_object_field_map[$touristic_object_name][$key])) {
                            $new_key = $this->_touristic_object_field_map[$touristic_object_name][$key];
                            $touristic_object->$new_key = $value;
                            unset($touristic_object->$key);
                        }
                    }
                    try {
                        /**@var AbstractObject $object * */
                        $object = new $class_name();
                        $object->fromStdClass($touristic_object);
                        $this->_current_touristic_data_to_import[] = $object;
                        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicData(' . $id_media_object . '): ' . $class_name . ' mapping successfull.', Writer::OUTPUT_FILE, 'import.log');
                    } catch (Exception $e) {
                        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicData(' . $id_media_object . '): ' . $class_name . ' mapping failed: ' . $e->getMessage(), Writer::OUTPUT_FILE, 'import_error.log');
                        $this->_errors[] = 'Importer::_importMediaObjectTouristicData(' . $id_media_object . '): ' . $class_name . ' mapping failed: ' . $e->getMessage();
                    }
                    if($touristic_object_name == 'touristic_housing_packages_description_links' && $this->_import_type == 'mediaobject') {
                        if(isset($touristic_object->id_media_object) && !empty($touristic_object->id_media_object)) {
                            $linked_media_object_ids[] = $touristic_object->id_media_object;
                        }
                    }
                    unset($object);
                    $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicData(' . $id_media_object . '): Object removed from heap', Writer::OUTPUT_FILE, 'import.log');
                }
            }
        }
        $starting_point_ids = [];
        foreach ($this->_current_touristic_data_to_import as $touristic_object_to_import) {
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicData(' . $id_media_object . '): inserting touristic data for ' . get_class($touristic_object_to_import), Writer::OUTPUT_FILE, 'import.log');
            /**@var AbstractObject $touristic_object_to_import * */
            if(is_a($touristic_object_to_import,'Pressmind\ORM\Object\Touristic\Startingpoint')) {
                $starting_point_ids[] = $touristic_object_to_import->id;
            }
            try {
                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicData(' . $id_media_object . '): deleting old data ' . get_class($touristic_object_to_import), Writer::OUTPUT_FILE, 'import.log');
                /*if($touristic_object_to_import->hasProperty('id_media_object')) {
                    $db->delete($touristic_object_to_import->getDbTableName(), ['id_media_object = ?', $id_media_object]);
                } else {
                    $touristic_object_to_import->delete();
                }*/
                $touristic_object_to_import->delete();
                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicData(' . $id_media_object . '): writing new data ' . get_class($touristic_object_to_import), Writer::OUTPUT_FILE, 'import.log');
                $touristic_object_to_import->create();
                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicData(' . $id_media_object . '): ' . get_class($touristic_object_to_import) . ' created.', Writer::OUTPUT_FILE, 'import.log');
            } catch (Exception $e) {
                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicData(' . $id_media_object . '): ' . get_class($touristic_object_to_import) . ' creation failed: ' . $e->getMessage(), Writer::OUTPUT_FILE, 'import_error.log');
                $this->_errors[] = 'Importer::_importMediaObjectTouristicData(' . $id_media_object . '): ' . get_class($touristic_object_to_import) . ' creation failed: ' . $e->getMessage();
            }
            unset($touristic_object_to_import);
            unset($touristic_data);
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicData(' . $id_media_object . '): Object removed from heap', Writer::OUTPUT_FILE, 'import.log');
        }
        if(count($linked_media_object_ids) > 0) {
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicData(' . $id_media_object . '): Linked media objects found, starting to import linked objects', Writer::OUTPUT_BOTH, 'import.log');
            $this->importMediaObjectsFromArray($linked_media_object_ids);
        }
        return $starting_point_ids;
    }

    /**
     * @param array $startingpointIds
     * @throws Exception
     */
    private function _importMediaObjectTouristicStartingPointOptions($startingpointIds) {
        $this->_start_time = microtime(true);
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicStartingPointOptions(' . implode(',', $startingpointIds) . '): REST request started', Writer::OUTPUT_BOTH, 'import.log');
        $response = $this->_client->sendRequest('StartingPoint', 'getById', ['ids' => implode(',', $startingpointIds)]);
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicStartingPointOptions(' . implode(',', $startingpointIds) . '): REST request done', Writer::OUTPUT_BOTH, 'import.log');
        if (is_a($response, 'stdClass') && isset($response->result) && is_array($response->result)) {
            foreach ($response->result as $result) {
                if(is_a($result, 'stdClass') && isset($result->options) && is_array($result->options)) {
                    $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicStartingPointOptions(' . implode(',', $startingpointIds) . '): writing data', Writer::OUTPUT_FILE, 'import.log');
                    foreach ($result->options as $option) {
                        $starting_point_option = new Option();
                        unset($option->zip_ranges);
                        $starting_point_option->fromStdClass($option);
                        $starting_point_option->id_startingpoint = $result->id;
                        try {
                            $starting_point_option->create();
                        } catch (Exception $e) {
                            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicStartingPointOptions(' . implode(',', $startingpointIds) . '): Error writing starting point option with ID ' . $starting_point_option->getId() . ': '. $e->getMessage(), Writer::OUTPUT_FILE, 'import_error.log');
                            $this->_errors[] = 'Importer::_importMediaObjectTouristicStartingPointOptions(' . implode(',', $startingpointIds) . '): Error writing starting point option with ID ' . $starting_point_option->getId() . ': '. $e->getMessage();
                        }
                        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicStartingPointOptions(' . implode(',', $startingpointIds) . '): Starting point option with ID ' . $starting_point_option->getId() . ' written', Writer::OUTPUT_FILE, 'import.log');
                        unset($starting_point_option);
                        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicStartingPointOptions(' . implode(',', $startingpointIds) . '): Object removed from heap', Writer::OUTPUT_FILE, 'import.log');
                    }
                }
            }
        }
        unset($response);
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectTouristicStartingPointOptions(' . implode(',', $startingpointIds) . '): Import finished', Writer::OUTPUT_BOTH, 'import.log');
    }

    /**
     * @param $ids
     * @throws Exception
     */
    private function _importCategoryTrees($ids)
    {
        try {
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importCategoryTrees(): REST request started', Writer::OUTPUT_BOTH, 'import.log');
            $response = $this->_client->sendRequest('Category', 'getById', ['ids' => implode(',', $ids)]);
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importCategoryTrees(): REST request done', Writer::OUTPUT_BOTH, 'import.log');
            $this->_checkApiResponse($response);
            if (is_a($response, 'stdClass') && isset($response->result) && is_array($response->result)) {
                foreach ($response->result as $result) {
                    if (is_a($result, 'stdClass') && isset($result->tree) && !empty($result->tree)) {
                        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importCategoryTrees(): Importing tree ID ' . $result->id , Writer::OUTPUT_FILE, 'import.log');
                        $tree = new ORM\Object\CategoryTree();
                        $tree->id = $result->id;
                        $tree->name = $result->name;
                        try {
                            $tree->delete();
                            $tree->create();
                        } catch (Exception $e) {
                            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importCategoryTrees(): Error importing tree ID ' . $tree->id . ': ' . $e->getMessage(), Writer::OUTPUT_FILE, 'import_error.log');
                            $this->_errors[] = 'Importer::_importCategoryTrees(): Error importing tree ID ' . $tree->id . ': ' . $e->getMessage();
                        }
                        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importCategoryTrees(): Tree import done ', Writer::OUTPUT_FILE, 'import.log');
                        if(isset($result->tree->item)) {
                            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importCategoryTrees(): Importing tree items ' . $result->id , Writer::OUTPUT_FILE, 'import.log');
                            $this->_iterateCategoryTreeItems($result->id, $result->tree->item);
                        }
                        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importCategoryTrees(): Importing tree items done', Writer::OUTPUT_FILE, 'import.log');
                    }
                }
            }
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importCategoryTrees(): Import finished', Writer::OUTPUT_BOTH, 'import.log');
        } catch (Exception $e) {
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importCategoryTrees(): Import Error: ' . $e->getMessage(), Writer::OUTPUT_FILE, 'import_error.log');
            $this->_errors[] = 'Importer::_importCategoryTrees(): Import Error: ' . $e->getMessage();
        }
    }

    /**
     * @param $id_tree
     * @param $items
     * @param null $parent
     * @throws Exception
     */
    private function _iterateCategoryTreeItems($id_tree, $items, $parent = null) {
        $sort = 0;
        foreach ($items as $item) {
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_iterateCategoryTreeItems(): Importing tree item ID ' . $item->id , Writer::OUTPUT_FILE, 'import.log');
            $sort++;
            $category_tree_item = new Item();
            $category_tree_item->id = $item->id;
            $category_tree_item->name = $item->name;
            $category_tree_item->id_parent = $parent;
            $category_tree_item->id_tree = $id_tree;
            $category_tree_item->code = empty($item->code) ? null : $item->code;
            $category_tree_item->sort = $sort;
            try {
                $category_tree_item->delete();
                $category_tree_item->create();
            } catch (Exception $e) {
                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_iterateCategoryTreeItems(): Error importing tree item ID ' . $item->id . ': '. $e->getMessage(), Writer::OUTPUT_FILE, 'import_error.log');
                $this->_errors[] = 'Importer::_iterateCategoryTreeItems(): Error importing tree item ID ' . $item->id . ': '. $e->getMessage();
            }
            if (isset($item->item)) {
                $this->_iterateCategoryTreeItems($id_tree, $item->item, $item->id);
            }
        }
    }

    /**
     * @param stdClass $media_object_data
     * @param integer $id_media_object
     * @return array
     * @throws Exception
     */
    private function _importMediaObjectData($media_object_data, $id_media_object, $import_linked_objects = true)
    {
        $category_tree_ids = [];
        $conf =  Registry::getInstance()->get('config');
        $default_language = $conf['languages']['default'];
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectData(' . $id_media_object . '): Importing media object data', Writer::OUTPUT_BOTH, 'import.log');
        $this->_current_media_object_data_to_import = [];
        $values = [];
        $ignore = [];
        $linked_media_object_ids = [];
        foreach ($media_object_data->data as $datafield) {
            if (is_array($datafield->sections)) {
                foreach ($datafield->sections as $section) {
                    $var_name = HelperFunctions::human_to_machine($datafield->var_name);
                    if(!in_array($var_name, $ignore)) {
                        $column_name = $var_name . '_' . HelperFunctions::human_to_machine($section->name);
                        $language = empty($section->language) ? $default_language : $section->language;
                        if (!isset($values[$language])) $values[$language] = [];
                        $section_id = $section->id;
                        $value = null;
                        if($datafield->type == 'categorytree' && isset($datafield->value)) {
                            $value = $datafield->value;
                        } else if($datafield->type == 'key_value') {
                            if(!empty($datafield->value)) {
                                $value = [
                                    'columns' => $datafield->columns,
                                    'values' => $datafield->value->$section_id
                                ];
                            }
                        } else if(isset($datafield->value) && isset($datafield->value->$section_id)) {
                            $value = $datafield->value->$section_id;
                        }
                        if($datafield->type == 'objectlink' && $this->_import_type == 'mediaobject' && !is_null($value) && is_a($value,'stdClass') && isset($value->objects) && is_array($value->objects) && $import_linked_objects == true) {
                            foreach ($value->objects as $linked_media_object_id) {
                                $linked_media_object_ids[] = $linked_media_object_id;
                            }
                        }
                        $values[$language]['language'] = $language;
                        $values[$language]['id_media_object'] = $media_object_data->id_media_object;
                        $values[$language][$column_name] = $value;
                    }
                }
            }
            if($datafield->type == 'categorytree' && isset($datafield->value) && isset($datafield->value->id_category)) {
                $category_tree_ids[] = $datafield->value->id_category;
            }
        }
        foreach ($values as $language => $section_data) {
            try {
                $media_object = Factory::createById($media_object_data->id_media_objects_data_type);
                $media_object->fromImport($section_data);
                $media_object->create();
                $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectData(' . $id_media_object . '): Object ' . get_class($media_object) . ' created with ID: ' . $media_object->getId(), Writer::OUTPUT_FILE, 'import.log');
                unset($media_object);
                unset($old_object);
            } catch (Exception $e) {
                $this->_log[] = $e->getMessage();
            }
        }
        unset($values);
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectData(' . $id_media_object . '): Heap cleaned up', Writer::OUTPUT_FILE, 'import.log');
        if(count($linked_media_object_ids) > 0 && $import_linked_objects == true) {
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::_importMediaObjectData(' . $id_media_object . '): Linked media objects found, starting to import linked objects', Writer::OUTPUT_BOTH, 'import.log');
            $this->importMediaObjectsFromArray($linked_media_object_ids, false);
        }
        return $category_tree_ids;
    }

    /**
     * @throws Exception
     */
    public function postImport()
    {
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::postImport(): Starting post import processes ', Writer::OUTPUT_FILE, 'import.log');

        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::postImport(): bash -c "exec nohup php ' . APPLICATION_PATH . '/cli/image_processor.php > /dev/null 2>&1 &"', Writer::OUTPUT_FILE, 'import.log');
        exec('bash -c "exec nohup php ' . APPLICATION_PATH . '/cli/image_processor.php > /dev/null 2>&1 &"');

        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::postImport(): bash -c "exec nohup php ' . APPLICATION_PATH . '/cli/file_downloader.php > /dev/null 2>&1 &"', Writer::OUTPUT_FILE, 'import.log');
        exec('bash -c "exec nohup php ' . APPLICATION_PATH . '/cli/file_downloader.php > /dev/null 2>&1 &"');
    }

    /**
     * Additional method to do post import image process for one mediaObject
     * 20200624 <mb@lbrmedia.de>.
     * @param int $id_media_object
     * @throws Exception
     */
    public function postImportImageProcessor($id_media_object)
    {
        $config = Registry::getInstance()->get('config');

        $images_save_path = BASE_PATH . DIRECTORY_SEPARATOR . $config['imageprocessor']['image_file_path'];

        if(!is_dir($images_save_path)) {
            mkdir($images_save_path, 0777, true);
        }

        Writer::write('Image processor started', WRITER::OUTPUT_FILE, 'image_processor.log');

        try {
            /** @var Picture[] $result */
            $result = Picture::listAll(array('path' => 'IS NULL', 'id_media_object' => (int)$id_media_object));
        } catch (Exception $e) {
            Writer::write($e->getMessage(), WRITER::OUTPUT_FILE, 'image_processor_error.log');
        }

        Writer::write('Processing ' . count($result) . ' images', WRITER::OUTPUT_FILE, 'image_processor.log');

        foreach ($result as $image) {
            Writer::write('Processing image ID:' . $image->getId(), WRITER::OUTPUT_FILE, 'image_processor.log');
            Writer::write('Downloading image from ' . $image->tmp_url, WRITER::OUTPUT_FILE, 'image_processor.log');
            try {
                $image->downloadOriginal($images_save_path);
            } catch (Exception $e) {
                Writer::write($e->getMessage(), WRITER::OUTPUT_FILE, 'image_processor_error.log');
                continue;
            }
            $imageProcessor = \Pressmind\Image\Processor\Adapter\Factory::create($config['imageprocessor']['adapter']);
            Writer::write('Creating derivatives', WRITER::OUTPUT_FILE, 'image_processor.log');
            foreach ($config['imageprocessor']['derivatives'] as $derivative_name => $derivative_config) {
                try {
                    $processor_config = Config::create($derivative_name, $derivative_config);
                    $image->createDerivative($processor_config, $imageProcessor);
                    Writer::write('Processing sections', WRITER::OUTPUT_FILE, 'image_processor.log');
                } catch (Exception $e) {
                    Writer::write($e->getMessage(), WRITER::OUTPUT_FILE, 'image_processor_error.log');
                    continue;
                }
                foreach ($image->sections as $section) {
                    Writer::write('Downloading section image from ' . $section->tmp_url, WRITER::OUTPUT_FILE, 'image_processor.log');
                    try {
                        $section->downloadOriginal($images_save_path);
                        Writer::write('Creating section image derivatives', WRITER::OUTPUT_FILE, 'image_processor.log');
                        $section->createDerivative($processor_config, $imageProcessor);
                    } catch (Exception $e) {
                        Writer::write($e->getMessage(), WRITER::OUTPUT_FILE, 'image_processor_error.log');
                        continue;
                    }
                }
            }
        }
        Writer::write('Image processor finished', WRITER::OUTPUT_FILE, 'image_processor.log');
    }

    /**
     * @param $ids
     * @throws Exception
     */
    public function importMediaObjectTypes($ids)
    {
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObjectTypes(' . implode(',' ,$ids) . '): Starting import', Writer::OUTPUT_FILE, 'import.log');
        $response = $this->_client->sendRequest('ObjectType', 'getById', ['ids' => implode(',', $ids)]);
        $this->_checkApiResponse($response);
        foreach ($response->result as $result) {
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObjectTypes(' . implode(',' ,$ids) . '): Starting scaffolding for ID: ' . $result->id, Writer::OUTPUT_FILE, 'import.log');
            $table_name = $result->id;
            $scaffolder = new ObjectTypeScaffolder($result, $table_name);
            $scaffolder->parse();
            if($scaffolder->hasErrors()) {
                echo ("WARNING: Importer::importMediaObjectTypes(" . implode(',' ,$ids) . ") threw errors:\n" . implode("\n", $scaffolder->getErrors())) . "\nSEE " . Writer::getLogFilePath() . DIRECTORY_SEPARATOR . "scaffolder_errors.log for details\n";
            }
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObjectTypes(' . implode(',' ,$ids) . '): Sacfolding for ID: ' . $result->id . ' finished', Writer::OUTPUT_FILE, 'import.log');
        }
    }

    private function _revertCurrentImport()
    {

    }

    /**
     * @return array
     */
    public function getLog()
    {
        return $this->_log;
    }

    public function hasErrors()
    {
        return count($this->_errors) > 0;
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * @return string
     */
    private function _getElapsedTimeAndHeap()
    {
        $text = number_format(microtime(true) - $this->_start_time, 4) . ' sec | Heap: ';
        $text .= bcdiv(memory_get_usage(), (1000 * 1000), 2) . ' MByte';
        return $text;
    }

    /**
     * @param $pResponse
     * @return bool
     * @throws Exception
     */
    private function _checkApiResponse($pResponse)
    {
        $error_msg = '';
        if (is_a($pResponse, 'stdClass') && isset($pResponse->result) && is_array($pResponse->result) && isset($pResponse->error) && $pResponse->error == false) {
            return true;
        }
        if(!isset($pResponse->result) || !isset($pResponse->error) || !isset($pResponse->msg) || !is_a($pResponse, 'stdClass')) {
            $error_msg = 'API response is not well formatted.';
        }
        if($pResponse->error == true) {
            $error_msg = $pResponse->msg;
        }
        throw new Exception($error_msg);
    }
}
