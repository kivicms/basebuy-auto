<?php
require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/helpers/Console.php');
require(__DIR__ . '/helpers/DataFile.php');
use \basebuy\basebuyAutoApi\BasebuyAutoApi;
use \basebuy\basebuyAutoApi\connectors\CurlGetConnector;
use \basebuy\basebuyAutoApi\exceptions\EmptyResponseException;
use \helpers\Console;
use helpers\DataFile;

define ("API_KEY", "kos@tiptopit.ruf0624c0dce143b00ea44508cc34f59a8");
define ("API_URL", "https://api.basebuy.ru/api/auto/v1/");

define ('DB_HOST', 'localhost');
define ('DB_NAME', 'basebuy_auto');
define ('DB_USERNAME', 'vitalik');
define ('DB_PASSWORD', 'Zc4e72xs'); 

class App {
    
    private $lastDateUpdate; // Дата последнего обращения к API, чтобы сперва сделать проверку, а уже потом выкачивать файлы
    private $idType; // Легковые автомобили (полный список можно получить через $basebuyAutoApi->typeGetAll())
    private $downloadFolder ;
    private $downloadedFilePath;
    private $lastDatesUpdate = [];
    private $df;
    private $basebuyAutoApi;
    
    private $types;
    
    
    private function init() {
        $df = new DataFile();
        $this->lastDatesUpdate = $df->getDates(); 
        $this->downloadFolder = $_SERVER['DOCUMENT_ROOT'];
        $this->downloadedFilePath = __DIR__;
        
        $this->basebuyAutoApi = new BasebuyAutoApi(
            new CurlGetConnector( API_KEY, API_URL, $this->downloadFolder)
        );
    }
    
    private function initDb() {
        ORM::configure('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME);
        ORM::configure('username', DB_USERNAME);
        ORM::configure('password', DB_PASSWORD);
        ORM::configure('driver_options', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
    }
    
    private function closeApp() {
        
    }
    
    public function run() {
        $this->init();
        $this->initDb();
        
                

        
        
        
        $this->idType = 1;
        // $this->lastDateUpdate = strtotime('01.01.2016 00:00:00');
        
        \helpers\Console::log('Начало работы:');
        
        
         if ( $this->basebuyAutoApi->markGetDateUpdate( 1 ) > strtotime('01.01.2016 00:00:00')){
            print_r($this->basebuyAutoApi->markGetDateUpdate( 1 ));
            $downloadedFilePath = $this->basebuyAutoApi->markGetAll( 20 );
        }
        echo $downloadedFilePath;
        
        die; 
        
        $this->updateTypes();       
        $this->updateMarks();
        $this->updateModels();
        $this->updateGenerations();
        $this->updateCharacteristics();
        $this->updateCharacteristicValues();
        $this->updateEquipment();

        
        
        try {
            
            
            
            /*
             if ( $basebuyAutoApi->markGetDateUpdate( $idType ) > $lastDateUpdate){
             print_r($basebuyAutoApi->markGetDateUpdate( $idType, BasebuyAutoApi::FORMAT_STRING ));
             $downloadedFilePath = $basebuyAutoApi->markGetAll( $idType );
             }
             
             if ( $basebuyAutoApi->modelGetDateUpdate( $idType ) > $lastDateUpdate){
             $downloadedFilePath = $basebuyAutoApi->modelGetAll( $idType );
             }
             
             if ( $basebuyAutoApi->generationGetDateUpdate( $idType ) > $lastDateUpdate){
             $downloadedFilePath = $basebuyAutoApi->generationGetAll( $idType );
             }
             
             if ( $basebuyAutoApi->serieGetDateUpdate( $idType ) > $lastDateUpdate){
             $downloadedFilePath = $basebuyAutoApi->serieGetAll( $idType );
             }
             
             if ( $basebuyAutoApi->modificationGetDateUpdate( $idType ) > $lastDateUpdate){
             $downloadedFilePath = $basebuyAutoApi->modificationGetAll( $idType );
             }
             
             if ( $basebuyAutoApi->characteristicGetDateUpdate( $idType ) > $lastDateUpdate){
             $downloadedFilePath = $basebuyAutoApi->characteristicGetAll( $idType );
             }
             
             if ( $basebuyAutoApi->characteristicValueGetDateUpdate( $idType ) > $lastDateUpdate){
             $downloadedFilePath = $basebuyAutoApi->characteristicValueGetAll( $idType );
             }
             */
            
/*             $fp = fopen( $downloadedFilePath, 'r');
            if ($fp){
                while (!feof($fp)){
                    $fileRow = fgets($fp, 999);
                    echo $fileRow."<br />";
                }
            } else {
                echo "Ошибка при открытии файла";
            }
            fclose($fp); */
            
            
            
        } catch( Exception $e ){
            

        }
    }
    
    private function updateOption() {
        \helpers\Console::log('Проверяем таблицу car_option');
        foreach ($this->types as $type) {
            if ( $this->basebuyAutoApi->optionGetDateUpdate( $type ) > $this->lastDatesUpdate['option']){
                
                if ($type > 1) {
                    return;
                }
                echo "Тип: " . $type . "\n";
                $this->downloadedFilePath = $this->basebuyAutoApi->optionGetAll( $type );
                
                
                ORM::configure('id_column', 'id_car_option');
                
                $fileData = $this->parseFile();
                
                foreach ($fileData as $fd) {
                    
                    $rec = ORM::for_table('car_option')->where('id_car_option', $fd[0])->find_one();
                    
                    if (! $rec) {
                        $rec = ORM::for_table('car_option')->create();
                        $rec->id_car_option                 = $fd[0];
                        $rec->name                          = $fd[1];
                        $rec->id_parent                     = $fd[2];
                        $rec->date_create                   = $fd[3];
                        $rec->date_update                   = $fd[4];
                        $rec->id_car_type                   = $type;
                        $rec->manual_update                 = 0;
                        $rec->save();
                    } else {
                        if ($rec->manual_update) {
                            Console::log('Таблица car_option:', 'normal', true);
                            $this->consoleArray('Старая запись', $fd, 'green');
                            $this->consoleArray('Новая запись', [$rec->id_car_option, $rec->name], 'red');
                            
                            if ($this->checkAnswer()) {
                                $rec->name          = $fd[2];
                                $rec->date_update   = $fd[6];
                                $rec->manual_update = 0;
                                $rec->save();
                            }
                        }
                    }
                }
            }
        }
    }
    
    private function updateEquipment() {
        \helpers\Console::log('Проверяем таблицу car_equipment');
        foreach ($this->types as $type) {
            if ( $this->basebuyAutoApi->equipmentGetDateUpdate( $type ) > $this->lastDatesUpdate['equipment']){
                
                if ($type > 1) {
                    return;
                }
                echo "Тип: " . $type . "\n";
                $this->downloadedFilePath = $this->basebuyAutoApi->equipmentGetAll( $type );
                
                
                ORM::configure('id_column', 'id_car_equipment');
                
                $fileData = $this->parseFile();
                
                foreach ($fileData as $fd) {
                    
                    $rec = ORM::for_table('car_equipment')->where('id_car_equipment', $fd[0])->find_one();
                    
                    if (! $rec) {
                        $rec = ORM::for_table('car_equipment')->create();
                        $rec->id_car_equipment              = $fd[0];
                        $rec->id_car_modification           = $fd[1];
                        $rec->name                          = $fd[2];
                        $rec->price_min                     = $fd[3];
                        $rec->year                          = $fd[4];
                        $rec->date_create                   = $fd[5];
                        $rec->date_update                   = $fd[6];
                        $rec->id_car_type                   = $type;
                        $rec->manual_update                 = 0;
                        $rec->save();
                    } else {
                        if ($rec->manual_update) {
                            Console::log('Таблица car_equipment:', 'normal', true);
                            $this->consoleArray('Старая запись', $fd, 'green');
                            $this->consoleArray('Новая запись', [$rec->id_car_equipment, $rec->name, $rec->price_min, $rec->year], 'red');
                            
                            if ($this->checkAnswer()) {
                                $rec->name          = $fd[2];
                                $rec->price_min     = $fd[2];
                                $rec->year          = $fd[4];
                                $rec->date_update   = $fd[6];
                                $rec->manual_update = 0;
                                $rec->save();
                            }
                        }
                    }
                }
            }
        }
    }
    
    private function updateCharacteristicValues() {
        \helpers\Console::log('Проверяем таблицу car_characteristic_value');
        foreach ($this->types as $type) {
            if ( $this->basebuyAutoApi->characteristicValueGetDateUpdate( $type ) > $this->lastDatesUpdate['characteristic_value']){
                
                if ($type > 1) {
                    return;
                }
                echo "Тип: " . $type . "\n";
                $this->downloadedFilePath = $this->basebuyAutoApi->characteristicValueGetAll( $type );
                
                
                ORM::configure('id_column', 'id_car_characteristic_value');
                
                $fileData = $this->parseFile();
                
                foreach ($fileData as $fd) {
                    
                    $rec = ORM::for_table('car_characteristic_value')->where('id_car_characteristic_value', $fd[0])->find_one();
                    
                    if (! $rec) {
                        $rec = ORM::for_table('car_characteristic_value')->create();
                        $rec->id_car_characteristic_value   = $fd[0];                        
                        $rec->id_car_modification           = $fd[1];
                        $rec->id_car_characteristic         = $fd[2];
                        $rec->value                         = $fd[3];
                        $rec->unit                          = $fd[4];
                        $rec->date_create                   = $fd[5];
                        $rec->date_update                   = $fd[6];
                        $rec->id_car_type                   = $type;
                        $rec->value_en                      = $fd[8];
                        $rec->unit_en                       = $fd[9];
                        $rec->manual_update                 = 0;
                        $rec->save();
                    } else {
                        if ($rec->manual_update) {
                            Console::log('Таблица car_characteristic_value:', 'normal', true);
                            $this->consoleArray('Старая запись', $fd, 'green');
                            $this->consoleArray('Новая запись', [$rec->id_car_characteristic_value, $rec->value, $rec->unit, ], 'red');
                            
                            if ($this->checkAnswer()) {
                                $rec->value         = $fd[3];
                                $rec->date_update   = $fd[6];
                                $rec->manual_update = 0;
                                $rec->save();
                            }
                        }
                    }
                }
            }
        }
    }
    
    
    private function updateCharacteristics() {
        \helpers\Console::log('Проверяем таблицу car_characteristic');
        foreach ($this->types as $type) {
            if ( $this->basebuyAutoApi->characteristicGetDateUpdate( $type ) > $this->lastDatesUpdate['characteristic']){
                
                if ($type > 1) {
                    return;
                }
                echo "Тип: " . $type . "\n";
                $this->downloadedFilePath = $this->basebuyAutoApi->characteristicGetAll( $type );
                
                
                ORM::configure('id_column', 'id_car_characteristic');
                
                $fileData = $this->parseFile();
                
                foreach ($fileData as $fd) {
                    
                    $rec = ORM::for_table('car_characteristic')->where('id_car_characteristic', $fd[0])->find_one();
                    
                    if (! $rec) {
                        $rec = ORM::for_table('car_characteristic')->create();
                        $rec->id_car_characteristic = $fd[0];
                        $rec->name                  = $fd[1];
                        $rec->id_parent             = $fd[2];
                        $rec->date_create           = $fd[3];
                        $rec->date_update           = $fd[4];                        
                        $rec->id_car_type           = $type;
                        $rec->name_eng              = $fd[6];
                        $rec->name_pol              = $fd[7];
                        $rec->name_deu              = $fd[8];
                        $rec->name_esp              = $fd[9];
                        $rec->manual_update     = 0;
                        $rec->save();
                    } else {
                        if ($rec->manual_update) {
                            Console::log('Таблица car_characteristic:', 'normal', true);
                            $this->consoleArray('Старая запись', $fd, 'green');
                            $this->consoleArray('Новая запись', [$rec->id_car_characteristic, $rec->name, $rec->name_eng, ], 'red');
                            
                            if ($this->checkAnswer()) {
                                $rec->name          = $fd[1];
                                $rec->date_update   = $fd[4];
                                $rec->manual_update = 0;
                                $rec->save();
                            }
                        }
                    }
                }
            }
        }
    }
    
    
    private function updateSeries() {
        \helpers\Console::log('Проверяем таблицу car_serie');
        foreach ($this->types as $type) {
            if ( $this->basebuyAutoApi->serieGetDateUpdate( $type ) > $this->lastDatesUpdate['serie']){
                
                if ($type > 1) {
                    return;
                }
                echo "Тип: " . $type . "\n";
                $this->downloadedFilePath = $this->basebuyAutoApi->serieGetAll( $type );
                
                
                ORM::configure('id_column', 'id_car_serie');
                
                $fileData = $this->parseFile();
                
                foreach ($fileData as $fd) {
                    
                    $rec = ORM::for_table('car_serie')->where('id_car_serie', $fd[0])->find_one();
                    
                    if (! $rec) {
                        $rec = ORM::for_table('car_serie')->create();
                        $rec->id_car_serie      = $fd[0];
                        $rec->id_car_model      = $fd[1];
                        $rec->id_car_generation = $fd[2];
                        $rec->name              = $fd[3];
                        $rec->date_create       = $fd[4];
                        $rec->date_update       = $fd[5];
                        $rec->id_car_type       = $type;
                        $rec->manual_update     = 0;
                        $rec->save();
                    } else {
                        if ($rec->manual_update) {
                            Console::log('Таблица car_generation:', 'normal', true);
                            $this->consoleArray('Старая запись', $fd, 'green');
                            $this->consoleArray('Новая запись', [$rec->id_car_serie, $rec->name], 'red');
                            
                            if ($this->checkAnswer()) {
                                $rec->name          = $fd[2];
                                $rec->date_update   = $fd[6];
                                $rec->manual_update = 0;
                                $rec->save();
                            }
                        }
                    }
                }
            }
        }
    }
    
    private function updateGenerations() {
        \helpers\Console::log('Проверяем таблицу car_generation');
        foreach ($this->types as $type) {
            if ( $this->basebuyAutoApi->generationGetDateUpdate( $type ) > $this->lastDatesUpdate['generation']){
                
                if ($type > 1) {
                    return;
                }
                echo "Тип: " . $type . "\n";
                $this->downloadedFilePath = $this->basebuyAutoApi->generationGetAll( $type );
                
                
                ORM::configure('id_column', 'id_car_generation');
                
                $fileData = $this->parseFile();
                
                foreach ($fileData as $fd) {
                    
                    $rec = ORM::for_table('car_generation')->where('id_car_generation', $fd[0])->find_one();
                    
                    if (! $rec) {
                        $rec = ORM::for_table('car_generation')->create();
                        $rec->id_car_generation = $fd[0];
                        $rec->id_car_model      = $fd[1];
                        $rec->name              = $fd[2];
                        $rec->year_begin        = $fd[3];
                        $rec->year_end          = $fd[4];
                        $rec->date_create       = $fd[5];
                        $rec->date_update       = $fd[6];
                        $rec->id_car_type       = $type;
                        $rec->manual_update     = 0;
                        $rec->save();
                    } else {
                        if ($rec->manual_update) {
                            Console::log('Таблица car_generation:', 'normal', true);
                            $this->consoleArray('Старая запись', $fd, 'green');
                            $this->consoleArray('Новая запись', [$rec->id_car_generation, $rec->name, $rec->year_begin, $rec->year_end], 'red');
                            
                            if ($this->checkAnswer()) {
                                $rec->name          = $fd[2];
                                $rec->year_begin    = $fd[3];
                                $rec->year_end      = $fd[4];
                                $rec->date_update   = $fd[6];
                                $rec->manual_update = 0;
                                $rec->save();
                            }
                        }
                    }
                }
            }
        }
    }
    
    private function updateModels() {
        \helpers\Console::log('Проверяем таблицу car_model');
        foreach ($this->types as $type) {
            if ( $this->basebuyAutoApi->modelGetDateUpdate( $type ) > $this->lastDatesUpdate['model']){

                if ($type > 1) {
                    return;
                }
                
                echo "Тип: " . $type . "\n";
                $this->downloadedFilePath = $this->basebuyAutoApi->modelGetAll( $type );

                
                ORM::configure('id_column', 'id_car_model');
                
                $fileData = $this->parseFile();
                
                foreach ($fileData as $fd) {
                    
                    $rec = ORM::for_table('car_model')->where('id_car_model', $fd[0])->find_one();
                    
                    if (! $rec) {
                        $rec = ORM::for_table('car_model')->create();
                        $rec->id_car_model      = $fd[0];
                        $rec->id_car_mark       = $fd[1];
                        $rec->name              = $fd[2];
                        $rec->name_rus          = $fd[3];
                        $rec->date_create       = $fd[4];
                        $rec->date_update       = $fd[5];
                        $rec->id_car_type       = $type;
                        $rec->manual_update     = 0;
                        $rec->save();
                    } else {
                        if ($rec->manual_update) {
                            Console::log('Таблица car_model:', 'normal', true);
                            $this->consoleArray('Старая запись', $fd, 'green');
                            $this->consoleArray('Новая запись', [$rec->id_car_model, $rec->name, $rec->name_rus], 'red');
                            
                            if ($this->checkAnswer()) {
                                $rec->name          = $fd[2];
                                $rec->name_rus      = $fd[3];
                                $rec->date_update   = $fd[5];
                                $rec->manual_update = 0;
                                $rec->save();
                            }
                        }
                    }                    
                }
            }            
        }
    }
    
    private function updateMarks() {
        \helpers\Console::log('Проверяем таблицу car_mark');
        foreach ($this->types as $type) {
            if ( $this->basebuyAutoApi->markGetDateUpdate( $type ) > $this->lastDatesUpdate['mark']){
                
                if ($type > 1) 
                    return;
                
                echo "Тип: " . $type . "\n";
                $this->downloadedFilePath = $this->basebuyAutoApi->markGetAll( $type );
                
                ORM::configure('id_column', 'id_car_mark');
                
                $fileData = $this->parseFile();
                
                foreach ($fileData as $fd) {

                    $rec = ORM::for_table('car_mark')->where('id_car_mark', $fd[0])->find_one();
                    
                    if (! $rec) {
                        $rec = ORM::for_table('car_mark')->create();
                        $rec->id_car_mark       = $fd[0];
                        $rec->name              = $fd[1];
                        $rec->name_rus          = $fd[2];
                        $rec->date_create       = $fd[3];
                        $rec->date_update       = $fd[4];
                        $rec->id_car_type       = $type;
                        $rec->manual_update     = 0;
                        $rec->save();
                    } else {
                        if ($rec->manual_update) {
                            Console::log('Таблица car_mark:', 'normal', true);
                            $this->consoleArray('Старая запись', $fd, 'green');
                            $this->consoleArray('Новая запись', [$rec->id_car_mark, $rec->name, $rec->name_rus], 'red');
                            
                            if ($this->checkAnswer()) {
                                $rec->name          = $fd[1];
                                $rec->name_rus      = $fd[2];
                                $rec->date_update   = $fd[4]; 
                                $rec->manual_update = 0;
                                $rec->save();
                            }
                        }
                    }
                    
                }
            }
            
        }
    }
    
    private function updateTypes() {
        \helpers\Console::log('Проверяем таблицу car_type');
        if ( $this->basebuyAutoApi->typeGetDateUpdate() > $this->lastDatesUpdate['type']){
            $this->downloadedFilePath = $this->basebuyAutoApi->typeGetAll();
            
            ORM::configure('id_column', 'id_car_type'); 
            
            $fileData = $this->parseFile();
            
            foreach ($fileData as $fd) {
                
                $this->types[] = $fd[0]; // наполняем массив существующих типов ТС
                
                $rec = ORM::for_table('car_type')->where('id_car_type', $fd[0])->find_one();
            
                if (! $rec) {
                    $rec = ORM::for_table('car_type')->create();
                    $rec->id_car_type = $fd[0];
                    $rec->name = $fd[1];
                    $rec->manual_update = 0;
                    $rec->save();
                } else {
                    if ($rec->manual_update) {
                        Console::log('Таблица car_type:', 'normal', true);
                        $this->consoleArray('Старая запись', $fd, 'green');
                        $this->consoleArray('Новая запись', [$rec->id_car_type, $rec->name], 'red');
                        
                        if ($this->checkAnswer()) {
                            $rec->name = $fd[1];
                            $rec->manual_update = 0;
                            $rec->save();
                        }
                    }
                }
                
            }
        }
    }
    
    private function parseFile() {
        $data = [];
//        echo $this->downloadedFilePath;die;
        $row = 0;
        if (($handle = fopen($this->downloadedFilePath, "r")) !== FALSE) {
            while (($d = fgetcsv($handle, 10000, ",")) !== FALSE) {                
                if ($row > 0) {
                    $data[] = $this->clearArray($d); 
                }
                $row ++;
            }
            fclose($handle);
        }
        return $data;
    }
    
    private function clearArray($arr) {
        $res = [];
        foreach ($arr as $a) {
            $res[] = trim($a, "'");
        }
        return $res;
    }
    
    private function checkAnswer() {
        \helpers\Console::log('Обновить строку? 1-Да, Все остальное - Нет');
        $s = readline();
        return $s == '1';
    }
    
    private function consoleArray($prefix, $arr, $color, $newStr = true) {
        
        Console::log($prefix, $color, $newStr);
        
        $cnt = count($arr);
        $i = 1;
        foreach ($arr as $a) {
            Console::log($a . ' - ', $color, $cnt == $i ? true : false);
            $i ++;
        }
        echo Console::bell();
    }
    
    private function showError($e) {
        switch ( $e->getCode() ){
            case 401:
                echo '<pre>'.$e->getMessage()."\nУказан неверный API-ключ или срок действия вашего ключа закончился. Обратитесь в службу поддержки по адресу support@basebuy.ru</pre>";
                break;
                
            case 404:
                echo '<pre>'.$e->getMessage()."\nПо заданным параметрам запроса невозможно построить результат. Проверьте наличие параметра id_type, который обязателен для всех сущностей, кроме собственно type.</pre>";
                break;
                
            case 500:
                echo '<pre>'.$e->getMessage()."\nВременные перебои в работе сервиса.</pre>";
                break;
                
            case 501:
                echo '<pre>'.$e->getMessage()."\nЗапрошено несуществующее действие для указанной сущности.</pre>";
                break;
                
            case 503:
                echo '<pre>'.$e->getMessage()."\nВременное прекращение работы сервиса в связи с обновлением базы данных.</pre>";
                break;
                
            default:
                echo '<pre>'.$e->getMessage()."</pre>";
        }
    }
    
}

$app = new App();

$app->run();