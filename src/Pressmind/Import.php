<?php

namespace Pressmind;

use Pressmind\DB\Adapter\Pdo;
use Pressmind\Import\Brand;
use Pressmind\Import\CategoryTree;
use Pressmind\Import\ImportInterface;
use Pressmind\Import\Itinerary;
use Pressmind\Import\MediaObjectData;
use Pressmind\Import\MediaObjectType;
use Pressmind\Import\MyContent;
use Pressmind\Import\Season;
use Pressmind\Import\StartingPointOptions;
use Pressmind\Import\TouristicData;
use Pressmind\Log\Writer;
use Pressmind\ORM\Object\MediaObject;
use Pressmind\REST\Client;
use \DirectoryIterator;
use \Exception;

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

    private $_import_type = null;

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
                if ($this->importMediaObject($id_media_object, false)) {
                    unlink($file_info->getPathname());
                    $this->_imported_ids[] = $id_media_object;
                }
            }
        }

        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . 'Fullimport finished', Writer::OUTPUT_BOTH, 'import.log');
    }

    /**
     * @param $media_object_ids
     * @param $import_linked_objects
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
     * @param $import_linked_objects
     * @return bool
     * @throws Exception
     */
    public function importMediaObject($id_media_object, $import_linked_objects = true)
    {
        $id_media_object = intval($id_media_object);

        $config = Registry::getInstance()->get('config');
        $this->_start_time = microtime(true);
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . '--------------------------------------------------------------------------------', Writer::OUTPUT_BOTH, 'import.log');
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . ')', Writer::OUTPUT_FILE, 'import.log');
        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '): REST Request started', Writer::OUTPUT_BOTH, 'import.log');

        try {
            $touristicOrigins = isset($config['data']['touristic']['origins']) && !empty($config['data']['touristic']['origins']) ? $config['data']['touristic']['origins'] : [0];
            $response = $this->_client->sendRequest('Text', 'getById', ['ids' => $id_media_object, 'withTouristicData' => 1, 'withDynamicData' => 1, 'byTouristicOrigin' => implode(',', $touristicOrigins)]);
        } catch (Exception $e) {
            $response = null;
        }

        $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '): REST Request done', Writer::OUTPUT_BOTH, 'import.log');

        $import_error = false;

        if (is_array($response) && count($response) > 0) {

            $old_object = null;
            $current_object = new ORM\Object\MediaObject();
            $current_object->setReadRelations(true);
            if($current_object->read($id_media_object)) {
                $old_object = clone $current_object;
                $current_object->delete(true);
            }

            $this->_start_time = microtime(true);
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '): parsing data', Writer::OUTPUT_FILE, 'import.log');

            if (is_a($response[0]->touristic, 'stdClass')) {
                $touristic_data_importer = new TouristicData();
                $touristic_data_importer_result = $touristic_data_importer->import($response[0]->touristic, $id_media_object, $this->_import_type);
                $touristic_linked_media_object_ids = $touristic_data_importer_result['linked_media_object_ids'];
                $starting_point_ids = $touristic_data_importer_result['starting_point_ids'];

                if(is_array($starting_point_ids) && count($starting_point_ids) > 0) {
                    $this->_log[] = ' Importer::_importMediaObjectTouristicData(' . $id_media_object . '): importing starting point options';
                    $starting_point_options_importer = new StartingPointOptions($starting_point_ids);
                    $starting_point_options_importer->import();
                }

                if(is_array($touristic_linked_media_object_ids) && count($touristic_linked_media_object_ids) > 0) {
                    $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '): found linked media objects in touristic data. Importing ...', Writer::OUTPUT_BOTH, 'import.log');
                    $this->importMediaObjectsFromArray($touristic_linked_media_object_ids, false);
                }
            }

            if(is_array($response[0]->my_contents_to_media_object)) {
                $my_content_importer = new MyContent($response[0]->my_contents_to_media_object);
                $my_content_importer->import();
            }

            if (is_array($response[0]->data)) {
                $media_object_data_importer = new MediaObjectData($response[0], $id_media_object, $import_linked_objects);
                $media_object_data_importer_result = $media_object_data_importer->import();

                $linked_media_object_ids = $media_object_data_importer_result['linked_media_object_ids'];
                $category_tree_ids = $media_object_data_importer_result['category_tree_ids'];

                if(is_array($category_tree_ids) && count($category_tree_ids) > 0) {
                    $this->_log[] = ' Importer::_importMediaObjectData(' . $id_media_object . '): Importing Category Trees';
                    $category_tree_importer = new CategoryTree($category_tree_ids);
                    $category_tree_importer->import();
                }

                if(is_array($linked_media_object_ids) && count($linked_media_object_ids) > 0) {
                    $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '): found linked media objects in media object data. Importing ...', Writer::OUTPUT_BOTH, 'import.log');
                    $this->importMediaObjectsFromArray($linked_media_object_ids, false);
                }
            }

            $brands_importer = new Brand();
            $brands_importer->import();

            $seasons_importer = new Season();
            $seasons_importer->import();

            $media_object_importer = new \Pressmind\Import\MediaObject();
            $media_object_importer->import($response[0]);

            $itinerary_importer = new Itinerary($id_media_object);
            $itinerary_importer->import();

            if(isset($config['data']['touristic']['my_content_class_map']) && isset($response[0]->my_contents_to_media_object) && is_array($response[0]->my_contents_to_media_object)) {
                foreach($response[0]->my_contents_to_media_object as $my_content) {
                    if(isset($config['data']['touristic']['my_content_class_map'][$my_content->id_my_content])) {
                        $touristic_class_name = $config['data']['touristic']['my_content_class_map'][$my_content->id_my_content];
                        /** @var ImportInterface $custom_importer */
                        $custom_importer = new $touristic_class_name($my_content, $id_media_object);
                        $custom_importer->import();
                    }
                }
            }

            unset($response);
            unset($old_object);

            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '):  Objects removed from heap', Writer::OUTPUT_BOTH, 'import.log');
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . '--------------------------------------------------------------------------------', Writer::OUTPUT_BOTH, 'import.log');
            $overall_time_elapsed = number_format(microtime(true) - $this->_overall_start_time, 4) . ' sec';
            $this->_log[] = Writer::write('Total import time: ' . $overall_time_elapsed, Writer::OUTPUT_BOTH, 'import.log');

            return ($import_error == false);
        } else {
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . ' Importer::importMediaObject(' . $id_media_object . '): RestClient-Request for Media Object ID: ' . $id_media_object . ' failed', Writer::OUTPUT_FILE, 'import_error.log');
            $this->_errors[] = 'Importer::importMediaObject(' . $id_media_object . '): RestClient-Request for Media Object ID: ' . $id_media_object . ' failed';
            $this->_log[] = Writer::write($this->_getElapsedTimeAndHeap() . '--------------------------------------------------------------------------------', Writer::OUTPUT_FILE, 'import.log');
        }
        return false;
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
        $media_object_type_importer = new MediaObjectType($ids);
        $media_object_type_importer->import();
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
