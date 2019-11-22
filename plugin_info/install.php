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
		exec('sudo chmod 775'.dirname(__FILE__).'../resources/cookies.txt');
		exec('sudo chmod 775'.dirname(__FILE__).'../resources/atome_connection.json');
        exec('sudo chown www-data:www-data -R '.dirname(__FILE__).'../resources/');
		sigri_atome::CronIsInstall();
	}
	
	function sigri_atome_update() {
		log::add('sigri_atome', 'debug', 'Mise à jour du plugin sigri_atome');
		sigri_atome::CronIsInstall();
	}
	
	function sigri_atome_remove() {
		// Empêche de supprimer les crons
		//return;

		$cron = cron::byClassAndFunction('sigri_atome', 'cronMinute');
		if (is_object($cron)) {
			log::add('sigri_atome', 'debug', 'Arrêt du cronMinute');
			$cron->stop();
			log::add('sigri_atome', 'debug', 'Suppression du cronMinute');
			$cron->remove();
		}

		$cron = cron::byClassAndFunction('sigri_atome', 'cronHoraire');
		if (is_object($cron)) {
			log::add('sigri_atome', 'debug', 'Arrêt du cronHoraire');
			$cron->stop();
			log::add('sigri_atome', 'debug', 'Suppression du cronHoraire');
			$cron->remove();
		}

		$cron = cron::byClassAndFunction('sigri_atome', 'cronJournalier');
		if (is_object($cron)) {
			log::add('sigri_atome', 'debug', 'Arrêt du cronJournalier');
			$cron->stop();
			log::add('sigri_atome', 'debug', 'Suppression du cronJournalier');
			$cron->remove();
		}
	}
?>