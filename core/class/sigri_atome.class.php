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
	
	/* * ***************************Includes********************************* */
	require_once __DIR__ . '/../../../../core/php/core.inc.php';

	// *****************
	// * Configuration *
	// *****************
	$URL_API = "https://esoftlink.esoftthings.com";
	$API_LOGIN = "/api/user/login.json";
	$API_DATA = "/graph-query-last-consumption";
	$URL_LOGIN = $URL_API . $API_LOGIN;
	$RESSOURCES_DIR = __DIR__.'/ressources/';
	$COOKIES_FILE = $RESSOURCES_DIR.'cookies.txt';
	
	class sigri_atome extends eqLogic {
		public function preUpdate() {

		}
		
		public function postUpdate() {
			log::add('sigri_atome', 'debug', 'Mise à jour de l\'équipement');
			self::CronIsInstall();
		}
		
		public function preRemove() {
			
		}
		
		public function postRemove() {
			
		}

		public static function cronHourly() {
			log::add('sigri_atome', 'debug', '----------Lancement du cronHourly----------');
			$eqLogics = eqLogic::byType('sigri_atome');
			foreach ($eqLogics as $eqLogic) {
				if ($eqLogic->getIsEnable() == 1) {
					if (!empty($eqLogic->getConfiguration('identifiant')) && !empty($eqLogic->getConfiguration('password'))) {
						$json_connection = $eqLogic->Call_Atome_Login();
						$period = "day";
						$eqLogic->Call_Atome_API($json_connection, $period);
					}
				}
			}
		}

		public static function cronDaily() {
			log::add('sigri_atome', 'debug', '----------Lancement du cronDaily----------');
			$eqLogics = eqLogic::byType('sigri_atome');
			foreach ($eqLogics as $eqLogic) {
				if ($eqLogic->getIsEnable() == 1) {
					if (!empty($eqLogic->getConfiguration('identifiant')) && !empty($eqLogic->getConfiguration('password'))) {
						$json_connection = $eqLogic->Call_Atome_Login();
						$period = "month";
						$eqLogic->Call_Atome_API($json_connection, $period);
					}
				}
			}
		}

		public function Call_Atome_Login() {
			// *******************************
			// * Etape 1 - Connexion à l'API *
			// *******************************
			log::add('sigri_atome', 'debug', 'Authentification sur Atome');

			// Forcer cURL à utiliser un nouveau cookie de session
			log::add('sigri_atome', 'debug', 'Configuration du cookie');
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_COOKIESESSION, true); 
	
			curl_setopt_array($curl, array(
				CURLOPT_URL => $URL_LOGIN,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "POST",
				CURLOPT_POSTFIELDS => "{\"email\": \"$this->getConfiguration('identifiant')\",\"plainPassword\": \"$this->getConfiguration('password')\"}",
				CURLOPT_HTTPHEADER => array(
					"Cache-Control: no-cache",
					"Content-Type: application/json"
				),
			));
	
			// Enregistrement du cookie
			log::add('sigri_atome', 'debug', 'Enregistrement du cookie');
			curl_setopt($curl, CURLOPT_COOKIEJAR, $COOKIES_FILE);
	
			log::add('sigri_atome', 'debug', 'Récupération de la connexion API');
			$response = curl_exec($curl);
	
			// Enregistrement de la connexion au format JSON
			log::add('sigri_atome', 'debug', 'Enregistrement de la connexion au format JSON');
			file_put_contents($JSON_CONNECTION, $response);

			// Récupération des erreurs curl
			$err = curl_error($curl);
			curl_close($curl);
	
			if ($err) {
				log::add('sigri_atome', 'error', 'cURL Error #:' . $err);
			} else {
				log::add('sigri_atome', 'debug', 'Connexion réussie, récupération des informations en cours ...');
			}

			return $response;
		}
		
		public function Call_Atome_API($response, $period) {
			// ************
			// * Ephémère *
			// ************
			// Configuration Serveur
			$STORAGE = "JSON"; // JSON ou BDD
			//$period = "day"; // null, day, week ou month

			// Configuration date
			$TODAY = date("Y-m-d");
			$NOW = date("Hi");

			// Configuration JSON
			$timestamp = date_timestamp_get(date_create($TODAY . $NOW)) + 3600;
    		$DAY_EXPORT = date("d_m_Y_H_i", $timestamp);
			$JSON_EXPORT_FILENAME = "export_".$period."_".$DAY_EXPORT.".json";  // Nom du fichier JSON à utiliser lors d'un export "API"
			$JSON_EXPORT_FILE = $RESSOURCES_DIR.$JSON_EXPORT_FILENAME;
			$JSON_CONNECTION = $RESSOURCES_DIR.'atome_connection.json';
			
			// Extraction des infos utilisateurs
			$json_login = json_decode($response);
			$user_id = $json_login->id;
			$user_reference = $json_login->subscriptions[0]->reference;

			// Configuration de la période à récupérer
			log::add('sigri_atome', 'debug', 'Configuration de la période à récupérer');
			if ($period != null) {
				$URL_DATA = $URL_API . "/" . $user_id . "/" . $user_reference . $API_DATA . "?period=" . $period;
			} else {
				$URL_DATA = $URL_API . "/" . $user_id . "/" . $user_reference . $API_DATA;
			}
	
			// ********************************************
			// * Etape 2 - Récupération des datas énergie *
			// ********************************************
			log::add('sigri_atome', 'debug', 'Récupération des datas énergie en cours ...');
			$curl = curl_init();
	
			curl_setopt_array($curl, array(
				CURLOPT_URL => $URL_DATA,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => "",
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_HTTPHEADER => array(
					"Cache-Control: no-cache",
					"Content-Type: application/json",
				),
			));
	
			// Récupération du cookie
			curl_setopt($curl, CURLOPT_COOKIESESSION, true);
	
			// Fichier dans lequel cURL va lire les cookies
			curl_setopt($curl, CURLOPT_COOKIEFILE, $COOKIES_FILE);
	
			$response = curl_exec($curl);
			$err = curl_error($curl);
	
			curl_close($curl);
	
			if ($err) {
				log::add('sigri_atome', 'error', 'cURL Error #:' . $err);
			} else {
				// Enregistrement des datas énergie
				if ($STORAGE == "JSON") {
					log::add('sigri_atome', 'debug', 'Enregistrement des datas énergie au format JSON');
					file_put_contents($JSON_EXPORT_FILE, $response);
				} elseif ($STORAGE == "BDD") {
					log::add('sigri_atome', 'debug', 'Enregistrement des datas énergie en BDD');
					if ($period == "day") {
						for ($i = 0; $i<25; $i++) {
							// Extraction des data énergie
							$json_data = json_decode($response);
							$date = substr($json_data->data[$i]->time, 0, 10);
							$time = substr($json_data->data[$i]->time, 11, 8);
							// Ajout d'1h pour corriger le fuseau horaire
							$timestamp = date_timestamp_get(date_create($date . $time)) + 3600;
							$datetime = date("Y-m-d H:i:s", $timestamp);
							$totalConsumption = $json_data->data[$i]->totalConsumption;
							$indexHP = $json_data->data[$i]->consumption->index2;
							$indexHC = $json_data->data[$i]->consumption->index1;
							$costHP = $json_data->data[$i]->consumption->bill2;
							$costHC = $json_data->data[$i]->consumption->bill1;

							// Enregistrement de l'heure dans la BDD
							log::add('sigri_atome', 'debug', 'Enregistrement dans la BDD en cours de l\'heure : '.$i);
							$sql = 'INSERT INTO sigri_atome_hour (hour, total_consumption, index_hp, index_hc, cost_hp, cost_hc) VALUES (\''.$datetime.'\', \''.$totalConsumption.'\', \''.$indexHP.'\', \''.$indexHC.'\', \''.$costHP.'\', \''.$costHC.'\')ON DUPLICATE KEY UPDATE total_consumption='.$totalConsumption.', index_hp='.$indexHP.', index_hc='.$indexHC.', cost_hp='.$costHP.', cost_hc='.$costHC.'';
							$result = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
							$nbenreg = count($result);
							log::add('sigri_atome', 'debug', 'Nombre d\'enregistrement "heure" effectués avec succès : '.$nbenreg);
						}
					} elseif ($period == "month") {
						for ($i = 0; $i<31; $i++) {
							// Extraction des data énergie
							$json_data = json_decode($response);
							$date = date("Y-m-d", strtotime(date("Y-m-d", strtotime(substr($json_data->data[$i]->time, 0, 10))) . " +1 day"));
							$totalConsumption = $json_data->data[$i]->totalConsumption;
							$indexHP = $json_data->data[$i]->consumption->index2;
							$indexHC = $json_data->data[$i]->consumption->index1;
							$costHP = $json_data->data[$i]->consumption->bill2;
							$costHC = $json_data->data[$i]->consumption->bill1;

							// Enregistrement de l'heure dans la BDD
							log::add('sigri_atome', 'debug', 'Enregistrement dans la BDD en cours du jour : '.$i);
							$sql = 'INSERT INTO sigri_atome_day (day, total_consumption, index_hp, index_hc, cost_hp, cost_hc) VALUES (\''.$date.'\', \''.$totalConsumption.'\', \''.$indexHP.'\', \''.$indexHC.'\', \''.$costHP.'\', \''.$costHC.'\')ON DUPLICATE KEY UPDATE total_consumption='.$totalConsumption.', index_hp='.$indexHP.', index_hc='.$indexHC.', cost_hp='.$costHP.', cost_hc='.$costHC.'';
							$result = DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
							$nbenreg = count($result);
							log::add('sigri_atome', 'debug', 'Nombre d\'enregistrement "jour" effectués avec succès : '.$nbenreg);
						}
					}
				} else {
					log::add('sigri_atome', 'error', 'Aucun mode d\'enregistrement n\'as été choisi !');
				}
			}
		}

		public static function CronIsInstall() {
			log::add('sigri_atome', 'debug', 'Vérification des cron');

			/*
			$cron = cron::byClassAndFunction('sigri_atome', 'launch_sigri_atome');
			if (!is_object($cron)) {
				log::add('sigri_atome', 'debug', 'Cron launch_sigri_atome inexistant, il faut le créer');
				$cron = new cron();
				$cron->setClass('sigri_atome');
				$cron->setFunction('launch_sigri_atome');
				$cron->setEnable(1);
				$cron->setDeamon(0);
				$cron->setSchedule("0 * * * *");
				$cron->save();
			} else {
				log::add('sigri_atome', 'debug', 'Cron launch_sigri_atome existe déjà');
			}
			*/

			$cron = cron::byClassAndFunction('sigri_atome', 'cronHourly');
			if (!is_object($cron)) {
				log::add('sigri_atome', 'debug', 'Cron cronHourly inexistant, il faut le créer');
				$cron = new cron();
				$cron->setClass('sigri_atome');
				$cron->setFunction('cronHourly');
				$cron->setEnable(1);
				$cron->setDeamon(0);
				$cron->setSchedule("0 * * * *");
				$cron->save();
			} else {
				log::add('sigri_atome', 'debug', 'Cron cronHourly existe déjà');
			}

			$cron = cron::byClassAndFunction('sigri_atome', 'cronDaily');
			if (!is_object($cron)) {
				log::add('sigri_atome', 'debug', 'Cron cronDaily inexistant, il faut le créer');
				$cron = new cron();
				$cron->setClass('sigri_atome');
				$cron->setFunction('cronDaily');
				$cron->setEnable(1);
				$cron->setDeamon(0);
				$cron->setSchedule("0 0 * * *");
				$cron->save();
			} else {
				log::add('sigri_atome', 'debug', 'Cron cronDaily existe déjà');
			}
		}
	}

	class sigri_atomeCmd extends cmd {
		public function execute($_options = array()) {

		}
	}
?>