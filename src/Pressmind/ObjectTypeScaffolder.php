<?php


namespace Pressmind;

use \Exception;
use Pressmind\Log\Writer;
use Pressmind\ORM\Object\ObjectdataTag;
use stdClass;

class ObjectTypeScaffolder
{

    /**
     * @var array
     */
    private $_log = [];

    /**
     * @var array
     */
    private $_errors = [];

    /**
     * @var stdClass
     */
    private $_object_definition;

    /**
     * @var array
     */
    private $_class_definitions;

    /**
     * @var array
     */
    private $_mysql_type_map = [
        'text' => 'longtext',
        'integer' => 'int(11)',
        'int' => 'int(11)',
        'table' => 'relation',
        'date' => 'datetime',
        'plaintext' => 'longtext',
        'wysiwyg' => 'longtext',
        'picture' => 'relation',
        'objectlink' => 'relation',
        'file' => 'relation',
        'categorytree' => 'relation',
        'location' => 'relation',
        'link' => 'relation',
        'key_value' => 'relation',
    ];

    /**
     * @var array
     */
    private $_php_type_map = [
        'integer' => 'integer',
        'int' => 'integer',
        'int(11)' => 'integer',
        'longtext' => 'string',
        'datetime' => 'DateTime',
        'relation' => 'relation'
    ];

    /**
     * @var string
     */
    private $_tablename;

    /**
     * ObjectTypeScaffolder constructor.
     * @param stdClass $pObjectDefinition
     * @param string $pTableName
     */
    public function __construct($pObjectDefinition, $pTableName)
    {
        $this->_object_definition = $pObjectDefinition;
        $this->_tablename = $pTableName;
    }

    /**
     * @throws Exception
     */
    public function parse()
    {
        $conf = Registry::getInstance()->get('config');
        $database_fields = [
            'id bigint(22) not null auto_increment',
            'language varchar(255)',
            'id_media_object bigint(22) not null'
        ];
        $definition_fields = [
            ['id', 'integer', 'integer'],
            ['id_media_object', 'integer', 'integer'],
            ['language', 'longtext', 'string'],
        ];
        $languages = [$conf['languages']['default']];
        unset($this->_object_definition->fields[0]);
        unset($this->_object_definition->fields[1]);
        unset($this->_object_definition->fields[2]);

        foreach($this->_object_definition->fields as $field_definition) {
            if(isset($field_definition->sections) && is_array($field_definition->sections)) {
                foreach($field_definition->sections as $section) {
                    $var_name = HelperFunctions::human_to_machine($field_definition->var_name);
                    if(isset($this->_mysql_type_map[$field_definition->type]) && $this->_mysql_type_map[$field_definition->type] != 'relation') {
                        $database_fields[] = $var_name . '_' . HelperFunctions::human_to_machine($section->name) . ' ' . $this->_mysql_type_map[$field_definition->type];
                    } else if($this->_mysql_type_map[$field_definition->type] == 'relation') {
                        $relation_field_names[] = [$var_name, $field_definition->type];
                    }
                    $definition_fields[] = [$var_name . '_' . HelperFunctions::human_to_machine($section->name), $field_definition->type, $this->_php_type_map[$this->_mysql_type_map[$field_definition->type]]];
                    if(!is_null($section->language) && !in_array($section->language, $languages)) {
                        $languages[] = $section->language;
                    }
                }
            }

        }
        $sql = 'CREATE TABLE IF NOT EXISTS objectdata_' . HelperFunctions::human_to_machine($this->_tablename) . '(' . implode(',', $database_fields) . ', PRIMARY KEY (id), INDEX (language), UNIQUE (id_media_object))';
        $this->generateORMFile($definition_fields);
        $this->_insertDatabaseTable($sql);
        $this->_insertTags();
        $this->generateObjectInformationFile();
        $this->generateExampleViewFile();
        foreach ($this->_log as $log) {
            Writer::write($log, Writer::OUTPUT_FILE, 'scaffolder.log');
        }
        foreach ($this->_errors as $error) {
            Writer::write($error, Writer::OUTPUT_FILE, 'scaffolder_error.log');
        }
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->_errors) > 0;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * @param array $pDefinitionFields
     */
    public function generateORMFile($pDefinitionFields) {
        $definitions = [
            'class' => [
                'name' => $this->_generateClassName($this->_object_definition->name)
            ],
            'database' => [
                'table_name' => 'objectdata_' . HelperFunctions::human_to_machine($this->_tablename),
                'primary_key' => 'id',
                'relation_key' => 'id_media_object'
            ],
            'properties' => []
        ];
        $properties = [];
        $use = '';
        foreach ($pDefinitionFields as $definitionField) {
            if($definitionField[2] == 'DateTime') {
                $use = "\nuse DateTime;";
            }
            $property = [
                'name' => $definitionField[0],
                'title' => $definitionField[0],
                'type' => $definitionField[2] == 'DateTime' ? 'datetime' : $definitionField[2],
                'required' => false,
                'validators' => null,
                'filters' => null
            ];
            if($definitionField[2] == 'relation') {
                $property['relation'] = [
                    'type' => 'hasMany',
                    'class' => '\Pressmind\ORM\Object\MediaObject\DataType\\' . ucfirst($definitionField[1]),
                    'related_id' => 'id_media_object',
                    'filters' => ['var_name' => $definitionField[0]]
                ];
                if(ucfirst($definitionField[1]) == 'Picture') {
                    $property['relation']['filters']['section_name'] = 'IS NULL';
                }
                $properties[] = ' * @property ' . 'DataType\\' . ucfirst($definitionField[1]) . '[] $' . $definitionField[0];
            } else {
                $properties[] = ' * @property ' . $definitionField[2] . ' $' . $definitionField[0];
            }
            $definitions['properties'][$definitionField[0]] = $property;
        }
        $this->_class_definitions = $definitions;
        $text = "<?php\n\nnamespace Custom\MediaType;\n\nuse Custom\AbstractMediaType;\nuse Pressmind\ORM\Object\MediaObject\DataType;" . $use . "\n\n/**\n * Class " . $this->_generateClassName($this->_object_definition->name) . "\n" . implode("\n", $properties) . "\n */\nclass " . $this->_generateClassName($this->_object_definition->name) . " extends AbstractMediaType {\nprotected \$_definitions = " . $this->_var_export($definitions, true) . ';}';
        file_put_contents(BASE_PATH . '/src/Custom/MediaType/' . $this->_generateClassName($this->_object_definition->name) . '.php', $text);
    }

    /**
     * @param $expression
     * @param bool $return
     * @return mixed|string|string[]|null
     */
    private function _var_export($expression, $return = false) {
        $export = var_export($expression, TRUE);
        $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [NULL, ']$1', ' => ['], $array);
        $export = join(PHP_EOL, array_filter(["["] + $array));
        if ((bool)$return) {
            return $export;
        } else  {
            echo $export;
        }
        return null;
    }

    /**
     * @param string $sql
     */
    private function _insertDatabaseTable($pSql)
    {
        /**@var DB\Adapter\AdapterInterface $db*/
        $db = Registry::getInstance()->get('db');
        $database_tables_property_name = 'Tables_in_' . Registry::getInstance()->get('config')['database']['dbname'];
        $exists = false;
        try {
            $tables = $db->fetchAll('Show Tables');
            foreach ($tables as $table) {
                if(isset($table->$database_tables_property_name) && $table->$database_tables_property_name == 'objectdata_' . HelperFunctions::human_to_machine($this->_tablename)) {
                    $exists = true;
                    $integrityCheck = new ObjectIntegrityCheck($this->_object_definition, 'objectdata_' . HelperFunctions::human_to_machine($this->_tablename));
                }
            }
        } catch (Exception $e) {
            $this->_log[] = $e->getMessage();
            $this->_errors[] = $e->getMessage();
        }
        if($exists == false) {
            try {
                $db->execute($pSql);
                $this->_log[] = $pSql;
            } catch (Exception $e) {
                $this->_log[] = 'Database error: ' . $e->getMessage();
                $this->_errors[] = 'Database error: ' . $e->getMessage();
            }
        } else {
            $this->_log[] = 'Database table objectdata_' . HelperFunctions::human_to_machine($this->_tablename) . ' exists';
        }
    }

    public function generateObjectInformationFile()
    {
        $rows = [
            '<tr>
                <th>Section</th>
                <th>Field Name</th>
                <th>Variable Name</th>
                <th>Variable Type</th>
                <th>Property Name</th>
                <th>Tags</th>
            </tr>'
        ];
        foreach ($this->_object_definition->fields as $field) {
            foreach ($field->sections as $section) {
                $tags = [];
                foreach ($section->tags as $tag) {
                    $tags[] = $tag;
                }
                $cols = [];
                $cols[] = $section->name;
                $cols[] = $field->name;
                $cols[] = $field->var_name;
                $cols[] = $field->type;
                $cols[] = $field->var_name . '_' . HelperFunctions::human_to_machine($section->name);
                $cols[] = implode(', ', $tags);
            }
            $rows[] = '<tr><td>' . implode('</td><td>', $cols) . '</td></tr>';
        }
        file_put_contents(BASE_PATH . '/docs/objecttypes/'  . HelperFunctions::human_to_machine($this->_object_definition->name) .  '.html', '<h1>Custom\\MediaType\\' . $this->_generateClassName($this->_object_definition->name) . '</h1><table border="1" cellspacing="0" cellpadding="5">' . implode($rows) . '</table>');
    }

    public function generateExampleViewFile()
    {
        $config = Registry::getInstance()->get('config');

        $property_list = '';

        foreach ($this->_class_definitions['properties'] as $property_name => $property) {
            if($property['type'] == 'relation') {
                if($property['relation']['class'] == '\Pressmind\ORM\Object\MediaObject\DataType\Picture') {
                    $property_list .= "\n<dt>" . $property_name . "</dt>\n<dd>type: " . $property['relation']['class'] . "\n<br>value:<br> \n\t" . '<?php foreach($' . strtolower(HelperFunctions::human_to_machine($this->_object_definition->name)) . '->' . $property_name . ' as $' . $property_name . "_item) {?>\n\t\t<img src=\"<?php echo $" . $property_name . "_item->getUri('thumbnail');?>\" title=\"<?php echo $" . $property_name . "_item->copyright;?>\" alt=\"<?php echo $" . $property_name . "_item->alt;?>\">\n\t\t<pre>\n\t\t\t<?php print_r($" . $property_name . "_item->toStdClass());?>\n\t\t</pre>\n\t<?php }?>\n</dd>";
                } else {
                    $property_list .= "\n<dt>" . $property_name . "</dt>\n<dd>type: " . $property['relation']['class'] . "\n<br>value: \n\t" . '<?php foreach($' . strtolower(HelperFunctions::human_to_machine($this->_object_definition->name)) . '->' . $property_name . ' as $' . $property_name . "_item) {?>\n\t\t<pre>\n\t\t\t<?php print_r($" . $property_name . "_item->toStdClass());?>\n\t\t</pre>\n\t<?php }?>\n</dd>";
                }
            } else if($property['type'] == 'datetime') {
                $property_list .= "\n<dt>" . $property_name . "</dt>\n<dd>type: " . $property['type'] . "\n<br>value: " . '<?php if(!is_null($' . strtolower(HelperFunctions::human_to_machine($this->_object_definition->name)) . '->' . $property_name . ')) { echo $' . strtolower(HelperFunctions::human_to_machine($this->_object_definition->name)) . '->' . $property_name . "->format('Y-m-d h:i:s'); }?></dd>";
            } else {
                $property_list .= "\n<dt>" . $property_name . "</dt>\n<dd>type: " . $property['type'] . "\n<br>value: " . '<?php echo $' . strtolower(HelperFunctions::human_to_machine($this->_object_definition->name)) . '->' . $property_name . ";?></dd>";
            }
        }

        $search = [
            '###CLASSNAME###',
            '###VARIABLENAME###',
            '###OBJECTNAME###',
            '###VIEWFILEPATH###',
            '###PROPERTYLIST###'
        ];

        $replace = [
            $this->_generateClassName($this->_object_definition->name),
            strtolower(HelperFunctions::human_to_machine($this->_object_definition->name)),
            $this->_object_definition->name,
            BASE_PATH . '/' . $config['view_scripts']['base_path'] . '/'  . $this->_generateClassName($this->_object_definition->name) .  '_Example.php',
            $property_list
        ];

        $text = str_replace($search, $replace, file_get_contents(__DIR__ . '/ObjectTypeScaffolderTemplates/view_template.txt'));
        file_put_contents(BASE_PATH . '/' . $config['view_scripts']['base_path'] . '/'  . $this->_generateClassName($this->_object_definition->name) .  '_Example.php', $text);
    }

    /**
     * @param string $pName
     * @return string
     */
    private function _generateClassName($pName) {
        return ucfirst(HelperFunctions::human_to_machine($pName));
    }

    /**
     * @throws Exception
     */
    private function _insertTags() {
        /**@var DB\Adapter\AdapterInterface $db*/
        $db = Registry::getInstance()->get('db');
        $this->_log[] = 'Deleting Tags for Object Type ' . HelperFunctions::human_to_machine($this->_tablename);
        $db->delete('pmt2core_objectdata_tags', ['id_object_type = ?', $this->_object_definition->id]);
        $this->_log[] = 'Inserting Tags for Object Type ' . HelperFunctions::human_to_machine($this->_tablename);
        foreach ($this->_object_definition->fields as $field) {
            if(is_array($field->sections)) {
                foreach ($field->sections as $section) {
                    if (is_array($section->tags)) {
                        foreach ($section->tags as $tag_name) {
                            try {
                                $tag = new ObjectdataTag();
                                $tag->objectdata_column_name = $field->var_name . '_' . HelperFunctions::human_to_machine($section->name);
                                $tag->tag_name = $tag_name;
                                $tag->id_object_type = $this->_object_definition->id;
                                $tag->create();
                                $this->_log[] = 'Tag ' . $tag_name . ' for property ' . $field->var_name . '_' . HelperFunctions::human_to_machine($section->name) . ' inserted';
                            } catch (Exception $e) {
                                $this->_log[] = 'Tag insert error: ' . $e->getMessage();
                                $this->_errors[] = 'Tag insert  error: ' . $e->getMessage();
                            }
                        }
                    }
                }
            }
        }
    }
}
