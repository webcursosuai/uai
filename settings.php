<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
	// Links a mostrar en bloque UAI
	$settings->add(new admin_setting_configmulticheckbox(
			'block_uai_local_modules',
			'Módulos locales activos',
			'Lista de módulos locales activos.',
			array(
					'toolbox' => 1,
					'reservasalas' => 1,
					'facebook' => 1,
					'emarking' => 1,
					'bibliography' => 1,
					'reportes' => 1,
					'paperattendance' => 0,
					'syncomega' => 1
			),
			array(
					'toolbox'=>'local/toolbox',
					'reservasalas'=>'local/reservasalas',
					'facebook'=>'local/facebook',
					'emarking'=>'local/emarking',
					'bibliography'=>'local/bibliography',
					'reportes'=>'local/reportes',
					'paperattendance'=>'local/paperattendance',
					'syncomega'=>'local/sync'					
			)
	));
}