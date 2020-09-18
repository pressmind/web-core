# pressmind® web-core SDK

##  System Requirements
* PHP 7.*
* MySQL or MariaDB
* PHP-Extensions:
    * ext-imagick or ext-gd
    * ext-json
    * ext-curl
    * ext-bcmath
    * ext-pdo
* a pressmind® License ;-)

### pressmind® API Credentials
You need a pressmind® REST API Access. (API Key, User, Password)
Ask your pressmind® Integration-Manager.

## Quickstart

### 1. Installation
* clone the repository 

```shell script
git clone https://github.com/pressmind/web-core.git
```
* create a MySQL database

```cli script
mysql -u root -p;
mysql> CREATE DATABASE pressmind;
mysql> GRANT ALL ON pressmind.* TO 'my_database_user'@'localhost' IDENTIFIED BY 'my_database_password' WITH GRANT OPTION;
```

* edit the configuration file config.json
* Insert your database information under development.database
```json
//... SNIP config.json
{ 
    "development": {
        "database": {
            "username": "yourusername",
            "password": "yourpassword",
            "host": "localhost",
            "dbname": "yourdatabasename",
            "engine": "Mysql"
        }
    }
}
//... SNAP
```
* Insert your pressmind API credentials under development.rest.client (credentials are provided by pressmind)
```json
//... SNIP config.json
{
    "development": {
        "rest": {
            "client": {
                "api_endpoint": "https://api.pm-t2.com/rest/",
                "api_key": "yourapikey",
                "api_user": "yourapisuername",
                "api_password": "yourapipassword"
            }
        }
    }
}
//... SNAP
```
* save the config.json file
### Security Notice
Please be aware, that it is NOT a good idea to leave the config file under the document root of your webserver when going into production. 
Best practice would be to have all files outside the document root, except of the assets folder for it holds all images and other files as well as an index.php which will include the bootstrap.php.

* on a console move to folder cli and execute install.php
```shell script
# Install
your-project-folder/cli$ php install.php
```
This will install the necessary database tables and generate the needed model-definitions for the media object types.  
Additionally some basic example php files that show the use of Views are generated in the folder examples/views as well as some html files with information on the installed media object types. You can find these under docs/objecttypes 
### 2. Import from pressmind®
To import data from pressmind run the script cli/import.php  
To do a fullimport (which is recommended after a fresh install add the argument fullimport)
```shell script
# Full Import
your-project-folder/cli$ php import.php fullimport
```
Depending on the amount of data that is stored in pressmind, the fullimport can last a while.  
For each media object all descriptive and touristic data will be imported into the database. Additionally all related files and images will be downloaded to the folder /assets.
### 3. Search and Display Data
After the install.php script has been executed, some example files can be found in the examples folder:
The index.php file demonstrates a simple search and will display a list of found data-sets with a link to the detail.php which demonstrates how a media_object can be rendered.  
The detail.php will render the information based on the view scripts that can be found in the examples/views folder.

### Quick Examples
#### Search for media objects
searchMediaObjects.php
```php
<?php
require_once dirname(__DIR__) . '/bootstrap.php';
use Pressmind\Search;

$search = new Search(
    [
        Search\Condition\PriceRange::create(1, 5000),
        Search\Condition\ObjectType::create(169),
        Search\Condition\Category::create('land_default', ['B9063101-0F6A-2322-83A6-FAF7A0D82827']),
        Search\Condition\Text::create(169, 'Riesengebirge', ['headline_default' => 'LIKE']),
        Search\Condition\DateRange::create(new DateTime('2020-06-01'), new DateTime('2020-07-31'))
    ],
    [
        'start' => 0,
        'length' => 100
    ],
    [
        '' => 'RAND()'
    ]
);
$mediaObjects = $search->getResults();

foreach ($mediaObjects as $mediaObject) {
    echo $mediaObject->render('test'); //will use Reise_Test.php as view file (code is shown below)
}
```

#### Build search filters
```php
<?php
namespace Pressmind;

require_once dirname(__DIR__) . '/bootstrap.php';

$search = new Search(
    [
        Search\Condition\Category::create('zielgebiet_default', ['304E15ED-302F-CD33-9153-14B8C6F955BD', '4C5833CB-F29A-A0F4-5A10-14B762FB4019', '78321653-CF81-2EF1-ED02-9D07E01651C1']),
        Search\Condition\PriceRange::create(100, 3000),
        Search\Condition\DurationRange::create(0, 30),
        Search\Condition\Visibility::create([10,30])
    ]
);

$category_filter = new Search\Filter\Category('1207', $search);
foreach ($category_filter->getResult() as $id => $tree_item) {
    echo  '<pre>' . $id . ': ' . $tree_item->name . '</pre>';
}
$price_range_filter = new Search\Filter\PriceRange($search);
echo '<pre>' . print_r($price_range_filter->getResult(), true) . '</pre>';

$duration_filter = new Search\Filter\Duration($search);
echo '<pre>' . print_r($duration_filter->getResult(), true) . '</pre>';

foreach ($search->getResults(true) as $result) {
    $cheapest_price = $result->getCheapestPrice();
    echo '<a href="/examples/detail.php?id=' . $result->getId() . '">' . $result->name . '(' . $cheapest_price->price_total . ' EUR, ' . $cheapest_price->duration . ' Tage)</a><br>';
}
```

#### View script for a media objects
Reise_Test.php (see also the *_Example.php scripts in /examples/views for reference)
```php
<?php
    /**
     * @var array $data
     */
     
    /**
     * @var Custom\MediaType\Reise $reise
     */
    $reise = $data['data'];
    
    /**
     * @var Pressmind\ORM\Object\Touristic\Booking\Package[] $booking_packages
     */
    $booking_packages = $data['booking_packages'];


    /**
     * @var Pressmind\ORM\Object\MediaObject $media_object
     */
    $media_object = $data['media_object'];

    echo "-\r\n";
    echo $reise->id_media_object."\r\n";
    echo $media_object->name."\r\n";
    foreach($reise->land_default as $land_default_item) {
        echo $land_default_item->item->name."\r\n";
    }

    foreach ($booking_packages as $booking_package){
        echo $booking_package->duration." Tage \r\n";
        echo "id_booking_package: ".$booking_package->id."\r\n";
        foreach($booking_package->dates as $date){
            echo $date->departure->format('d.m.Y') .' - '.$date->arrival->format('d.m.Y')."\r\n";
        }

        foreach ($booking_package->housing_packages as $housing_package){
            echo 'HousingPackage: '.$housing_package->name."\r\n";
            echo 'Nights: '.$housing_package->nights."\r\n";

            foreach ($housing_package->options as $option){
                echo $option->code.' '.$option->name.' '.$option->price."\r\n";
            }
        }


    }
```
#### 2. Get a media object by ID
getById.php
```php
<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use Pressmind\ORM\Object\MediaObject;

// get a specified MediaObject by ID
$mediaObject = new MediaObject(938117);
echo $mediaObject->name;
```
