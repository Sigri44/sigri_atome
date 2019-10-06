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
		// Empêche de supprimer les crons
		//return;

		/*
		$cron = cron::byClassAndFunction('sigri_atome', 'launch_sigri_atome');
		if (is_object($cron)) {
			log::add('sigri_atome', 'debug', 'Arrêt du cron launch_sigri_atome');
			$cron->stop();
			log::add('sigri_atome', 'debug', 'Suppression du cron launch_sigri_atome');
			$cron->remove();
		}
		*/

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

		// Suppression de la Database uniquement si la case est cochée
        /*
        log::add('sigri_atome', 'debug', '*** Désinstallation : Partie 1 ***');

        $eqLogics = eqLogic::byType('sigri_atome');
        log::add('sigri_atome', 'debug', '$eqLogics : ' . var_dump($eqLogics));
        //if (!empty($eqLogics)) {
        foreach ($eqLogics as $eqLogic) {
            log::add('sigri_atome', 'debug', '$eqLogic : ' . var_dump($eqLogic));
        }

        foreach (eqLogic::byType('sigri_atome') as $sigriatome) {
            log::add('sigri_atome', 'debug', '$sigriatome : '.var_dump($sigriatome));
            if (!empty($sigriatome->getConfiguration('isDrop')) && !empty($sigriatome->getConfiguration('saveIntoDatabase')) && !empty($sigriatome->getConfiguration('saveIntoJson'))) {
                if ($sigriatome->getConfiguration('isDrop')) {
                    log::add('sigri_atome', 'debug', '$eqLogic->getConfiguration(\'isDrop\') : ' . $sigriatome->getConfiguration('isDrop'));
                }
                if ($sigriatome->getConfiguration('saveIntoDatabase')) {
                    log::add('sigri_atome', 'debug', '$eqLogic->getConfiguration(\'saveIntoDatabase\') : ' . $sigriatome->getConfiguration('saveIntoDatabase'));
                }
                if ($sigriatome->getConfiguration('saveIntoJson')) {
                    log::add('sigri_atome', 'debug', '$eqLogic->getConfiguration(\'saveIntoJson\') : ' . $sigriatome->getConfiguration('saveIntoJson'));
                }
            } else {
                log::add('sigri_atome', 'debug', 'Tous les champs de configuration sont vides.');
            }
            log::add('sigri_atome', 'debug', 'Valeur isDrop : ' . $sigriatome->getIsDrop);
            log::add('sigri_atome', 'debug', 'Valeur saveIntoDatabase : ' . $sigriatome->getIsDrop);
            log::add('sigri_atome', 'debug', 'Valeur saveIntoJson : ' . $sigriatome->getIsDrop);
        }

		log::add('sigri_atome', 'debug', '*** Désinstallation : Partie 2 ***');
        */
        /*
        $sql = "DROP TABLE IF EXISTS `sigri_atome_hour`;";
        DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);

        $sql = "DROP TABLE IF EXISTS `sigri_atome_day`;";
        DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);
        */
	}
?>