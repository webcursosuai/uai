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
    
    function get_content() {
    	global $CFG, $PAGE;
    	// First check if we have already generated, don't waste cycles
    	$PAGE->requires->jquery();
    	$PAGE->requires->jquery_plugin ( 'ui' );
    	$PAGE->requires->jquery_plugin ( 'ui-css' );
    	$this->content = new stdClass();
    	
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
    	$this->content->text = $menu;
    	// Set content generated to true so that we know it has been done
    	$this->contentgenerated = true;
    	return $this->content;
    }
    
    protected function get_navigation() {

    }
    
    public function html_attributes() {

    }
    
    public function get_aria_role() {
    	return 'navigation';
    }
}