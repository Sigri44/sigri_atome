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
	
	class sigri_atome extends eqLogic {
		public function preUpdate() {
			/*
			if (empty($this->getConfiguration('identifiant'))) {
				throw new Exception(__('L\'identifiant ne peut pas être vide',__FILE__));
			}
			
			if (empty($this->getConfiguration('password'))) {
				throw new Exception(__('Le mot de passe ne peut etre vide',__FILE__));
			}
			*/
		}
		
		public function postUpdate() {
			log::add('sigri_atome', 'debug', 'Mise à jour de l\'équipement');
			self::CronIsInstall();

			/*
			if ($this->getIsEnable()){
				$cmd = $this->getCmd(null, 'consoheure');
				if ( ! is_object($cmd)) {
					$cmd = new sigri_atomeCmd();
					$cmd->setName('Consommation Horaire');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('consoheure');
					$cmd->setUnite('kW');
					$cmd->setType('info');
					$cmd->setSubType('numeric');
					$cmd->setIsHistorized(1);
					$cmd->setEventOnly(1);
					$cmd->save();
				}
				
				$cmd = $this->getCmd(null, 'consojour');
				if (!is_object($cmd)) {
					$cmd = new sigri_atomeCmd();
					$cmd->setName('Consommation journalière');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('consojour');
					$cmd->setUnite('kWh');
					$cmd->setType('info');
					$cmd->setSubType('numeric');
					$cmd->setIsHistorized(1);
					$cmd->setEventOnly(1);
					$cmd->save();
				}
				$cmd = $this->getCmd(null, 'consomois');
				if (!is_object($cmd)) {
					$cmd = new sigri_atomeCmd();
					$cmd->setName('Consommation Mensuelle');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('consomois');
					$cmd->setUnite('kWh');
					$cmd->setType('info');
					$cmd->setSubType('numeric');
					$cmd->setIsHistorized(1);
					$cmd->setEventOnly(1);
					$cmd->save();
				}
				
				$cmd = $this->getCmd(null, 'consoan');
				if (!is_object($cmd)) {
					$cmd = new sigri_atomeCmd();
					$cmd->setName('Consommation annuelle');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('consoan');
					$cmd->setUnite('kWh');
					$cmd->setType('info');
					$cmd->setSubType('numeric');
					$cmd->setIsHistorized(1);
					$cmd->setEventOnly(1);
					$cmd->save();
				}
			}
			*/
		}
		
		public function preRemove() {
			
		}
		
		public function postRemove() {
			
		}
				
		public static function launch_sigri_atome() {
			foreach (eqLogic::byType('sigri_atome', true) as $sigri_atome) {
				
				log::add('sigri_atome', 'info', 'Debut d\'interrogration Atome');
				if ($sigri_atome->getIsEnable() == 1) {
					if (!empty($sigri_atome->getConfiguration('identifiant')) && !empty($sigri_atome->getConfiguration('password'))) {
						
						$cmd_date = $sigri_atome->getCmd(null, 'consojour');
						if (is_object($cmd_date)) {
							$value = $cmd_date->execCmd();
							$collectDate = $cmd_date->getCollectDate();
							$command_date = new DateTime($collectDate);
							$start_date = new DateTime();
							$start_date->sub(new DateInterval('P1D'));
							if(date_format($command_date, 'Y-m-d') == date_format($start_date, 'Y-m-d')) {
								log::add('sigri_atome', 'debug', 'Donnees deja presentes pour aujourd\'hui');
							} else {
								$Useragent = $sigri_atome->GetUserAgent();
								log::add('sigri_atome', 'debug', 'UserAgent pour ce lancement : '.$Useragent);
								$API_cookies = $sigri_atome->Login_Enedis_API($Useragent);
								
								$cmd = $sigri_atome->getCmd(null, 'consoheure');
								if (is_object($cmd)) {
									$end_date = new DateTime();
									$start_date = (new DateTime())->setTime(0,0);
									$start_date->sub(new DateInterval('P7D'));
									$sigri_atome->Call_Enedis_API($API_cookies, $Useragent, "urlCdcHeure", $start_date, $end_date);
								}
								
								$cmd = $sigri_atome->getCmd(null, 'consojour');
								if (is_object($cmd)) {
									$end_date = new DateTime();
									$start_date = new DateTime();
									$start_date->sub(new DateInterval('P30D'));
									$sigri_atome->Call_Enedis_API($API_cookies, $Useragent, "urlCdcJour", $start_date, $end_date);
								}
								
								$cmd = $sigri_atome->getCmd(null, 'consomois');
								if (is_object($cmd)) {
									$end_date = new DateTime();
									$start_date = new DateTime('first day of this month');
									$start_date->sub(new DateInterval('P12M'));
									$sigri_atome->Call_Enedis_API($API_cookies, $Useragent, "urlCdcMois", $start_date, $end_date);
								}
								
								$cmd = $sigri_atome->getCmd(null, 'consoan');
								if (is_object($cmd)) {
									$end_date = new DateTime('first day of January');
									$start_date = new DateTime('first day of January');
									$start_date->sub(new DateInterval('P5Y'));
									$sigri_atome->Call_Enedis_API($API_cookies, $Useragent, "urlCdcAn", $start_date, $end_date);
								}
							}
						}
						log::add('sigri_atome', 'info', 'Fin d\'interrogration Atome');
					} else {
						log::add('sigri_atome', 'error', 'Identifiants requis');
					}
				}
			}
		}

		public  static function cronDaily() {
			log::add('sigri_atome', 'debug', '----------Lancement du cronDaily----------');
		}

		public static function CronIsInstall() {
			log::add('sigri_atome', 'debug', 'Vérification des cron');

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
		}
		
		public function Login_Enedis_API($Useragent) {
			log::add('sigri_atome', 'debug', 'Tentative d\'authentification sur Atome');
			
			$URL_API = "https://esoftlink.esoftthings.com";
			$API_LOGIN = "/api/user/login.json";
			$API_DATA = "/graph-query-last-consumption";
			$URL_LOGIN = $URL_API . $API_LOGIN;
			$cookies_file = __DIR__.'/ressources/cookies.txt';

			// ************
			// * Ephémère *
			// ************
			// Configuration Serveur
			$storage = "BDD"; // JSON ou BDD
			$period = "day"; // null, day, week ou month
		
			// Configuration date
			$today = date("Y-m-d");
			$now = date("Hi");
		
			// Configuration JSON
			$day_export = date("d_m_Y_H_i");
			$json_export_filename = "export_".$period."_".$day_export.".json";  // Nom du fichier JSON à utiliser lors d'un export "API"
			
			// Configuration SQL
			$sqlHost = "localhost";
			$sqlPort = "8889";
			$sqlLogin = "root";
			$sqlPassword = "root";
			$sqlDatabase = "jeedom";
			
			// *******************************
			// * Etape 1 - Connexion à l'API *
			// *******************************
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
			curl_setopt($curl, CURLOPT_COOKIEJAR, $cookies_file);
	
			$response = curl_exec($curl);
	
			// Enregistrement au format JSON
			if ($storage == "JSON") {
				log::add('sigri_atome', 'debug', 'Enregistrement JSON');
				file_put_contents($json_export_filename, $response);
			} elseif ($storage == "BDD") {
				log::add('sigri_atome', 'debug', 'Enregistrement BDD');
				/*
				try {
					$bdd = new PDO('mysql:host='.$sqlHost.';dbname='.$sqlDatabase.';charset=utf8', $sqlLogin, $sqlPassword);
				} catch (Exception $e) {
					die('Erreur : ' . $e->getMessage());
				}
				$reponse = $bdd->query('SELECT * FROM sigri_atome_day');
				while ($donnees = $reponse->fetch()) {
					echo $donnees['day'] . " : " . $donnees['value'] . "<br />";
				}
				echo ("<br />Fin test SQL.");
				*/
			} else {
				log::add('sigri_atome', 'error', 'Aucun mode d\'enregistrement n\'as été choisi !');
			}
	
			$err = curl_error($curl);
			curl_close($curl);
	
			if ($err) {
				log::add('sigri_atome', 'error', 'cURL Error #:' . $err);
			} else {
				echo "<br />Connexion réussie, récupération des informations en cours ...";
			}
	
			// Extraction des infos utilisateurs
			$json_login = json_decode($response);
			$user_id = $json_login->id;
			$user_reference = $json_login->subscriptions[0]->reference;
	
			if ($period != null) {
				$URL_DATA = $URL_API . "/" . $user_id . "/" . $user_reference . $API_DATA . "?period=" . $period;
			} else {
				$URL_DATA = $URL_API . "/" . $user_id . "/" . $user_reference . $API_DATA;
			}
	
			// *******************************************
			// * Etape 2 - Récupération des informations *
			// *******************************************
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
			curl_setopt($curl, CURLOPT_COOKIEFILE, $cookies_file);
	
			$response = curl_exec($curl);
			$err = curl_error($curl);
	
			curl_close($curl);
	
			if ($err) {
				log::add('sigri_atome', 'error', 'cURL Error #:' . $err);
			} else {
				echo("<br /><br />");
				echo("Réponse DATA :<br />");
				echo $response;
			}
			






			// **********
			// OLD ENEDIS
			// ********** 
			$data = array(
				"IDToken1=".urlencode($this->getConfiguration('identifiant')),
				"IDToken2=".urlencode($this->getConfiguration('password')),
				"SunQueryParamsString=".base64_encode('realm=particuliers'),
				"encoded=true",
				"gx_charset=UTF-8",
			);
			
			for ($login_phase1_attemps = 1; $login_phase1_attemps <= 11; $login_phase1_attemps++) {
				
				if ($login_phase1_attemps == 11) {
					log::add('sigri_atome', 'error', 'Erreur de connexion au site Enedis (Phase 1)');
					exit(1);
				}
				log::add('sigri_atome', 'debug', 'Connexion au site Enedis Phase 1 : Tentative '.$login_phase1_attemps.'/10');
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $data));
				curl_setopt($ch, CURLOPT_URL, $URL_LOGIN);
				curl_setopt($ch, CURLOPT_HEADER  ,1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				$content = curl_exec($ch);
				$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				
				if ($http_status == "302") { 
					preg_match_all('|Set-Cookie: (.*);|U', $content, $cookiesheader);   
					$ResponseCookie = $cookiesheader[1];
					foreach($ResponseCookie as $key => $val) {
						$cookie_explode = explode('=', $val);
						$cookies[$cookie_explode[0]]=$cookie_explode[1];
					}
					$cookie_iPlanetDirectoryPro = $cookies['iPlanetDirectoryPro'];
					if($cookie_iPlanetDirectoryPro === "LOGOUT") {
						log::add('sigri_atome', 'error', 'Erreur d\'identification');
						exit(1);
						} else {
						log::add('sigri_atome', 'info', 'Connexion au site Enedis Phase 1 : OK');
						break;
					}
				}
			}
			
			$headers = array(
				"Cookie: iPlanetDirectoryPro=".$cookie_iPlanetDirectoryPro
			);
			
			for ($login_phase2_attemps = 1; $login_phase2_attemps <= 11; $login_phase2_attemps++) {
				
				if ($login_phase2_attemps == 11) {
					log::add('sigri_atome', 'error', 'Erreur de connexion au site Enedis (Phase 2)');
					exit(1);
				}
				log::add('sigri_atome', 'debug', 'Connexion au site Enedis Phase 2 : Tentative '.$login_phase2_attemps.'/10');
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $URL_ACCUEIL);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_HEADER  ,1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_USERAGENT, $Useragent);
				$content = curl_exec($ch);
				$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				
				if ($http_status == "302") { 
					preg_match_all('|Set-Cookie: (.*);|U', $content, $cookiesheader);   
					$ResponseCookie = $cookiesheader[1];
					foreach($ResponseCookie as $key => $val) {
						$cookie_explode = explode('=', $val);
						$cookies[$cookie_explode[0]]=$cookie_explode[1];
					}
					$cookie_JSESSIONID = $cookies['JSESSIONID'];
					log::add('sigri_atome', 'info', 'Connexion au site Enedis Phase 2 : OK');
					break;
				}
				
			}
			
			$API_cookies = array(
			"Cookie: iPlanetDirectoryPro=".$cookie_iPlanetDirectoryPro,
			"Cookie: JSESSIONID=".$cookie_JSESSIONID,
			);
			
			log::add('sigri_atome', 'debug', 'Cookies d\'authentification OK : '.print_r($API_cookies));
			
			log::add('sigri_atome', 'debug', 'Verification si demande des conditions d\'utilisation');
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $URL_ACCUEIL);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $API_cookies);
			curl_setopt($ch, CURLOPT_HEADER  ,1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER  ,1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION  ,1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_USERAGENT, $Useragent);
			$content = curl_exec($ch);
			$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);
			
			if ($http_status == "200") { 
				preg_match("/\<title.*\>(.*)\<\/title\>/isU", $content, $matches);
				if (strpos($matches[1], "Conditions d'utilisation") !== false) {
					log::add('sigri_atome', 'error', 'Enedis vous demande de reconfirmer les conditions d\'utilisation, merci de vous reconnecter via leur site web');
					exit(1);
					} else {
					log::add('sigri_atome', 'debug', 'Pas de demande de conditions d\'utilisation : OK');
				}
			}
			return $API_cookies;
		}
		
		public function Call_Enedis_API($cookies, $Useragent, $resource_id, $start_datetime=None, $end_datetime=None) {
			$URL_CONSO = "https://espace-client-particuliers.enedis.fr/group/espace-particuliers/suivi-de-consommation";
			
			$prefix = '_lincspartdisplaycdc_WAR_lincspartcdcportlet_';
			
			$start_date = $start_datetime->format('d/m/Y');
			$end_date = $end_datetime->format('d/m/Y');
			
			$data = array(
				$prefix."dateDebut"."=".$start_date,
				$prefix."dateFin"."=".$end_date
			);
			
			$param = array(
				"p_p_id=lincspartdisplaycdc_WAR_lincspartcdcportlet",
				"p_p_lifecycle=2",
				"p_p_state=normal",
				"p_p_mode=view",
				"p_p_resource_id=".$resource_id,
				"p_p_cacheability=cacheLevelPage",
				"p_p_col_id=column-1",
				"p_p_col_pos=1",
				"p_p_col_count=3"
			);
			
			for ($retreive_attemps = 1; $retreive_attemps <= 11; $retreive_attemps++) {
				
				if ($retreive_attemps == 11) {
					log::add('sigri_atome', 'error', 'Erreur lors de la récupération des données ('.$resource_id.') depuis Enedis');
					break;
				}
				log::add('sigri_atome', 'info', 'Recupération des données ('.$resource_id.') depuis Enedis : Tentative '.$retreive_attemps.'/10');
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $URL_CONSO."?".implode('&', $param));
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, implode('&', $data));
				curl_setopt($ch, CURLOPT_HTTPHEADER, $cookies);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_USERAGENT, $Useragent);
				$content = curl_exec($ch);
				$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);
				
				if ($http_status == "200") {
					$this->Enedis_Results_Jeedom($resource_id, $content, $start_datetime);
					log::add('sigri_atome', 'info', 'Recupération des données ('.$resource_id.') depuis Enedis : OK');
					break;
				}
			}
		}
		
		public function Enedis_Results_Jeedom($resource_id, $content, $start_datetime) {
			$obj = json_decode($content, true);
			log::add('sigri_atome', 'debug',var_dump($obj));
			
			if ($obj['etat']['valeur'] == "erreur") {
				log::add('sigri_atome', 'error', 'Enedis renvoi une erreur sur la page '.$resource_id);
				if (isset($obj['etat']['erreurText'])) { 
					log::add('sigri_atome', 'error', 'Message d\'erreur : '.$obj['etat']['erreurText']);
				}
			} else {
				if ($resource_id == "urlCdcHeure") {
					log::add('sigri_atome', 'debug', 'Traitement données heures');
					$cmd = $this->getCmd(null, 'consoheure');
					$delta = "30 minutes";
					$start_date = $start_datetime;
					$date_format = "Y-m-d H:i:00";
					} elseif ($resource_id == "urlCdcJour") { 
					log::add('sigri_atome', 'debug', 'Traitement données jours');
					$cmd = $this->getCmd(null, 'consojour');
					$delta = "1 day";
					$start_date = $obj['graphe']['periode']['dateDebut'];
					$start_date = date_create_from_format('d/m/Y', $start_date);
					$date_format = "Y-m-d";
					} elseif ($resource_id == "urlCdcMois") { 
					log::add('sigri_atome', 'debug', 'Traitement données mois');
					$cmd = $this->getCmd(null, 'consomois');
					$delta = "1 month";
					$start_date = $obj['graphe']['periode']['dateDebut'];
					$start_date = date_create_from_format('d/m/Y', $start_date);
					$date_format = "Y-m-d";
					} elseif ($resource_id == "urlCdcAn") { 
					$cmd = $this->getCmd(null, 'consoan');
					log::add('sigri_atome', 'debug', 'Traitement données ans');
					$delta = "1 year";
					$start_date = $obj['graphe']['periode']['dateDebut'];
					$start_date = date_create_from_format('d/m/Y', $start_date);
					$start_date = date_create($start_date->format('Y-1-1'));
					$date_format = "Y-m-d";
				}
				
				foreach ($obj['graphe']['data'] as &$value) {
					$jeedom_event_date = $start_date->format($date_format);
					if ($value['valeur'] == "-1" OR $value['valeur'] == "-2") {
						log::add('sigri_atome', 'debug', 'Date : '.$jeedom_event_date.' : Valeur incorrect : '.$value['valeur']);
					} else {
						log::add('sigri_atome', 'debug', 'Date : '.$jeedom_event_date.' : Indice : '.$value['valeur'].' kWh');
						$cmd->event($value['valeur'], $jeedom_event_date);
					}
					date_add($start_date,date_interval_create_from_date_string($delta));
				}
			}
		}
	}
	
	class sigri_atomeCmd extends cmd {
		public function execute($_options = array()) {

		}
	}
?>