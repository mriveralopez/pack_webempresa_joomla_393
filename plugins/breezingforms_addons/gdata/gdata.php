<?php
/**
 * @package     BreezingForms
 * @author      Markus Bopp
 * @link        http://www.crosstec.de
 * @license     GNU/GPL
*/

// TODO: uninstall routine

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

function bf_gdata_startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
}

function strToHex($string){
    $hex = '';
    for ($i=0; $i<strlen($string); $i++){
        $ord = ord($string[$i]);
        $hexCode = dechex($ord);
        $hex .= substr('0'.$hexCode, -2);
    }
    return strToUpper($hex);
}

function hexToStr($hex){
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2){
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
}

use Google\Spreadsheet\DefaultServiceRequest;
use Google\Spreadsheet\ServiceRequestFactory;

if(!defined('DS')){
    define('DS', DIRECTORY_SEPARATOR);
}

require_once JPATH_SITE . DS . 'plugins' . DS . 'breezingforms_addons' . DS . 'gdata' . DS . 'breezingforms_addons_gdata_libraries' . DS . 'Google/autoload.php';
   
$srcDir = realpath(__DIR__ . '/breezingforms_addons_gdata_libraries/');

set_include_path($srcDir . PATH_SEPARATOR . get_include_path());

spl_autoload_register(function ($class) {
    if(strpos($class, '\\') !== false && bf_gdata_startsWith($class, 'Google')) {
        include str_replace("\\", "/", $class) . '.php';
    }
});

jimport( 'joomla.plugin.plugin' );
jimport('joomla.version');
               
class plgBreezingforms_addonsGdata extends JPlugin
{
    
    private $client = null;

	function bf_getTableFields($tables, $typeOnly = true)
	{
		jimport('joomla.version');
		$version = new JVersion();

		if(version_compare($version->getShortVersion(), '3.0', '<')){
			return JFactory::getDBO()->getTableFields($tables);
		}

		$results = array();

		settype($tables, 'array');

		foreach ($tables as $table)
		{
			try{
				$results[$table] = JFactory::getDbo()->getTableColumns($table, $typeOnly);
			}catch(Exception $e){  }
		}

		return $results;
	}
    
    function __construct( &$subject, $params )
    {
        parent::__construct($subject, $params);
    
                  
        $this->client = new Google_Client();
        $this->client->setApplicationName('BreezingForms Google Drive Spreadsheets');
        
        $this->client->addScope(array('https://www.googleapis.com/auth/drive','https://spreadsheets.google.com/feeds'));
        $this->client->setClientId('34263101371-4rcre0p6r9ehuhoat1d6ls8u84etuanp.apps.googleusercontent.com');
        $this->client->setClientSecret('IDq59sdLo6wC81KCUweDKVf2');
        $this->client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
        $this->client->setAccessType('offline');
        
        $lang = JFactory::getLanguage();
        $lang->load('plg_breezingforms_addons_gdata', JPATH_ADMINISTRATOR);
        
        $db = JFactory::getDBO();

        $tables = $db->getTableList();
        
        if( !in_array( $db->getPrefix().'breezingforms_addons_gdata', $tables ) ){
            $db->setQuery("CREATE TABLE `#__breezingforms_addons_gdata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_id` int(11) NOT NULL DEFAULT '0',
  `username` varchar(255) NOT NULL DEFAULT '',
  `password` longtext NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `spreadsheet_id` varchar(64) NOT NULL DEFAULT '',
  `worksheet_id` varchar(64) NOT NULL DEFAULT '',
  `fields` text NOT NULL,
  `meta` varchar(255) NOT NULL,
  `debug` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `form_id_2` (`form_id`),
  KEY `form_id` (`form_id`,`spreadsheet_id`,`worksheet_id`)
) DEFAULT CHARSET=utf8 AUTO_INCREMENT=1");
            $db->query();
        }

	    $tables = $this->bf_getTableFields( JFactory::getDBO()->getTableList() );


	    if(isset( $tables[$db->getPrefix().'breezingforms_addons_gdata']['password'] )
		        && $tables[$db->getPrefix().'breezingforms_addons_gdata']['password'] == 'varchar'){

		    JFactory::getDBO()->setQuery("ALTER TABLE ".$db->getPrefix()."breezingforms_addons_gdata MODIFY password LONGTEXT;");
		    JFactory::getDBO()->query();
	    }

        jimport('joomla.filesystem.file');
        
        if(!JFile::exists(JPATH_SITE.'/plugins/breezingforms_addons/gdata/updated.txt')){
            
            $db->setQuery("ALTER TABLE #__breezingforms_addons_gdata MODIFY spreadsheet_id VARCHAR(128)");
            $db->query();
            $db->setQuery("ALTER TABLE #__breezingforms_addons_gdata MODIFY worksheet_id VARCHAR(128)");
            $db->query();
            
            $buffer = date('Y-m-d H:i:s');
            JFile::write(JPATH_SITE.'/plugins/breezingforms_addons/gdata/updated.txt', $buffer);
        }
    }
    
    function onPropertiesDisplay($form_id, $tabs){
        
        if(!$form_id) return '';
        
        $error = '';
        
        $db = JFactory::getDBO();
        
        $db->setQuery("Select `title`,`name`,`id` From #__facileforms_elements Where form = " . intval($form_id) . " And `title` Not In ('bfFakeTitle','bfFakeTitle2','bfFakeTitle3','bfFakeTitle4','bfFakeTitle5') And `type` Not In ('','UNKNOWN') Order By ordering");
        $breezingforms_fields = $db->loadObjectList();
        
        $db->setQuery("Select `enabled`, `username`, `password`, `worksheet_id`, `spreadsheet_id`, `fields`, `meta`, `debug` From #__breezingforms_addons_gdata Where form_id = " . intval($form_id));
        $gdata = $db->loadObject();
        
        if( $gdata === null ){
            $gdata = new stdClass();
            $gdata->username = '';
            $gdata->password = '';
            $gdata->enabled = 0;
            $gdata->worksheet_id = '';
            $gdata->spreadsheet_id = '';
            $gdata->fields = '';
            $gdata->meta = '';
            $gdata->debug = 0;
        }
        
        $gdata->fields = explode('/,/', $gdata->fields);
        $gdata->meta   = explode('/,/', $gdata->meta);
        
        $gdata_spreadsheets = array();
        $gdata_worksheets = array();
        $gdata_columns = array();
        
        //if( $gdata->enabled == 1 ){
            
            try{
            
                $spreadsheetFeed = null;
                
                $auth_url = '';
                
                $db->setQuery("Select password From #__breezingforms_addons_gdata Where form_id = " . intval($form_id));
                $accessToken = $db->loadResult();

                if(!$accessToken){
                    
                    $auth_url = $this->client->createAuthUrl();
                    
                } else {
                    
                    try{
                        
                        $this->client->setAccessToken($accessToken);
                        $token = json_decode($accessToken);
                
                        if ($this->client->isAccessTokenExpired()) {
                            $this->client->refreshToken($token->refresh_token);
                            $token = json_decode($this->client->getAccessToken());
                        } 
                        
                        $serviceRequest = new DefaultServiceRequest($token->access_token, $token->token_type);
                        ServiceRequestFactory::setInstance($serviceRequest);

                        $spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
                        $spreadsheetFeed = $spreadsheetService->getSpreadsheets();
                        
                    }catch(Exception $e){
                        
                        $accessToken = null;
                        $auth_url = $this->client->createAuthUrl();
                    }
                }

                if($spreadsheetFeed !== null){
                    foreach($spreadsheetFeed As $sheet){
                        $gdata_spreadsheets[$sheet->getId()] = $sheet->getTitle();
                    }
                }
                
                if($gdata->spreadsheet_id != '' && isset( $gdata_spreadsheets[$gdata->spreadsheet_id] ) && $spreadsheetFeed !== null){

                    $spreadsheet = $spreadsheetFeed->getByTitle($gdata_spreadsheets[$gdata->spreadsheet_id]);
                    $worksheetFeed = $spreadsheet->getWorksheets();
                    
                    foreach ( $worksheetFeed as $sheet ){
                        $gdata_worksheets[$sheet->getId()] = $sheet->getTitle();
                    }
                    
                    if($gdata->worksheet_id != '' && isset( $gdata_worksheets[$gdata->worksheet_id] )){
                        
                        $worksheet = $worksheetFeed->getByTitle($gdata_worksheets[$gdata->worksheet_id]);
                        $cellFeed = $worksheet->getCellFeed();

                        foreach($cellFeed->getEntries() as $cellEntry) {
                            
                            $row = $cellEntry->getRow();
                            $col = $cellEntry->getColumn();
                            
                            if( $row > 1 ){
                                break;
                            }
                            
                            $gdata_columns[] = $cellFeed->getCell($row, $col)->getContent();
                            
                        }
                        
                    }
                }
            
            } catch(Exception $e){
                
                $error = $e->getMessage();
            }
        //}
        
        ob_start();
        $version = new JVersion();
        if(version_compare($version->getShortVersion(), '1.6', '<')){
            require_once JPATH_SITE . DS . 'plugins' . DS . 'breezingforms_addons' . DS . 'breezingforms_addons_gdata_tmpl' . DS . 'properties.php';

        }else{
            require_once JPATH_SITE . DS . 'plugins' . DS . 'breezingforms_addons' . DS . 'gdata' . DS . 'breezingforms_addons_gdata_tmpl' . DS . 'properties.php';
        }
        $c = ob_get_contents();
        ob_end_clean();
        return $c;
    }
    
    function onPropertiesSave($form_id){
        
        if(!$form_id) return '';
        
        $accessToken = '';
        $reset_accessToken = false;
        
        if(isset($_POST['gdata_code']) && $_POST['gdata_code'] != ''){
            
            $accessToken = $this->client->authenticate($_POST['gdata_code']);
        }
        
        if(isset($_POST['gdata_reset'])){
            $reset_accessToken = true;
            $accessToken = '';
            
        }
        
        if(isset($_POST['gdata_fields']) && is_array($_POST['gdata_fields'])){
            $_POST['gdata_fields'] = implode('/,/', $_POST['gdata_fields']);
        }else{
            $_POST['gdata_fields'] = '';
        }
        
        if(isset($_POST['gdata_meta']) && is_array($_POST['gdata_meta'])){
            $_POST['gdata_meta'] = implode('/,/', $_POST['gdata_meta']);
        }else{
            $_POST['gdata_meta'] = '';
        }
        
        $db = JFactory::getDBO();
        
        $db->setQuery("Select form_id From #__breezingforms_addons_gdata Where form_id = " . intval($form_id));
        $exists = $db->loadResult();
        
        if(!$exists){
            $db->setQuery("Insert Into #__breezingforms_addons_gdata (
                `form_id`, `enabled`,`password`,`spreadsheet_id`,`worksheet_id`,`fields`,`meta`) Values 
                (   ".intval($form_id).",
                    ".JRequest::getInt('gdata_enabled', 0).",
                    ".($accessToken ? $db->quote($accessToken).',' : '"",')."
                    ".$db->quote(hexToStr(JRequest::getVar('gdata_spreadsheet_id', ''))).",
                    ".$db->quote(hexToStr(JRequest::getVar('gdata_worksheet_id', ''))).",
                    ".$db->quote($_POST['gdata_fields']).",
                    ".$db->quote($_POST['gdata_meta'])."
                )");
            $db->query();
        } else {
            $db->setQuery("Update #__breezingforms_addons_gdata Set
                `enabled`  = ".JRequest::getInt('gdata_enabled', 0).",
                ".($accessToken || $reset_accessToken ? "`password` = " . $db->quote($accessToken).',' : '')."
                `spreadsheet_id` = ".$db->quote($reset_accessToken ? "''" : hexToStr(JRequest::getVar('gdata_spreadsheet_id', "''"))).",
                `worksheet_id` = ".$db->quote($reset_accessToken ? "''" : hexToStr(JRequest::getVar('gdata_worksheet_id', "''"))).",
                `fields` = ".$db->quote($_POST['gdata_fields']).",
                `meta` = ".$db->quote($_POST['gdata_meta'])."
                 Where form_id = " . intval($form_id) . "
            ");
            $db->query();
        }
        
        
        if(JRequest::getVar('gdata_synch', 0) == 1){
            
            $db = JFactory::getDBO();
        
            $db->setQuery("Select `enabled`, `username`, `password`, `worksheet_id`, `spreadsheet_id`, `fields`, `meta`, `debug` From #__breezingforms_addons_gdata Where form_id = " . intval($form_id));
            $gdata = $db->loadObject();

            if( $gdata === null ){
                $gdata = new stdClass();
                $gdata->username = '';
                $gdata->password = '';
                $gdata->enabled = 0;
                $gdata->worksheet_id = '';
                $gdata->spreadsheet_id = '';
                $gdata->fields = '';
                $gdata->meta = '';
                $gdata->debug = 0;
            }

            $gdata->fields = explode('/,/', $gdata->fields);
            $gdata->meta = explode('/,/', $gdata->meta);

            if( $gdata->enabled == 1 ){

                try{
                    
                    $db->setQuery("Select password From #__breezingforms_addons_gdata Where form_id = " . intval($form_id));
                    $accessToken = $db->loadResult();

                    $this->client->setAccessToken($accessToken);
                    $token = json_decode($accessToken);

                    if ($this->client->isAccessTokenExpired()) {
                        $this->client->refreshToken($token->refresh_token);
                        $token = json_decode($this->client->getAccessToken());
                    }
                    
                    $serviceRequest = new DefaultServiceRequest($token->access_token, $token->token_type);
                    ServiceRequestFactory::setInstance($serviceRequest);

                    $spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
                    $spreadsheetFeed = $spreadsheetService->getSpreadsheets();
                    
                    foreach($spreadsheetFeed As $sheet){
                        $gdata_spreadsheets[$sheet->getId()] = $sheet->getTitle();
                    }
                    
                    $spreadsheet = $spreadsheetFeed->getByTitle($gdata_spreadsheets[$gdata->spreadsheet_id]);
                    $worksheetFeed = $spreadsheet->getWorksheets();
                    
                    foreach ( $worksheetFeed as $sheet ){
                        $gdata_worksheets[$sheet->getId()] = $sheet->getTitle();
                    }
                    
                    $worksheet = $worksheetFeed->getByTitle($gdata_worksheets[$gdata->worksheet_id]);
                    $listFeed = $worksheet->getListFeed();
                    
                    $bffields = array();
                    $gdatafields = array();
                    $gdatameta = array();
                    $bfmeta = array();

                    // meta

                    $meta_fields = array();
                    $meta_fields[0] = new stdClass();
                    $meta_fields[0]->title = 'Record ID';

                    $meta_fields[1] = new stdClass();
                    $meta_fields[1]->title = 'Form ID';

                    $meta_fields[2] = new stdClass();
                    $meta_fields[2]->title = 'Form Title';

                    $meta_fields[3] = new stdClass();
                    $meta_fields[3]->title = 'Form Name';

                    $meta_fields[4] = new stdClass();
                    $meta_fields[4]->title = 'Timestamp';

                    $meta_fields[5] = new stdClass();
                    $meta_fields[5]->title = 'Date';

                    $meta_fields[6] = new stdClass();
                    $meta_fields[6]->title = 'Time';

                    $meta_fields[7] = new stdClass();
                    $meta_fields[7]->title = 'IP';

                    $meta_fields[8] = new stdClass();
                    $meta_fields[8]->title = 'USER AGENT';

                    $meta_fields[9] = new stdClass();
                    $meta_fields[9]->title = 'User ID';

                    $meta_fields[10] = new stdClass();
                    $meta_fields[10]->title = 'Username';

                    $meta_fields[11] = new stdClass();
                    $meta_fields[11]->title = 'User Fullname';

                    foreach($gdata->meta As $fields){
                        $field = explode('::', $fields);
                        if(isset($field[1])){
                            $bfmeta[]    = $field[0];
                            $gdatameta[$field[0]] = $field[1];
                        }
                    }
                    
                    foreach($gdata->fields As $fields){
                        $field = explode('::', $fields);
                        if(isset($field[1])){
                            $bffields[]    = $field[0];
                            $gdatafields[$field[0]] = $field[1];
                        }
                    }

                    // record start
                    
                    require_once(JPATH_SITE . '/administrator/components/com_breezingforms/libraries/Zend/Json/Decoder.php');
                    require_once(JPATH_SITE . '/administrator/components/com_breezingforms/libraries/Zend/Json/Encoder.php');
                    $db->setQuery("Select template_areas, template_code_processed From #__facileforms_forms Where id = " . intval($form_id));
                    $formrow = $db->loadObject();
                    $areas = Zend_Json::decode($formrow->template_areas);
                    
                    $db->setQuery("Select * From #__facileforms_records Where form = " . intval($form_id) . " Order By submitted");
                    $records = $db->loadAssocList();
                    
                    foreach($records As $record){
                    
                        $rowData = array();

                        foreach($meta_fields As $data){
                            if( in_array($data->title, $bfmeta) ){
                                $value = '';
                                switch($data->title){
                                    case 'Record ID'; $value = $record['id'];break;
                                    case 'Form ID'; $value = $record['form'];break;
                                    case 'Form Title'; $value = $record['title'];break;
                                    case 'Form Name'; $value = $record['name'];break;
                                    case 'Timestamp'; $value = JHTML::_('date', $record['submitted'], 'Y-m-d H:i:s', false);break;
                                    case 'Date'; $value = JHTML::_('date', $record['submitted'], 'Y-m-d', false);break;
                                    case 'Time'; $value = JHTML::_('date', $record['submitted'], 'H:i:s', false);break;
                                    case 'IP'; $value = $record['ip'];break;
                                    case 'USER AGENT'; $value = $record['browser'];break;
                                    case 'User ID'; $value = $record['user_id'];break;
                                    case 'Username'; $value = $record['username'];break;
                                    case 'User Fullname'; $value = $record['user_full_name'];break;
                                }
                                $rowData[$gdatameta[$data->title]] = $value;
                            }
                        }

                        // fields

                        $db->setQuery("Select * From #__facileforms_subrecords Where record = " . intval($record['id']) . " Order By id");
                        $subrecords = $db->loadAssocList();
                        $subs = array();
                        $uploads = array();
                        foreach($subrecords As $subrecord){
                            $subs[$subrecord['title'].$subrecord['element']][] = $subrecord['value'];
                            if( $subrecord['type'] == 'File Upload' ){
                                $uploads[$subrecord['title'].$subrecord['element']] = $subrecord['value'];
                            }
                        }
                        
                        foreach($subs As $title => $value){
                            if( in_array($title, $bffields) ){
                                if(isset($uploads[$title])){
                                    $useUrl = false;
                                    $useUrlDownloadDirectory = '';
                                    if (trim($formrow->template_code_processed) == 'QuickMode' && is_array($areas)) {
                                        foreach ($areas As $area) { // don't worry, size is only 1 in QM
                                            if (isset($area['elements'])) {
                                                foreach ($area['elements'] As $element) {
                                                    if (isset($element['options']) && isset($element['options']['useUrl']) && isset($element['name']) && trim($element['title']) == trim($title) && isset($element['internalType']) && $element['internalType'] == 'bfFile') {
                                                        $useUrl = $element['options']['useUrl'];
                                                        $useUrlDownloadDirectory = $element['options']['useUrlDownloadDirectory'];
                                                        break;
                                                    }
                                                }
                                            }
                                            break; // just in case
                                        }
                                    }
                                    if($uploads[$title] != '' && $useUrl && $useUrlDownloadDirectory != ''){
                                        $value = '';
                                        $files = explode("\n", $uploads[$title]);
                                        echo $uploads[$title];
                                        foreach($files As $file){
                                           $value .= $useUrlDownloadDirectory . '/' . basename($file) . "\n";
                                        }
                                        $rowData[$gdatafields[$title]] = $value;
                                    }else{
                                        $rowData[$gdatafields[$title]] = implode(', ',$value);
                                    }
                                }else{
                                    $rowData[$gdatafields[$title]] = implode(', ',$value);
                                }
                            }
                        }

                        $listFeed->insert($rowData);
                    
                    }
                    // record end
                    
                }catch(Exception $e){}
            }
        }
        //exit;
    }
    
    function onPropertiesExecute($processor){
        
        if(!$processor->form) return;
        
        $db = JFactory::getDBO();
        
        $db->setQuery("Select `enabled`, `username`, `password`, `worksheet_id`, `spreadsheet_id`, `fields`, `meta`, `debug` From #__breezingforms_addons_gdata Where form_id = " . intval($processor->form));
        $gdata = $db->loadObject();
        
        if( $gdata === null ){
            $gdata = new stdClass();
            $gdata->username = '';
            $gdata->password = '';
            $gdata->enabled = 0;
            $gdata->worksheet_id = '';
            $gdata->spreadsheet_id = '';
            $gdata->fields = '';
            $gdata->meta = '';
            $gdata->debug = 0;
        }
        
        $gdata->fields = explode('/,/', $gdata->fields);
        $gdata->meta = explode('/,/', $gdata->meta);
        
        if( $gdata->enabled == 1 ){
            
            try{
            
                $db->setQuery("Select password From #__breezingforms_addons_gdata Where form_id = " . intval($processor->form));
                $accessToken = $db->loadResult();

                $this->client->setAccessToken($accessToken);
                $token = json_decode($accessToken);

                if ($this->client->isAccessTokenExpired()) {
                    $this->client->refreshToken($token->refresh_token);
                    $token = json_decode($this->client->getAccessToken());
                }
                
                $serviceRequest = new DefaultServiceRequest($token->access_token, $token->token_type);
                ServiceRequestFactory::setInstance($serviceRequest);

                $spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
                $spreadsheetFeed = $spreadsheetService->getSpreadsheets();

                foreach($spreadsheetFeed As $sheet){
                    $gdata_spreadsheets[$sheet->getId()] = $sheet->getTitle();
                }

                $spreadsheet = $spreadsheetFeed->getByTitle($gdata_spreadsheets[$gdata->spreadsheet_id]);
                $worksheetFeed = $spreadsheet->getWorksheets();

                foreach ( $worksheetFeed as $sheet ){
                    $gdata_worksheets[$sheet->getId()] = $sheet->getTitle();
                }

                $worksheet = $worksheetFeed->getByTitle($gdata_worksheets[$gdata->worksheet_id]);
                $listFeed = $worksheet->getListFeed();
                
                $rowData = array();
                $bffields = array();
                $gdatafields = array();
                $gdatameta = array();
                $bfmeta = array();
                
                // meta
                
                $meta_fields = array();
                $meta_fields[0] = new stdClass();
                $meta_fields[0]->title = 'Record ID';

                $meta_fields[1] = new stdClass();
                $meta_fields[1]->title = 'Form ID';

                $meta_fields[2] = new stdClass();
                $meta_fields[2]->title = 'Form Title';

                $meta_fields[3] = new stdClass();
                $meta_fields[3]->title = 'Form Name';

                $meta_fields[4] = new stdClass();
                $meta_fields[4]->title = 'Timestamp';

                $meta_fields[5] = new stdClass();
                $meta_fields[5]->title = 'Date';

                $meta_fields[6] = new stdClass();
                $meta_fields[6]->title = 'Time';

                $meta_fields[7] = new stdClass();
                $meta_fields[7]->title = 'IP';

                $meta_fields[8] = new stdClass();
                $meta_fields[8]->title = 'USER AGENT';

                $meta_fields[9] = new stdClass();
                $meta_fields[9]->title = 'User ID';

                $meta_fields[10] = new stdClass();
                $meta_fields[10]->title = 'Username';

                $meta_fields[11] = new stdClass();
                $meta_fields[11]->title = 'User Fullname';
                
                foreach($gdata->meta As $fields){
                   $field = explode('::', $fields);
                   if(isset($field[1])){
                       $bfmeta[]    = $field[0];
                       $gdatameta[$field[0]] = $field[1];
                   }
                }
                
                foreach($meta_fields As $data){
                    if( in_array($data->title, $bfmeta) ){
                        $value = '';
                        switch($data->title){
                            case 'Record ID'; $value = $processor->record_id;break;
                            case 'Form ID'; $value = $processor->form;break;
                            case 'Form Title'; $value = $processor->formrow->title;break;
                            case 'Form Name'; $value = $processor->formrow->name;break;
                            case 'Timestamp'; $value = $processor->submitted;break;
                            case 'Date'; $value = date('Y-m-d', strtotime($processor->submitted));break;
                            case 'Time'; $value = date('H:i:s', strtotime($processor->submitted));break;
                            case 'IP'; $value = $processor->ip;break;
                            case 'USER AGENT'; $value = $processor->browser;break;
                            case 'User ID'; $value = JFactory::getUser()->get('id', 0);break;
                            case 'Username'; $value = JFactory::getUser()->get('username', '');break;
                            case 'User Fullname'; $value = JFactory::getUser()->get('name', '');break;
                        }
                        $rowData[$gdatameta[$data->title]] = $value;
                    }
                }
                
                // fields
                
                foreach($gdata->fields As $fields){
                   $field = explode('::', $fields);
                   if(isset($field[1])){
                       $bffields[]    = $field[0];
                       $gdatafields[$field[0]] = $field[1];
                   }
                }
                
                foreach($processor->maildata As $data){
                    if( in_array($data[_FF_DATA_TITLE].$data[_FF_DATA_ID], $bffields) ){
                        $rowData[$gdatafields[$data[_FF_DATA_TITLE].$data[_FF_DATA_ID]]] = $data[_FF_DATA_VALUE];
                    }
                }
                
                $listFeed->insert($rowData);
                
            } catch(Exception $e){
                //if($gdata->debug){
                //    echo $e->getMessage();
                //}
            }
        }
    }
}
