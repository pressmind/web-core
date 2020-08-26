<?php
namespace Pressmind;

use Exception;
use ImagickException;
use Pressmind\Image\Processor\Adapter\Factory;
use Pressmind\Image\Processor\Config;
use Pressmind\Log\Writer;
use Pressmind\ORM\Object\MediaObject\DataType\Picture;

if(php_sapi_name() == 'cli') {
    putenv('ENV=DEVELOPMENT');
}

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

$args = $argv;
$args[1] = isset($argv[1]) ? $argv[1] : null;

$config = Registry::getInstance()->get('config');

$images_save_path = BASE_PATH . DIRECTORY_SEPARATOR . $config['imageprocessor']['image_file_path'];

if(!is_dir($images_save_path)) {
    mkdir($images_save_path, 0777, true);
}

Writer::write('Image processor started', WRITER::OUTPUT_FILE, 'image_processor.log');

try {
    /** @var Picture[] $result */
    $result = Picture::listAll(array('path' => 'IS NULL'));
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
    $imageProcessor = Factory::create($config['imageprocessor']['adapter']);
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
