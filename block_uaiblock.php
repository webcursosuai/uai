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
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

class block_uaiblock extends block_base {
	
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
    
    protected function emarking() {
    	global $CFG, $PAGE;
    	
    	if($CFG->block_uaiblock_local_modules && !in_array('emarking',explode(',',$CFG->block_uaiblock_local_modules))) {
    		return false;
    	}
    	
    	$context = $PAGE->context;
    	$course = $PAGE->course;
    	$courseid = $course->id;
    	
    	if($courseid == null || $courseid == 1 || !has_capability('mod/assign:grade', $context)) {
    		return false;
    	}
    	
    	$root = array();
    	
    	$root["string"] = get_string('blockexams', 'block_uaiblock');
    	
    	$root["newprintorder"] = array();
    	$root["newprintorder"]["string"] = get_string('blocknewprintorder', 'block_uaiblock');
    	$root["newprintorder"]["url"]	 = new moodle_url("/course/modedit.php", array("sr"=>0,"add"=>"emarking","section"=>0,"course"=>$courseid));
    	$root["newprintorder"]["icon"]	 = 't/portfolioadd';
    	
    	$root["myexams"] = array();
    	$root["myexams"]["string"] = get_string('blockmyexams', 'block_uaiblock');
    	$root["myexams"]["url"]	   = new moodle_url("/mod/emarking/print/exams.php", array("course"=>$courseid));
    	$root["myexams"]["icon"]   = 'a/view_list_active';
    	
    	$root["cycle"] = array();
    	$root["cycle"]["string"] = get_string('cycle', 'block_uaiblock');
    	$root["cycle"]["url"]	 = new moodle_url("/mod/emarking/reports/cycle.php", array("course"=>$courseid));
    	$root["cycle"]["icon"]	 = 'i/course';
    	
    	return $root;
    }
    
    protected function reports() {
    	global $CFG, $PAGE;
    	
    	$course = $PAGE->course;
    	
    	$reportsurl = new moodle_url("/local/reportes/index.php", array("courseid"=>$course->id));
    	
    	$root = array();
    	
    	$root["string"] = get_string('reportes', 'block_uaiblock');
    	
    	$root["reports"] = array();
    	$root["reports"]["string"] = get_string('reportes', 'block_uaiblock');
    	$root["reports"]["url"] =	 new moodle_url("/local/reportes/index.php", array("courseid"=>$course->id));
    	$root["reports"]["icon"] =	 "t/scales";
    	
    	return $root;
    }
    
    protected function reserva_salas() {
    	global $USER, $CFG, $DB, $COURSE, $PAGE;
    	
    	$context = context_system::instance();
    	$root = array();
    	
    	$root["string"] = get_string('reservasal', 'block_uaiblock');
    	
    	$root["book"] = array();
    	$root["book"]["string"] = get_string('reservar', 'block_uaiblock');
    	$root["book"]["url"] =	  new moodle_url("/local/reservasalas/reservar.php");
    	$root["book"]["icon"] =	  'i/report';
    	
    	//if(!has_capability('local/reservasalas:advancesearch', $context)) {
    		$root["booked"] = array();
    		$root["booked"]["string"] = get_string('misreservas', 'block_uaiblock');
    		$root["booked"]["url"] =	new moodle_url("/local/reservasalas/misreservas.php");
    		$root["booked"]["icon"] =	'i/report';
    	//} else {
    		$root["search"] = array();
    		$root["search"]["string"] =	get_string('search', 'block_uaiblock');
    		$root["search"]["url"] =	new moodle_url("/local/reservasalas/search.php");
    		$root["search"]["icon"] =	'i/report';
    	//}
    	
    	//if(has_capability('local/reservasalas:administration', $context) || 
    			//has_capability('local/reservasalas:bockinginfo', $context) ||
				//has_capability('local/reservasalas:blocking', $context)) {
			$root["settings"] = array();
			$root["settings"]["string"] = 	get_string('ajustesrs', 'block_uaiblock');
			$root["settings"]["icon"] =		'i/settings';
		//}
		
		//if(has_capability('local/reservasalas:administration', $context)) {
			$root["settings"]["rooms"] = array();
			$root["settings"]["rooms"]["string"] = get_string('ajmodversal', 'block_uaiblock');
			$root["settings"]["rooms"]["url"] =	   new moodle_url("/local/reservasalas/salas.php");
			$root["settings"]["rooms"]["icon"] =   'i/report';
    	
	    	$root["settings"]["buildings"] = array();
	    	$root["settings"]["buildings"]["string"] = get_string('ajmodvered', 'block_uaiblock');
	    	$root["settings"]["buildings"]["url"] =	   new moodle_url("/local/reservasalas/edificios.php");
	    	$root["settings"]["buildings"]["icon"] =   'i/report';
    	
	    	$root["settings"]["campus"] = array();
	    	$root["settings"]["campus"]["string"] = get_string('ajsedes', 'block_uaiblock');
	    	$root["settings"]["campus"]["url"] =	new moodle_url("/local/reservasalas/sedes.php");
	    	$root["settings"]["campus"]["icon"] =   'i/report';
    	
	    	$root["settings"]["resources"] = array();
	    	$root["settings"]["resources"]["string"] = get_string('urlresources', 'block_uaiblock');
	    	$root["settings"]["resources"]["url"] =	   new moodle_url("/local/reservasalas/resources.php");
	    	$root["settings"]["resources"]["icon"] =   'i/report';
		//}
		
		//if(has_capability('local/reservasalas:bockinginfo', $context)) {
			$root["settings"]["userbooks"] = array();
			$root["settings"]["userbooks"]["string"] = get_string('viewuserreserves', 'block_uaiblock');
			$root["settings"]["userbooks"]["url"] =	   new moodle_url("/local/reservasalas/reservasusuarios.php");
			$root["settings"]["userbooks"]["icon"] =   'i/report';
    	
	    	$root["settings"]["diagnostic"] = array();
	    	$root["settings"]["diagnostic"]["string"] =	get_string('diagnostic', 'block_uaiblock');
	    	$root["settings"]["diagnostic"]["url"] =	new moodle_url("/local/reservasalas/diagnostico.php");
	    	$root["settings"]["diagnostic"]["icon"] =	'i/report';
		//}
		
		//if(has_capability('local/reservasalas:blocking', $context)) {
			$root["settings"]["usersettings"] = array();
			$root["settings"]["usersettings"]["string"] = get_string('usuarios', 'block_uaiblock');
			$root["settings"]["usersettings"]["icon"] = 'i/role';
    	
	    	$root["settings"]["usersettings"]["block"] = array();
	    	$root["settings"]["usersettings"]["block"]["string"] = get_string('bloquear', 'block_uaiblock');
	    	$root["settings"]["usersettings"]["block"]["url"] =	   new moodle_url("/local/reservasalas/bloquear.php");
	    	$root["settings"]["usersettings"]["block"]["icon"] =   'i/report';
    	
	    	$root["settings"]["usersettings"]["unblock"] = array();
	    	$root["settings"]["usersettings"]["unblock"]["string"] = get_string('desbloq', 'block_uaiblock');
	    	$root["settings"]["usersettings"]["unblock"]["url"] =	 new moodle_url("/local/reservasalas/desbloquear.php");
	    	$root["settings"]["usersettings"]["unblock"]["icon"] =	 'i/report';
		//}
		
		//if(isset($CFG->local_uai_debug) && $CFG->local_uai_debug == 1) {
			//if(has_capability('local/reservasalas:upload', $context)) {
				$root["upload"] = array();
				$root["upload"]["string"] =	get_string('upload', 'block_uaiblock');
				$root["upload"]["url"] =	new moodle_url("/local/reservasalas/upload.php");
				$root["upload"]["icon"] =	'i/report';
			//}
		//}
    	
    	return $root;
    }
    
    protected function syncomega() {
    	$root = array();
    	
    	$root["string"] = get_string('syncomega', 'block_uaiblock');
    	
    	$root["create"] = array();
    	$root["create"]["string"] = get_string('synccreate', 'block_uaiblock');
    	$root["create"]["url"] =	new moodle_url("/local/sync/create.php");
    	$root["create"]["icon"] =	'e/new_document';
    	
    	$root["records"] = array();
    	$root["records"]["string"] = get_string('syncrecord', 'block_uaiblock');
    	$root["records"]["url"] =	 new moodle_url("/local/sync/record.php");
    	$root["records"]["icon"] =	 'e/fullpage';
    	
    	$root["history"] = array();
    	$root["history"]["string"] = get_string('synchistory', 'block_uaiblock');
    	$root["history"]["url"] =	 new moodle_url("/local/sync/history.php");
    	$root["history"]["icon"] =	 'i/siteevent';
    	
    	return $root;
    }
    
    function get_content() {
    	global $CFG, $PAGE;
    	// First check if we have already generated, don't waste cycles
    	$PAGE->requires->jquery();
    	$PAGE->requires->jquery_plugin ( 'ui' );
    	$PAGE->requires->jquery_plugin ( 'ui-css' );
    	$this->content = new stdClass();
    	/*
    	$menu = ' <ul id="accordion">
					<li><div>Reserva de Salas</div>
						<ul>
							<li><a href="#">Golf</a></li>
							<li><a href="#">Cricket</a></li>
							<li><a href="#">Football</a></li>
						</ul>
					</li>
					<li><div>Facebook</div>
						<ul>
							<li><a href="#">iPhone</a></li>
							<li><a href="#">Facebook</a></li>
							<li><a href="#">Twitter</a></li>
						</ul>
					</li>
					<li><div>Sincronizaciones Omega</div>
						<ul>
							<li><a href="#">Obama</a></li>
							<li><a href="#">Iran Election</a></li>
							<li><a href="#">Health Care</a></li>
						</ul>
					</li>
				</ul>';
    	
    	$menu .= "<script>
	    			$('#accordion > li > div').click(function(){	
						if(false == $(this).next().is(':visible')) {
							$('#accordion ul').slideUp(300);
						}
						$(this).next().slideToggle(300);
					});
					
    			</script>";
    	
    	
    	$menu = '
				  <div class="panel-group">
				    <div class="panel panel-default">
    			
				      <div class="panel-heading">
				        <h4 class="panel-title">
				          <a data-toggle="collapse" href="#collapse1">Reserva de salas</a>
				        </h4>
				      </div>
				      <div id="collapse1" class=" collapse">
				        <ul class="list-group">
				          <li class="list-group-item">One</li>
				          <li class="list-group-item">Two</li>
				          <li class="list-group-item">Three</li>
				        </ul>				       
				    </div>
    			
	    			<div class="panel-heading">
					        <h5 class="panel-title">
					          <a data-toggle="collapse" href="#collapse2">Paper Attendance</a>
					        </h5>
					      </div>
					      <div id="collapse2" class=" collapse">
					        <ul class="list-group">
					          <li class="list-group-item">One</li>
					          <li class="list-group-item">Two</li>
					          <li class="list-group-item">Three</li>
					        </ul>				       
					    </div>
    			
	    			<div class="panel-heading">
						        <h4 class="panel-title">
						          <a data-toggle="collapse" href="#collapse3">Sincronizaciones Omega</a>
						        </h4>
						      </div>
						      <div id="collapse3" class=" collapse">
						        <ul class="list-group">
						          <li class="list-group-item">One</li>
						          <li class="list-group-item">Two</li>
						          <li class="list-group-item">Three</li>
						        </ul>				       
						    </div>
    			
    			
				  </div>
				</div>';
    	
    	$menu = '<ul class="nav nav-list">
			    <li class="nav-header"  data-toggle="collapse" data-target="#test"> <span>
			            Home
			            <span class="pull-right">X</span>
			</span>
			        <ul class="nav nav-list collapse" id="test">
			            <li><a href="/ticket_list.cfm" title="Show list of tickets">Open Tickets</a>
			            </li>
			            <li><a href="/account/" title="Edit user accounts">Accounts / Community</a>
			            </li>
			        </ul>
			    </li>
			</ul>';
    	*/
    	
    	$menu = array();
    	
    	if($emarking = $this->emarking()) {
    		$menu[] = $emarking;
    	}
    	
    	if($reports = $this->reports()) {
    		$menu[] = $reports;
    	}
    	
    	if($reservasalas = $this->reserva_salas()) {
    		$menu[] = $reservasalas;
    	}
    	
    	if($syncomega = $this->syncomega()) {
    		$menu[] = $syncomega;
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
    	global $OUTPUT;
    	$content = array();
    	
    	$id = 0;
    	
    	// For each plugin to be shown, make a collapsible list
    	foreach($plugins as $plugin) {
    		$elementhtml = "";
    		
    		// For each element in the plugin, create a collapsable list element
    		foreach($plugin as $element => $values) {
    			// The "string" element is the plugin's name
    			if($element != "string" && $element != "settings") {
    				// Define the icon along with the title & link to its page
    				$html = $OUTPUT->pix_icon($values["icon"], "").
    					html_writer::tag("a", $values["string"], array("href" => $values["url"]));
    				
    				// Place it in a "li" element from the list
    				$html = html_writer::tag("li", $html, array("style" => "padding-left: 20px;"));
    			
    				$elementhtml .= $html;
    			} else if($element == "settings") {
    				// The settings element is a sub-collapsible list, with its own elements
    				$settingshtml = "";
    				
    				// Loop over the settings elements (max: 6 loops)
    				foreach($values as $setting => $value) {
    					if($setting != "string" && $setting != "icon" && $setting != "usersettings") {
	    					$html = $OUTPUT->pix_icon($value["icon"], "").
	    						html_writer::tag("a", $value["string"], array("href" => $value["url"]));
	    					
	    					$html = html_writer::tag("li", $html, array("style" => "padding-left: 20px;"));
	    					
	    					$settingshtml .= $html;
    					} else if($setting == "usersettings") {
    						// The user settings element is also a sub-collapsible list, so it needs another loop
    						$usersettingshtml = "";
    						foreach($value as $usersetting => $uservalue) {
    							// Assemble each sub element in user' settings
    							if($usersetting != "string" && $usersetting != "icon") {
    								$html = $OUTPUT->pix_icon($uservalue["icon"], "").
    									html_writer::tag("a", $uservalue["string"], array("href" => $uservalue["url"]));
    								
    								$html = html_writer::tag("li", $html, array("style" => "padding-left: 20px;"));
    								
    								$usersettingshtml .= $html;
    							}
    						}
    							
    						$usersettingshtml = html_writer::tag("ul", $usersettingshtml, array(
    								"class" => "nav nav-list collapse",
    								"id" => "us".$id,
    								"style" => "padding-left: 20px;"
    						));
    						$usersettingsspan = $OUTPUT->pix_icon($value["icon"], "").html_writer::tag("span", $value["string"]);
    						$usersettingsspan = html_writer::tag("li", $usersettingsspan, array(
    								"class" => "nav-header",
    								"data-toggle" => "collapse",
    								"data-target" => "#us".$id,
    								"style" => "cursor: pointer; padding-left: 20px;"
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
    				$settingsspan = $OUTPUT->pix_icon($values["icon"], "").html_writer::tag("span", $values["string"]);
    				$settingsspan = html_writer::tag("li", $settingsspan, array(
    						"class" => "nav-header",
    						"data-toggle" => "collapse",
    						"data-target" => "#s".$id,
    						"style" => "cursor: pointer; padding-left: 20px;"
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
    				"class" => "nav-header", 
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