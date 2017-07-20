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
//a

/**
 *
*
* @package    blocks
* @subpackage uai
* @copyright  2017 Mark Michaelsen (mmichaelsen678@gmail.com)
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined("MOODLE_INTERNAL") || die;

if ($ADMIN->fulltree) {
	$settings->add(new admin_setting_configmulticheckbox(
			"block_uai_local_modules",
			"Módulos locales activos",
			"Lista de módulos locales activos.",
			array(
					"reservasalas" => 1,
					"facebook" => 1,
					"emarking" => 1,
					"reportes" => 0,
					"paperattendance" => 1,
					"syncomega" => 1,
					"deportes" => 1
			),
			array(
					"reservasalas" => "local/reservasalas",
					"facebook" => "local/facebook",
					"emarking" => "local/emarking",
					"reportes" => "local/reportes",
					"paperattendance" => "local/paperattendance",
					"syncomega" => "local/sync",
					"deportes" => "local/deportes"
			)
	));
	
	$settings->add(new admin_setting_configcheckbox(
			"block_uai_icons",
			"Iconos de bloque",
			"Define si se muestran los íconos de cada módulo",
			1
	));
}