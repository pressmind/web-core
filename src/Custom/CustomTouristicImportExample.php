<?php


namespace Custom;


use DateTime;
use Exception;
use Pressmind\Import\ImportInterface;
use Pressmind\ORM\Object\MediaObject;
use Pressmind\ORM\Object\Touristic;
use stdClass;

/**
 * Class CustomTouristicImportExample
 * @package Custom
 */
class CustomTouristicImportExample implements ImportInterface
{

    /**
     * @var array
     */
    private $_log = [];

    /**
     * @var array
     */
    private $_errors = [];

    private $_fake_data = '"ABC";"12.12.2020";"16.12.2020";6;"Doppelzimmer";"14.80";"2"
    "ABC";"12.12.2020";"16.12.2020";6;"Einzelzimmer";"16.80";"1"
    "ABC";"12.12.2020";"16.12.2020";6;"Studio";"12.80";"3"
    "DEF";"12.01.2021";"16.01.2021";6;"Doppelzimmer";"20.80";"2"
    "DEF";"12.01.2021";"16.01.2021";6;"Einzelzimmer";"24.80";"1"
    "DEF";"12.01.2021";"16.01.2021";6;"Studio";"18.80";"3"';

    /**
     * @var stdClass
     */
    private $_data;

    /**
     * @var integer
     */
    private $_id_media_object;

    /**
     * @var MediaObject
     */
    private $_media_object;

    /**
     * CustomTouristicImportExample constructor.
     * @param stdClass $data
     * @param integer $id_media_object
     * @throws Exception
     */
    public function __construct($data, $id_media_object)
    {
        $this->_data = $data;
        $this->_id_media_object = $id_media_object;
        //if you need info from the media object for touristic data import you can load it here
        $this->_media_object = new MediaObject($this->_id_media_object, true);
    }

    public function import() {
        $this->_log[] = '\Custom\CustomTouristicImportExample: Starting import of external touristic data for media object ID: ' . $this->_id_media_object;
        $array_of_lines = explode("\n", $this->_fake_data);
        $booking_packages = [];
        $booking_package = null;
        foreach ($array_of_lines as $line) {
            $array = str_getcsv($line, ";");
            $booking_packages[$array[0]][] = $array;
        }
        // ATTENTION be sure that all primary keys for touristic objects are set here, for THERE IS NO AUTOINCREMENT ON PRIMARY KEYS IN THE DATABASE!
        // Primary IDs are of type varchar(32)
        foreach ($booking_packages as $code => $booking_package_data) {
            if($code = $this->_data->import_id) {
                $id = md5($code);
                $date = new Touristic\Date();
                $date->season = $code;
                $date->id = md5($code . 'date');
                $date->id_booking_package = $id;
                $housing_package = new Touristic\Housing\Package();
                $housing_package->id = md5($code . 'housing');
                $housing_package->options = [];
                $housing_package->room_type = 'room';
                $booking_package = new Touristic\Booking\Package();
                $booking_package->id = $id;
                $booking_package->id_origin = $this->_data->id_my_content;
                $booking_package->id_media_object = $this->_data->id_media_object;
                $booking_package->price_mix = 'date_housing';
                $housing_package->id_media_object = $this->_data->id_media_object;
                $housing_package->id_booking_package = $booking_package->id;
                $oi=0;
                foreach ($booking_package_data as $option_info) {
                    $option = new Touristic\Option();
                    $option->id = md5($code . 'option' . $oi);
                    $option->id_booking_package = $booking_package->id;
                    $option->id_housing_package = $housing_package->id;
                    $option->id_media_object = $this->_data->id_media_object;
                    $option->price = $option_info[5];
                    $option->occupancy = $option_info[6];
                    $option->name = $option_info[4];
                    $option->season = $code;
                    $option->type = 'housing_option';
                    $housing_package->options[] = $option;
                    $date->departure = DateTime::createFromFormat('d.m.Y', $option_info[2]);
                    $date->arrival = DateTime::createFromFormat('d.m.Y', $option_info[1]);
                    $booking_package->duration = $option_info[3];
                    $oi++;
                }
                $booking_package->housing_packages = [$housing_package];
                $booking_package->dates = [$date];
            }
        }
        try {
            $booking_package->create();
        } catch (Exception $e) {
            $this->_log[] = '\Custom\CustomTouristicImportExample: Error occurred!';
            $this->_errors[] = $e->getMessage();
        }
        $this->_log[] = '\Custom\CustomTouristicImportExample: Import of external data for media object ID:' . $this->_id_media_object . ' done';
        return $booking_package->toStdClass();
    }

    public function getLog()
    {
        return $this->_log;
    }

    public function getErrors()
    {
        return $this->_errors;
    }
}
