<?php
namespace Pressmind;
use Exception;
use Pressmind\Log\Writer;
use Pressmind\REST\Client;

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'bootstrap.php';

$args = $argv;
$args[1] = isset($argv[1]) ? $argv[1] : null;

$namespace = 'Pressmind\ORM\Object';

if($args[1] != 'only_static') {
    $models = [
        '\Airline',
        '\Airport',
        '\Bank',
        '\ObjectdataTag',
        '\Geozip',
        '\CategoryTree',
        '\CategoryTree\Item',
        '\CheapestPriceSpeed',
        '\FulltextSearch',
        '\MediaObject',
        '\MediaObject\DataType\Categorytree',
        '\MediaObject\DataType\File',
        '\MediaObject\DataType\Link',
        '\MediaObject\DataType\Location',
        '\MediaObject\DataType\Objectlink',
        '\MediaObject\DataType\Picture',
        '\MediaObject\DataType\Picture\Section',
        '\MediaObject\DataType\Picture\Derivative',
        '\MediaObject\DataType\Table',
        '\MediaObject\DataType\Table\Row',
        '\MediaObject\DataType\Table\Row\Column',
        '\MediaObject\DataType\Key_value',
        '\MediaObject\DataType\Key_value\Row',
        '\MediaObject\DataType\Key_value\Row\Column',
        '\Route',
        '\Search',
        '\Touristic\Booking\Earlybird',
        '\Touristic\Booking\Package',
        '\Touristic\Date',
        '\Touristic\ExtrasToMediaObject',
        '\Touristic\Housing\Package',
        '\Touristic\Housing\Package\DescriptionLink',
        '\Touristic\Insurance',
        '\Touristic\Insurance\Group',
        '\Touristic\Insurance\InsuranceToGroup',
        '\Touristic\Insurance\PriceTable',
        '\Touristic\Option',
        '\Touristic\Option\Description',
        '\Touristic\Pickupservice',
        '\Touristic\Pickupservice\Option',
        '\Touristic\SeasonalPeriod',
        '\Touristic\Startingpoint',
        '\Touristic\Startingpoint\Option',
        '\Touristic\Startingpoint\Option\ZipRange',
        '\Touristic\Transport',
        '\Itinerary\Variant',
        '\Itinerary\Variant\Step',
        '\Itinerary\Variant\Step\Geopoint',
        '\Itinerary\Variant\Step\DocumentMediaObject',
        '\Itinerary\Variant\Step\DocumentMediaObject\Derivative',
        '\Itinerary\Variant\Step\Board',
        '\Itinerary\Variant\Step\Section',
        '\Itinerary\Variant\Step\Section\Content'
    ];

    foreach ($models as $model) {
        try {
            /** @var ORM\Object\AbstractObject $model_name */
            $model_name = $namespace . $model;
            Writer::write('Creating database table for model: ' . $model_name, Writer::OUTPUT_BOTH, 'install.log');
            $scaffolder = new DB\Scaffolder\Mysql(new $model_name());
            $scaffolder->run($args[1] === 'drop_tables');
            foreach ($scaffolder->getLog() as $scaffolder_log) {
                Writer::write($scaffolder_log, Writer::OUTPUT_FILE, 'install.log');
            }
        } catch (Exception $e) {
            Writer::write($model_name, Writer::OUTPUT_BOTH, 'install_errors.log');
            Writer::write($e->getMessage(), Writer::OUTPUT_BOTH, 'install_errors.log');
        }
    }

    try {
        Writer::write('Requesting and parsing information on media object types ...', Writer::OUTPUT_BOTH, 'install.log');
        $importer = new Import();
        $ids = [];
        $client = new Client();
        $response = $client->sendRequest('ObjectType', 'getAll');
        $config = Registry::getInstance()->get('config');
        $media_types = [];
        $media_types_pretty_url = [];
        foreach ($response->result as $item) {
            $media_types[$item->id_type] = $item->type_name;
            $ids[] = $item->id_type;
            $pretty_url = [
                'prefix' => '/' . HelperFunctions::human_to_machine($item->type_name) . '/',
                'field' => ['name' => 'name'],
                'strategy' => 'none',
                'suffix' => '/'
            ];
            $media_types_pretty_url[$item->id_type] = $pretty_url;
        }
        $config['data']['media_types'] = $media_types;
        $config['data']['media_types_pretty_url'] = $media_types_pretty_url;
        Registry::getInstance()->get('config_adapter')->write($config);
        Registry::getInstance()->add('config', $config);
        $importer->importMediaObjectTypes($ids);
    } catch (Exception $e) {
        Writer::write($e->getMessage(), Writer::OUTPUT_BOTH, 'install_errors.log');
    }
}

if($args[1] == 'with_static' || $args[1] == 'only_static') {
    try {
        Writer::write('Dumping static data, this may take a while ...', Writer::OUTPUT_BOTH, 'install.log');
        Writer::write('Data will be dumped using "gunzip" and "mysql" with "shell_exec". Dump data in ' . HelperFunctions::buildPathString([dirname(__DIR__), 'src', 'data']) . ' by hand if shell_exec fails', Writer::OUTPUT_BOTH, 'install.log');
        $config = Registry::getInstance()->get('config');
        Writer::write('Dumping data for pmt2core_airlines', Writer::OUTPUT_BOTH, 'install.log');
        shell_exec("gunzip < " . HelperFunctions::buildPathString([dirname(__DIR__), 'src', 'data', 'pmt2core_airlines.sql.gz']) . " | mysql --host=" . $config['database']['host'] . " --user=" . $config['database']['username'] . " --password=" . $config['database']['password'] . " " . $config['database']['dbname']);
        Writer::write('Dumping data for pmt2core_airports', Writer::OUTPUT_BOTH, 'install.log');
        shell_exec("gunzip < " . HelperFunctions::buildPathString([dirname(__DIR__), 'src', 'data', 'pmt2core_airports.sql.gz']) . " | mysql --host=" . $config['database']['host'] . " --user=" . $config['database']['username'] . " --password=" . $config['database']['password'] . " " . $config['database']['dbname']);
        Writer::write('Dumping data for pmt2core_banks', Writer::OUTPUT_BOTH, 'install.log');
        shell_exec("gunzip < " . HelperFunctions::buildPathString([dirname(__DIR__), 'src', 'data', 'pmt2core_banks.sql.gz']) . " | mysql --host=" . $config['database']['host'] . " --user=" . $config['database']['username'] . " --password=" . $config['database']['password'] . " " . $config['database']['dbname']);
        Writer::write('Dumping data for pmt2core_geozip', Writer::OUTPUT_BOTH, 'install.log');
        shell_exec("gunzip < " . HelperFunctions::buildPathString([dirname(__DIR__), 'src', 'data', 'pmt2core_geozip.sql.gz']) . " | mysql --host=" . $config['database']['host'] . " --user=" . $config['database']['username'] . " --password=" . $config['database']['password'] . " " . $config['database']['dbname']);
    } catch (Exception $e) {
        Writer::write($e->getMessage(), Writer::OUTPUT_BOTH, 'install_errors.log');
    }
} else {
    Writer::write('Necessary static data has not been dumped. Dump static data by calling "install.php with_static" or "install.php only_static"', Writer::OUTPUT_BOTH, 'install.log');
    Writer::write('You can also dump the data by hand. Data resides here: ' . HelperFunctions::buildPathString([dirname(__DIR__), 'src', 'data']), Writer::OUTPUT_BOTH, 'install.log');
}
