<?php
	/* This file is part of Jeedom.
		*
		* Jeedom is free software: you can redistribute it and/or modify
		* it under the terms of the GNU General Public License as published by
		* the Free Software Foundation, either version 3 of the License, or
		* (at your option) any later version.
		*
		* Jeedom is distributed in the hope that it will be useful,
		* but WITHOUT ANY WARRANTY; without even the implied warranty of
		* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
		* GNU General Public License for more details.
		*
		* You should have received a copy of the GNU General Public License
		* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
	*/
	
	require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
	
	function sigri_atome_install() {
		log::add('sigri_atome', 'debug', 'Installation du plugin sigri_atome');
		exec('sudo chmod 777'.dirname(__FILE__).'/install.sql');
		$sql = file_get_contents(dirname(__FILE__).'/install.sql');
		DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
		sigri_atome::CronIsInstall();
	}
	
	function sigri_atome_update() {
		log::add('sigri_atome', 'debug', 'Mise à jour du plugin sigri_atome');
		sigri_atome::CronIsInstall();
	}
	
	function sigri_atome_remove() {

		return;

		$cron = cron::byClassAndFunction('sigri_atome', 'launch_sigri_atome');
		if (is_object($cron)) {
			log::add('sigri_atome', 'debug', 'Arrêt du cron launch_sigri_atome');
			$cron->stop();
			log::add('sigri_atome', 'debug', 'Suppression du cron launch_sigri_atome');
			$cron->remove();
		}

		$cron = cron::byClassAndFunction('sigri_atome', 'cronHoraire');
		if (is_object($cron)) {
			log::add('sigri_atome', 'debug', 'Arrêt du cron cronHoraire');
			$cron->stop();
			log::add('sigri_atome', 'debug', 'Suppression du cron cronHoraire');
			$cron->remove();
		}

		$cron = cron::byClassAndFunction('sigri_atome', 'cronJournalier');
		if (is_object($cron)) {
			log::add('sigri_atome', 'debug', 'Arrêt du cron cronJournalier');
			$cron->stop();
			log::add('sigri_atome', 'debug', 'Suppression du cron cronJournalier');
			$cron->remove();
		}

		$sql = "DROP TABLE IF EXISTS `sigri_atome_hour`;";
		DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);

		$sql = "DROP TABLE IF EXISTS `sigri_atome_day`;";
		DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
	}
?>