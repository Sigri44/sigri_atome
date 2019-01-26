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

	/*
	$URL_API = "https://esoftlink.esoftthings.com";
	$API_LOGIN = "/api/user/login.json";
	$API_DATA = "/graph-query-last-consumption";
	$URL_LOGIN = $URL_API . $API_LOGIN;
	$RESSOURCES_DIR = __DIR__.'/ressources/';
	$COOKIES_FILE = $RESSOURCES_DIR.'cookies.txt';
	*/

	class sigri_atome extends eqLogic {
		// *****************
		// * Configuration *
		// *****************
		const URL_API = "https://esoftlink.esoftthings.com";
		const API_LOGIN = "/api/user/login.json";
		const API_DATA = "/graph-query-last-consumption";
		const URL_LOGIN = self::URL_API . self::API_LOGIN;
		const RESSOURCES_DIR = __DIR__.'/../../ressources/';
		const JSON_CONNECTION = self::RESSOURCES_DIR.'atome_connection.json';
		const COOKIES_FILE = self::RESSOURCES_DIR.'cookies.txt';

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

		public static function cronHoraire() {
			log::add('sigri_atome', 'debug', '********** Etape 0 - Lancement du cronHoraire **********');
			$eqLogics = eqLogic::byType('sigri_atome');
			foreach ($eqLogics as $eqLogic) {
				if ($eqLogic->getIsEnable() == 1) {
					if (!empty($eqLogic->getConfiguration('identifiant')) && !empty($eqLogic->getConfiguration('password'))) {
						log::add('sigri_atome', 'debug', 'Debug avant login');
						log::add('sigri_atome', 'debug', 'Login : '.$eqLogic->getConfiguration('identifiant'));
						log::add('sigri_atome', 'debug', 'Password : '.$eqLogic->getConfiguration('password'));
						$json_connection = $eqLogic->Call_Atome_Login($eqLogic->getConfiguration('identifiant'), $eqLogic->getConfiguration('password'));
						$period = "day";
						$eqLogic->Call_Atome_API($json_connection, $period);
					}
				}
			}
		}

		public static function cronJournalier() {
			log::add('sigri_atome', 'debug', '********** Etape 0 - Lancement du cronJournalier **********');
			$eqLogics = eqLogic::byType('sigri_atome');
			foreach ($eqLogics as $eqLogic) {
				if ($eqLogic->getIsEnable() == 1) {
					if (!empty($eqLogic->getConfiguration('identifiant')) && !empty($eqLogic->getConfiguration('password'))) {
						log::add('sigri_atome', 'debug', 'Debug avant login');
						log::add('sigri_atome', 'debug', 'Login : '.$eqLogic->getConfiguration('identifiant'));
						log::add('sigri_atome', 'debug', 'Password : '.$eqLogic->getConfiguration('password'));
						$json_connection = $eqLogic->Call_Atome_Login($eqLogic->getConfiguration('identifiant'), $eqLogic->getConfiguration('password'));
						$period = "month";
						$eqLogic->Call_Atome_API($json_connection, $period);
					}
				}
			}
		}

		public function Call_Atome_Login($login, $password) {
			// Debug complet de la fonction
			log::add('sigri_atome', 'debug', '********** Etape 1 - Authentification à l\'API **********');
			log::add('sigri_atome', 'debug', '********** -- Call_Atome_Login -- **********');
			log::add('sigri_atome', 'debug', '$URL_API : '.self::URL_API);
			log::add('sigri_atome', 'debug', '$API_LOGIN : '.self::API_LOGIN);
			log::add('sigri_atome', 'debug', '$API_DATA : '.self::API_DATA);
			log::add('sigri_atome', 'debug', '$URL_LOGIN : '.self::URL_LOGIN);
			log::add('sigri_atome', 'debug', '$RESSOURCES_DIR : '.self::RESSOURCES_DIR);
			log::add('sigri_atome', 'debug', '$COOKIES_FILE : '.self::COOKIES_FILE);
			log::add('sigri_atome', 'debug', '$JSON_CONNECTION : '.self::JSON_CONNECTION);
			log::add('sigri_atome', 'debug', '$login : '.$login);
			log::add('sigri_atome', 'debug', '$password : '.$password);
			log::add('sigri_atome', 'debug', '** 1.X - FinConfig **');

			// *******************************
			// * Etape 1 - Connexion à l'API *
			// *******************************
			log::add('sigri_atome', 'debug', '** 1.0 - Authentification sur Atome **');

			// Forcer cURL à utiliser un nouveau cookie de session
			log::add('sigri_atome', 'debug', '** 1.1 - Configuration du cookie **');

			$fb = "";
			$response = false;
			
			try {
				$curl = curl_init();

				// TEST - Désactiver les erreurs certificats avec curl
				//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		
				curl_setopt_array($curl, array(
					CURLOPT_COOKIEFILE => self::COOKIES_FILE,
					CURLOPT_COOKIEJAR => self::COOKIES_FILE,
					CURLOPT_COOKIESESSION => true,
					CURLOPT_CUSTOMREQUEST => "POST",
					CURLOPT_ENCODING => "",
					CURLOPT_HEADER => true,
					CURLOPT_HTTPHEADER => array(
						"Cache-Control: no-cache",
						"Content-Type: application/json"
					),
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_POSTFIELDS => "{\"email\": \"".$login."\",\"plainPassword\": \"".$password."\"}",
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_TIMEOUT => 30,
					CURLOPT_URL => self::URL_LOGIN,
				));

				log::add('sigri_atome', 'debug', 'URL_LOGIN : --' . self::URL_LOGIN . "--");

				// Configuration du chemin du cookie
				/*
				log::add('sigri_atome', 'debug', '** 1.2 - Configuration du chemin du cookie **');
				// COOKIEJAR -> Reading CookieFile
				curl_setopt($curl, CURLOPT_COOKIEJAR, self::COOKIES_FILE);
				// COOKIEFILE -> Writing CookieFile
				curl_setopt($curl, CURLOPT_COOKIEFILE, self::COOKIES_FILE);

				*/
		
				log::add('sigri_atome', 'debug', '** 1.3 - Récupération de la connexion API **');
				log::add('sigri_atome', 'debug', '$curl envoyé : '.$curl);
				$fb = curl_exec($curl);
				log::add('sigri_atome', 'debug', '$fb1 : '.$fb);

				// Enregistrement de la connexion au format JSON
				log::add('sigri_atome', 'debug', '** 1.4 - Enregistrement de la connexion au format JSON **');
				file_put_contents(self::JSON_CONNECTION, $fb);

				// Récupération des erreurs curl
				$err = curl_error($curl);
				$errno = curl_errno($curl);
				curl_close($curl);
			} catch (Exception $e) {
				throw new Exception("Invalid URL",0,$e);
				log::add('sigri_atome', 'debug', 'Throw : ' . $e);
			}
			

			/*

			$client = new http\Client;
			$request = new http\Client\Request;

			$body = new http\Message\Body;
			$body->append('{"email": "'.$login.'","plainPassword": "'.$password.'"}');

			$request->setRequestUrl('https://esoftlink.esoftthings.com/api/user/login.json');
			$request->setRequestMethod('POST');
			$request->setBody($body);

			$request->setHeaders(array(
				'cache-control' => 'no-cache',
				'Content-Type' => 'application/json'
			));

			$client->enqueue($request)->send();
			$response = $client->getResponse();

			*/
			/*

			$request = new HttpRequest();
			$request->setUrl('https://esoftlink.esoftthings.com/api/user/login.json');
			$request->setMethod(HTTP_METH_POST);

			$request->setHeaders(array(
				'cache-control' => 'no-cache',
				'Content-Type' => 'application/json'
			));

			$request->setBody('{"email": "'.$login.'","plainPassword": "'.$password.'"}');

			try {
				$response = $request->send();

				log::add('sigri_atome', 'debug', '$response : ' . $response->getBody());
			} catch (HttpException $ex) {
				log::add('sigri_atome', 'error', 'Erreur HttpException : ' . $ex);
			}

			die();
			*/

			if ($err) {
				log::add('sigri_atome', 'error', 'cURL Error #:' . $err);
			} else {
				log::add('sigri_atome', 'debug', '$response : ' . $response);
			}

			die();

			log::add('sigri_atome', 'debug', '$fb2 : ' . $fb);
			if ($fb == "true" || $fb == "false") {
				log::add('sigri_atome', 'debug', '$fb3 : ' . $fb);
				$response = $fb;
			}
			log::add('sigri_atome', 'debug', '$response : ' . $response);
			
			die();

			log::add('sigri_atome', 'error', 'Erreur cURL rencontré n°'.$errno.' : ' . $err);

			if (!self::COOKIES_FILE) {
				log::add('sigri_atome', 'error', 'Aucun fichier cookies n\'as pu être enregistré !');
			}

			if ($err) {
				log::add('sigri_atome', 'error', 'cURL Error n°'.$errno.' : ' . $err);
			} else {
				log::add('sigri_atome', 'debug', '** 1.5 - Connexion réussie, récupération des informations en cours ... **');
			}
			
			// Debug temporaire pour parser le tableau d'erreur
			$json_debug = json_decode($response);
			if ($json_debug->errors[0] == "Login Failed") {	
				log::add('sigri_atome', 'error', 'Erreur au niveau de la connexion API : ' . $json_debug->errors[0]);
				// Kill de la connexion si erreur au login
				die();
			} else {
				return $response;
			}
		}
		
		public function Call_Atome_API($response, $period) {
			// Debug complet de la fonction
			log::add('sigri_atome', 'debug', '********** Etape 2 - Récupération des datas énergie **********');
			log::add('sigri_atome', 'debug', '********** -- Call_Atome_API -- **********');
			log::add('sigri_atome', 'debug', '$URL_API : '.self::URL_API);
			log::add('sigri_atome', 'debug', '$API_LOGIN : '.self::API_LOGIN);
			log::add('sigri_atome', 'debug', '$API_DATA : '.self::API_DATA);
			log::add('sigri_atome', 'debug', '$URL_LOGIN : '.self::URL_LOGIN);
			log::add('sigri_atome', 'debug', '$RESSOURCES_DIR : '.self::RESSOURCES_DIR);
			log::add('sigri_atome', 'debug', '$COOKIES_FILE : '.self::COOKIES_FILE);
			log::add('sigri_atome', 'debug', '** 2.X - FinConfig **');
			
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
			$JSON_EXPORT_FILE = self::RESSOURCES_DIR.$JSON_EXPORT_FILENAME;
			
			
			// Extraction des infos utilisateurs
			log::add('sigri_atome', 'debug', '$response : '.$response);
			$json_login = json_decode($response);
			log::add('sigri_atome', 'debug', '$json_login : '.$json_login);
			$user_id = $json_login->id;
			$user_reference = $json_login->subscriptions[0]->reference;

			// Configuration de la période à récupérer
			log::add('sigri_atome', 'debug', '** 2.1 - Configuration de la période à récupérer **');
			if ($period != null) {
				$URL_DATA = $URL_API . "/" . $user_id . "/" . $user_reference . $API_DATA . "?period=" . $period;
				log::add('sigri_atome', 'debug', '$URL_DATA : '.$URL_DATA);
			} else {
				$URL_DATA = $URL_API . "/" . $user_id . "/" . $user_reference . $API_DATA;
				log::add('sigri_atome', 'debug', '$URL_DATA : '.$URL_DATA);
			}
	
			// ********************************************
			// * Etape 2 - Récupération des datas énergie *
			// ********************************************
			log::add('sigri_atome', 'debug', '** 2.2 - Récupération des datas énergie en cours ... **');
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
					log::add('sigri_atome', 'debug', '** 2.3a - Enregistrement des datas énergie au format JSON **');
					file_put_contents($JSON_EXPORT_FILE, $response);
				} elseif ($STORAGE == "BDD") {
					log::add('sigri_atome', 'debug', '** 2.3b - Enregistrement des datas énergie en BDD **');
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
					log::add('sigri_atome', 'error', '** 2.3 - Aucun mode d\'enregistrement n\'as été choisi ! **');
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

			$cron = cron::byClassAndFunction('sigri_atome', 'cronHoraire');
			if (!is_object($cron)) {
				log::add('sigri_atome', 'debug', 'Cron cronHoraire inexistant, il faut le créer');
				$cron = new cron();
				$cron->setClass('sigri_atome');
				$cron->setFunction('cronHoraire');
				$cron->setEnable(1);
				$cron->setDeamon(0);
				$cron->setSchedule("0 * * * *");
				$cron->save();
			} else {
				log::add('sigri_atome', 'debug', 'Cron cronHoraire existe déjà');
			}

			$cron = cron::byClassAndFunction('sigri_atome', 'cronJournalier');
			if (!is_object($cron)) {
				log::add('sigri_atome', 'debug', 'Cron cronJournalier inexistant, il faut le créer');
				$cron = new cron();
				$cron->setClass('sigri_atome');
				$cron->setFunction('cronJournalier');
				$cron->setEnable(1);
				$cron->setDeamon(0);
				$cron->setSchedule("0 0 * * *");
				$cron->save();
			} else {
				log::add('sigri_atome', 'debug', 'Cron cronJournalier existe déjà');
			}
		}
	}

	class sigri_atomeCmd extends cmd {
		public function execute($_options = array()) {

		}
	}
?>