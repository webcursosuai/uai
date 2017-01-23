<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 *
*
* @package    local
* @subpackage uai
* @copyright  2016 Hans Jeria (hansjeria@gmail.com)
* @copyright  2017 Mark Michaelsen (mmichaelsen678@gmail.com)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

class block_uai extends block_base {
	
	/** @var int This allows for multiple navigation trees */
    public static $navcount;
    /** @var string The name of the block */
    public $blockname = null;
    /** @var bool A switch to indicate whether content has been generated or not. */
    protected $contentgenerated = false;
    /** @var bool|null variable for checking if the block is docked*/
    protected $docked = null;
    
    function init() {
    	$this->blockname = get_class($this);
    	$this->title = "UAI";
    }
    function has_config() {
    	return true;
    }
    
    function instance_allow_multiple() {
    	return false;
    }
    
    function applicable_formats() {
    	return array('all' => true);
    }
    
    function instance_allow_config() {
    	return true;
    }
    
    function  instance_can_be_hidden() {
    	return false;
    }
    
    function instance_can_be_docked() {
    	return (parent::instance_can_be_docked() && (empty($this->config->enabledock) || $this->config->enabledock=='yes'));
    }
    
    function get_required_javascript() {
    	parent::get_required_javascript();
    	$arguments = array(
    			'instanceid' => $this->instance->id
    	);
    	$this->page->requires->string_for_js('viewallcourses', 'moodle');
    	$this->page->requires->js_call_amd('block_navigation/navblock', 'init', $arguments);
    	$this->page->requires->jquery();
    	$this->page->requires->jquery_plugin ( 'ui' );
    	$this->page->requires->jquery_plugin ( 'ui-css' );
    }
    
    function emarking(){ // desplegamos el contenido de eMarking
    	global $COURSE, $CFG, $PAGE;
    	if($CFG->block_uai_local_modules && !in_array('emarking',explode(',',$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	$context = $PAGE->context;
    	$course = $PAGE->course;
    	$courseid = $course->id;
    	if ($courseid==null || $courseid==1 || !has_capability('mod/assign:grade', $context)){ //checkeamos si tenemos la capacidad
    		return false;
    	}
    	$nodonewprintorder = navigation_node::create(
    			get_string('blocknewprintorder', 'block_uai'),
    			new moodle_url("/course/modedit.php", array("sr"=>0,"add"=>"emarking","section"=>0,"course"=>$courseid)), //url para enlazar y ver información de facebook
    			navigation_node::TYPE_CUSTOM,
    			null, null, new pix_icon('t/portfolioadd', get_string('newprintorder', 'mod_emarking')));
    
    	$nodomyexams = navigation_node::create(
    			get_string('blockmyexams', 'block_uai'),
    			new moodle_url("/mod/emarking/print/exams.php", array("course"=>$courseid)), //url para enlazar y ver información de facebook
    			navigation_node::TYPE_CUSTOM,
    			null, null, new pix_icon('a/view_list_active', get_string('myexams', 'mod_emarking')));
    
    	$nodocycle = navigation_node::create(
    			get_string('cycle', 'block_uai'),
    			new moodle_url("/mod/emarking/reports/cycle.php", array("course"=>$courseid)), //url para enlazar y ver información de facebook
    			navigation_node::TYPE_CUSTOM,
    			null, null, new pix_icon('i/course', get_string('cycle', 'mod_emarking')));
    	$rootnode = navigation_node::create(get_string('blockexams', 'block_uai'));
    	$rootnode->add_node($nodonewprintorder);
    	$rootnode->add_node($nodomyexams);
    	$rootnode->add_node($nodocycle);
    	return $rootnode;
    
    }
    
    /**
     * URL a local/reportes, módulo de reportes de la UAI.
     *
     * @return string URL al index del módulo reportes
     */
    function reportes(){
    	global $COURSE, $CFG, $PAGE, $USER;
    
    	if($CFG->block_uai_local_modules && !in_array('reportes',explode(',',$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	$course = $PAGE->course;
    	if(!$course || !has_capability('local/reportes:view', $PAGE->context) || $course->id <= 1)
    		return false;
    	
    	$urlreportes = new moodle_url("/local/reportes/index.php", array("courseid"=>$course->id));
    	$rootnode = navigation_node::create(
    			get_string('reportes', 'block_uai'),
    			$urlreportes,
    			navigation_node::TYPE_CUSTOM,
    			null, null, new pix_icon('t/scales', get_string('reportes', 'block_uai'))
    	);
    
    	return $rootnode;
    }
    function reserva_salas(){ //desplegamos el contenido de reserva de salas
    	global $USER, $CFG, $DB, $COURSE, $PAGE;
    	if($CFG->block_uai_local_modules
    			&& !in_array('reservasalas',explode(',',$CFG->block_uai_local_modules))) {
    				return false;
    			}
    			$nodosedes = navigation_node::create(
    					get_string('ajsedes', 'block_uai'),
    					new moodle_url("/local/reservasalas/sedes.php"),
    					navigation_node::TYPE_CUSTOM, null, null,
    					new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para ver sedes
    					$nodosalas = navigation_node::create(
    							get_string('ajmodversal', 'block_uai'),
    							new moodle_url("/local/reservasalas/salas.php"),
    							navigation_node::TYPE_CUSTOM, null, null,
    							new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para ver salas creadas
    							$nodoedificios = navigation_node::create(
    									get_string('ajmodvered', 'block_uai'),
    									new moodle_url("/local/reservasalas/edificios.php"),
    									navigation_node::TYPE_CUSTOM, null, null,
    									new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para ver edificios creados
    									/*
    									$nodohistorial = navigation_node::create(
    									get_string('historial', 'block_uai'),
    									new moodle_url("/local/reservasalas/historial.php"),
    									navigation_node::TYPE_CUSTOM, null, null,
    									new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para ver el historial de todas las reservas
    									*/
    									$nodoreservar = navigation_node::create(
    											get_string('reservar', 'block_uai'),
    											new moodle_url("/local/reservasalas/reservar.php"),
    											navigation_node::TYPE_CUSTOM, null, null,
    											new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para reservar salas
    											$nodomisreservas = navigation_node::create(
    													get_string('misreservas', 'block_uai'),
    													new moodle_url("/local/reservasalas/misreservas.php"),
    													navigation_node::TYPE_CUSTOM, null, null,
    													new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para ver las reservas de un usuario
    													$nodobloquear = navigation_node::create(
    															get_string('bloquear', 'block_uai'),
    															new moodle_url("/local/reservasalas/bloquear.php"),
    															navigation_node::TYPE_CUSTOM, null, null,
    															new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para bloquear usuarios
    															$nododesbloquear = navigation_node::create(
    																	get_string('desbloq', 'block_uai'),
    																	new moodle_url("/local/reservasalas/desbloquear.php"),
    																	navigation_node::TYPE_CUSTOM, null, null,
    																	new pix_icon('i/report', get_string('ajsedes', 'block_uai'))); //url para desbloquar usuarios
    																	/*
    																	$nodoestadisticas = navigation_node::create(
    																	get_string('statistics', 'block_uai'),//'Estadísticas',
    																	new moodle_url("/local/reservasalas/estadisticas.php"),
    																	navigation_node::TYPE_CUSTOM, null, null,
    																	new pix_icon('i/report', get_string('ajsedes', 'block_uai')));
    																	*/
    																	$nodoreservasporusuario = navigation_node::create(
    																			get_string('viewuserreserves', 'block_uai'),//'Ver reservas por usuario',
    																			new moodle_url("/local/reservasalas/reservasusuarios.php"),
    																			navigation_node::TYPE_CUSTOM, null, null,
    																			new pix_icon('i/report', get_string('ajsedes', 'block_uai')));
    																	$nododiagnostico = navigation_node::create(
    																			get_string('diagnostic', 'block_uai'),//'Diagnóstico',
    																			new moodle_url("/local/reservasalas/diagnostico.php"),
    																			navigation_node::TYPE_CUSTOM, null, null,
    																			new pix_icon('i/report', get_string('ajsedes', 'block_uai')));
    																	$nodoresources = navigation_node::create(
    																			get_string('urlresources', 'block_uai'),
    																			new moodle_url("/local/reservasalas/resources.php"),
    																			navigation_node::TYPE_CUSTOM, null, null,
    																			new pix_icon('i/report', get_string('ajsedes', 'block_uai')));
    																	$nodoupload = navigation_node::create(
    																			get_string('upload', 'block_uai'),//'upload',
    																			new moodle_url("/local/reservasalas/upload.php"),
    																			navigation_node::TYPE_CUSTOM, null, null,
    																			new pix_icon('i/report', get_string('ajsedes', 'block_uai')));
    																	$nodosearch = navigation_node::create(
    																			get_string('search', 'block_uai'),
    																			new moodle_url("/local/reservasalas/search.php"),
    																			navigation_node::TYPE_CUSTOM, null, null,
    																			new pix_icon('i/report', get_string('ajsedes', 'block_uai')));
    
    																	$context = context_system::instance();
    
    																	$rootnode = navigation_node::create(get_string('reservasal', 'block_uai'));
    																	$rootnode->add_node($nodoreservar);
    
    
    																	if(has_capability('local/reservasalas:advancesearch', $context)) {
    																		$rootnode->add_node($nodosearch);
    																	}
    
    																	if(has_capability('local/reservasalas:administration', $context)||
    																			has_capability('local/reservasalas:bockinginfo', $context)||
    																			has_capability('local/reservasalas:blocking', $context)) {
    																				$nodesettings = navigation_node::create(
    																						get_string('ajustesrs', 'block_uai'),
    																						null,
    																						navigation_node::TYPE_UNKNOWN
    																						);
    
    																				$rootnode->add_node($nodesettings);
    																			}
    
    																			if(has_capability('local/reservasalas:administration', $context)) {
    																				$nodesettings->add_node($nodosalas);
    																				$nodesettings->add_node($nodoedificios);
    																				$nodesettings->add_node($nodosedes);
    																				$nodesettings->add_node($nodoresources);
    																			}
    
    																			if(has_capability('local/reservasalas:bockinginfo', $context)){ //revisamos la capacidad del usuario
    																				//administrador
    																				//$nodesettings->add_node($nodohistorial);
    																				$nodesettings->add_node($nodoreservasporusuario);
    																				//$nodesettings->add_node($nodoestadisticas);
    																				$nodesettings->add_node($nododiagnostico);
    																			}
    
    																			if(has_capability('local/reservasalas:blocking', $context)){
    																				$nodeusuarios = navigation_node::create(
    																						get_string('usuarios', 'block_uai'),
    																						null,
    																						navigation_node::TYPE_UNKNOWN
    																						);
    
    																				$nodeusuarios->add_node($nododesbloquear);
    																				$nodeusuarios->add_node($nodobloquear);
    																				$rootnode->add_node($nodeusuarios);
    																			}
    																			if(isset($CFG->local_uai_debug) && $CFG->local_uai_debug==1) {
    																				if(has_capability('local/reservasalas:upload', $context)){
    																					$rootnode->add_node($nodoupload);
    																				}
    																			}
    
    																			//alumnos
    																			if(!has_capability('local/reservasalas:advancesearch', $context)) {
    																				$rootnode->add_node($nodomisreservas);
    																			}
    
    																			return $rootnode;
    }
    function print_orders(){ //desplegamos las ordenes de impresion de evaluaciones
    	global $DB, $USER, $CFG, $COURSE, $PAGE;
    
    	if($CFG->block_uai_local_modules && !in_array('emarking',explode(',',$CFG->block_uai_local_modules))) {
    		return false;
    	}
    
    	if(!has_capability('mod/emarking:printordersview', $PAGE->context))
    		return false;
    
    		$categoryid = 0;
    		if($COURSE && $COURSE->id > 1) {
    			$categoryid = $COURSE->category;
    		} elseif ($PAGE->context instanceof context_coursecat) {
    			$categoryid = intval($PAGE->context->__get('instanceid'));
    		}
    
    		if(!$categoryid) {
    			return false;
    		}
    
    		$rootnode = navigation_node::create(get_string('printorders', 'mod_emarking'));
    
    		$url = new moodle_url("/mod/emarking/print/printorders.php", array("category"=>$categoryid));
    		$nodeprintorders = navigation_node::create(
    				get_string('printorders', 'mod_emarking'),
    				$url,
    				navigation_node::TYPE_CUSTOM,
    				null, null, new pix_icon('t/print', get_string('printorders', 'mod_emarking')));
    
    		$url = new moodle_url("/mod/emarking/reports/costcenter.php", array("category"=>$categoryid));
    
    		$nodecostreport = navigation_node::create(
    				get_string('costreport', 'mod_emarking'),
    				$url,
    				navigation_node::TYPE_CUSTOM,
    				null, null, new pix_icon('t/ranges', get_string('printorders', 'mod_emarking')));
    
    		$url = new moodle_url("/mod/emarking/reports/costconfig.php", array("category"=>$categoryid));
    
    		$nodecostconfiguration = navigation_node::create(
    				get_string('costsettings', 'mod_emarking'),
    				$url,
    				navigation_node::TYPE_CUSTOM,
    				null, null, new pix_icon('a/setting', get_string('printorders', 'mod_emarking')));
    		$rootnode->add_node($nodeprintorders);
    		$rootnode->add_node($nodecostreport);
    		$rootnode->add_node($nodecostconfiguration);
    
    		return $rootnode;
    }
    function facebook(){ //Show facebook content
    	global $USER, $CFG, $DB, $COURSE;
    
    	//$context = context_block::instance($COURSE->id);
    	if($CFG->block_uai_local_modules && !in_array('facebook',explode(',',$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	$nodoconnect = navigation_node::create(
    			get_string('connect', 'block_uai'),
    			new moodle_url("/local/facebook/connect.php"), //url para enlazar y ver información de facebook
    			navigation_node::TYPE_CUSTOM,
    			null, null);
    
    	$nodoinfo = navigation_node::create(
    			get_string('info', 'block_uai'),
    			new moodle_url("/local/facebook/connect.php"), //url para enlazar y ver información de facebook
    			navigation_node::TYPE_CUSTOM,
    			null, null);
    
    	$nodoapp = navigation_node::create(
    			get_string('goapp', 'block_uai'),
    			$CFG->fbk_url,
    			navigation_node::TYPE_CUSTOM,
    			null, null);
    	$rootnode = navigation_node::create(get_string('facebook', 'block_uai'));
    	$context = context_system::instance();
    	$exist = $DB->get_record('facebook_user',array('moodleid'=>$USER->id,'status'=>'1'));
    	if($exist==false){
    		$rootnode->add_node($nodoconnect);
    			
    	} else {
    		$rootnode->add_node($nodoinfo);
    		$rootnode->add_node($nodoapp);
    		$facebook =''.$CFG->wwwroot.'/blocks/uai/img/like.png" height="20" width="20"';
    	}
    	return $rootnode;
    
    }
    // Bloque de Paperattendance.
    function paperattendance() {
    	global $COURSE, $PAGE, $CFG;
    
    	if($CFG->block_uai_local_modules && !in_array('paperattendance',explode(',',$CFG->block_uai_local_modules))) {
    		return false;
    	}
    
    	$categoryid = optional_param("categoryid", 1, PARAM_INT);
    	$context = $PAGE->context;
    
    	$rootnode = navigation_node::create(get_string('paperattendance', 'block_uai'));
    
    	//url para subir un pdf escaneado del curso
    	$uploadattendanceurl = new moodle_url("/local/paperattendance/upload.php", array("courseid" => $COURSE->id,"categoryid" => $categoryid));
    	$nodouploadattendance = navigation_node::create(
    			get_string('uploadpaperattendance', 'block_uai'),
    			$uploadattendanceurl,
    			navigation_node::TYPE_CUSTOM,
    			null, null, new pix_icon('i/backup', get_string('uploadpaperattendance', 'block_uai')));
    
    	//url para agregar, editar y eliminar modulos
    	$modulesattendanceurl = new moodle_url("/local/paperattendance/modules.php");
    	$nodomodulesattendance = navigation_node::create(
    			get_string('modulespaperattendance', 'block_uai'),
    			$modulesattendanceurl,
    			navigation_node::TYPE_CUSTOM,
    			null, null, new pix_icon('i/calendar', get_string('modulespaperattendance', 'block_uai')));
    
    	//url para descargar pdf del listado del curso para tomar asistencia
    	$printattendanceurl = new moodle_url("/local/paperattendance/print.php", array("courseid" => $COURSE->id,"categoryid" =>$categoryid));
    	$nodoprintattendance = navigation_node::create(
    			get_string('printpaperattendance', 'block_uai'),
    			$printattendanceurl,
    			navigation_node::TYPE_CUSTOM,
    			null, null, new pix_icon('e/print', get_string('printpaperattendance', 'block_uai')));
    
    	//url para ver el historial de pdfs escaneados del curso y sus asistencias digitales
    	$historyattendanceurl = new moodle_url("/local/paperattendance/history.php", array("courseid" => $COURSE->id));
    	$nodohistoryattendance = navigation_node::create(
    			get_string('historypaperattendance', 'block_uai'),
    			$historyattendanceurl,
    			navigation_node::TYPE_CUSTOM,
    			null, null, new pix_icon('i/grades', get_string('historypaperattendance', 'block_uai')));
    
    	//url para ver las discusiones de asistencia pendientes
    	$discussionattendanceurl = new moodle_url("/local/paperattendance/discussion.php", array(
    			"courseid" => $COURSE->id
    	));
    	$nododiscussionattendance = navigation_node::create(
    			get_string('discussionpaperattendance', 'block_uai'),
    			$discussionattendanceurl,
    			navigation_node::TYPE_CUSTOM,
    			null, null, new pix_icon('i/cohort', get_string('discussionpaperattendance', 'block_uai')));
    
    	if(has_capability('local/paperattendance:upload', $context)){
    		$rootnode->add_node($nodouploadattendance);
    	}
    	if(has_capability('local/paperattendance:modules', $context)){
    		$rootnode->add_node($nodomodulesattendance);
    	}
    
    	if($COURSE->id > 1 && $COURSE->idnumber != NULL){
    		if(has_capability('local/paperattendance:print', $context) || has_capability('local/paperattendance:printsecre', $context)){
    			$rootnode->add_node($nodoprintattendance);
    		}
    		if(has_capability('local/paperattendance:history', $context)){
    			$rootnode->add_node($nodohistoryattendance);
    			$rootnode->add_node($nododiscussionattendance);
    		}
    	}
    
    	return $rootnode;
    }
    
    function syncomega(){
    	global $CFG;
    
    	if($CFG->block_uai_local_modules
    			&& !in_array('syncomega',explode(',',$CFG->block_uai_local_modules))) {
    				return false;
    			}
    			$nodohistorial = navigation_node::create(
    					get_string('synchistory', 'block_uai'),
    					new moodle_url("/local/sync/history.php"),
    					navigation_node::TYPE_CUSTOM, null, null,
    					new pix_icon('i/siteevent', get_string('synchistory', 'block_uai'))); //url para reservar salas;
    					$nodocreate = navigation_node::create(
    							get_string('synccreate', 'block_uai'),
    							new moodle_url("/local/sync/create.php"),
    							navigation_node::TYPE_CUSTOM, null, null,
    							new pix_icon('e/new_document', get_string('synccreate', 'block_uai')));
    					$nodorecord = navigation_node::create(
    							get_string('syncrecord', 'block_uai'),
    							new moodle_url("/local/sync/record.php"),
    							navigation_node::TYPE_CUSTOM, null, null,
    							new pix_icon('e/fullpage', get_string('syncrecord', 'block_uai')));
    					$context = context_system::instance();
    					if(has_capability('local/sync:history', $context)) {
    						$rootnode = navigation_node::create(get_string('syncomega', 'block_uai'));
    						$rootnode->add_node($nodocreate);
    						$rootnode->add_node($nodorecord);
    						$rootnode->add_node($nodohistorial);
    						return $rootnode;
    					}
    					else{
    						return false;
    					}
    }
    
    protected function emarking_new() {
    	global $CFG, $PAGE;
    	
    	if($CFG->block_uai_local_modules && !in_array('emarking',explode(',',$CFG->block_uai_local_modules))) {
			return false;
		}
    	
    	$context = $PAGE->context;
    	$course = $PAGE->course;
    	$courseid = $course->id;
    	
    	if($courseid == null || $courseid == 1 || !has_capability('mod/assign:grade', $context)) {
    		return false;
    	}
    	
    	$root = array();
    	
    	$root["string"] = get_string('blockexams', 'block_uai');
    	$root["icon"] =   $CFG->dirroot."\mod\emarking\pix\icon.png";
    	
    	$root["newprintorder"] = array();
    	$root["newprintorder"]["string"] = get_string('blocknewprintorder', 'block_uai');
    	$root["newprintorder"]["url"]	 = new moodle_url("/course/modedit.php", array("sr" => 0, "add" => "emarking", "section" => 0, "course" => $courseid));
    	$root["newprintorder"]["icon"]	 = 't/portfolioadd';
    	
    	$root["myexams"] = array();
    	$root["myexams"]["string"] = get_string('blockmyexams', 'block_uai');
    	$root["myexams"]["url"]	   = new moodle_url("/mod/emarking/print/exams.php", array("course" => $courseid));
    	$root["myexams"]["icon"]   = 'a/view_list_active';
    	
    	$root["cycle"] = array();
    	$root["cycle"]["string"] = get_string('cycle', 'block_uai');
    	$root["cycle"]["url"]	 = new moodle_url("/mod/emarking/reports/cycle.php", array("course" => $courseid));
    	$root["cycle"]["icon"]	 = 'i/course';
    	
    	return $root;
    }
    
    protected function print_orders_new() {
    	global $DB, $USER, $CFG, $COURSE, $PAGE;
    	
    	if($CFG->block_uai_local_modules && !in_array('emarking',explode(',',$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	
    	if(!has_capability('mod/emarking:printordersview', $PAGE->context)) {
    		return false;
    	}
    	
    	$categoryid = 0;
    	if($COURSE && $COURSE->id > 1) {
    		$categoryid = $COURSE->category;
    	} elseif ($PAGE->context instanceof context_coursecat) {
    		$categoryid = intval($PAGE->context->__get('instanceid'));
    	}
    	
    	if(!$categoryid) {
    		return false;
    	}
    	
    	$root = array();
    	$root["string"] = get_string('printorders', 'mod_emarking');
    	
    	$root["printorders"] = array();
    	$root["printorders"]["string"] = get_string('printorders', 'mod_emarking');
    	$root["printorders"]["url"] =	 new moodle_url("/mod/emarking/print/printorders.php", array("category"=>$categoryid));
    	$root["printorders"]["icon"] =	 't/print';
    	
    	$root["costreport"] = array();
    	$root["costreport"]["string"] = get_string('costreport', 'mod_emarking');
    	$root["costreport"]["url"] =	new moodle_url("/mod/emarking/reports/costcenter.php", array("category"=>$categoryid));
    	$root["costreport"]["icon"] =	't/ranges';
    	
    	$root["costsettings"] = array();
    	$root["costsettings"]["string"] = get_string('costsettings', 'mod_emarking');
    	$root["costsettings"]["url"] =	  new moodle_url("/mod/emarking/reports/costconfig.php", array("category"=>$categoryid));
    	$root["costsettings"]["icon"] =	  'a/setting';
    	
    	return $root;
    }
    
    protected function reserva_salas_new() {
    	global $USER, $CFG, $DB, $COURSE, $PAGE;
    	
    	if($CFG->block_uai_local_modules && !in_array('reservasalas',explode(',',$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	
    	$context = context_system::instance();
    	$root = array();
    	
    	$root["string"] = get_string('reservasal', 'block_uai');
    	
    	$root["book"] = array();
    	$root["book"]["string"] = get_string('reservar', 'block_uai');
    	$root["book"]["url"] =	  new moodle_url("/local/reservasalas/reservar.php");
    	$root["book"]["icon"] =	  'i/report';
    	
    	if(!has_capability('local/reservasalas:advancesearch', $context)) {
    		$root["booked"] = array();
    		$root["booked"]["string"] = get_string('misreservas', 'block_uai');
    		$root["booked"]["url"] =	new moodle_url("/local/reservasalas/misreservas.php");
    		$root["booked"]["icon"] =	'i/report';
    	} else {
    		$root["search"] = array();
    		$root["search"]["string"] =	get_string('search', 'block_uai');
    		$root["search"]["url"] =	new moodle_url("/local/reservasalas/search.php");
    		$root["search"]["icon"] =	'i/report';
    	}
    	
    	if(has_capability('local/reservasalas:administration', $context) || 
    			has_capability('local/reservasalas:bockinginfo', $context) ||
				has_capability('local/reservasalas:blocking', $context)) {
			$root["settings"] = array();
			$root["settings"]["string"] = 	get_string('ajustesrs', 'block_uai');
			$root["settings"]["icon"] =		'i/settings';
		}
		
		if(has_capability('local/reservasalas:administration', $context)) {
			$root["settings"]["rooms"] = array();
			$root["settings"]["rooms"]["string"] = get_string('ajmodversal', 'block_uai');
			$root["settings"]["rooms"]["url"] =	   new moodle_url("/local/reservasalas/salas.php");
			$root["settings"]["rooms"]["icon"] =   'i/report';
    	
	    	$root["settings"]["buildings"] = array();
	    	$root["settings"]["buildings"]["string"] = get_string('ajmodvered', 'block_uai');
	    	$root["settings"]["buildings"]["url"] =	   new moodle_url("/local/reservasalas/edificios.php");
	    	$root["settings"]["buildings"]["icon"] =   'i/report';
    	
	    	$root["settings"]["campus"] = array();
	    	$root["settings"]["campus"]["string"] = get_string('ajsedes', 'block_uai');
	    	$root["settings"]["campus"]["url"] =	new moodle_url("/local/reservasalas/sedes.php");
	    	$root["settings"]["campus"]["icon"] =   'i/report';
    	
	    	$root["settings"]["resources"] = array();
	    	$root["settings"]["resources"]["string"] = get_string('urlresources', 'block_uai');
	    	$root["settings"]["resources"]["url"] =	   new moodle_url("/local/reservasalas/resources.php");
	    	$root["settings"]["resources"]["icon"] =   'i/report';
		}
		
		if(has_capability('local/reservasalas:bockinginfo', $context)) {
			$root["settings"]["userbooks"] = array();
			$root["settings"]["userbooks"]["string"] = get_string('viewuserreserves', 'block_uai');
			$root["settings"]["userbooks"]["url"] =	   new moodle_url("/local/reservasalas/reservasusuarios.php");
			$root["settings"]["userbooks"]["icon"] =   'i/report';
    	
	    	$root["settings"]["diagnostic"] = array();
	    	$root["settings"]["diagnostic"]["string"] =	get_string('diagnostic', 'block_uai');
	    	$root["settings"]["diagnostic"]["url"] =	new moodle_url("/local/reservasalas/diagnostico.php");
	    	$root["settings"]["diagnostic"]["icon"] =	'i/report';
		}
		
		if(has_capability('local/reservasalas:blocking', $context)) {
			$root["settings"]["usersettings"] = array();
			$root["settings"]["usersettings"]["string"] = get_string('usuarios', 'block_uai');
			$root["settings"]["usersettings"]["icon"] = 'i/role';
    	
	    	$root["settings"]["usersettings"]["block"] = array();
	    	$root["settings"]["usersettings"]["block"]["string"] = get_string('bloquear', 'block_uai');
	    	$root["settings"]["usersettings"]["block"]["url"] =	   new moodle_url("/local/reservasalas/bloquear.php");
	    	$root["settings"]["usersettings"]["block"]["icon"] =   'i/report';
    	
	    	$root["settings"]["usersettings"]["unblock"] = array();
	    	$root["settings"]["usersettings"]["unblock"]["string"] = get_string('desbloq', 'block_uai');
	    	$root["settings"]["usersettings"]["unblock"]["url"] =	 new moodle_url("/local/reservasalas/desbloquear.php");
	    	$root["settings"]["usersettings"]["unblock"]["icon"] =	 'i/report';
		}
		
		if(isset($CFG->local_uai_debug) && $CFG->local_uai_debug == 1) {
			if(has_capability('local/reservasalas:upload', $context)) {
				$root["upload"] = array();
				$root["upload"]["string"] =	get_string('upload', 'block_uai');
				$root["upload"]["url"] =	new moodle_url("/local/reservasalas/upload.php");
				$root["upload"]["icon"] =	'i/report';
			}
		}
    	
    	return $root;
    }
    
    protected function paperattendance_new() {
    	global $COURSE, $PAGE, $CFG;
    	
    	if($CFG->block_uai_local_modules && !in_array('paperattendance',explode(',',$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	
    	$categoryid = optional_param("categoryid", 1, PARAM_INT);
    	$context = $PAGE->context;
    	
    	$root = array();
    	
    	if(has_capability('local/paperattendance:upload', $context)){
    		$root["upload"] = array();
    		$root["upload"]["string"] = get_string('uploadpaperattendance', 'block_uai');
    		$root["upload"]["url"] = 	new moodle_url("/local/paperattendance/upload.php", array("courseid" => $COURSE->id,"categoryid" => $categoryid));
    		$root["upload"]["icon"] = 	'i/backup';
    	}
    	 
    	if(has_capability('local/paperattendance:modules', $context)){
    		$root["modules"] = array();
    		$root["modules"]["string"] = get_string('modulespaperattendance', 'block_uai');
    		$root["modules"]["url"] =	 new moodle_url("/local/paperattendance/modules.php");
    		$root["modules"]["icon"] =	 'i/calendar';
    	}
    	 
    	if($COURSE->id > 1 && $COURSE->idnumber != NULL){
    		if(has_capability('local/paperattendance:print', $context) || has_capability('local/paperattendance:printsecre', $context)){
    			$root["print"] = array();
    			$root["print"]["string"] = get_string('printpaperattendance', 'block_uai');
    			$root["print"]["url"] =	   new moodle_url("/local/paperattendance/print.php", array("courseid" => $COURSE->id, "categoryid"  => $categoryid));
    			$root["print"]["icon"] =   'e/print';
    		}
    		if(has_capability('local/paperattendance:history', $context)){
    			$root["history"] = array();
    			$root["history"]["string"] = get_string('historypaperattendance', 'block_uai');
    			$root["history"]["url"] =	 new moodle_url("/local/paperattendance/history.php", array("courseid" => $COURSE->id));
    			$root["history"]["icon"] =	 'i/grades';
    	
    			$root["discussion"] = array();
    			$root["discussion"]["string"] = get_string('discussionpaperattendance', 'block_uai');
    			$root["discussion"]["url"] =	new moodle_url("/local/paperattendance/discussion.php", array("courseid" => $COURSE->id));
    			$root["discussion"]["icon"] =	'i/cohort';
    		}
    	}
    	
    	if(empty($root)) {
    		return false;
    	}
    	
    	$root["string"] = get_string('paperattendance', 'block_uai');
    	
    	return $root;
    }
    
    protected function facebook_new() {
    	global $USER, $CFG, $DB;
    	
    	if($CFG->block_uai_local_modules && !in_array('facebook',explode(',',$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	
    	$root = array();
    	$root["string"] = get_string('facebook', 'block_uai');
    	
    	$exist = $DB->get_record('facebook_user', array('moodleid' => $USER->id, 'status' => '1'));
    	if($exist == false) {
    		$root["connect"] = array();
    		$root["connect"]["string"] = get_string('connect', 'block_uai');
    		$root["connect"]["url"] =	 new moodle_url("/local/facebook/connect.php");
    		$root["connect"]["icon"] =	 'i/mnethost';
    	} else {
    		$root["info"] = array();
    		$root["info"]["string"] = get_string('info', 'block_uai');
    		$root["info"]["url"] =	  new moodle_url("/local/facebook/connect.php");
    		$root["info"]["icon"] =	  'i/info';
    		
    		$root["app"] = array();
    		$root["app"]["string"] = get_string('goapp', 'block_uai');
    		$root["app"]["url"] =	 $CFG->fbk_url;
    		$root["app"]["icon"] =	 't/right';
    	}
    	return $root;
    }
    
    protected function syncomega_new() {
    	global $CFG;
    	
    	if($CFG->block_uai_local_modules && !in_array('syncomega',explode(',',$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	
    	$context = context_system::instance();
    	if(!has_capability('local/sync:history', $context)) {
    		return false;
    	}
    	
    	$root = array();
    	$root["string"] = get_string('syncomega', 'block_uai');
    	
    	$root["create"] = array();
    	$root["create"]["string"] = get_string('synccreate', 'block_uai');
    	$root["create"]["url"] =	new moodle_url("/local/sync/create.php");
    	$root["create"]["icon"] =	'e/new_document';
    	
    	$root["records"] = array();
    	$root["records"]["string"] = get_string('syncrecord', 'block_uai');
    	$root["records"]["url"] =	 new moodle_url("/local/sync/record.php");
    	$root["records"]["icon"] =	 'e/fullpage';
    	
    	$root["history"] = array();
    	$root["history"]["string"] = get_string('synchistory', 'block_uai');
    	$root["history"]["url"] =	 new moodle_url("/local/sync/history.php");
    	$root["history"]["icon"] =	 'i/siteevent';
    	
    	return $root;
    }
    
    protected function dashboard_new() {
    	global $CFG;
    	
    	if($CFG->block_uai_local_modules && !in_array('dashboard', explode(',',$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	
    	$context = context_system::instance();
    	if(!has_capability('local/dashboard:view', $context)) {
    		return false;
    	}
    	
    	$root = array();
    	$root["string"] = "Dashboard";
    	
    	$root["dashboard"]["string"] = "Dashboard";
    	$root["dashboard"]["url"] = new moodle_url('/local/dashboard/frontpage.php');
    	$root["dashboard"]["icon"] = 'i/scales';
    	
    	return $root;
    }
    
    function get_content() {
    	global $CFG, $PAGE;
    	
    	// Check if content is already generated. If so, doesn't do it again
    	if ($this->content !== null) {
    		return $this->content;
    	}
    	
    	$PAGE->requires->jquery();
    	$PAGE->requires->jquery_plugin ( 'ui' );
    	$PAGE->requires->jquery_plugin ( 'ui-css' );
    	$this->content = new stdClass();
    	
    	$version = $CFG->block_uai_version;
    	
    	if($version == "30") {
    		$root = navigation_node::create(
    				"UAI",
    				null,
    				navigation_node::TYPE_ROOTNODE,
    				null,
    				null
    		);
    		
    		if($nodereservasalas = $this->reserva_salas()){
    			$root->add_node($nodereservasalas);
    		}
    		if($nodeprintorders = $this->print_orders()){
    			$root->add_node($nodeprintorders);
    		}
    		if($nodeemarking = $this->emarking()){
    			$root->add_node($nodeemarking);
    		}
    		if($nodefacebook = $this->facebook()){
    			$root->add_node($nodefacebook);
    		}
    		if($nodereportes = $this->reportes()){
    			$root->add_node($nodereportes);
    		}
    		if($nodepaperattendance = $this->paperattendance()){
    			$root->add_node($nodepaperattendance);
    		}
    		if($nodesyncomega = $this->syncomega()){
    			$root->add_node($nodesyncomega);
    		}
    		
    		$renderer = $this->page->get_renderer('block_uai');
    		$this->content->text = $renderer->uai_tree($root);
    		$this->content->footer = '';
    		return $this->content;
    	} else if($version == "31") {
    		$menu = array();
    		 
    		if($emarking = $this->emarking_new()) {
    			$menu[] = $emarking;
    		}
    		 
    		if($printorders = $this->print_orders_new()) {
    			$menu[] = $printorders;
    		}
    		 
    		if($reservasalas = $this->reserva_salas_new()) {
    			$menu[] = $reservasalas;
    		}
    		 
    		if($facebook = $this->facebook_new()) {
    			$menu[] = $facebook;
    		}
    		 
    		if($syncomega = $this->syncomega_new()) {
    			$menu[] = $syncomega;
    		}
    		 
    		if($paperattendance = $this->paperattendance_new()) {
    			$menu[] = $paperattendance;
    		}
    		 
    		$this->content->text = $this->block_uai_renderer($menu);
    		// Set content generated to true so that we know it has been done
    		$this->contentgenerated = true;
    		return $this->content;
    	}
    }
    
    /*
     * Produces a list of collapsible lists for each plugin to be displayed
     * 
     * @param array $plugins containing data sub-arrays of every plugin
     * @return html string to be inserted directly into the block
     */
    protected function block_uai_renderer($plugins) {
    	global $OUTPUT;
    	$content = array();
    	
    	$id = 0;
    	
    	// For each plugin to be shown, make a collapsible list
    	foreach($plugins as $plugin) {
    		$elementhtml = "";
    		
    		// For each element in the plugin, create a collapsable list element
    		foreach($plugin as $element => $values) {
    			// The "string" element is the plugin's name
    			if($element != "string" && $element != "settings" && $element != "icon") {
    				// Define the icon along with the title & link to its page
    				$html = $OUTPUT->pix_icon($values["icon"], "")." ".html_writer::tag("span", $values["string"]);
    				$html = html_writer::tag("a", $html, array("href" => $values["url"]));
    				
    				// Place it in a "li" element from the list
    				$html = html_writer::tag("li", $html);
    			
    				$elementhtml .= $html;
    			} else if($element == "settings") {
    				// The settings element is a sub-collapsible list, with its own elements
    				$settingshtml = "";
    				
    				// Loop over the settings elements (max: 6 loops)
    				foreach($values as $setting => $value) {
    					if($setting != "string" && $setting != "icon" && $setting != "usersettings") {
	    					$html = $OUTPUT->pix_icon($value["icon"], "")." ".html_writer::tag("span", $value["string"]);
	    					$html = html_writer::tag("a", $html, array("href" => $value["url"]));
	    					
	    					$html = html_writer::tag("li", $html);
	    					
	    					$settingshtml .= $html;
    					} else if($setting == "usersettings") {
    						// The user settings element is also a sub-collapsible list, so it needs another loop
    						$usersettingshtml = "";
    						foreach($value as $usersetting => $uservalue) {
    							// Assemble each sub element in user' settings
    							if($usersetting != "string" && $usersetting != "icon") {
    								$html = $OUTPUT->pix_icon($uservalue["icon"], "")." ".html_writer::tag("span", $uservalue["string"]);
    								$html = html_writer::tag("a", $html, array("href" => $uservalue["url"]));
    								
    								$html = html_writer::tag("li", $html);
    								
    								$usersettingshtml .= $html;
    							}
    						}
    							
    						$usersettingshtml = html_writer::tag("ul", $usersettingshtml, array(
    								"class" => "nav nav-list collapse",
    								"id" => "us".$id,
    								"style" => "padding-left: 20px;"
    						));
    						$usersettingsspan = $OUTPUT->pix_icon($value["icon"], "")." ".html_writer::tag("span", $value["string"]);
    						$usersettingsspan = html_writer::tag("a", $usersettingsspan, array("href" => "", "style" => "text-decoration: none !important; pointer-events: none;"));
    						$usersettingsspan = html_writer::tag("li", $usersettingsspan, array(
    								"data-toggle" => "collapse",
    								"data-target" => "#us".$id,
    								"style" => "cursor: pointer; padding-top: 0px !important; padding-bottom: 0px !important;"
    						));
    							
    						$usersettingshtml = html_writer::tag("li", $usersettingshtml);
    						$elementhtml .= $usersettingsspan.$usersettingshtml;
    					}
    				}
    				
    				$settingshtml = html_writer::tag("ul", $settingshtml, array(
    						"class" => "nav nav-list collapse", 
    						"id" => "s".$id,
    						"style" => "padding-left: 20px;"
    				));
    				$settingsspan = $OUTPUT->pix_icon($values["icon"], "")." ".html_writer::tag("span", $values["string"]);
    				$settingsspan = html_writer::tag("a", $settingsspan, array("href" => "", "style" => "text-decoration: none !important; pointer-events: none;"));
    				$settingsspan = html_writer::tag("li", $settingsspan, array(
    						"data-toggle" => "collapse",
    						"data-target" => "#s".$id,
    						"style" => "cursor: pointer;"
    				));
    				
    				$settingshtml = html_writer::tag("li", $settingshtml);
    				$elementhtml .= $settingsspan.$settingshtml;
    			}
    		}
    		
    		// Get all the list components above in one collapsable list delimeter ("ul" tag)
    		$pluginhtml = html_writer::tag("ul", $elementhtml, array("class" => "nav nav-list collapse", "id" => $id));
    		
    		// Then make it part of the plugins list
    		$pluginspan = html_writer::tag("span", $plugin["string"]);
    		$pluginspan = html_writer::tag("li", $pluginspan, array(
    				"data-toggle" => "collapse", 
    				"data-target" => "#".$id,
    				"style" => "cursor: pointer;"
    		));
    		
    		$pluginhtml = html_writer::tag("li", $pluginhtml);
    		
    		// Save each plugin's content in an array to be displayed later
    		$content[] = $pluginspan.$pluginhtml;
    		
    		// This id is used as each element's id for collapse toggling
    		$id++;
    	}
    	
    	return html_writer::tag("ul", implode("", $content), array("class" => "nav nav-list"));
    }
    
    protected function get_navigation() {

    }
    
    public function html_attributes() {

    }
    
    public function get_aria_role() {
    	return 'navigation';
    }
}