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
			    $root["takeattendance"]["url"] =	 new moodle_url("/local/paperattendance/takeattendance.php");
			    $root["takeattendance"]["icon"] =	 "f/env";
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
