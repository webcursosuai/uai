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
    	return array("all" => true);
    }
    
    function instance_allow_config() {
    	return true;
    }
    
    function  instance_can_be_hidden() {
    	return false;
    }
    
    function instance_can_be_docked() {
    	return (parent::instance_can_be_docked() && (empty($this->config->enabledock) || $this->config->enabledock=="yes"));
    }
    
    function get_required_javascript() {
    	parent::get_required_javascript();
    	$arguments = array(
    			"instanceid" => $this->instance->id
    	);
    	$this->page->requires->string_for_js("viewallcourses", "moodle");
    	//$this->page->requires->js_call_amd("block_navigation/navblock", "init", $arguments);
    	$this->page->requires->jquery();
    	$this->page->requires->jquery_plugin ( "ui" );
    	$this->page->requires->jquery_plugin ( "ui-css" );
    }
    
    protected function emarking() {
    	global $CFG, $PAGE;
    	
    	if($CFG->block_uai_local_modules && !in_array("emarking",explode(",",$CFG->block_uai_local_modules))) {
			return false;
		}
    	
    	$context = $PAGE->context;
    	$course = $PAGE->course;
    	$courseid = $course->id;
    	
    	if($courseid == null || $courseid == 1 || !has_capability("mod/assign:grade", $context)) {
    		return false;
    	}
    	
    	$root = array();
    	
    	$root["string"] = get_string("blockexams", "block_uai");
    	$root["icon"] =   "emarking.png";
    	
    	$root["newprintorder"] = array();
    	$root["newprintorder"]["string"] = get_string("blocknewprintorder", "block_uai");
    	$root["newprintorder"]["url"]	 = new moodle_url("/course/modedit.php", array("sr" => 0, "add" => "emarking", "section" => 0, "course" => $courseid));
    	$root["newprintorder"]["icon"]	 = "t/portfolioadd";
    	
    	$root["myexams"] = array();
    	$root["myexams"]["string"] = get_string("blockmyexams", "block_uai");
    	$root["myexams"]["url"]	   = new moodle_url("/mod/emarking/print/exams.php", array("course" => $courseid));
    	$root["myexams"]["icon"]   = "a/view_list_active";
    	
    	$root["cycle"] = array();
    	$root["cycle"]["string"] = get_string("cycle", "block_uai");
    	$root["cycle"]["url"]	 = new moodle_url("/mod/emarking/reports/cycle.php", array("course" => $courseid));
    	$root["cycle"]["icon"]	 = "i/course";
    	
    	return $root;
    }
    
    protected function print_orders() {
    	global $DB, $USER, $CFG, $COURSE, $PAGE;
    	
    	if($CFG->block_uai_local_modules && !in_array("emarking",explode(",",$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	
    	if(!has_capability("mod/emarking:printordersview", $PAGE->context)) {
    		return false;
    	}
    	
    	$categoryid = 0;
    	if($COURSE && $COURSE->id > 1) {
    		$categoryid = $COURSE->category;
    	} elseif ($PAGE->context instanceof context_coursecat) {
    		$categoryid = intval($PAGE->context->__get("instanceid"));
    	}
    	
    	if(!$categoryid) {
    		return false;
    	}
    	
    	$root = array();
    	$root["string"] = get_string("printorders", "mod_emarking");
    	$root["icon"] =   "orders.png";
    	
    	$root["printorders"] = array();
    	$root["printorders"]["string"] = get_string("printorders", "mod_emarking");
    	$root["printorders"]["url"] =	 new moodle_url("/mod/emarking/print/printorders.php", array("category"=>$categoryid));
    	$root["printorders"]["icon"] =	 "t/print";
    	
    	$root["costreport"] = array();
    	$root["costreport"]["string"] = get_string("costreport", "mod_emarking");
    	$root["costreport"]["url"] =	new moodle_url("/mod/emarking/reports/costcenter.php", array("category"=>$categoryid));
    	$root["costreport"]["icon"] =	"t/ranges";
    	
    	$root["costsettings"] = array();
    	$root["costsettings"]["string"] = get_string("costsettings", "mod_emarking");
    	$root["costsettings"]["url"] =	  new moodle_url("/mod/emarking/reports/costconfig.php", array("category"=>$categoryid));
    	$root["costsettings"]["icon"] =	  "a/setting";
    	
    	return $root;
    }
    
    protected function reserva_salas() {
    	global $USER, $CFG, $DB, $COURSE, $PAGE;
    	
    	if($CFG->block_uai_local_modules && !in_array("reservasalas",explode(",",$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	
    	$context = context_system::instance();
    	$root = array();
    	
    	$root["string"] = get_string("reservasal", "block_uai");
    	$root["icon"] =   "rooms.png";
    	
    	$root["book"] = array();
    	$root["book"]["string"] = get_string("reservar", "block_uai");
    	$root["book"]["url"] =	  new moodle_url("/local/reservasalas/reservar.php");
    	$root["book"]["icon"] =	  "i/report";
    	
    	if(!has_capability("local/reservasalas:advancesearch", $context)) {
    		$root["booked"] = array();
    		$root["booked"]["string"] = get_string("misreservas", "block_uai");
    		$root["booked"]["url"] =	new moodle_url("/local/reservasalas/misreservas.php");
    		$root["booked"]["icon"] =	"i/report";
    	} else {
    		$root["search"] = array();
    		$root["search"]["string"] =	get_string("search", "block_uai");
    		$root["search"]["url"] =	new moodle_url("/local/reservasalas/search.php");
    		$root["search"]["icon"] =	"i/report";
    	}
    	
    	if(has_capability("local/reservasalas:administration", $context) || 
    			has_capability("local/reservasalas:bockinginfo", $context) ||
				has_capability("local/reservasalas:blocking", $context)) {
			$root["settings"] = array();
			$root["settings"]["string"] = 	get_string("ajustesrs", "block_uai");
			$root["settings"]["icon"] =		"i/settings";
		}
		
		if(has_capability("local/reservasalas:administration", $context)) {
			$root["settings"]["rooms"] = array();
			$root["settings"]["rooms"]["string"] = get_string("ajmodversal", "block_uai");
			$root["settings"]["rooms"]["url"] =	   new moodle_url("/local/reservasalas/salas.php");
			$root["settings"]["rooms"]["icon"] =   "i/report";
    	
	    	$root["settings"]["buildings"] = array();
	    	$root["settings"]["buildings"]["string"] = get_string("ajmodvered", "block_uai");
	    	$root["settings"]["buildings"]["url"] =	   new moodle_url("/local/reservasalas/edificios.php");
	    	$root["settings"]["buildings"]["icon"] =   "i/report";
    	
	    	$root["settings"]["campus"] = array();
	    	$root["settings"]["campus"]["string"] = get_string("ajsedes", "block_uai");
	    	$root["settings"]["campus"]["url"] =	new moodle_url("/local/reservasalas/sedes.php");
	    	$root["settings"]["campus"]["icon"] =   "i/report";
    	
	    	$root["settings"]["resources"] = array();
	    	$root["settings"]["resources"]["string"] = get_string("urlresources", "block_uai");
	    	$root["settings"]["resources"]["url"] =	   new moodle_url("/local/reservasalas/resources.php");
	    	$root["settings"]["resources"]["icon"] =   "i/report";
		}
		
		if(has_capability("local/reservasalas:bockinginfo", $context)) {
			$root["settings"]["userbooks"] = array();
			$root["settings"]["userbooks"]["string"] = get_string("viewuserreserves", "block_uai");
			$root["settings"]["userbooks"]["url"] =	   new moodle_url("/local/reservasalas/reservasusuarios.php");
			$root["settings"]["userbooks"]["icon"] =   "i/report";
    	
	    	$root["settings"]["diagnostic"] = array();
	    	$root["settings"]["diagnostic"]["string"] =	get_string("diagnostic", "block_uai");
	    	$root["settings"]["diagnostic"]["url"] =	new moodle_url("/local/reservasalas/diagnostico.php");
	    	$root["settings"]["diagnostic"]["icon"] =	"i/report";
		}
		
		if(has_capability("local/reservasalas:blocking", $context)) {
			$root["settings"]["usersettings"] = array();
			$root["settings"]["usersettings"]["string"] = get_string("usuarios", "block_uai");
			$root["settings"]["usersettings"]["icon"] = "i/role";
   	
	    	$root["settings"]["usersettings"]["block"] = array();
	    	$root["settings"]["usersettings"]["block"]["string"] = get_string("bloquear", "block_uai");
	    	$root["settings"]["usersettings"]["block"]["url"] =	   new moodle_url("/local/reservasalas/bloquear.php");
	    	$root["settings"]["usersettings"]["block"]["icon"] =   "i/report";
    	
	    	$root["settings"]["usersettings"]["unblock"] = array();
	    	$root["settings"]["usersettings"]["unblock"]["string"] = get_string("desbloq", "block_uai");
	    	$root["settings"]["usersettings"]["unblock"]["url"] =	 new moodle_url("/local/reservasalas/desbloquear.php");
	    	$root["settings"]["usersettings"]["unblock"]["icon"] =	 "i/report";
		}
		
		if(isset($CFG->local_uai_debug) && $CFG->local_uai_debug == 1) {
			if(has_capability("local/reservasalas:upload", $context)) {
				$root["upload"] = array();
				$root["upload"]["string"] =	get_string("upload", "block_uai");
				$root["upload"]["url"] =	new moodle_url("/local/reservasalas/upload.php");
				$root["upload"]["icon"] =	"i/report";
			}
		}
    	
    	return $root;
    }
    
    protected function paperattendance() {
 		global $COURSE, $PAGE, $CFG, $DB, $USER;
 		
 		if($CFG->block_uai_local_modules && !in_array("paperattendance",explode(",",$CFG->block_uai_local_modules))) {
 			return false;
 		}
 		
 		$categoryid = optional_param("categoryid", $CFG->block_uai_categoryid, PARAM_INT);
 		$context = $PAGE->context;
 		
 		//new feature for the secretary to see printsearch and upload from everywhere
 		$sqlcategory = "SELECT cc.*
					FROM {course_categories} cc
					INNER JOIN {role_assignments} ra ON (ra.userid = ?)
					INNER JOIN {role} r ON (r.id = ra.roleid AND r.shortname = ?)
					INNER JOIN {context} co ON (co.id = ra.contextid  AND  co.instanceid = cc.id  )";
 		
 		$categoryparams = array($USER->id, "secrepaper");
 		
 		$categorys = $DB->get_records_sql($sqlcategory, $categoryparams);
 		$categoryscount = count($categorys);
 		$is_secretary = 0;
 		if($categoryscount > 0){
 			$is_secretary = 1;
 		}
 		
 		$root = array();
		
 		if(has_capability("local/paperattendance:upload", $context) || $is_secretary){
 			$root["upload"] = array();
			$root["upload"]["string"] = get_string("uploadpaperattendance", "block_uai");
 			$root["upload"]["url"] = 	new moodle_url("/local/paperattendance/upload.php", array("courseid" => $COURSE->id,"categoryid" => $categoryid));
 			$root["upload"]["icon"] = 	"i/backup";
 		}
 		
 		if(has_capability("local/paperattendance:modules", $context)){
 			$root["modules"] = array();
 			$root["modules"]["string"] = get_string("modulespaperattendance", "block_uai");
 			$root["modules"]["url"] =	 new moodle_url("/local/paperattendance/modules.php");
 			$root["modules"]["icon"] =	 "i/calendar";
 		}
 		
 		if(has_capability("local/paperattendance:printsearch", $context) || $is_secretary){
 			$root["search"] = array();
 			$root["search"]["string"] = get_string("printsearchpaperattendance", "block_uai");
 			$root["search"]["url"] =	new moodle_url("/local/paperattendance/printsearch.php", array("courseid" => $COURSE->id,"categoryid" => $categoryid));
 			$root["search"]["icon"] =	"t/print";
 		}
 		
 		if(has_capability("local/paperattendance:missingpages", $context) || $is_secretary){
 			$root["missing"] = array();
 			$root["missing"]["string"] = get_string("missingpagespaperattendance", "block_uai");
 			$root["missing"]["url"] =	 new moodle_url("/local/paperattendance/missingpages.php");
 			$root["missing"]["icon"] =	 "i/warning";
 		}
 			
 		if($COURSE->id > 1){
 			if(has_capability("local/paperattendance:print", $context) || has_capability("local/paperattendance:printsecre", $context)){
 				$root["print"] = array();
 				if($COURSE->idnumber != NULL){
     				$root["print"]["string"] = get_string("printpaperattendance", "block_uai");
     				$root["print"]["url"] =	   new moodle_url("/local/paperattendance/print.php", array("courseid" => $COURSE->id, "categoryid"  => $categoryid));
 				}else{
 				    $root["print"]["string"] = get_string("notomegacourse", "block_uai");
 				    $root["print"]["url"] = '';
 				}
 				$root["print"]["icon"] =   "e/print";
 			}
 			if(has_capability("local/paperattendance:history", $context)){
 				$root["history"] = array();
 				if($COURSE->idnumber != NULL){
     				$root["history"]["string"] = get_string("historypaperattendance", "block_uai");
     				$root["history"]["url"] =	 new moodle_url("/local/paperattendance/history.php", array("courseid" => $COURSE->id));
 				}else{
 				    $root["history"]["string"] = get_string("notomegacourse", "block_uai");
 				    $root["history"]["url"] = '';
 				}
 				$root["history"]["icon"] =	 "i/grades";
 				
 				$root["discussion"] = array();
 				if($COURSE->idnumber != NULL){
     				$root["discussion"]["string"] = get_string("discussionpaperattendance", "block_uai");
    				$root["discussion"]["url"] =	new moodle_url("/local/paperattendance/discussion.php", array("courseid" => $COURSE->id));
 				}else{
 				    $root["discussion"]["string"] = get_string("notomegacourse", "block_uai");
 				    $root["discussion"]["url"] = '';
 				}
				$root["discussion"]["icon"] =	"i/cohort";
			}
			if(has_capability("local/paperattendance:takeattendance", $context)){
			    $root["takeattendance"] = array();
			    $root["takeattendance"]["string"] = get_string("takeattendance", "block_uai");
			    $root["takeattendance"]["url"] =	 new moodle_url("/local/paperattendance/attendance.php", array("courseid" => $COURSE->id));
			    $root["takeattendance"]["icon"] =	 "e/bullet_list";
			    
			}
		}
 		
 		if(empty($root)) {
 			return false;
 		}
 		
 		$root["string"] = get_string("paperattendance", "block_uai");
 		$root["icon"] =   "attendance.png";
 		
 		return $root;
 	}
    
    protected function facebook() {
    	global $USER, $CFG, $DB;
    	
    	if($CFG->block_uai_local_modules && !in_array("facebook",explode(",",$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	
    	$root = array();
    	$root["string"] = get_string("facebook", "block_uai");
    	$root["icon"] =   "facebook.png";
    	
    	$exist = $DB->get_record("facebook_user", array("moodleid" => $USER->id, "status" => "1"));
    	if($exist == false) {
    		$root["connect"] = array();
    		$root["connect"]["string"] = get_string("connect", "block_uai");
    		$root["connect"]["url"] =	 new moodle_url("/local/facebook/connect.php");
    		$root["connect"]["icon"] =	 "i/mnethost";
    	} else {
    		$root["info"] = array();
    		$root["info"]["string"] = get_string("info", "block_uai");
    		$root["info"]["url"] =	  new moodle_url("/local/facebook/connect.php");
    		$root["info"]["icon"] =	  "i/info";
    		
    		$root["app"] = array();
    		$root["app"]["string"] = get_string("goapp", "block_uai");
    		$root["app"]["url"] =	 $CFG->fbk_url;
    		$root["app"]["icon"] =	 "t/right";
    	}
    	return $root;
    }
    
    protected function syncomega() {
    	global $CFG;
    	
    	if($CFG->block_uai_local_modules && !in_array("syncomega",explode(",",$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	
    	$context = context_system::instance();
    	if(!has_capability("local/sync:history", $context)) {
    		return false;
    	}
    	
    	$root = array();
    	$root["string"] = get_string("syncomega", "block_uai");
    	$root["icon"] =   "omega.png";
    	
    	$root["create"] = array();
    	$root["create"]["string"] = get_string("synccreate", "block_uai");
    	$root["create"]["url"] =	new moodle_url("/local/sync/create.php");
    	$root["create"]["icon"] =	"e/new_document";
    	
    	$root["records"] = array();
    	$root["records"]["string"] = get_string("syncrecord", "block_uai");
    	$root["records"]["url"] =	 new moodle_url("/local/sync/record.php");
    	$root["records"]["icon"] =	 "e/fullpage";
    	
    	$root["history"] = array();
    	$root["history"]["string"] = get_string("synchistory", "block_uai");
    	$root["history"]["url"] =	 new moodle_url("/local/sync/history.php");
    	$root["history"]["icon"] =	 "i/siteevent";
    	
    	$root["aaddfile"] = array();
    	$root["aaddfile"]["string"] = "a add file";
    	$root["aaddfile"]["url"] =	 new moodle_url("#");
    	$root["aaddfile"]["icon"] =	 "a/add_file";
    	
    	$root["acreatefolder"] = array();
    	$root["acreatefolder"]["string"] = "a createfolder";
    	$root["acreatefolder"]["url"] =	 new moodle_url("#");
    	$root["acreatefolder"]["icon"] =	 "a/create_folder";
    	
    	$root["adownloadall"] = array();
    	$root["adownloadall"]["string"] = "a download all";
    	$root["adownloadall"]["url"] =	 new moodle_url("#");
    	$root["adownloadall"]["icon"] =	 "a/download_all";
    	
    	$root["aem1_bwgreater"] = array();
    	$root["aem1_bwgreater"]["string"] = "a em1_bwgreater";
    	$root["aem1_bwgreater"]["url"] =	 new moodle_url("#");
    	$root["aem1_bwgreater"]["icon"] =	 "a/em1_bwgreater";
    	
    	$root["aem1_greater"] = array();
    	$root["aem1_greater"]["string"] = "a em1_greater";
    	$root["aem1_greater"]["url"] =	 new moodle_url("#");
    	$root["aem1_greater"]["icon"] =	 "a/em1_greater";
    	
    	$root["aem1_lesser"] = array();
    	$root["aem1_lesser"]["string"] = "a em1_lesser";
    	$root["aem1_lesser"]["url"] =	 new moodle_url("#");
    	$root["aem1_lesser"]["icon"] =	 "a/em1_lesser";
    	
    	$root["aem1_raquo"] = array();
    	$root["aem1_raquo"]["string"] = "a em1_raquo";
    	$root["aem1_raquo"]["url"] =	 new moodle_url("#");
    	$root["aem1_raquo"]["icon"] =	 "a/em1_raquo";
    	
    	$root["ahelp"] = array();
    	$root["ahelp"]["string"] = "a help";
    	$root["ahelp"]["url"] =	 new moodle_url("#");
    	$root["ahelp"]["icon"] =	 "a/help";
    	
    	$root["al_breadcrumb"] = array();
    	$root["al_breadcrumb"]["string"] = "a l_breadcrumb";
    	$root["al_breadcrumb"]["url"] =	 new moodle_url("#");
    	$root["al_breadcrumb"]["icon"] =	 "a/l_breadcrumb";
    	
    	$root["alogout"] = array();
    	$root["alogout"]["string"] = "a logout";
    	$root["alogout"]["url"] =	 new moodle_url("#");
    	$root["alogout"]["icon"] =	 "a/logout";
    	
    	$root["ar_breadcrumb"] = array();
    	$root["ar_breadcrumb"]["string"] = "a r_breadcrumb";
    	$root["ar_breadcrumb"]["url"] =	 new moodle_url("#");
    	$root["ar_breadcrumb"]["icon"] =	 "a/r_breadcrumb";
    	
    	$root["ar_go"] = array();
    	$root["ar_go"]["string"] = "a r_go";
    	$root["ar_go"]["url"] =	 new moodle_url("#");
    	$root["ar_go"]["icon"] =	 "a/r_go";
    	
    	$root["ar_next"] = array();
    	$root["ar_next"]["string"] = "a r_next";
    	$root["ar_next"]["url"] =	 new moodle_url("#");
    	$root["ar_next"]["icon"] =	 "a/r_next";
    	
    	$root["ar_previous"] = array();
    	$root["ar_previous"]["string"] = "a r_previous";
    	$root["ar_previous"]["url"] =	 new moodle_url("#");
    	$root["ar_previous"]["icon"] =	 "a/r_previous";
    	
    	$root["arefresh"] = array();
    	$root["arefresh"]["string"] = "a refresh";
    	$root["arefresh"]["url"] =	 new moodle_url("#");
    	$root["arefresh"]["icon"] =	 "a/refresh";
    	
    	$root["asearch"] = array();
    	$root["asearch"]["string"] = "a search";
    	$root["asearch"]["url"] =	 new moodle_url("#");
    	$root["asearch"]["icon"] =	 "a/search";
    	
    	$root["asetting"] = array();
    	$root["asetting"]["string"] = "a setting";
    	$root["asetting"]["url"] =	 new moodle_url("#");
    	$root["asetting"]["icon"] =	 "a/setting";
    	
    	$root["aview_icon_active"] = array();
    	$root["aview_icon_active"]["string"] = "a view_icon_active";
    	$root["aview_icon_active"]["url"] =	 new moodle_url("#");
    	$root["aview_icon_active"]["icon"] =	 "a/view_icon_active";
    	
    	$root["aview_list_active"] = array();
    	$root["aview_list_active"]["string"] = "a view_list_active";
    	$root["aview_list_active"]["url"] =	 new moodle_url("#");
    	$root["aview_list_active"]["icon"] =	 "a/view_list_active";
    	
    	$root["aview_tree_active"] = array();
    	$root["aview_tree_active"]["string"] = "a view_tree_active";
    	$root["aview_tree_active"]["url"] =	 new moodle_url("#");
    	$root["aview_tree_active"]["icon"] =	 "a/view_tree_active";
    	
    	$root["bbookmark-new"] = array();
    	$root["bbookmark-new"]["string"] = "b bookmark-new";
    	$root["bbookmark-new"]["url"] =	 new moodle_url("#");
    	$root["bbookmark-new"]["icon"] =	 "b/bookmark-new";
    	
    	$root["bdocument-edit"] = array();
    	$root["bdocument-edit"]["string"] = "b document-edit";
    	$root["bdocument-edit"]["url"] =	 new moodle_url("#");
    	$root["bdocument-edit"]["icon"] =	 "b/document-edit";
    	
    	$root["bdocument-new"] = array();
    	$root["bdocument-new"]["string"] = "b document-new";
    	$root["bdocument-new"]["url"] =	 new moodle_url("#");
    	$root["bdocument-new"]["icon"] =	 "b/document-new";
    	
    	$root["bdocument-properties"] = array();
    	$root["bdocument-properties"]["string"] = "b document-properties";
    	$root["bdocument-properties"]["url"] =	 new moodle_url("#");
    	$root["bdocument-properties"]["icon"] =	 "b/document-properties";
    	
    	$root["bedit-copy"] = array();
    	$root["bedit-copy"]["string"] = "b edit-copy";
    	$root["bedit-copy"]["url"] =	 new moodle_url("#");
    	$root["bedit-copy"]["icon"] =	 "b/edit-copy";
    	
    	$root["bedit-delete"] = array();
    	$root["bedit-delete"]["string"] = "b edit-delete";
    	$root["bedit-delete"]["url"] =	 new moodle_url("#");
    	$root["bedit-delete"]["icon"] =	 "b/edit-delete";
    	
    	$root["cevent"] = array();
    	$root["cevent"]["string"] = "c event";
    	$root["cevent"]["url"] =	 new moodle_url("#");
    	$root["cevent"]["icon"] =	 "c/event";
    	
    	$root["eabbr"] = array();
    	$root["eabbr"]["string"] = "e abbr";
    	$root["eabbr"]["url"] =	 new moodle_url("#");
    	$root["eabbr"]["icon"] =	 "e/abbr";
    	
    	$root["eabsolute"] = array();
    	$root["eabsolute"]["string"] = "e absolute";
    	$root["eabsolute"]["url"] =	 new moodle_url("#");
    	$root["eabsolute"]["icon"] =	 "e/absolute";
    	
    	$root["eaccessibility_checker"] = array();
    	$root["eaccessibility_checker"]["string"] = "e accessibility_checker";
    	$root["eaccessibility_checker"]["url"] =	 new moodle_url("#");
    	$root["eaccessibility_checker"]["icon"] =	 "e/accessibility_checker";
    	
    	$root["eacronym"] = array();
    	$root["eacronym"]["string"] = "e acronym";
    	$root["eacronym"]["url"] =	 new moodle_url("#");
    	$root["eacronym"]["icon"] =	 "e/acronym";
    	
    	$root["eadvance_hr"] = array();
    	$root["eadvance_hr"]["string"] = "e advance_hr";
    	$root["eadvance_hr"]["url"] =	 new moodle_url("#");
    	$root["eadvance_hr"]["icon"] =	 "e/advance_hr";
    	
    	$root["ealign_center"] = array();
    	$root["ealign_center"]["string"] = "e align_center";
    	$root["ealign_center"]["url"] =	 new moodle_url("#");
    	$root["ealign_center"]["icon"] =	 "e/align_center";
    	
    	$root["ealign_left"] = array();
    	$root["ealign_left"]["string"] = "e align_left";
    	$root["ealign_left"]["url"] =	 new moodle_url("#");
    	$root["ealign_left"]["icon"] =	 "e/align_left";
    	
    	$root["ealign_right"] = array();
    	$root["ealign_right"]["string"] = "e align_right";
    	$root["ealign_right"]["url"] =	 new moodle_url("#");
    	$root["ealign_right"]["icon"] =	 "e/align_right";
    	
    	$root["eanchor"] = array();
    	$root["eanchor"]["string"] = "e anchor";
    	$root["eanchor"]["url"] =	 new moodle_url("#");
    	$root["eanchor"]["icon"] =	 "e/anchor";
    	
    	$root["ebackward"] = array();
    	$root["ebackward"]["string"] = "e backward";
    	$root["ebackward"]["url"] =	 new moodle_url("#");
    	$root["ebackward"]["icon"] =	 "e/backward";
    	
    	$root["ebold"] = array();
    	$root["ebold"]["string"] = "e bold";
    	$root["ebold"]["url"] =	 new moodle_url("#");
    	$root["ebold"]["icon"] =	 "e/bold";
    	
    	$root["ebullet_list"] = array();
    	$root["ebullet_list"]["string"] = "e bullet_list";
    	$root["ebullet_list"]["url"] =	 new moodle_url("#");
    	$root["ebullet_list"]["icon"] =	 "e/bullet_list";
    	
    	$root["ecancel"] = array();
    	$root["ecancel"]["string"] = "e cancel";
    	$root["ecancel"]["url"] =	 new moodle_url("#");
    	$root["ecancel"]["icon"] =	 "e/cancel";
    	
    	$root["ecell_props"] = array();
    	$root["ecell_props"]["string"] = "e cell_props";
    	$root["ecell_props"]["url"] =	 new moodle_url("#");
    	$root["ecell_props"]["icon"] =	 "e/cell_props";
    	
    	$root["ecite"] = array();
    	$root["ecite"]["string"] = "e cite";
    	$root["ecite"]["url"] =	 new moodle_url("#");
    	$root["ecite"]["icon"] =	 "e/cite";
    	
    	$root["ecleanup_messy_code"] = array();
    	$root["ecleanup_messy_code"]["string"] = "e cleanup_messy_code";
    	$root["ecleanup_messy_code"]["url"] =	 new moodle_url("#");
    	$root["ecleanup_messy_code"]["icon"] =	 "e/cleanup_messy_code";
    	
    	$root["eclear_formatting"] = array();
    	$root["eclear_formatting"]["string"] = "e clear_formatting";
    	$root["eclear_formatting"]["url"] =	 new moodle_url("#");
    	$root["eclear_formatting"]["icon"] =	 "e/clear_formatting";
    	
    	$root["ecopy"] = array();
    	$root["ecopy"]["string"] = "e copy";
    	$root["ecopy"]["url"] =	 new moodle_url("#");
    	$root["ecopy"]["icon"] =	 "e/copy";
    	
    	$root["ecut"] = array();
    	$root["ecut"]["string"] = "e cut";
    	$root["ecut"]["url"] =	 new moodle_url("#");
    	$root["ecut"]["icon"] =	 "e/cut";
    	
    	$root["edecrease_indent"] = array();
    	$root["edecrease_indent"]["string"] = "e decrease_indent";
    	$root["edecrease_indent"]["url"] =	 new moodle_url("#");
    	$root["edecrease_indent"]["icon"] =	 "e/decrease_indent";
    	
    	$root["edelete"] = array();
    	$root["edelete"]["string"] = "e delete";
    	$root["edelete"]["url"] =	 new moodle_url("#");
    	$root["edelete"]["icon"] =	 "e/delete";
    	
    	$root["edelete_col"] = array();
    	$root["edelete_col"]["string"] = "e delete_col";
    	$root["edelete_col"]["url"] =	 new moodle_url("#");
    	$root["edelete_col"]["icon"] =	 "e/delete_col";
    	
    	$root["edelete_row"] = array();
    	$root["edelete_row"]["string"] = "e delete_row";
    	$root["edelete_row"]["url"] =	 new moodle_url("#");
    	$root["edelete_row"]["icon"] =	 "e/delete_row";
    	
    	$root["edelete_table"] = array();
    	$root["edelete_table"]["string"] = "e delete_table";
    	$root["edelete_table"]["url"] =	 new moodle_url("#");
    	$root["edelete_table"]["icon"] =	 "e/delete_table";
    	
    	$root["edocument_properties"] = array();
    	$root["edocument_properties"]["string"] = "e document_properties";
    	$root["edocument_properties"]["url"] =	 new moodle_url("#");
    	$root["edocument_properties"]["icon"] =	 "e/document_properties";
    	
    	$root["eemoticons"] = array();
    	$root["eemoticons"]["string"] = "e emoticons";
    	$root["eemoticons"]["url"] =	 new moodle_url("#");
    	$root["eemoticons"]["icon"] =	 "e/emoticons";
    	
    	$root["efind_replace"] = array();
    	$root["efind_replace"]["string"] = "e find_replace";
    	$root["efind_replace"]["url"] =	 new moodle_url("#");
    	$root["efind_replace"]["icon"] =	 "e/find_replace";
    	
    	$root["eforward"] = array();
    	$root["eforward"]["string"] = "e forward";
    	$root["eforward"]["url"] =	 new moodle_url("#");
    	$root["eforward"]["icon"] =	 "e/forward";
    	
    	$root["efullpage"] = array();
    	$root["efullpage"]["string"] = "e fullpage";
    	$root["efullpage"]["url"] =	 new moodle_url("#");
    	$root["efullpage"]["icon"] =	 "e/fullpage";
    	
    	$root["efullscreen"] = array();
    	$root["fullscreen"]["string"] = "e fullscreen";
    	$root["fullscreen"]["url"] =	 new moodle_url("#");
    	$root["fullscreen"]["icon"] =	 "e/fullscreen";
    	
    	$root["ehelp"] = array();
    	$root["ehelp"]["string"] = "e help";
    	$root["ehelp"]["url"] =	 new moodle_url("#");
    	$root["ehelp"]["icon"] =	 "e/help";
    	
    	$root["eincrease_indent"] = array();
    	$root["eincrease_indent"]["string"] = "e increase_indent";
    	$root["eincrease_indent"]["url"] =	 new moodle_url("#");
    	$root["eincrease_indent"]["icon"] =	 "e/increase_indent";
    	
    	$root["einsert"] = array();
    	$root["einsert"]["string"] = "e insert";
    	$root["einsert"]["url"] =	 new moodle_url("#");
    	$root["einsert"]["icon"] =	 "e/insert";
    	
    	$root["einsert_col_after"] = array();
    	$root["einsert_col_after"]["string"] = "e insert_col_after";
    	$root["einsert_col_after"]["url"] =	 new moodle_url("#");
    	$root["einsert_col_after"]["icon"] =	 "e/insert_col_after";
    	
    	$root["einsert_col_before"] = array();
    	$root["einsert_col_before"]["string"] = "e insert_col_before";
    	$root["einsert_col_before"]["url"] =	 new moodle_url("#");
    	$root["einsert_col_before"]["icon"] =	 "e/insert_col_before";
    	
    	$root["einsert_date"] = array();
    	$root["einsert_date"]["string"] = "e insert_date";
    	$root["einsert_date"]["url"] =	 new moodle_url("#");
    	$root["einsert_date"]["icon"] =	 "e/insert_date";
    	
    	$root["einsert_edit_image"] = array();
    	$root["einsert_edit_image"]["string"] = "e insert_edit_image";
    	$root["einsert_edit_image"]["url"] =	 new moodle_url("#");
    	$root["einsert_edit_image"]["icon"] =	 "e/insert_edit_image";
    	
    	$root["einsert_edit_link"] = array();
    	$root["insert_edit_link"]["string"] = "e insert_edit_link";
    	$root["insert_edit_link"]["url"] =	 new moodle_url("#");
    	$root["insert_edit_link"]["icon"] =	 "e/insert_edit_link";
    	
    	$root["einsert_edit_video"] = array();
    	$root["einsert_edit_video"]["string"] = "e insert_edit_video";
    	$root["einsert_edit_video"]["url"] =	 new moodle_url("#");
    	$root["einsert_edit_video"]["icon"] =	 "e/insert_edit_video";
    	
    	$root["einsert_file"] = array();
    	$root["einsert_file"]["string"] = "e insert_file";
    	$root["einsert_file"]["url"] =	 new moodle_url("#");
    	$root["einsert_file"]["icon"] =	 "e/insert_file";
    	
    	$root["einsert_horizontal_ruler"] = array();
    	$root["einsert_horizontal_ruler"]["string"] = "e insert_horizontal_ruler";
    	$root["einsert_horizontal_ruler"]["url"] =	 new moodle_url("#");
    	$root["einsert_horizontal_ruler"]["icon"] =	 "e/insert_horizontal_ruler";
    	
    	$root["einsert_nonbreaking_space"] = array();
    	$root["einsert_nonbreaking_space"]["string"] = "e insert_nonbreaking_space";
    	$root["einsert_nonbreaking_space"]["url"] =	 new moodle_url("#");
    	$root["einsert_nonbreaking_space"]["icon"] =	 "e/insert_nonbreaking_space";
    	
    	$root["einsert_page_break"] = array();
    	$root["einsert_page_break"]["string"] = "e insert_page_break";
    	$root["einsert_page_break"]["url"] =	 new moodle_url("#");
    	$root["einsert_page_break"]["icon"] =	 "e/insert_page_break";
    	
    	$root["einsert_row_after"] = array();
    	$root["einsert_row_after"]["string"] = "e insert_row_after";
    	$root["einsert_row_after"]["url"] =	 new moodle_url("#");
    	$root["einsert_row_after"]["icon"] =	 "e/insert_row_after";
    	
    	$root["einsert_row_before"] = array();
    	$root["einsert_row_before"]["string"] = "e insert_row_before";
    	$root["einsert_row_before"]["url"] =	 new moodle_url("#");
    	$root["einsert_row_before"]["icon"] =	 "e/insert_row_before";
    	
    	$root["einsert_time"] = array();
    	$root["einsert_time"]["string"] = "e insert_time";
    	$root["einsert_time"]["url"] =	 new moodle_url("#");
    	$root["einsert_time"]["icon"] =	 "e/insert_time";
    	
    	$root["eitalic"] = array();
    	$root["eitalic"]["string"] = "e italic";
    	$root["eitalic"]["url"] =	 new moodle_url("#");
    	$root["eitalic"]["icon"] =	 "e/italic";
    	
    	$root["ejustify"] = array();
    	$root["ejustify"]["string"] = "e justify";
    	$root["ejustify"]["url"] =	 new moodle_url("#");
    	$root["ejustify"]["icon"] =	 "e/justify";
    	
    	$root["elayers"] = array();
    	$root["elayers"]["string"] = "e layers";
    	$root["elayers"]["url"] =	 new moodle_url("#");
    	$root["elayers"]["icon"] =	 "e/layers";
    	
    	$root["elayers_over"] = array();
    	$root["elayers_over"]["string"] = "e layers_over";
    	$root["elayers_over"]["url"] =	 new moodle_url("#");
    	$root["elayers_over"]["icon"] =	 "e/layers_over";
    	
    	$root["elayers_under"] = array();
    	$root["elayers_under"]["string"] = "e layers_under";
    	$root["elayers_under"]["url"] =	 new moodle_url("#");
    	$root["elayers_under"]["icon"] =	 "e/layers_under";
    	
    	$root["eleft_to_right"] = array();
    	$root["eleft_to_right"]["string"] = "e left_to_right";
    	$root["eleft_to_right"]["url"] =	 new moodle_url("#");
    	$root["eleft_to_right"]["icon"] =	 "e/left_to_right";
    	
    	$root["emanage_files"] = array();
    	$root["emanage_files"]["string"] = "e manage_files";
    	$root["emanage_files"]["url"] =	 new moodle_url("#");
    	$root["emanage_files"]["icon"] =	 "e/manage_files";
    	
    	$root["emath"] = array();
    	$root["emath"]["string"] = "e math";
    	$root["emath"]["url"] =	 new moodle_url("#");
    	$root["emath"]["icon"] =	 "e/math";
    	
    	$root["emerge_cells"] = array();
    	$root["emerge_cells"]["string"] = "e merge_cells";
    	$root["emerge_cells"]["url"] =	 new moodle_url("#");
    	$root["emerge_cells"]["icon"] =	 "e/merge_cells";
    	
    	$root["enew_document"] = array();
    	$root["enew_document"]["string"] = "e new_document";
    	$root["enew_document"]["url"] =	 new moodle_url("#");
    	$root["enew_document"]["icon"] =	 "e/new_document";
    	
    	$root["enumbered_list"] = array();
    	$root["enumbered_list"]["string"] = "e numbered_list";
    	$root["enumbered_list"]["url"] =	 new moodle_url("#");
    	$root["enumbered_list"]["icon"] =	 "e/numbered_list";
    	
    	$root["epage_break"] = array();
    	$root["epage_break"]["string"] = "e page_break";
    	$root["epage_break"]["url"] =	 new moodle_url("#");
    	$root["epage_break"]["icon"] =	 "e/page_break";
    	
    	$root["epaste"] = array();
    	$root["epaste"]["string"] = "e paste";
    	$root["epaste"]["url"] =	 new moodle_url("#");
    	$root["epaste"]["icon"] =	 "e/paste";
    	
    	$root["epaste_text"] = array();
    	$root["epaste_text"]["string"] = "e paste_text";
    	$root["epaste_text"]["url"] =	 new moodle_url("#");
    	$root["epaste_text"]["icon"] =	 "e/paste_text";
    	
    	$root["epaste_word"] = array();
    	$root["epaste_word"]["string"] = "e paste_word";
    	$root["epaste_word"]["url"] =	 new moodle_url("#");
    	$root["epaste_word"]["icon"] =	 "e/paste_word";
    	
    	$root["eprevent_autolink"] = array();
    	$root["eprevent_autolink"]["string"] = "e prevent_autolink";
    	$root["eprevent_autolink"]["url"] =	 new moodle_url("#");
    	$root["eprevent_autolink"]["icon"] =	 "e/prevent_autolink";
    	
    	$root["epreview"] = array();
    	$root["epreview"]["string"] = "e preview";
    	$root["epreview"]["url"] =	 new moodle_url("#");
    	$root["epreview"]["icon"] =	 "e/preview";
    	
    	$root["eprint"] = array();
    	$root["eprint"]["string"] = "e print";
    	$root["eprint"]["url"] =	 new moodle_url("#");
    	$root["eprint"]["icon"] =	 "e/print";
    	
    	$root["equestion"] = array();
    	$root["equestion"]["string"] = "e question";
    	$root["equestion"]["url"] =	 new moodle_url("#");
    	$root["equestion"]["icon"] =	 "e/question";
    	
    	$root["eredo"] = array();
    	$root["eredo"]["string"] = "e redo";
    	$root["eredo"]["url"] =	 new moodle_url("#");
    	$root["eredo"]["icon"] =	 "e/redo";
    	
    	$root["eremove_link"] = array();
    	$root["eremove_link"]["string"] = "e remove_link";
    	$root["eremove_link"]["url"] =	 new moodle_url("#");
    	$root["eremove_link"]["icon"] =	 "e/remove_link";
    	
    	$root["eremove_page_break"] = array();
    	$root["eremove_page_break"]["string"] = "e remove_page_break";
    	$root["eremove_page_break"]["url"] =	 new moodle_url("#");
    	$root["eremove_page_break"]["icon"] =	 "e/remove_page_break";
    	
    	$root["eresize"] = array();
    	$root["eresize"]["string"] = "e resize";
    	$root["eresize"]["url"] =	 new moodle_url("#");
    	$root["eresize"]["icon"] =	 "e/resize";
    	
    	$root["erestore_draft"] = array();
    	$root["erestore_draft"]["string"] = "e restore_draft";
    	$root["erestore_draft"]["url"] =	 new moodle_url("#");
    	$root["erestore_draft"]["icon"] =	 "e/restore_draft";
    	
    	$root["erestore_last_draft"] = array();
    	$root["erestore_last_draft"]["string"] = "e restore_last_draft";
    	$root["erestore_last_draft"]["url"] =	 new moodle_url("#");
    	$root["erestore_last_draft"]["icon"] =	 "e/restore_last_draft";
    	
    	$root["eright_to_left"] = array();
    	$root["eright_to_left"]["string"] = "e right_to_left";
    	$root["eright_to_left"]["url"] =	 new moodle_url("#");
    	$root["eright_to_left"]["icon"] =	 "e/right_to_left";
    	
    	$root["erow_props"] = array();
    	$root["erow_props"]["string"] = "e row_props";
    	$root["erow_props"]["url"] =	 new moodle_url("#");
    	$root["erow_props"]["icon"] =	 "e/row_props";
    	
    	$root["esave"] = array();
    	$root["esave"]["string"] = "e save";
    	$root["esave"]["url"] =	 new moodle_url("#");
    	$root["esave"]["icon"] =	 "e/save";
    	
    	$root["escreenreader_helper"] = array();
    	$root["escreenreader_helper"]["string"] = "e screenreader_helper";
    	$root["escreenreader_helper"]["url"] =	 new moodle_url("#");
    	$root["escreenreader_helper"]["icon"] =	 "e/screenreader_helper";
    	
    	$root["esearch"] = array();
    	$root["esearch"]["string"] = "e search";
    	$root["esearch"]["url"] =	 new moodle_url("#");
    	$root["esearch"]["icon"] =	 "e/search";
    	
    	$root["eselect_all"] = array();
    	$root["eselect_all"]["string"] = "e select_all";
    	$root["eselect_all"]["url"] =	 new moodle_url("#");
    	$root["eselect_all"]["icon"] =	 "e/select_all";
    	
    	$root["eshow_invisible_characters"] = array();
    	$root["eshow_invisible_characters"]["string"] = "e show_invisible_characters";
    	$root["eshow_invisible_characters"]["url"] =	 new moodle_url("#");
    	$root["eshow_invisible_characters"]["icon"] =	 "e/show_invisible_characters";
    	
    	$root["esource_code"] = array();
    	$root["esource_code"]["string"] = "e source_code";
    	$root["esource_code"]["url"] =	 new moodle_url("#");
    	$root["esource_code"]["icon"] =	 "e/source_code";
    	
    	$root["especial_character"] = array();
    	$root["especial_character"]["string"] = "e special_character";
    	$root["especial_character"]["url"] =	 new moodle_url("#");
    	$root["especial_character"]["icon"] =	 "e/special_character";
    	
    	$root["espellcheck"] = array();
    	$root["espellcheck"]["string"] = "e spellcheck";
    	$root["espellcheck"]["url"] =	 new moodle_url("#");
    	$root["espellcheck"]["icon"] =	 "e/spellcheck";
    	
    	$root["esplit_cells"] = array();
    	$root["esplit_cells"]["string"] = "e split_cells";
    	$root["esplit_cells"]["url"] =	 new moodle_url("#");
    	$root["esplit_cells"]["icon"] =	 "e/split_cells";
    	
    	$root["estrikethrough"] = array();
    	$root["estrikethrough"]["string"] = "e strikethrough";
    	$root["estrikethrough"]["url"] =	 new moodle_url("#");
    	$root["estrikethrough"]["icon"] =	 "e/strikethrough";
    	
    	$root["estyleprops"] = array();
    	$root["estyleprops"]["string"] = "e styleprops";
    	$root["estyleprops"]["url"] =	 new moodle_url("#");
    	$root["estyleprops"]["icon"] =	 "e/styleprops";
    	
    	$root["esubscript"] = array();
    	$root["esubscript"]["string"] = "e subscript";
    	$root["esubscript"]["url"] =	 new moodle_url("#");
    	$root["esubscript"]["icon"] =	 "e/subscript";
    	
    	$root["esuperscript"] = array();
    	$root["esuperscript"]["string"] = "e superscript";
    	$root["esuperscript"]["url"] =	 new moodle_url("#");
    	$root["esuperscript"]["icon"] =	 "e/superscript";
    	
    	$root["etable"] = array();
    	$root["etable"]["string"] = "e table";
    	$root["etable"]["url"] =	 new moodle_url("#");
    	$root["etable"]["icon"] =	 "e/table";
    	
    	$root["etable_props"] = array();
    	$root["etable_props"]["string"] = "e table_props";
    	$root["etable_props"]["url"] =	 new moodle_url("#");
    	$root["etable_props"]["icon"] =	 "e/table_props";
    	
    	$root["etemplate"] = array();
    	$root["etemplate"]["string"] = "e template";
    	$root["etemplate"]["url"] =	 new moodle_url("#");
    	$root["etemplate"]["icon"] =	 "e/template";
    	
    	$root["etext_color"] = array();
    	$root["etext_color"]["string"] = "e text_color";
    	$root["etext_color"]["url"] =	 new moodle_url("#");
    	$root["etext_color"]["icon"] =	 "e/text_color";
    	
    	$root["etext_color_picker"] = array();
    	$root["etext_color_picker"]["string"] = "e text_color_picker";
    	$root["etext_color_picker"]["url"] =	 new moodle_url("#");
    	$root["etext_color_picker"]["icon"] =	 "e/text_color_picker";
    	
    	$root["etext_highlight"] = array();
    	$root["etext_highlight"]["string"] = "e text_highlight";
    	$root["etext_highlight"]["url"] =	 new moodle_url("#");
    	$root["etext_highlight"]["icon"] =	 "e/text_highlight";
    	
    	$root["etext_highlight_picker"] = array();
    	$root["etext_highlight_picker"]["string"] = "e text_highlight_picker";
    	$root["etext_highlight_picker"]["url"] =	 new moodle_url("#");
    	$root["etext_highlight_picker"]["icon"] =	 "e/text_highlight_picker";
    	
    	$root["etick"] = array();
    	$root["etick"]["string"] = "e tick";
    	$root["etick"]["url"] =	 new moodle_url("#");
    	$root["etick"]["icon"] =	 "e/tick";
    	
    	$root["etoggle_blockquote"] = array();
    	$root["etoggle_blockquote"]["string"] = "e toggle_blockquote";
    	$root["etoggle_blockquote"]["url"] =	 new moodle_url("#");
    	$root["etoggle_blockquote"]["icon"] =	 "e/toggle_blockquote";
    	
    	$root["eunderline"] = array();
    	$root["eunderline"]["string"] = "e underline";
    	$root["eunderline"]["url"] =	 new moodle_url("#");
    	$root["eunderline"]["icon"] =	 "e/underline";
    	
    	$root["eundo"] = array();
    	$root["eundo"]["string"] = "e undo";
    	$root["eundo"]["url"] =	 new moodle_url("#");
    	$root["eundo"]["icon"] =	 "e/undo";
    	
    	$root["evisual_aid"] = array();
    	$root["evisual_aid"]["string"] = "e visual_aid";
    	$root["evisual_aid"]["url"] =	 new moodle_url("#");
    	$root["evisual_aid"]["icon"] =	 "e/visual_aid";
    	
    	$root["evisual_blocks"] = array();
    	$root["evisual_blocks"]["string"] = "e visual_blocks";
    	$root["evisual_blocks"]["url"] =	 new moodle_url("#");
    	$root["evisual_blocks"]["icon"] =	 "e/visual_blocks";
    	
    	$root["gf1"] = array();
    	$root["gf1"]["string"] = "g f1";
    	$root["gf1"]["url"] =	 new moodle_url("#");
    	$root["gf1"]["icon"] =	 "g/f1";
    	
    	$root["gf2"] = array();
    	$root["gf2"]["string"] = "g f2";
    	$root["gf2"]["url"] =	 new moodle_url("#");
    	$root["gf2"]["icon"] =	 "g/f2";
    	
    	$root["iaddblock"] = array();
    	$root["iaddblock"]["string"] = "i addblock";
    	$root["iaddblock"]["url"] =	 new moodle_url("#");
    	$root["iaddblock"]["icon"] =	 "i/addblock";
    	
    	$root["iadmin"] = array();
    	$root["iadmin"]["string"] = "i admin";
    	$root["iadmin"]["url"] =	 new moodle_url("#");
    	$root["iadmin"]["icon"] =	 "i/admin";
    	
    	$root["iagg_mean"] = array();
    	$root["iagg_mean"]["string"] = "i agg_mean";
    	$root["iagg_mean"]["url"] =	 new moodle_url("#");
    	$root["iagg_mean"]["icon"] =	 "i/agg_mean";
    	
    	$root["iagg_sum"] = array();
    	$root["iagg_sum"]["string"] = "i agg_sum";
    	$root["iagg_sum"]["url"] =	 new moodle_url("#");
    	$root["iagg_sum"]["icon"] =	 "i/agg_sum";
    	
    	$root["i/assignroles"] = array();
    	$root["i/assignroles"]["string"] = "i assignroles";
    	$root["i/assignroles"]["url"] =	 new moodle_url("#");
    	$root["i/assignroles"]["icon"] =	 "i/assignroles";
    	
    	$root["i/backup"] = array();
    	$root["i/backup"]["string"] = "i backup";
    	$root["i/backup"]["url"] =	 new moodle_url("#");
    	$root["i/backup"]["icon"] =	 "i/backup";
    	
    	$root["i/badge"] = array();
    	$root["i/badge"]["string"] = "i badge";
    	$root["i/badge"]["url"] =	 new moodle_url("#");
    	$root["i/badge"]["icon"] =	 "i/badge";
    	
    	$root["i/calc"] = array();
    	$root["i/calc"]["string"] = "i calc";
    	$root["i/calc"]["url"] =	 new moodle_url("#");
    	$root["i/calc"]["icon"] =	 "i/calc";
    	
    	$root["i/calendar"] = array();
    	$root["i/calendar"]["string"] = "i calendar";
    	$root["i/calendar"]["url"] =	 new moodle_url("#");
    	$root["i/calendar"]["icon"] =	 "i/calendar";
    	
    	$root["i/calendareventdescription"] = array();
    	$root["i/calendareventdescription"]["string"] = "i calendareventdescription";
    	$root["i/calendareventdescription"]["url"] =	 new moodle_url("#");
    	$root["i/calendareventdescription"]["icon"] =	 "i/calendareventdescription";
    	
    	$root["i/calendareventtime"] = array();
    	$root["i/calendareventtime"]["string"] = "i calendareventtime";
    	$root["i/calendareventtime"]["url"] =	 new moodle_url("#");
    	$root["i/calendareventtime"]["icon"] =	 "i/calendareventtime";
    	
    	$root["i/categoryevent"] = array();
    	$root["i/categoryevent"]["string"] = "i categoryevent";
    	$root["i/categoryevent"]["url"] =	 new moodle_url("#");
    	$root["i/categoryevent"]["icon"] =	 "i/categoryevent";
    	
    	$root["i/caution"] = array();
    	$root["i/caution"]["string"] = "i caution";
    	$root["i/caution"]["url"] =	 new moodle_url("#");
    	$root["i/caution"]["icon"] =	 "i/caution";
    	
    	$root["i/checked"] = array();
    	$root["i/checked"]["string"] = "i checked";
    	$root["i/checked"]["url"] =	 new moodle_url("#");
    	$root["i/checked"]["icon"] =	 "i/checked";
    	
    	$root["i/checkpermissions"] = array();
    	$root["i/checkpermissions"]["string"] = "i checkpermissions";
    	$root["i/checkpermissions"]["url"] =	 new moodle_url("#");
    	$root["i/checkpermissions"]["icon"] =	 "i/checkpermissions";
    	
    	$root["i/closed"] = array();
    	$root["i/closed"]["string"] = "i closed";
    	$root["i/closed"]["url"] =	 new moodle_url("#");
    	$root["i/closed"]["icon"] =	 "i/closed";
    	
    	$root["i/cohort"] = array();
    	$root["i/cohort"]["string"] = "i cohort";
    	$root["i/cohort"]["url"] =	 new moodle_url("#");
    	$root["i/cohort"]["icon"] =	 "i/cohort";
    	
    	$root["i/colourpicker"] = array();
    	$root["i/colourpicker"]["string"] = "i colourpicker";
    	$root["i/colourpicker"]["url"] =	 new moodle_url("#");
    	$root["i/colourpicker"]["icon"] =	 "i/colourpicker";
    	
    	$root["i/competencies"] = array();
    	$root["i/competencies"]["string"] = "i competencies";
    	$root["i/competencies"]["url"] =	 new moodle_url("#");
    	$root["i/competencies"]["icon"] =	 "i/competencies";
    	
    	$root["i/completion_self"] = array();
    	$root["i/completion_self"]["string"] = "i completion_self";
    	$root["i/completion_self"]["url"] =	 new moodle_url("#");
    	$root["i/completion_self"]["icon"] =	 "i/completion_self";
    	
    	$root["i/completion-auto-enabled"] = array();
    	$root["i/completion-auto-enabled"]["string"] = "i completion-auto-enabled";
    	$root["i/completion-auto-enabled"]["url"] =	 new moodle_url("#");
    	$root["i/completion-auto-enabled"]["icon"] =	 "i/completion-auto-enabled";
    	
    	$root["i/completion-auto-fail"] = array();
    	$root["i/completion-auto-fail"]["string"] = "i completion-auto-fail";
    	$root["i/completion-auto-fail"]["url"] =	 new moodle_url("#");
    	$root["i/completion-auto-fail"]["icon"] =	 "i/completion-auto-fail";
    	
    	$root["i/completion-auto-n"] = array();
    	$root["i/completion-auto-n"]["string"] = "i completion-auto-n";
    	$root["i/completion-auto-n"]["url"] =	 new moodle_url("#");
    	$root["i/completion-auto-n"]["icon"] =	 "i/completion-auto-n";
    	
    	$root["i/completion-auto-n-override"] = array();
    	$root["i/completion-auto-n-override"]["string"] = "i completion-auto-n-override";
    	$root["i/completion-auto-n-override"]["url"] =	 new moodle_url("#");
    	$root["i/completion-auto-n-override"]["icon"] =	 "i/completion-auto-n-override";
    	
    	$root["i/completion-auto-pass"] = array();
    	$root["i/completion-auto-pass"]["string"] = "i completion-auto-pass";
    	$root["i/completion-auto-pass"]["url"] =	 new moodle_url("#");
    	$root["i/completion-auto-pass"]["icon"] =	 "i/completion-auto-pass";
    	
    	$root["i/completion-auto-y"] = array();
    	$root["i/completion-auto-y"]["string"] = "i completion-auto-y";
    	$root["i/completion-auto-y"]["url"] =	 new moodle_url("#");
    	$root["i/completion-auto-y"]["icon"] =	 "i/completion-auto-y";
    	
    	$root["i/completion-auto-y-override"] = array();
    	$root["i/completion-auto-y-override"]["string"] = "i completion-auto-y-override";
    	$root["i/completion-auto-y-override"]["url"] =	 new moodle_url("#");
    	$root["i/completion-auto-y-override"]["icon"] =	 "i/completion-auto-y-override";
    	
    	$root["i/completion-manual-enabled"] = array();
    	$root["i/completion-manual-enabled"]["string"] = "i completion-manual-enabled";
    	$root["i/completion-manual-enabled"]["url"] =	 new moodle_url("#");
    	$root["i/completion-manual-enabled"]["icon"] =	 "i/completion-manual-enabled";
    	
    	$root["i/completion-manual-n"] = array();
    	$root["i/completion-manual-n"]["string"] = "i completion-manual-n";
    	$root["i/completion-manual-n"]["url"] =	 new moodle_url("#");
    	$root["i/completion-manual-n"]["icon"] =	 "i/completion-manual-n";
    	
    	$root["i/completion-manual-n-override"] = array();
    	$root["i/completion-manual-n-override"]["string"] = "i completion-manual-n-override";
    	$root["i/completion-manual-n-override"]["url"] =	 new moodle_url("#");
    	$root["i/completion-manual-n-override"]["icon"] =	 "i/completion-manual-n-override";
    	
    	$root["i/completion-manual-y"] = array();
    	$root["i/completion-manual-y"]["string"] = "i completion-manual-y";
    	$root["i/completion-manual-y"]["url"] =	 new moodle_url("#");
    	$root["i/completion-manual-y"]["icon"] =	 "i/completion-manual-y";
    	
    	$root["i/completion-manual-y-override"] = array();
    	$root["i/completion-manual-y-override"]["string"] = "i completion-manual-y-override";
    	$root["i/completion-manual-y-override"]["url"] =	 new moodle_url("#");
    	$root["i/completion-manual-y-override"]["icon"] =	 "i/completion-manual-y-override";
    	
    	$root["i/configlock"] = array();
    	$root["i/configlock"]["string"] = "i configlock";
    	$root["i/configlock"]["url"] =	 new moodle_url("#");
    	$root["i/configlock"]["icon"] =	 "i/configlock";
    	
    	$root["i/course"] = array();
    	$root["i/course"]["string"] = "i course";
    	$root["i/course"]["url"] =	 new moodle_url("#");
    	$root["i/course"]["icon"] =	 "i/course";
    	
    	$root["i/courseevent"] = array();
    	$root["i/courseevent"]["string"] = "i courseevent";
    	$root["i/courseevent"]["url"] =	 new moodle_url("#");
    	$root["i/courseevent"]["icon"] =	 "i/courseevent";
    	
    	$root["i/dashboard"] = array();
    	$root["i/dashboard"]["string"] = "i dashboard";
    	$root["i/dashboard"]["url"] =	 new moodle_url("#");
    	$root["i/dashboard"]["icon"] =	 "i/dashboard";
    	
    	$root["i/db"] = array();
    	$root["i/db"]["string"] = "i db";
    	$root["i/db"]["url"] =	 new moodle_url("#");
    	$root["i/db"]["icon"] =	 "i/db";
    	
    	$root["i/delete"] = array();
    	$root["i/delete"]["string"] = "i delete";
    	$root["i/delete"]["url"] =	 new moodle_url("#");
    	$root["i/delete"]["icon"] =	 "i/delete";
    	
    	$root["i/down"] = array();
    	$root["i/down"]["string"] = "i down";
    	$root["i/down"]["url"] =	 new moodle_url("#");
    	$root["i/down"]["icon"] =	 "i/down";
    	
    	$root["i/dragdrop"] = array();
    	$root["i/dragdrop"]["string"] = "i dragdrop";
    	$root["i/dragdrop"]["url"] =	 new moodle_url("#");
    	$root["i/dragdrop"]["icon"] =	 "i/dragdrop";
    	
    	$root["i/dropdown"] = array();
    	$root["i/dropdown"]["string"] = "i dropdown";
    	$root["i/dropdown"]["url"] =	 new moodle_url("#");
    	$root["i/dropdown"]["icon"] =	 "i/dropdown";
    	
    	$root["i/duration"] = array();
    	$root["i/duration"]["string"] = "i duration";
    	$root["i/duration"]["url"] =	 new moodle_url("#");
    	$root["i/duration"]["icon"] =	 "i/duration";
    	
    	$root["i/edit"] = array();
    	$root["i/edit"]["string"] = "i edit";
    	$root["i/edit"]["url"] =	 new moodle_url("#");
    	$root["i/edit"]["icon"] =	 "i/edit";
    	
    	$root["i/email"] = array();
    	$root["i/email"]["string"] = "i email";
    	$root["i/email"]["url"] =	 new moodle_url("#");
    	$root["i/email"]["icon"] =	 "i/email";
    	
    	$root["i/empty"] = array();
    	$root["i/empty"]["string"] = "i empty";
    	$root["i/empty"]["url"] =	 new moodle_url("#");
    	$root["i/empty"]["icon"] =	 "i/empty";
    	
    	$root["i/enrolmentsuspended"] = array();
    	$root["i/enrolmentsuspended"]["string"] = "i enrolmentsuspended";
    	$root["i/enrolmentsuspended"]["url"] =	 new moodle_url("#");
    	$root["i/enrolmentsuspended"]["icon"] =	 "i/enrolmentsuspended";
    	
    	$root["i/enrolusers"] = array();
    	$root["i/enrolusers"]["string"] = "i enrolusers";
    	$root["i/enrolusers"]["url"] =	 new moodle_url("#");
    	$root["i/enrolusers"]["icon"] =	 "i/enrolusers";
    	
    	$root["i/expired"] = array();
    	$root["i/expired"]["string"] = "i expired";
    	$root["i/expired"]["url"] =	 new moodle_url("#");
    	$root["i/expired"]["icon"] =	 "i/expired";
    	
    	$root["i/export"] = array();
    	$root["i/export"]["string"] = "i export";
    	$root["i/export"]["url"] =	 new moodle_url("#");
    	$root["i/export"]["icon"] =	 "i/export";
    	
    	$root["i/feedback"] = array();
    	$root["i/feedback"]["string"] = "i feedback";
    	$root["i/feedback"]["url"] =	 new moodle_url("#");
    	$root["i/feedback"]["icon"] =	 "i/feedback";
    	
    	$root["i/feedback_add"] = array();
    	$root["i/feedback_add"]["string"] = "i feedback_add";
    	$root["i/feedback_add"]["url"] =	 new moodle_url("#");
    	$root["i/feedback_add"]["icon"] =	 "i/feedback_add";
    	
    	$root["i/files"] = array();
    	$root["i/files"]["string"] = "i files";
    	$root["i/files"]["url"] =	 new moodle_url("#");
    	$root["i/files"]["icon"] =	 "i/files";
    	
    	$root["i/filter"] = array();
    	$root["i/filter"]["string"] = "i filter";
    	$root["i/filter"]["url"] =	 new moodle_url("#");
    	$root["i/filter"]["icon"] =	 "i/filter";
    	
    	$root["i/flagged"] = array();
    	$root["i/flagged"]["string"] = "i flagged";
    	$root["i/flagged"]["url"] =	 new moodle_url("#");
    	$root["i/flagged"]["icon"] =	 "i/flagged";
    	
    	$root["i/folder"] = array();
    	$root["i/folder"]["string"] = "i folder";
    	$root["i/folder"]["url"] =	 new moodle_url("#");
    	$root["i/folder"]["icon"] =	 "i/folder";
    	
    	$root["i/grade_correct"] = array();
    	$root["i/grade_correct"]["string"] = "i grade_correct";
    	$root["i/grade_correct"]["url"] =	 new moodle_url("#");
    	$root["i/grade_correct"]["icon"] =	 "i/grade_correct";
    	
    	$root["i/grade_incorrect"] = array();
    	$root["i/grade_incorrect"]["string"] = "i grade_incorrect";
    	$root["i/grade_incorrect"]["url"] =	 new moodle_url("#");
    	$root["i/grade_incorrect"]["icon"] =	 "i/grade_incorrect";
    	
    	$root["i/grade_partiallycorrect"] = array();
    	$root["i/grade_partiallycorrect"]["string"] = "i grade_partiallycorrect";
    	$root["i/grade_partiallycorrect"]["url"] =	 new moodle_url("#");
    	$root["i/grade_partiallycorrect"]["icon"] =	 "i/grade_partiallycorrect";
    	
    	$root["i/grademark"] = array();
    	$root["i/grademark"]["string"] = "i grademark";
    	$root["i/grademark"]["url"] =	 new moodle_url("#");
    	$root["i/grademark"]["icon"] =	 "i/grademark";
    	
    	$root["i/grademark-grey"] = array();
    	$root["i/grademark-grey"]["string"] = "i grademark-grey";
    	$root["i/grademark-grey"]["url"] =	 new moodle_url("#");
    	$root["i/grademark-grey"]["icon"] =	 "i/grademark-grey";
    	
    	$root["i/grades"] = array();
    	$root["i/grades"]["string"] = "i grades";
    	$root["i/grades"]["url"] =	 new moodle_url("#");
    	$root["i/grades"]["icon"] =	 "i/grades";
    	
    	$root["i/group"] = array();
    	$root["i/group"]["string"] = "i group";
    	$root["i/group"]["url"] =	 new moodle_url("#");
    	$root["i/group"]["icon"] =	 "i/group";
    	
    	$root["i/groupevent"] = array();
    	$root["i/groupevent"]["string"] = "i groupevent";
    	$root["i/groupevent"]["url"] =	 new moodle_url("#");
    	$root["i/groupevent"]["icon"] =	 "i/groupevent";
    	
    	$root["i/groupn"] = array();
    	$root["i/groupn"]["string"] = "i groupn";
    	$root["i/groupn"]["url"] =	 new moodle_url("#");
    	$root["i/groupn"]["icon"] =	 "i/groupn";
    	
    	$root["i/groups"] = array();
    	$root["i/groups"]["string"] = "i groups";
    	$root["i/groups"]["url"] =	 new moodle_url("#");
    	$root["i/groups"]["icon"] =	 "i/groups";
    	
    	$root["i/groupv"] = array();
    	$root["i/groupv"]["string"] = "i groupv";
    	$root["i/groupv"]["url"] =	 new moodle_url("#");
    	$root["i/groupv"]["icon"] =	 "i/groupv";
    	
    	$root["i/guest"] = array();
    	$root["i/guest"]["string"] = "i guest";
    	$root["i/guest"]["url"] =	 new moodle_url("#");
    	$root["i/guest"]["icon"] =	 "i/guest";
    	
    	$root["i/hide"] = array();
    	$root["i/hide"]["string"] = "i hide";
    	$root["i/hide"]["url"] =	 new moodle_url("#");
    	$root["i/hide"]["icon"] =	 "i/hide";
    	
    	$root["i/hierarchylock"] = array();
    	$root["i/hierarchylock"]["string"] = "i hierarchylock";
    	$root["i/hierarchylock"]["url"] =	 new moodle_url("#");
    	$root["i/hierarchylock"]["icon"] =	 "i/hierarchylock";
    	
    	$root["i/home"] = array();
    	$root["i/home"]["string"] = "i home";
    	$root["i/home"]["url"] =	 new moodle_url("#");
    	$root["i/home"]["icon"] =	 "i/home";
    	
    	$root["i/ical"] = array();
    	$root["i/ical"]["string"] = "i ical";
    	$root["i/ical"]["url"] =	 new moodle_url("#");
    	$root["i/ical"]["icon"] =	 "i/ical";
    	
    	$root["i/import"] = array();
    	$root["i/import"]["string"] = "i import";
    	$root["i/import"]["url"] =	 new moodle_url("#");
    	$root["i/import"]["icon"] =	 "i/import";
    	
    	$root["i/info"] = array();
    	$root["i/info"]["string"] = "i info";
    	$root["i/info"]["url"] =	 new moodle_url("#");
    	$root["i/info"]["icon"] =	 "i/info";
    	
    	$root["i/invalid"] = array();
    	$root["i/invalid"]["string"] = "i invalid";
    	$root["i/invalid"]["url"] =	 new moodle_url("#");
    	$root["i/invalid"]["icon"] =	 "i/invalid";
    	
    	$root["i/item"] = array();
    	$root["i/item"]["string"] = "i item";
    	$root["i/item"]["url"] =	 new moodle_url("#");
    	$root["i/item"]["icon"] =	 "i/item";
    	
    	$root["i/key"] = array();
    	$root["i/key"]["string"] = "i key";
    	$root["i/key"]["url"] =	 new moodle_url("#");
    	$root["i/key"]["icon"] =	 "i/key";
    	
    	$root["i/loading"] = array();
    	$root["i/loading"]["string"] = "i loading";
    	$root["i/loading"]["url"] =	 new moodle_url("#");
    	$root["i/loading"]["icon"] =	 "i/loading";
    	
    	$root["i/loading_small"] = array();
    	$root["i/loading_small"]["string"] = "i loading_small";
    	$root["i/loading_small"]["url"] =	 new moodle_url("#");
    	$root["i/loading_small"]["icon"] =	 "i/loading_small";
    	
    	$root["i/lock"] = array();
    	$root["i/lock"]["string"] = "i lock";
    	$root["i/lock"]["url"] =	 new moodle_url("#");
    	$root["i/lock"]["icon"] =	 "i/lock";
    	
    	$root["i/log"] = array();
    	$root["i/log"]["string"] = "i log";
    	$root["i/log"]["url"] =	 new moodle_url("#");
    	$root["i/log"]["icon"] =	 "i/log";
    	
    	$root["i/mahara_host"] = array();
    	$root["i/mahara_host"]["string"] = "i mahara_host";
    	$root["i/mahara_host"]["url"] =	 new moodle_url("#");
    	$root["i/mahara_host"]["icon"] =	 "i/mahara_host";
    	
    	$root["i/manual_item"] = array();
    	$root["i/manual_item"]["string"] = "i manual_item";
    	$root["i/manual_item"]["url"] =	 new moodle_url("#");
    	$root["i/manual_item"]["icon"] =	 "i/manual_item";
    	
    	$root["i/marked"] = array();
    	$root["i/marked"]["string"] = "i marked";
    	$root["i/marked"]["url"] =	 new moodle_url("#");
    	$root["i/marked"]["icon"] =	 "i/marked";
    	
    	$root["i/marker"] = array();
    	$root["i/marker"]["string"] = "i marker";
    	$root["i/marker"]["url"] =	 new moodle_url("#");
    	$root["i/marker"]["icon"] =	 "i/marker";
    	
    	$root["i/mean"] = array();
    	$root["i/mean"]["string"] = "i mean";
    	$root["i/mean"]["url"] =	 new moodle_url("#");
    	$root["i/mean"]["icon"] =	 "i/mean";
    	
    	$root["i/menu"] = array();
    	$root["i/menu"]["string"] = "i menu";
    	$root["i/menu"]["url"] =	 new moodle_url("#");
    	$root["i/menu"]["icon"] =	 "i/menu";
    	
    	$root["i/mnethost"] = array();
    	$root["i/mnethost"]["string"] = "i mnethost";
    	$root["i/mnethost"]["url"] =	 new moodle_url("#");
    	$root["i/mnethost"]["icon"] =	 "i/mnethost";
    	
    	$root["i/moodle_host"] = array();
    	$root["i/moodle_host"]["string"] = "i moodle_host";
    	$root["i/moodle_host"]["url"] =	 new moodle_url("#");
    	$root["i/moodle_host"]["icon"] =	 "i/moodle_host";
    	
    	$root["i/move_2d"] = array();
    	$root["i/move_2d"]["string"] = "i move_2d";
    	$root["i/move_2d"]["url"] =	 new moodle_url("#");
    	$root["i/move_2d"]["icon"] =	 "i/move_2d";
    	
    	$root["i/navigationitem"] = array();
    	$root["i/navigationitem"]["string"] = "i navigationitem";
    	$root["i/navigationitem"]["url"] =	 new moodle_url("#");
    	$root["i/navigationitem"]["icon"] =	 "i/navigationitem";
    	
    	$root["i/ne_red_mark"] = array();
    	$root["i/ne_red_mark"]["string"] = "i ne_red_mark";
    	$root["i/ne_red_mark"]["url"] =	 new moodle_url("#");
    	$root["i/ne_red_mark"]["icon"] =	 "i/ne_red_mark";
    	
    	$root["i/new"] = array();
    	$root["i/new"]["string"] = "i new";
    	$root["i/new"]["url"] =	 new moodle_url("#");
    	$root["i/new"]["icon"] =	 "i/new";
    	
    	$root["i/news"] = array();
    	$root["i/news"]["string"] = "i news";
    	$root["i/news"]["url"] =	 new moodle_url("#");
    	$root["i/news"]["icon"] =	 "i/news";
    	
    	$root["i/nosubcat"] = array();
    	$root["i/nosubcat"]["string"] = "i nosubcat";
    	$root["i/nosubcat"]["url"] =	 new moodle_url("#");
    	$root["i/nosubcat"]["icon"] =	 "i/nosubcat";
    	
    	$root["i/notifications"] = array();
    	$root["i/notifications"]["string"] = "i notifications";
    	$root["i/notifications"]["url"] =	 new moodle_url("#");
    	$root["i/notifications"]["icon"] =	 "i/notifications";
    	
    	$root["i/open"] = array();
    	$root["i/open"]["string"] = "i open";
    	$root["i/open"]["url"] =	 new moodle_url("#");
    	$root["i/open"]["icon"] =	 "i/open";
    	
    	$root["i/outcomes"] = array();
    	$root["i/outcomes"]["string"] = "i outcomes";
    	$root["i/outcomes"]["url"] =	 new moodle_url("#");
    	$root["i/outcomes"]["icon"] =	 "i/outcomes";
    	
    	$root["i/payment"] = array();
    	$root["i/payment"]["string"] = "i payment";
    	$root["i/payment"]["url"] =	 new moodle_url("#");
    	$root["i/payment"]["icon"] =	 "i/payment";
    	
    	$root["i/permissionlock"] = array();
    	$root["i/permissionlock"]["string"] = "i permissionlock";
    	$root["i/permissionlock"]["url"] =	 new moodle_url("#");
    	$root["i/permissionlock"]["icon"] =	 "i/permissionlock";
    	
    	$root["i/permissions"] = array();
    	$root["i/permissions"]["string"] = "i permissions";
    	$root["i/permissions"]["url"] =	 new moodle_url("#");
    	$root["i/permissions"]["icon"] =	 "i/permissions";
    	
    	$root["i/persona_sign_in_black"] = array();
    	$root["i/persona_sign_in_black"]["string"] = "i persona_sign_in_black";
    	$root["i/persona_sign_in_black"]["url"] =	 new moodle_url("#");
    	$root["i/persona_sign_in_black"]["icon"] =	 "i/persona_sign_in_black";
    	
    	$root["i/portfolio"] = array();
    	$root["i/portfolio"]["string"] = "i portfolio";
    	$root["i/portfolio"]["url"] =	 new moodle_url("#");
    	$root["i/portfolio"]["icon"] =	 "i/portfolio";
    	
    	$root["i/preview"] = array();
    	$root["i/preview"]["string"] = "i preview";
    	$root["i/preview"]["url"] =	 new moodle_url("#");
    	$root["i/preview"]["icon"] =	 "i/preview";
    	
    	$root["i/privatefiles"] = array();
    	$root["i/privatefiles"]["string"] = "i privatefiles";
    	$root["i/privatefiles"]["url"] =	 new moodle_url("#");
    	$root["i/privatefiles"]["icon"] =	 "i/privatefiles";
    	
    	$root["i/progressbar"] = array();
    	$root["i/progressbar"]["string"] = "i progressbar";
    	$root["i/progressbar"]["url"] =	 new moodle_url("#");
    	$root["i/progressbar"]["icon"] =	 "i/progressbar";
    	
    	$root["i/publish"] = array();
    	$root["i/publish"]["string"] = "i publish";
    	$root["i/publish"]["url"] =	 new moodle_url("#");
    	$root["i/publish"]["icon"] =	 "i/publish";
    	
    	$root["i/questions"] = array();
    	$root["i/questions"]["string"] = "i questions";
    	$root["i/questions"]["url"] =	 new moodle_url("#");
    	$root["i/questions"]["icon"] =	 "i/questions";
    	
    	$root["i/reload"] = array();
    	$root["i/reload"]["string"] = "i reload";
    	$root["i/reload"]["url"] =	 new moodle_url("#");
    	$root["i/reload"]["icon"] =	 "i/reload";
    	
    	$root["i/report"] = array();
    	$root["i/report"]["string"] = "i report";
    	$root["i/report"]["url"] =	 new moodle_url("#");
    	$root["i/report"]["icon"] =	 "i/report";
    	
    	$root["i/repository"] = array();
    	$root["i/repository"]["string"] = "i repository";
    	$root["i/repository"]["url"] =	 new moodle_url("#");
    	$root["i/repository"]["icon"] =	 "i/repository";
    	
    	$root["i/restore"] = array();
    	$root["i/restore"]["string"] = "i restore";
    	$root["i/restore"]["url"] =	 new moodle_url("#");
    	$root["i/restore"]["icon"] =	 "i/restore";
    	
    	$root["i/return"] = array();
    	$root["i/return"]["string"] = "i return";
    	$root["i/return"]["url"] =	 new moodle_url("#");
    	$root["i/return"]["icon"] =	 "i/return";
    	
    	$root["i/risk_config"] = array();
    	$root["i/risk_config"]["string"] = "i risk_config";
    	$root["i/risk_config"]["url"] =	 new moodle_url("#");
    	$root["i/risk_config"]["icon"] =	 "i/risk_config";
    	
    	$root["i/risk_dataloss"] = array();
    	$root["i/risk_dataloss"]["string"] = "i risk_dataloss";
    	$root["i/risk_dataloss"]["url"] =	 new moodle_url("#");
    	$root["i/risk_dataloss"]["icon"] =	 "i/risk_dataloss";
    	
    	$root["i/risk_managetrust"] = array();
    	$root["i/risk_managetrust"]["string"] = "i risk_managetrust";
    	$root["i/risk_managetrust"]["url"] =	 new moodle_url("#");
    	$root["i/risk_managetrust"]["icon"] =	 "i/risk_managetrust";
    	
    	$root["i/risk_personal"] = array();
    	$root["i/risk_personal"]["string"] = "i risk_personal";
    	$root["i/risk_personal"]["url"] =	 new moodle_url("#");
    	$root["i/risk_personal"]["icon"] =	 "i/risk_personal";
    	
    	$root["i/risk_spam"] = array();
    	$root["i/risk_spam"]["string"] = "i risk_spam";
    	$root["i/risk_spam"]["url"] =	 new moodle_url("#");
    	$root["i/risk_spam"]["icon"] =	 "i/risk_spam";
    	
    	$root["i/risk_xss"] = array();
    	$root["i/risk_xss"]["string"] = "i risk_xss";
    	$root["i/risk_xss"]["url"] =	 new moodle_url("#");
    	$root["i/risk_xss"]["icon"] =	 "i/risk_xss";
    	
    	$root["i/role"] = array();
    	$root["i/role"]["string"] = "i role";
    	$root["i/role"]["url"] =	 new moodle_url("#");
    	$root["i/role"]["icon"] =	 "i/role";
    	
    	$root["i/rss"] = array();
    	$root["i/rss"]["string"] = "i rss";
    	$root["i/rss"]["url"] =	 new moodle_url("#");
    	$root["i/rss"]["icon"] =	 "i/rss";
    	
    	$root["i/rsssitelogo"] = array();
    	$root["i/rsssitelogo"]["string"] = "i rsssitelogo";
    	$root["i/rsssitelogo"]["url"] =	 new moodle_url("#");
    	$root["i/rsssitelogo"]["icon"] =	 "i/rsssitelogo";
    	
    	$root["i/scales"] = array();
    	$root["i/scales"]["string"] = "i scales";
    	$root["i/scales"]["url"] =	 new moodle_url("#");
    	$root["i/scales"]["icon"] =	 "i/scales";
    	
    	$root["i/scheduled"] = array();
    	$root["i/scheduled"]["string"] = "i scheduled";
    	$root["i/scheduled"]["url"] =	 new moodle_url("#");
    	$root["i/scheduled"]["icon"] =	 "i/scheduled";
    	
    	$root["i/search"] = array();
    	$root["i/search"]["string"] = "i search";
    	$root["i/search"]["url"] =	 new moodle_url("#");
    	$root["i/search"]["icon"] =	 "i/search";
    	
    	$root["i/section"] = array();
    	$root["i/section"]["string"] = "i section";
    	$root["i/section"]["url"] =	 new moodle_url("#");
    	$root["i/section"]["icon"] =	 "i/section";
    	
    	$root["i/settings"] = array();
    	$root["i/settings"]["string"] = "i settings";
    	$root["i/settings"]["url"] =	 new moodle_url("#");
    	$root["i/settings"]["icon"] =	 "i/settings";
    	
    	$root["i/show"] = array();
    	$root["i/show"]["string"] = "i show";
    	$root["i/show"]["url"] =	 new moodle_url("#");
    	$root["i/show"]["icon"] =	 "i/show";
    	
    	$root["i/siteevent"] = array();
    	$root["i/siteevent"]["string"] = "i siteevent";
    	$root["i/siteevent"]["url"] =	 new moodle_url("#");
    	$root["i/siteevent"]["icon"] =	 "i/siteevent";
    	
    	$root["i/star-rating"] = array();
    	$root["i/star-rating"]["string"] = "i star-rating";
    	$root["i/star-rating"]["url"] =	 new moodle_url("#");
    	$root["i/star-rating"]["icon"] =	 "i/star-rating";
    	
    	$root["i/stats"] = array();
    	$root["i/stats"]["string"] = "i stats";
    	$root["i/stats"]["url"] =	 new moodle_url("#");
    	$root["i/stats"]["icon"] =	 "i/stats";
    	
    	$root["i/switch"] = array();
    	$root["i/switch"]["string"] = "i switch";
    	$root["i/switch"]["url"] =	 new moodle_url("#");
    	$root["i/switch"]["icon"] =	 "i/switch";
    	
    	$root["i/switchrole"] = array();
    	$root["i/switchrole"]["string"] = "i switchrole";
    	$root["i/switchrole"]["url"] =	 new moodle_url("#");
    	$root["i/switchrole"]["icon"] =	 "i/switchrole";
    	
    	$root["i/test"] = array();
    	$root["i/test"]["string"] = "i test";
    	$root["i/test"]["url"] =	 new moodle_url("#");
    	$root["i/test"]["icon"] =	 "i/test";
    	
    	$root["i/twoway"] = array();
    	$root["i/twoway"]["string"] = "i twoway";
    	$root["i/twoway"]["url"] =	 new moodle_url("#");
    	$root["i/twoway"]["icon"] =	 "i/twoway";
    	
    	$root["i/unchecked"] = array();
    	$root["i/unchecked"]["string"] = "i unchecked";
    	$root["i/unchecked"]["url"] =	 new moodle_url("#");
    	$root["i/unchecked"]["icon"] =	 "i/unchecked";
    	
    	$root["i/unflagged"] = array();
    	$root["i/unflagged"]["string"] = "i unflagged";
    	$root["i/unflagged"]["url"] =	 new moodle_url("#");
    	$root["i/unflagged"]["icon"] =	 "i/unflagged";
    	
    	$root["i/unlock"] = array();
    	$root["i/unlock"]["string"] = "i unlock";
    	$root["i/unlock"]["url"] =	 new moodle_url("#");
    	$root["i/unlock"]["icon"] =	 "i/unlock";
    	
    	$root["i/up"] = array();
    	$root["i/up"]["string"] = "i up";
    	$root["i/up"]["url"] =	 new moodle_url("#");
    	$root["i/up"]["icon"] =	 "i/up";
    	
    	$root["i/user"] = array();
    	$root["i/user"]["string"] = "i user";
    	$root["i/user"]["url"] =	 new moodle_url("#");
    	$root["i/user"]["icon"] =	 "i/user";
    	
    	$root["i/userevent"] = array();
    	$root["i/userevent"]["string"] = "i userevent";
    	$root["i/userevent"]["url"] =	 new moodle_url("#");
    	$root["i/userevent"]["icon"] =	 "i/userevent";
    	
    	$root["i/users"] = array();
    	$root["i/users"]["string"] = "i users";
    	$root["i/users"]["url"] =	 new moodle_url("#");
    	$root["i/users"]["icon"] =	 "i/users";
    	
    	$root["i/valid"] = array();
    	$root["i/valid"]["string"] = "i valid";
    	$root["i/valid"]["url"] =	 new moodle_url("#");
    	$root["i/valid"]["icon"] =	 "i/valid";
    	
    	$root["i/warning"] = array();
    	$root["i/warning"]["string"] = "i warning";
    	$root["i/warning"]["url"] =	 new moodle_url("#");
    	$root["i/warning"]["icon"] =	 "i/warning";
    	
    	$root["i/withsubcat"] = array();
    	$root["i/withsubcat"]["string"] = "i withsubcat";
    	$root["i/withsubcat"]["url"] =	 new moodle_url("#");
    	$root["i/withsubcat"]["icon"] =	 "i/withsubcat";
    	
    	$root["m/USD"] = array();
    	$root["m/USD"]["string"] = "m USD";
    	$root["m/USD"]["url"] =	 new moodle_url("#");
    	$root["m/USD"]["icon"] =	 "m/USD";
    	
    	$root["t/add"] = array();
    	$root["t/add"]["string"] = "t add";
    	$root["t/add"]["url"] =	 new moodle_url("#");
    	$root["t/add"]["icon"] =	 "t/add";
    	
    	$root["t/addcontact"] = array();
    	$root["t/addcontact"]["string"] = "t addcontact";
    	$root["t/addcontact"]["url"] =	 new moodle_url("#");
    	$root["t/addcontact"]["icon"] =	 "t/addcontact";
    	
    	$root["t/adddir"] = array();
    	$root["t/adddir"]["string"] = "t adddir";
    	$root["t/adddir"]["url"] =	 new moodle_url("#");
    	$root["t/adddir"]["icon"] =	 "t/adddir";
    	
    	$root["t/addfile"] = array();
    	$root["t/addfile"]["string"] = "t addfile";
    	$root["t/addfile"]["url"] =	 new moodle_url("#");
    	$root["t/addfile"]["icon"] =	 "t/addfile";
    	
    	$root["t/approve"] = array();
    	$root["t/approve"]["string"] = "t approve";
    	$root["t/approve"]["url"] =	 new moodle_url("#");
    	$root["t/approve"]["icon"] =	 "t/approve";
    	
    	$root["t/arrow_left"] = array();
    	$root["t/arrow_left"]["string"] = "t arrow_left";
    	$root["t/arrow_left"]["url"] =	 new moodle_url("#");
    	$root["t/arrow_left"]["icon"] =	 "t/arrow_left";
    	
    	$root["t/assignroles"] = array();
    	$root["t/assignroles"]["string"] = "t assignroles";
    	$root["t/assignroles"]["url"] =	 new moodle_url("#");
    	$root["t/assignroles"]["icon"] =	 "t/assignroles";
    	
    	$root["t/award"] = array();
    	$root["t/award"]["string"] = "t award";
    	$root["t/award"]["url"] =	 new moodle_url("#");
    	$root["t/award"]["icon"] =	 "t/award";
    	
    	$root["t/backpack"] = array();
    	$root["t/backpack"]["string"] = "t backpack";
    	$root["t/backpack"]["url"] =	 new moodle_url("#");
    	$root["t/backpack"]["icon"] =	 "t/backpack";
    	
    	$root["t/backup"] = array();
    	$root["t/backup"]["string"] = "t backup";
    	$root["t/backup"]["url"] =	 new moodle_url("#");
    	$root["t/backup"]["icon"] =	 "t/backup";
    	
    	$root["t/block"] = array();
    	$root["t/block"]["string"] = "t block";
    	$root["t/block"]["url"] =	 new moodle_url("#");
    	$root["t/block"]["icon"] =	 "t/block";
    	
    	$root["t/block_to_dock"] = array();
    	$root["t/block_to_dock"]["string"] = "t block_to_dock";
    	$root["t/block_to_dock"]["url"] =	 new moodle_url("#");
    	$root["t/block_to_dock"]["icon"] =	 "t/block_to_dock";
    	
    	$root["t/block_to_dock_rtl"] = array();
    	$root["t/block_to_dock_rtl"]["string"] = "t block_to_dock_rtl";
    	$root["t/block_to_dock_rtl"]["url"] =	 new moodle_url("#");
    	$root["t/block_to_dock_rtl"]["icon"] =	 "t/block_to_dock_rtl";
    	
    	$root["t/calc"] = array();
    	$root["t/calc"]["string"] = "t calc";
    	$root["t/calc"]["url"] =	 new moodle_url("#");
    	$root["t/calc"]["icon"] =	 "t/calc";
    	
    	$root["t/calc_off"] = array();
    	$root["t/calc_off"]["string"] = "t calc_off";
    	$root["t/calc_off"]["url"] =	 new moodle_url("#");
    	$root["t/calc_off"]["icon"] =	 "t/calc_off";
    	
    	$root["t/calendar"] = array();
    	$root["t/calendar"]["string"] = "t calendar";
    	$root["t/calendar"]["url"] =	 new moodle_url("#");
    	$root["t/calendar"]["icon"] =	 "t/calendar";
    	
    	$root["t/check"] = array();
    	$root["t/check"]["string"] = "t check";
    	$root["t/check"]["url"] =	 new moodle_url("#");
    	$root["t/check"]["icon"] =	 "t/check";
    	
    	$root["t/cohort"] = array();
    	$root["t/cohort"]["string"] = "t cohort";
    	$root["t/cohort"]["url"] =	 new moodle_url("#");
    	$root["t/cohort"]["icon"] =	 "t/cohort";
    	
    	$root["t/collapsed"] = array();
    	$root["t/collapsed"]["string"] = "t collapsed";
    	$root["t/collapsed"]["url"] =	 new moodle_url("#");
    	$root["t/collapsed"]["icon"] =	 "t/collapsed";
    	
    	$root["t/collapsed_empty"] = array();
    	$root["t/collapsed_empty"]["string"] = "t collapsed_empty";
    	$root["t/collapsed_empty"]["url"] =	 new moodle_url("#");
    	$root["t/collapsed_empty"]["icon"] =	 "t/collapsed_empty";
    	
    	$root["t/collapsed_empty_rtl"] = array();
    	$root["t/collapsed_empty_rtl"]["string"] = "t collapsed_empty_rtl";
    	$root["t/collapsed_empty_rtl"]["url"] =	 new moodle_url("#");
    	$root["t/collapsed_empty_rtl"]["icon"] =	 "t/collapsed_empty_rtl";
    	
    	$root["t/collapsed_rtl"] = array();
    	$root["t/collapsed_rtl"]["string"] = "t collapsed_rtl";
    	$root["t/collapsed_rtl"]["url"] =	 new moodle_url("#");
    	$root["t/collapsed_rtl"]["icon"] =	 "t/collapsed_rtl";
    	
    	$root["t/contextmenu"] = array();
    	$root["t/contextmenu"]["string"] = "t contextmenu";
    	$root["t/contextmenu"]["url"] =	 new moodle_url("#");
    	$root["t/contextmenu"]["icon"] =	 "t/contextmenu";
    	
    	$root["t/copy"] = array();
    	$root["t/copy"]["string"] = "t copy";
    	$root["t/copy"]["url"] =	 new moodle_url("#");
    	$root["t/copy"]["icon"] =	 "t/copy";
    	
    	$root["t/delete"] = array();
    	$root["t/delete"]["string"] = "t delete";
    	$root["t/delete"]["url"] =	 new moodle_url("#");
    	$root["t/delete"]["icon"] =	 "t/delete";
    	
    	$root["t/dock_to_block"] = array();
    	$root["t/dock_to_block"]["string"] = "t dock_to_block";
    	$root["t/dock_to_block"]["url"] =	 new moodle_url("#");
    	$root["t/dock_to_block"]["icon"] =	 "t/dock_to_block";
    	
    	$root["t/dock_to_block_rtl"] = array();
    	$root["t/dock_to_block_rtl"]["string"] = "t dock_to_block_rtl";
    	$root["t/dock_to_block_rtl"]["url"] =	 new moodle_url("#");
    	$root["t/dock_to_block_rtl"]["icon"] =	 "t/dock_to_block_rtl";
    	
    	$root["t/dockclose"] = array();
    	$root["t/dockclose"]["string"] = "t dockclose";
    	$root["t/dockclose"]["url"] =	 new moodle_url("#");
    	$root["t/dockclose"]["icon"] =	 "t/dockclose";
    	
    	$root["t/down"] = array();
    	$root["t/down"]["string"] = "t down";
    	$root["t/down"]["url"] =	 new moodle_url("#");
    	$root["t/down"]["icon"] =	 "t/down";
    	
    	$root["t/download"] = array();
    	$root["t/download"]["string"] = "t download";
    	$root["t/download"]["url"] =	 new moodle_url("#");
    	$root["t/download"]["icon"] =	 "t/download";
    	
    	$root["t/dropdown"] = array();
    	$root["t/dropdown"]["string"] = "t dropdown";
    	$root["t/dropdown"]["url"] =	 new moodle_url("#");
    	$root["t/dropdown"]["icon"] =	 "t/dropdown";
    	
    	$root["t/edit"] = array();
    	$root["t/edit"]["string"] = "t edit";
    	$root["t/edit"]["url"] =	 new moodle_url("#");
    	$root["t/edit"]["icon"] =	 "t/edit";
    	
    	$root["t/edit_menu"] = array();
    	$root["t/edit_menu"]["string"] = "t edit_menu";
    	$root["t/edit_menu"]["url"] =	 new moodle_url("#");
    	$root["t/edit_menu"]["icon"] =	 "t/edit_menu";
    	
    	$root["t/editinline"] = array();
    	$root["t/editinline"]["string"] = "t editinline";
    	$root["t/editinline"]["url"] =	 new moodle_url("#");
    	$root["t/editinline"]["icon"] =	 "t/editinline";
    	
    	$root["t/editstring"] = array();
    	$root["t/editstring"]["string"] = "t editstring";
    	$root["t/editstring"]["url"] =	 new moodle_url("#");
    	$root["t/editstring"]["icon"] =	 "t/editstring";
    	
    	$root["t/email"] = array();
    	$root["t/email"]["string"] = "t email";
    	$root["t/email"]["url"] =	 new moodle_url("#");
    	$root["t/email"]["icon"] =	 "t/email";
    	
    	$root["t/emailno"] = array();
    	$root["t/emailno"]["string"] = "t emailno";
    	$root["t/emailno"]["url"] =	 new moodle_url("#");
    	$root["t/emailno"]["icon"] =	 "t/emailno";
    	
    	$root["t/enroladd"] = array();
    	$root["t/enroladd"]["string"] = "t enroladd";
    	$root["t/enroladd"]["url"] =	 new moodle_url("#");
    	$root["t/enroladd"]["icon"] =	 "t/enroladd";
    	
    	$root["t/enrolusers"] = array();
    	$root["t/enrolusers"]["string"] = "t enrolusers";
    	$root["t/enrolusers"]["url"] =	 new moodle_url("#");
    	$root["t/enrolusers"]["icon"] =	 "t/enrolusers";
    	
    	$root["t/expanded"] = array();
    	$root["t/expanded"]["string"] = "t expanded";
    	$root["t/expanded"]["url"] =	 new moodle_url("#");
    	$root["t/expanded"]["icon"] =	 "t/expanded";
    	
    	$root["t/feedback"] = array();
    	$root["t/feedback"]["string"] = "t feedback";
    	$root["t/feedback"]["url"] =	 new moodle_url("#");
    	$root["t/feedback"]["icon"] =	 "t/feedback";
    	
    	$root["t/feedback_add"] = array();
    	$root["t/feedback_add"]["string"] = "t feedback_add";
    	$root["t/feedback_add"]["url"] =	 new moodle_url("#");
    	$root["t/feedback_add"]["icon"] =	 "t/feedback_add";
    	
    	$root["t/go"] = array();
    	$root["t/go"]["string"] = "t go";
    	$root["t/go"]["url"] =	 new moodle_url("#");
    	$root["t/go"]["icon"] =	 "t/go";
    	
    	$root["t/grades"] = array();
    	$root["t/grades"]["string"] = "t grades";
    	$root["t/grades"]["url"] =	 new moodle_url("#");
    	$root["t/grades"]["icon"] =	 "t/grades";
    	
    	$root["t/groupn"] = array();
    	$root["t/groupn"]["string"] = "t groupn";
    	$root["t/groupn"]["url"] =	 new moodle_url("#");
    	$root["t/groupn"]["icon"] =	 "t/groupn";
    	
    	$root["t/groups"] = array();
    	$root["t/groups"]["string"] = "t groups";
    	$root["t/groups"]["url"] =	 new moodle_url("#");
    	$root["t/groups"]["icon"] =	 "t/groups";
    	
    	$root["t/groupv"] = array();
    	$root["t/groupv"]["string"] = "t groupv";
    	$root["t/groupv"]["url"] =	 new moodle_url("#");
    	$root["t/groupv"]["icon"] =	 "t/groupv";
    	
    	$root["t/hiddenuntil"] = array();
    	$root["t/hiddenuntil"]["string"] = "t hiddenuntil";
    	$root["t/hiddenuntil"]["url"] =	 new moodle_url("#");
    	$root["t/hiddenuntil"]["icon"] =	 "t/hiddenuntil";
    	
    	$root["t/hide"] = array();
    	$root["t/hide"]["string"] = "t hide";
    	$root["t/hide"]["url"] =	 new moodle_url("#");
    	$root["t/hide"]["icon"] =	 "t/hide";
    	
    	$root["t/hideuntil"] = array();
    	$root["t/hideuntil"]["string"] = "t hideuntil";
    	$root["t/hideuntil"]["url"] =	 new moodle_url("#");
    	$root["t/hideuntil"]["icon"] =	 "t/hideuntil";
    	
    	$root["t/left"] = array();
    	$root["t/left"]["string"] = "t left";
    	$root["t/left"]["url"] =	 new moodle_url("#");
    	$root["t/left"]["icon"] =	 "t/left";
    	
    	$root["t/less"] = array();
    	$root["t/less"]["string"] = "t less";
    	$root["t/less"]["url"] =	 new moodle_url("#");
    	$root["t/less"]["icon"] =	 "t/less";
    	
    	$root["t/lock"] = array();
    	$root["t/lock"]["string"] = "t lock";
    	$root["t/lock"]["url"] =	 new moodle_url("#");
    	$root["t/lock"]["icon"] =	 "t/lock";
    	
    	$root["t/locked"] = array();
    	$root["t/locked"]["string"] = "t locked";
    	$root["t/locked"]["url"] =	 new moodle_url("#");
    	$root["t/locked"]["icon"] =	 "t/locked";
    	
    	$root["t/locktime"] = array();
    	$root["t/locktime"]["string"] = "t locktime";
    	$root["t/locktime"]["url"] =	 new moodle_url("#");
    	$root["t/locktime"]["icon"] =	 "t/locktime";
    	
    	$root["t/log"] = array();
    	$root["t/log"]["string"] = "t log";
    	$root["t/log"]["url"] =	 new moodle_url("#");
    	$root["t/log"]["icon"] =	 "t/log";
    	
    	$root["t/markasread"] = array();
    	$root["t/markasread"]["string"] = "t markasread";
    	$root["t/markasread"]["url"] =	 new moodle_url("#");
    	$root["t/markasread"]["icon"] =	 "t/markasread";
    	
    	$root["t/mean"] = array();
    	$root["t/mean"]["string"] = "t mean";
    	$root["t/mean"]["url"] =	 new moodle_url("#");
    	$root["t/mean"]["icon"] =	 "t/mean";
    	
    	$root["t/message"] = array();
    	$root["t/message"]["string"] = "t message";
    	$root["t/message"]["url"] =	 new moodle_url("#");
    	$root["t/message"]["icon"] =	 "t/message";
    	
    	$root["t/messages"] = array();
    	$root["t/messages"]["string"] = "t messages";
    	$root["t/messages"]["url"] =	 new moodle_url("#");
    	$root["t/messages"]["icon"] =	 "t/messages";
    	
    	$root["t/more"] = array();
    	$root["t/more"]["string"] = "t more";
    	$root["t/more"]["url"] =	 new moodle_url("#");
    	$root["t/more"]["icon"] =	 "t/more";
    	
    	$root["t/move"] = array();
    	$root["t/move"]["string"] = "t move";
    	$root["t/move"]["url"] =	 new moodle_url("#");
    	$root["t/move"]["icon"] =	 "t/move";
    	
    	$root["t/moveleft"] = array();
    	$root["t/moveleft"]["string"] = "t moveleft";
    	$root["t/moveleft"]["url"] =	 new moodle_url("#");
    	$root["t/moveleft"]["icon"] =	 "t/moveleft";
    	
    	$root["t/nonempty"] = array();
    	$root["t/nonempty"]["string"] = "t nonempty";
    	$root["t/nonempty"]["url"] =	 new moodle_url("#");
    	$root["t/nonempty"]["icon"] =	 "t/nonempty";
    	
    	$root["t/online"] = array();
    	$root["t/online"]["string"] = "t online";
    	$root["t/online"]["url"] =	 new moodle_url("#");
    	$root["t/online"]["icon"] =	 "t/online";
    	
    	$root["t/outcomes"] = array();
    	$root["t/outcomes"]["string"] = "t outcomes";
    	$root["t/outcomes"]["url"] =	 new moodle_url("#");
    	$root["t/outcomes"]["icon"] =	 "t/outcomes";
    	
    	$root["t/passwordunmask-edit"] = array();
    	$root["t/passwordunmask-edit"]["string"] = "t passwordunmask-edit";
    	$root["t/passwordunmask-edit"]["url"] =	 new moodle_url("#");
    	$root["t/passwordunmask-edit"]["icon"] =	 "t/passwordunmask-edit";
    	
    	$root["t/passwordunmask-reveal"] = array();
    	$root["t/passwordunmask-reveal"]["string"] = "t passwordunmask-reveal";
    	$root["t/passwordunmask-reveal"]["url"] =	 new moodle_url("#");
    	$root["t/passwordunmask-reveal"]["icon"] =	 "t/passwordunmask-reveal";
    	
    	$root["t/portfolioadd"] = array();
    	$root["t/portfolioadd"]["string"] = "t portfolioadd";
    	$root["t/portfolioadd"]["url"] =	 new moodle_url("#");
    	$root["t/portfolioadd"]["icon"] =	 "t/portfolioadd";
    	
    	$root["t/preferences"] = array();
    	$root["t/preferences"]["string"] = "t preferences";
    	$root["t/preferences"]["url"] =	 new moodle_url("#");
    	$root["t/preferences"]["icon"] =	 "t/preferences";
    	
    	$root["t/preview"] = array();
    	$root["t/preview"]["string"] = "t preview";
    	$root["t/preview"]["url"] =	 new moodle_url("#");
    	$root["t/preview"]["icon"] =	 "t/preview";
    	
    	$root["t/print"] = array();
    	$root["t/print"]["string"] = "t print";
    	$root["t/print"]["url"] =	 new moodle_url("#");
    	$root["t/print"]["icon"] =	 "t/print";
    	
    	$root["t/ranges"] = array();
    	$root["t/ranges"]["string"] = "t ranges";
    	$root["t/ranges"]["url"] =	 new moodle_url("#");
    	$root["t/ranges"]["icon"] =	 "t/ranges";
    	
    	$root["t/reload"] = array();
    	$root["t/reload"]["string"] = "t reload";
    	$root["t/reload"]["url"] =	 new moodle_url("#");
    	$root["t/reload"]["icon"] =	 "t/reload";
    	
    	$root["t/removecontact"] = array();
    	$root["t/removecontact"]["string"] = "t removecontact";
    	$root["t/removecontact"]["url"] =	 new moodle_url("#");
    	$root["t/removecontact"]["icon"] =	 "t/removecontact";
    	
    	$root["t/removeright"] = array();
    	$root["t/removeright"]["string"] = "t removeright";
    	$root["t/removeright"]["url"] =	 new moodle_url("#");
    	$root["t/removeright"]["icon"] =	 "t/removeright";
    	
    	$root["t/reset"] = array();
    	$root["t/reset"]["string"] = "t reset";
    	$root["t/reset"]["url"] =	 new moodle_url("#");
    	$root["t/reset"]["icon"] =	 "t/reset";
    	
    	$root["t/restore"] = array();
    	$root["t/restore"]["string"] = "t restore";
    	$root["t/restore"]["url"] =	 new moodle_url("#");
    	$root["t/restore"]["icon"] =	 "t/restore";
    	
    	$root["t/right"] = array();
    	$root["t/right"]["string"] = "t right";
    	$root["t/right"]["url"] =	 new moodle_url("#");
    	$root["t/right"]["icon"] =	 "t/right";
    	
    	$root["t/scales"] = array();
    	$root["t/scales"]["string"] = "t scales";
    	$root["t/scales"]["url"] =	 new moodle_url("#");
    	$root["t/scales"]["icon"] =	 "t/scales";
    	
    	$root["t/show"] = array();
    	$root["t/show"]["string"] = "t show";
    	$root["t/show"]["url"] =	 new moodle_url("#");
    	$root["t/show"]["icon"] =	 "t/show";
    	
    	$root["t/sigma"] = array();
    	$root["t/sigma"]["string"] = "t sigma";
    	$root["t/sigma"]["url"] =	 new moodle_url("#");
    	$root["t/sigma"]["icon"] =	 "t/sigma";
    	
    	$root["t/sigmaplus"] = array();
    	$root["t/sigmaplus"]["string"] = "t sigmaplus";
    	$root["t/sigmaplus"]["url"] =	 new moodle_url("#");
    	$root["t/sigmaplus"]["icon"] =	 "t/sigmaplus";
    	
    	$root["t/sort"] = array();
    	$root["t/sort"]["string"] = "t sort";
    	$root["t/sort"]["url"] =	 new moodle_url("#");
    	$root["t/sort"]["icon"] =	 "t/sort";
    	
    	$root["t/sort_asc"] = array();
    	$root["t/sort_asc"]["string"] = "t sort_asc";
    	$root["t/sort_asc"]["url"] =	 new moodle_url("#");
    	$root["t/sort_asc"]["icon"] =	 "t/sort_asc";
    	
    	$root["t/sort_desc"] = array();
    	$root["t/sort_desc"]["string"] = "t sort_desc";
    	$root["t/sort_desc"]["url"] =	 new moodle_url("#");
    	$root["t/sort_desc"]["icon"] =	 "t/sort_desc";
    	
    	$root["t/stop"] = array();
    	$root["t/stop"]["string"] = "t stop";
    	$root["t/stop"]["url"] =	 new moodle_url("#");
    	$root["t/stop"]["icon"] =	 "t/stop";
    	
    	$root["t/switch"] = array();
    	$root["t/switch"]["string"] = "t switch";
    	$root["t/switch"]["url"] =	 new moodle_url("#");
    	$root["t/switch"]["icon"] =	 "t/switch";
    	
    	$root["t/switch_minus"] = array();
    	$root["t/switch_minus"]["string"] = "t switch_minus";
    	$root["t/switch_minus"]["url"] =	 new moodle_url("#");
    	$root["t/switch_minus"]["icon"] =	 "t/switch_minus";
    	
    	$root["t/switch_plus"] = array();
    	$root["t/switch_plus"]["string"] = "t switch_plus";
    	$root["t/switch_plus"]["url"] =	 new moodle_url("#");
    	$root["t/switch_plus"]["icon"] =	 "t/switch_plus";
    	
    	$root["t/switch_whole"] = array();
    	$root["t/switch_whole"]["string"] = "t switch_whole";
    	$root["t/switch_whole"]["url"] =	 new moodle_url("#");
    	$root["t/switch_whole"]["icon"] =	 "t/switch_whole";
    	
    	$root["t/tag"] = array();
    	$root["t/tag"]["string"] = "t tag";
    	$root["t/tag"]["url"] =	 new moodle_url("#");
    	$root["t/tag"]["icon"] =	 "t/tag";
    	
    	$root["t/unblock"] = array();
    	$root["t/unblock"]["string"] = "t unblock";
    	$root["t/unblock"]["url"] =	 new moodle_url("#");
    	$root["t/unblock"]["icon"] =	 "t/unblock";
    	
    	$root["t/unlock"] = array();
    	$root["t/unlock"]["string"] = "t unlock";
    	$root["t/unlock"]["url"] =	 new moodle_url("#");
    	$root["t/unlock"]["icon"] =	 "t/unlock";
    	
    	$root["t/unlocked"] = array();
    	$root["t/unlocked"]["string"] = "t unlocked";
    	$root["t/unlocked"]["url"] =	 new moodle_url("#");
    	$root["t/unlocked"]["icon"] =	 "t/unlocked";
    	
    	$root["t/up"] = array();
    	$root["t/up"]["string"] = "t up";
    	$root["t/up"]["url"] =	 new moodle_url("#");
    	$root["t/up"]["icon"] =	 "t/up";
    	
    	$root["t/user"] = array();
    	$root["t/user"]["string"] = "t user";
    	$root["t/user"]["url"] =	 new moodle_url("#");
    	$root["t/user"]["icon"] =	 "t/user";
    	
    	$root["t/usernot"] = array();
    	$root["t/usernot"]["string"] = "t usernot";
    	$root["t/usernot"]["url"] =	 new moodle_url("#");
    	$root["t/usernot"]["icon"] =	 "t/usernot";
    	
    	$root["t/viewdetails"] = array();
    	$root["t/viewdetails"]["string"] = "t viewdetails";
    	$root["t/viewdetails"]["url"] =	 new moodle_url("#");
    	$root["t/viewdetails"]["icon"] =	 "t/viewdetails";
    		
    	
    	
    	
    	return $root;
    }
    
    protected function dashboard() {
    	global $CFG;
    	
    	if($CFG->block_uai_local_modules && !in_array("dashboard", explode(",",$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	
    	$context = context_system::instance();
    	if(!has_capability("local/dashboard:view", $context)) {
    		return false;
    	}
    	
    	$root = array();
    	$root["string"] = "Dashboard";
    	$root["icon"] =   "emarking.png";
    	
    	$root["dashboard"]["string"] = "Dashboard";
    	$root["dashboard"]["url"] = new moodle_url("/local/dashboard/frontpage.php");
    	$root["dashboard"]["icon"] = "i/scales";
    	
    	return $root;
    }
    
    protected function deportes() {
    	global $USER, $CFG, $DB;
    	
    	if($CFG->block_uai_local_modules && !in_array("deportes",explode(",",$CFG->block_uai_local_modules))) {
    		return false;
    	}
    	
    	$email = explode("@", $USER->email);
    	$context = context_system::instance();
    	if($email[1] == $CFG->deportes_emailextension || is_siteadmin() || has_capability("local/deportes:edit", $context)){
    	
    		
	    	$root = array();
	    	$root["string"] = get_string("deportes", "block_uai");
	    	$root["icon"] =   "deportes.ico";
	    	
	    	if($CFG->deportes_courseid != 0) {
		    	$root["page"] = array();
		    	$root["page"]["string"] = get_string("page", "block_uai");
		    	$root["page"]["url"] = new moodle_url("/course/view.php", array("id" => $CFG->deportes_courseid));
		    	$root["page"]["icon"] = "a/view_list_active";
	    	}
	    	
    		$root["attendance"] = array();
    		$root["attendance"]["string"] = get_string("attendance", "block_uai");
    		$root["attendance"]["url"] = new moodle_url("/local/deportes/attendance.php");
    		$root["attendance"]["icon"] = "i/completion-auto-enabled";
    		
    		$root["reserve"] = array();
    		$root["reserve"]["string"] = get_string("reserve", "block_uai");
    		$root["reserve"]["url"] = new moodle_url("/local/deportes/reserve.php");
    		$root["reserve"]["icon"] = "t/assignroles";
    		
    		$root["schedule"] = array();
    		$root["schedule"]["string"] = get_string("schedule", "block_uai");
    		$root["schedule"]["url"] = new moodle_url("/local/deportes/schedule.php");
    		$root["schedule"]["icon"] =	 "e/table";
    		
    		if(has_capability("local/deportes:edit", $context)){
    		
	    	/*	$root["modules"] = array();
	    		$root["modules"]["string"] = get_string("modules", "block_uai");
	    		$root["modules"]["url"] = new moodle_url("/local/deportes/modules.php");
	    		$root["modules"]["icon"] =	 "i/calendar";
	    		
	    		$root["sports"] = array();
	    		$root["sports"]["string"] = get_string("editsports", "block_uai");
	    		$root["sports"]["url"] = new moodle_url("/local/deportes/addsports.php");
	    		$root["sports"]["icon"] =	 "i/edit";
	    	*/
	    		$root["scheduleedit"] = array();
	    		$root["scheduleedit"]["string"] = get_string("editschedule", "block_uai");
	    		$root["scheduleedit"]["url"] = new moodle_url("/local/deportes/addsportfile.php");
	    		$root["scheduleedit"]["icon"] =	 "e/table_props";
	    		
    		}
    	}else{
    		return false;
    	}
    	
    	return $root;
    }
    
    function get_content() {
    	global $CFG, $PAGE;
    	
    	// Check if content is already generated. If so, doesn't do it again
    	if ($this->content !== null) {
    		return $this->content;
    	}
    	
    	// Check if an user is logged in. Block doesn't render if not.
    	if (!isloggedin()) {
    		return false;
    	}

    	$this->content = new stdClass();
    	
    	$menu = array();
    		 
    	if($emarking = $this->emarking()) {
    		$menu[] = $emarking;
    	}
    		 
    	if($printorders = $this->print_orders()) {
    		$menu[] = $printorders;
    	}
    		 
    	if($reservasalas = $this->reserva_salas()) {
    		$menu[] = $reservasalas;
    	}
    		 
    	if($facebook = $this->facebook()) {
    		$menu[] = $facebook;
    	}
    		 
    	if($syncomega = $this->syncomega()) {
    		$menu[] = $syncomega;
    	}
    		 
    	if($paperattendance = $this->paperattendance()) {
    		$menu[] = $paperattendance;
    	}
    	if($deportes = $this->deportes()) {
    		$menu[] = $deportes;
    	}
    		 
    	$this->content->text = $this->block_uai_renderer($menu);
    	// Set content generated to true so that we know it has been done
    	$this->contentgenerated = true;
    	return $this->content;
    }
    
    /*
     * Produces a list of collapsible lists for each plugin to be displayed
     * 
     * @param array $plugins containing data sub-arrays of every plugin
     * @return html string to be inserted directly into the block
     */
    protected function block_uai_renderer($plugins) {
    	global $OUTPUT, $CFG;
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
    								"class" => "collapse",
    								"id" => "us".$id,
    						    "style" => "list-style-type: none; width:100%;"
    						));
    						$usersettingsspan = $OUTPUT->pix_icon($value["icon"], "")." ".html_writer::tag("span", $value["string"]);
    						$usersettingsspan = html_writer::tag("a", $usersettingsspan, array("href" => "", "style" => "text-decoration: none !important; pointer-events: none;"));
    						$usersettingsspan = html_writer::tag("li", $usersettingsspan, array(
    								"data-toggle" => "collapse",
    								"data-target" => "#us".$id,
    								"style" => "list-style-type: none; cursor: pointer; width:100%;"
    						));
    							
    						$usersettingshtml = html_writer::tag("ul", $usersettingshtml);
    						$elementhtml .= $usersettingsspan.$usersettingshtml;
    					}
    				}
    				
    				$settingshtml = html_writer::tag("ul", $settingshtml, array(
    						"class" => "collapse", 
    						"id" => "s".$id,
    				        "style" => "list-style-type: none; width:100%;"
    				));
    				$settingsspan = $OUTPUT->pix_icon($values["icon"], "")." ".html_writer::tag("span", $values["string"]);
    				$settingsspan = html_writer::tag("a", $settingsspan, array("href" => "", "style" => "text-decoration: none !important; pointer-events: none;"));
    				$settingsspan = html_writer::tag("li", $settingsspan, array(
    						"data-toggle" => "collapse",
    						"data-target" => "#s".$id,
    						"style" => " cursor: pointer; width:100%;"
    				));
    				
    				$settingshtml = html_writer::tag("ul", $settingshtml);
    				$elementhtml .= $settingsspan.$settingshtml;
    			}
    		}
    		
    		// Get all the list components above in one collapsable list delimeter ("ul" tag)
    		$pluginhtml = html_writer::tag("ul", $elementhtml, array(
    				"class" => "collapse",
    				"id" => $id,
    		    "style" => "list-style-type: none; width:100%;" 
    		));
    		
    		// Then make it part of the plugins list
    		$pluginspan = html_writer::tag("span", $plugin["string"]);
    		
    		// Modules icons
    		if($CFG->block_uai_icons == "1") {
    			$pluginspan = html_writer::empty_tag("img", array("src" => $CFG->wwwroot."/blocks/uai/pix/".$plugin["icon"], "height" => "16", "width" => "16"))." ".$pluginspan;
    		}
    		$pluginspan = html_writer::tag("li", $pluginspan, array(
    				"data-toggle" => "collapse", 
    				"data-target" => "#".$id,
    				"style" => "list-style-type: none; cursor: pointer; width:100%;"
    		));
    		
    		$pluginhtml = html_writer::tag("li", $pluginhtml);
    		
    		// Save each plugin's content in an array to be displayed later
    		$content[] = $pluginspan.$pluginhtml;
    		
    		// This id is used as each element's id for collapse toggling
    		$id++;
    	}
    	
    	return html_writer::tag("ul", implode("", $content), array("class" => "nav nav-list"));
    }
    
    public function get_aria_role() {
    	return "navigation";
    }
}
