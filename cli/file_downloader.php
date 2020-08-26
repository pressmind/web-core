<?php

use Pressmind\HelperFunctions;
use Pressmind\Log\Writer;
use Pressmind\ORM\Object\MediaObject\DataType\File;
use Pressmind\Registry;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

Writer::write('File downloader started', WRITER::OUTPUT_FILE, 'file_downloader.log');

$db = Registry::getInstance()->get('db');
$config = Registry::getInstance()->get('config');

$result = $db->fetchAll("SELECT * FROM pmt2core_media_object_files WHERE file_path IS NULL");

$file_save_path = HelperFunctions::buildPathString([WEBSERVER_DOCUMENT_ROOT, $config['file_download']['download_file_path']]);

if(!is_dir($file_save_path)) {
    mkdir($file_save_path, 0777, true);
}

Writer::write('Downloading ' . count($result) . ' files', WRITER::OUTPUT_FILE, 'file_downloader.log');

foreach ($result as $file_result) {
    try {
        $file = new File();
        $file->fromStdClass($file_result);
        $download_url = $file->download_url;
        $file_name = $file->file_name;
        Writer::write('Downloading file from ' . $download_url, WRITER::OUTPUT_FILE, 'file_downloader.log');
        $downloader = new \Pressmind\Image\Download('binary');
        $downloader->download($download_url, $file_save_path, $file_name);
        $file->file_path = $file_save_path;
        $file->download_url = $config['file_download']['download_file_path'] . '/' . $file_name;
        Writer::write('Downloaded to ' . $file_save_path . '/' . $file_name, WRITER::OUTPUT_FILE, 'file_downloader.log');
        $file->update();
    } catch (Exception $e) {
        Writer::write($e->getMessage(), WRITER::OUTPUT_FILE, 'file_downloader_error.log');
    }
}
