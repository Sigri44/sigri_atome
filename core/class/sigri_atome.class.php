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
	require_once __DIR__ . "/../../../../core/php/core.inc.php";

	class sigri_atome extends eqLogic {
		// *****************
		// * Configuration *
		// *****************
		const URL_API = "https://esoftlink.esoftthings.com/api";
		const API_LOGIN = "/user/login.json";
        const URL_LOGIN = self::URL_API . self::API_LOGIN;

        const API_DATA = "/graph-query-last-consumption";
        const API_COMMON = "/subscription/";
        const API_CONSUMPTION = "/consumption.json?period=sod";
        const API_CURRENT_MEASURE = "/measure/live.json?mobileId=247DA355-FB45-4258-86A2-FE964DF2B1F6";

		const RESOURCES_DIR = __DIR__."/../../resources/";
		const JSON_CONNECTION = self::RESOURCES_DIR."atome_connection.json";
		const COOKIES_FILE = self::RESOURCES_DIR."cookies.txt";

        public function preUpdate() {

		}
		
		public function postUpdate() {
			log::add('sigri_atome', 'debug', 'Exécution de la fonction postUpdate');
			self::CronIsInstall();

			if ($this->getIsEnable()) {
				$cmd = $this->getCmd(null,'consoheure');
				if (!is_object($cmd)) {
					$cmd = new sigri_atomeCmd();
					$cmd->setName('Conso Horaire');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('consoheure');
					$cmd->setUnite('kW');
					$cmd->setType('info');
					$cmd->setSubType('numeric');
					$cmd->setIsHistorized(1);
					$cmd->setEventOnly(1);
					$cmd->save();
				}
			}
			if ($this->getIsEnable()) {
				$cmd = $this->getCmd(null,'consojour');
				if (!is_object($cmd)) {
					$cmd = new sigri_atomeCmd();
					$cmd->setName('Conso Journalière');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('consojour');
					$cmd->setUnite('kWh');
					$cmd->setType('info');
					$cmd->setSubType('numeric');
					$cmd->setIsHistorized(1);
					$cmd->setEventOnly(1);
					$cmd->save();
				}
			}
		}
		
		public function preRemove() {
			
		}
		
		public function postRemove() {
			
		}

        // New Beta cronMinute
        /*
        public function cronMinute() {
            log::add("sigri_atome", "debug", "********** Etape 0 - Lancement du cronMinute **********");
            $period = "day";
            $this->baseSqueleton($period);
        }
        */

		public function cronHoraire() {
			log::add('sigri_atome', 'debug', '********** Etape 0 - Lancement du cronHoraire **********');
            $period = "day";
            self::baseSkeleton($period);
		}

		public function cronJournalier() {
            log::add('sigri_atome', 'debug', '********** Etape 0 - Lancement du cronHoraire **********');
            $period = "month";
            self::baseSkeleton($period);
		}

		private function callAtomeLogin($login, $password) {
			// Debug complet de la fonction
			log::add('sigri_atome', 'debug', '********** Etape 1 - Authentification à l\'API **********');
			log::add('sigri_atome', 'debug', '********** -- callAtomeLogin -- **********');
			log::add('sigri_atome', 'debug', '$URL_API : '.self::URL_API);
			log::add('sigri_atome', 'debug', '$API_LOGIN : '.self::API_LOGIN);
			log::add('sigri_atome', 'debug', '$API_DATA : '.self::API_DATA);
			log::add('sigri_atome', 'debug', '$URL_LOGIN : '.self::URL_LOGIN);
			log::add('sigri_atome', 'debug', '$RESOURCES_DIR : '.self::RESOURCES_DIR);
			log::add('sigri_atome', 'debug', '$COOKIES_FILE : '.self::COOKIES_FILE);
			log::add('sigri_atome', 'debug', '$JSON_CONNECTION : '.self::JSON_CONNECTION);
			log::add('sigri_atome', 'debug', '$login : '.$login);
			log::add('sigri_atome', 'debug', '$password : '.$password);

			// *******************************
			// * Etape 1 - Connexion à l'API *
			// *******************************
			log::add('sigri_atome', 'debug', '** 1.0 - Authentification sur Atome **');

            $response = $this->execCurlLoginCommand($login, $password);

            log::add('sigri_atome', 'debug', '$response : ' . $response);

            // Vérification de l'intégrité du JSON
            $checkJsonIntegrity = json_decode($response);

            if ($checkJsonIntegrity->errors) {
                log::add('sigri_atome', 'debug', '$response->errors[0] : ' . $checkJsonIntegrity->errors[0]);
                if ($checkJsonIntegrity->errors[0] == "Login Failed") {
                    log::add('sigri_atome', 'error', '"Login Failed" à la connexion API, réessayez plus tard...');
                    die();
                } else {
                    log::add('sigri_atome', 'error', 'Erreur à la connexion API : ' . $response);
                    die();
                }
            }

            // Enregistrement de la connexion au format JSON
            log::add('sigri_atome', 'debug', '** 1.1 - Enregistrement de la connexion au format JSON **');
            // Test de l'écriture sur les fichiers du dossier resources.
            $this->checkWriteRights($response);

			if (!self::COOKIES_FILE) {
				log::add('sigri_atome', 'error', 'Aucun fichier cookies n\'as pu être enregistré !');
			}

            log::add('sigri_atome', 'debug', '** 1.2 - Connexion réussie, récupération des informations en cours ... **');
            $jsonResponse = json_decode($response);
            return $jsonResponse;
		}

        private function retrieveUserDetails($jsonResponse) {
            log::add("sigri_atome", "debug", "********** Récupération des infos utilisateurs **********");
            if ( empty($jsonResponse->subscriptions) ) {
                log::add("sigri_atome", "error", "No information found from user");
                die();
            }
            $userDetails["id"] = $jsonResponse->id;
            $userDetails["reference"] = $jsonResponse->subscriptions[0]->reference;

            return $userDetails;
        }

		private function callAtomeAPI($jsonResponse, $period) {
			// Debug complet de la fonction
			log::add('sigri_atome', 'debug', '********** Etape 2 - Récupération des datas énergie **********');

            // Extraction des infos utilisateurs
            log::add("sigri_atome", "debug", "callAtomeAPI :: Retrieve user details");
            $userDetails = $this->retrieveUserDetails($jsonResponse);

			// Configuration
			$STORAGE = "BDD"; // JSON ou BDD

			// Configuration date
			$TODAY = date("Y-m-d");
			$NOW = date("Hi");
			$start_date = date("Y-m-d H:i:s");

            // Configuration de la période à récupérer
            log::add("sigri_atome", "debug", "callAtomeAPI :: Generate url to call");
            $urlApi = self::URL_API . self::API_COMMON . $userDetails["id"] . "/" . $userDetails["reference"] . self::API_CONSUMPTION;

            // ********************************************
            // * Etape 2 - Récupération des datas énergie *
            // ********************************************
            log::add("sigri_atome", "debug", "callAtomeAPI :: call API : ".$urlApi);
            $jsonResponse = json_decode($this->execCurlCommand($urlApi));

            // Get datas
            $consoTime = $jsonResponse->time;
            $consoTotal = $jsonResponse->total;
            $consoPrice = $jsonResponse->price;
            $consoStart = $jsonResponse->startPeriod;
            $consoEnd = $jsonResponse->endPeriod;
            $consoImpactCO2 = $jsonResponse->impactCo2;
            log::add("sigri_atome", "info", "callAtomeAPI :: consoTime=".$consoTime.", consoTotal=".$consoTotal.", consoPrice=".$consoPrice.", consoStart".$consoStart.", consoEnd".$consoEnd.", consoImpactCO2".$consoImpactCO2);

            // DONE MTB

            // Configuration JSON
			$timestamp = date_timestamp_get(date_create($TODAY . $NOW)) + 3600;
    		$DAY_EXPORT = date("d_m_Y_H_i", $timestamp);
			$JSON_EXPORT_FILENAME = "export_".$period."_".$DAY_EXPORT.".json";  // Nom du fichier JSON à utiliser lors d'un export "API"
			$JSON_EXPORT_FILE = self::RESOURCES_DIR.$JSON_EXPORT_FILENAME;

			// Configuration de la période à récupérer
			log::add('sigri_atome', 'debug', '** 2.1 - Configuration de la période à récupérer **');
			if ($period != null) {
				$URL_DATA = self::URL_API . "/" . $userDetails["id"] . "/" . $userDetails["reference"] . self::API_DATA . "?period=" . $period;
				log::add('sigri_atome', 'debug', '$URL_DATA : '.$URL_DATA);
			} else {
				$URL_DATA = self::URL_API . "/" . $userDetails["id"] . "/" . $userDetails["reference"] . self::API_DATA;
				log::add('sigri_atome', 'debug', '$URL_DATA : '.$URL_DATA);
			}

			// ********************************************
			// * Etape 2 - Récupération des datas énergie *
			// ********************************************
			log::add('sigri_atome', 'debug', '** 2.2 - Récupération des datas énergie en cours ... **');
			$curl = curl_init();

			curl_setopt_array($curl, array(
				CURLOPT_COOKIEFILE => self::COOKIES_FILE,
				CURLOPT_COOKIEJAR => self::COOKIES_FILE,
				CURLOPT_COOKIESESSION => true,
				CURLOPT_CUSTOMREQUEST => "GET",
				CURLOPT_ENCODING => "",
				CURLOPT_HTTPHEADER => array(
					"Cache-Control: no-cache",
					"Content-Type: application/json",
				),
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_TIMEOUT => 30,
				CURLOPT_URL => $URL_DATA,
			));

			$response = curl_exec($curl);
			$err = curl_error($curl);

			curl_close($curl);

            log::add('sigri_atome', 'debug', '$response : ' . $response);

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
                            //log::add('sigri_atome', 'debug', '$response : ' . $response);

                            if ($response === '{"message":"Login failed "}') {
                                log::add('sigri_atome', 'error', '"Login Failed" à la connexion API, réessayez plus tard...');
                                die();
                            }

                            $json_data = json_decode($response);
                            if ($json_data === false) {
                                log::add('sigri_atome', 'debug', '$json_data->data['.$i.'] : ' . print_r($json_data->data[$i], true));
                                die();
                            }

                            /*
                            for ($c = 1; $c < 6; $c++) {
                                $key = "code".$c;
                                log::add('sigri_atome', 'debug', '$key : ' . $key);
                                $resultCode = $json_data->data[$i]->consumption->$key;
                                if ($resultCode === false) {
                                    die();
                                } else {
                                    $sql = 'INSERT INTO sigri_atome_config (key, value) VALUES (\'$code'.$i.'\', \''.$resultCode.'\') ';
                                    log::add('sigri_atome', 'debug', 'RQT $sql : ' . $sql);
                                    DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
                                    log::add('sigri_atome', 'debug', 'Insertion SQL réussie pour la clé : ' . $key);
                                }
                            }

                            die();
                            */

                            // Enregistrement en BDD si value n'existe pas.
                            /*
                            $sql = 'INSERT INTO sigri_atome_config (key, value) VALUES (\'CleMamen\', \'Valeure\') ON DUPLICATE KEY UPDATE
                            key = CASE
                                WHEN key <=\''.$code1.'\'
                                THEN \''.$code1.'\'
                                ELSE key
                                END,
                            value = CASE
                                WHEN value <=\''.$code2.'\'
                                THEN \''.$code2.'\'
                                ELSE value
                                END';
                            log::add('sigri_atome', 'debug', 'RQT $sql : ' . $sql);
                            DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);
                            */

                            $date = substr($json_data->data[$i]->time, 0, 10);
							$time = substr($json_data->data[$i]->time, 11, 8);
							// Ajout d'1h pour corriger le fuseau horaire
							$timestamp = date_timestamp_get(date_create($date . $time)) + 3600;
							$datetime = date("Y-m-d H:i:s", $timestamp);
							$totalConsumption = $json_data->data[$i]->totalConsumption;

                            // Debug affichage des values SQL
                            log::add('sigri_atome', 'debug', '************ VALUES SQL ************');
                            log::add('sigri_atome', 'debug', '$datetime : ' . $datetime);
                            log::add('sigri_atome', 'debug', '$totalConsumption : ' . $totalConsumption);

							$this->insertIndex($i, $json_data, $datetime, $totalConsumption);

							/* Codes :
							HPDE : Heures Pleines Direct Energie
							HCDE : Heures Creuses Direct Energie
							HSCDE : Heures Super Creuses Direct Energie

							$code1 = $json_data->data[$i]->consumption->code1;
							$code2 = $json_data->data[$i]->consumption->code2;
							$code3 = $json_data->data[$i]->consumption->code3;
							$code4 = $json_data->data[$i]->consumption->code4; // 4 pour tester la nullité !

							// $index = 1,2,3 ou 4
							// $code = $json_data->data[$i]->consumption->codeX
                            function checkCodeDE($code, $index) {
                                $indexName = "index" . $index;
                                $billName = "bill" . $index;
                                $index = $json_data->data[$i]->consumption->$indexName;
                                $bill = $json_data->data[$i]->consumption->$billName;

                                if ($code === "HPDE") {
                                    return array("indexHP" => $index, "costHP" => $bill);
                                } elseif ($code === "HCDE") {
                                    return array("indexHC" => $index, "costHC" => $bill);
                                } elseif ($code2 ==== "HSCDE") {
                                    return array("indexHSC" => $index, "costSHC" => $bill);
                                } else {
                                    log::add('sigri_atome', 'error', '$code2 n\'est pas un code valide : ' . $code2);
                                }
                            }
							*/


							/*
							// Debug affichage des values SQL
							log::add('sigri_atome', 'debug', '************ VALUES SQL ************');
							log::add('sigri_atome', 'debug', '$datetime : ' . $datetime);
							log::add('sigri_atome', 'debug', '$totalConsumption : ' . $totalConsumption);
							log::add('sigri_atome', 'debug', '$indexHP : ' . $indexHP);
							log::add('sigri_atome', 'debug', '$indexHC : ' . $indexHC);
							log::add('sigri_atome', 'debug', '$costHP : ' . $costHP);
							log::add('sigri_atome', 'debug', '$costHC : ' . $costHC);
							log::add('sigri_atome', 'debug', '************************************');

							die();
							*/

							/*
							// Enregistrement de l'heure dans la BDD
							log::add('sigri_atome', 'debug', 'Enregistrement dans la BDD en cours de l\'heure : '.$i);
							//$sql = 'INSERT INTO sigri_atome_hour (hour, total_consumption, index_hp, index_hc, cost_hp, cost_hc) VALUES (\''.$datetime.'\', \''.$totalConsumption.'\', \''.$indexHP.'\', \''.$indexHC.'\', \''.$costHP.'\', \''.$costHC.'\') ON DUPLICATE KEY UPDATE total_consumption='.$totalConsumption.', index_hp='.$indexHP.', index_hc='.$indexHC.', cost_hp='.$costHP.', cost_hc='.$costHC;
							//$sql = 'INSERT INTO sigri_atome_hour (hour, total_consumption, index_hp, index_hc, cost_hp, cost_hc) VALUES (\''.$datetime.'\', \''.$totalConsumption.'\', \''.$indexHP.'\', \''.$indexHC.'\', \''.$costHP.'\', \''.$costHC.'\') ON DUPLICATE KEY UPDATE hour="'.$datetime.'"';
							$sql = 'INSERT INTO sigri_atome_hour (hour, total_consumption, index_hp, index_hc, cost_hp, cost_hc) VALUES (\''.$datetime.'\', \''.$totalConsumption.'\', \''.$indexHP.'\', \''.$indexHC.'\', \''.$costHP.'\', \''.$costHC.'\') ON DUPLICATE KEY UPDATE total_consumption = CASE WHEN total_consumption <=\''.$totalConsumption.'\' THEN \''.$totalConsumption.'\' ELSE total_consumption END, index_hp = CASE WHEN index_hp <=\''.$indexHP.'\' THEN \''.$indexHP.'\' ELSE index_hp END, index_hc = CASE WHEN index_hc <=\''.$indexHC.'\' THEN \''.$indexHC.'\' ELSE index_hc END, cost_hp = CASE WHEN cost_hp <=\''.$costHP.'\' THEN \''.$costHP.'\' ELSE cost_hp END, cost_hc = CASE WHEN cost_hc <=\''.$costHC.'\' THEN \''.$costHC.'\' ELSE cost_hc END';
							log::add('sigri_atome', 'debug', 'RQT $sql : ' . $sql);
                            DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);

                            // Historisation de la valeur dans Jeedom
							$cmd = $this->getCmd(null, 'consoheure');
							$totalConsumption = $totalConsumption / 1000;
							log::add('sigri_atome', 'debug', 'Date : : ' . $datetime . ' : Indice : ' . $totalConsumption . ' kWh');
                            log::add('sigri_atome', 'debug', '**************** FIN ***************');

                            $cmd->event($totalConsumption, $datetime);
							*/
						}
					} elseif ($period == "month") {
						for ($i = 0; $i<31; $i++) {
							// Extraction des data énergie
                            //log::add('sigri_atome', 'debug', '$response : ' . $response);

                            if ($response === '{"message":"Login failed "}') {
                                log::add('sigri_atome', 'error', '"Login Failed" à la connexion API, réessayez plus tard...');
                                die();
                            }

                            $json_data = json_decode($response);
                            log::add('sigri_atome', 'debug', '$json_data->data[$i] : ' . $json_data->data[$i]);

                            $date = date("Y-m-d", strtotime(date("Y-m-d", strtotime(substr($json_data->data[$i]->time, 0, 10))) . " +1 day"));
							$totalConsumption = $json_data->data[$i]->totalConsumption;
							$indexHP = $json_data->data[$i]->consumption->index2;
							$indexHC = $json_data->data[$i]->consumption->index1;
							$costHP = $json_data->data[$i]->consumption->bill2;
							$costHC = $json_data->data[$i]->consumption->bill1;

							// Debug affichage des values SQL
							log::add('sigri_atome', 'debug', $i.' - ************ VALUES SQL ************');
							log::add('sigri_atome', 'debug', $i.' - $date : ' . $date);
							log::add('sigri_atome', 'debug', $i.' - $totalConsumption : ' . $totalConsumption);
							log::add('sigri_atome', 'debug', $i.' - $indexHP : ' . $indexHP);
							log::add('sigri_atome', 'debug', $i.' - $indexHC : ' . $indexHC);
							log::add('sigri_atome', 'debug', $i.' - $costHP : ' . $costHP);
							log::add('sigri_atome', 'debug', $i.' - $costHC : ' . $costHC);
							log::add('sigri_atome', 'debug', $i.' - ************************************');
							if ($indexHC == "0" && $indexHP == "0") {
								log::add('sigri_atome', 'debug', '$indexHC && $indexHP sont égaux à 0 !!');
							}

							// Enregistrement du jour dans la BDD
							log::add('sigri_atome', 'debug', 'Enregistrement dans la BDD en cours du jour : '.$i);
							//$sql = 'INSERT INTO sigri_atome_day (day, total_consumption, index_hp, index_hc, cost_hp, cost_hc) VALUES (\''.$date.'\', \''.$totalConsumption.'\', \''.$indexHP.'\', \''.$indexHC.'\', \''.$costHP.'\', \''.$costHC.'\') ON DUPLICATE KEY UPDATE total_consumption='.$totalConsumption.', index_hp='.$indexHP.', index_hc='.$indexHC.', cost_hp='.$costHP.', cost_hc='.$costHC;
							//$sql = 'INSERT INTO sigri_atome_day (day, total_consumption, index_hp, index_hc, cost_hp, cost_hc) VALUES (\''.$date.'\', \''.$totalConsumption.'\', \''.$indexHP.'\', \''.$indexHC.'\', \''.$costHP.'\', \''.$costHC.'\') ON DUPLICATE KEY UPDATE day = "'.$date.'"';
							$sql = 'INSERT INTO sigri_atome_day (day, total_consumption, index_hp, index_hc, cost_hp, cost_hc) VALUES (\''.$date.'\', \''.$totalConsumption.'\', \''.$indexHP.'\', \''.$indexHC.'\', \''.$costHP.'\', \''.$costHC.'\') ON DUPLICATE KEY UPDATE total_consumption = CASE WHEN total_consumption <=\''.$totalConsumption.'\' THEN \''.$totalConsumption.'\' ELSE total_consumption END, index_hp = CASE WHEN index_hp <=\''.$indexHP.'\' THEN \''.$indexHP.'\' ELSE index_hp END, index_hc = CASE WHEN index_hc <=\''.$indexHC.'\' THEN \''.$indexHC.'\' ELSE index_hc END, cost_hp = CASE WHEN cost_hp <=\''.$costHP.'\' THEN \''.$costHP.'\' ELSE cost_hp END, cost_hc = CASE WHEN cost_hc <=\''.$costHC.'\' THEN \''.$costHC.'\' ELSE cost_hc END';
							log::add('sigri_atome', 'debug', $i.' - RQT $sql : ' . $sql);
                            DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);

							// Historisation de la valeur dans Jeedom
							$cmd = $this->getCmd(null, 'consojour');
							$totalConsumption = $totalConsumption / 1000;
							log::add('sigri_atome', 'debug', $i.' - Date : ' . $date . ' : Indice : ' . $totalConsumption . ' kWh');
							$cmd->event($totalConsumption, $date);
						}
					}
				} else {
					log::add('sigri_atome', 'error', '** 2.3 - Aucun mode d\'enregistrement n\'as été choisi ! **');
				}
			}
			log::add('sigri_atome', 'debug', '********** Etape 3 - Fin du Cron, tout s\'est bien déroulé ! **********');
			// Enregistrement des values dans Jeedom
			//$this->Save_Atome_Jeedom($period, $response, $start_date);
		}

        private function insertIndex($i, $json_data, $datetime, $totalConsumption) {
            $i = $i + 1;
            $code = $json_data->data[$i]->consumption->code.$i;
            $index = $json_data->data[$i]->consumption->index.$i;
            $cost = $json_data->data[$i]->consumption->bill.$i;

            log::add('sigri_atome', 'debug', '$code'.$code.' : ' . $code);
            log::add('sigri_atome', 'debug', '$index'.$code.' : ' . $index);
            log::add('sigri_atome', 'debug', '$cost'.$code.' : ' . $cost);
            log::add('sigri_atome', 'debug', '************************************');

            // Enregistrement de l'heure dans la BDD
            log::add('sigri_atome', 'debug', 'Enregistrement dans la BDD en cours de l\'heure : '.$i);
            $sql = 'INSERT INTO sigri_atome_hour (hour, code, total_consumption, index, cost)
                    VALUES (\''.$datetime.'\', \''.$code.'\', \''.$totalConsumption.'\', \''.$index.'\', \''.$cost.'\')
                    ON DUPLICATE KEY UPDATE
                    total_consumption = CASE
                        WHEN total_consumption <=\''.$totalConsumption.'\'
                        THEN \''.$totalConsumption.'\'
                        ELSE total_consumption END,
                    index = CASE
                        WHEN index <=\''.$index.'\'
                        THEN \''.$index.'\'
                        ELSE index END,
                    code = CASE
                        WHEN code =\''.$code.'\'
                        THEN \''.$code.'\'
                        ELSE code END,
                    cost = CASE
                        WHEN cost <=\''.$cost.'\'
                        THEN \''.$cost.'\'
                        ELSE cost END';
            log::add('sigri_atome', 'debug', 'RQT $sql : ' . $sql);
            DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL);

            // Historisation de la valeur dans Jeedom
            $cmd = $this->getCmd(null, 'consoheure');
            $totalConsumption = $totalConsumption / 1000;
            log::add('sigri_atome', 'debug', 'Date : : ' . $datetime . ' : Indice : ' . $totalConsumption . ' kWh');
            log::add('sigri_atome', 'debug', '**************** FIN ***************');

            $cmd->event($totalConsumption, $datetime);
        }

        /* INSTALLATION DES CRONS */
		public function CronIsInstall() {
			log::add('sigri_atome', 'debug', 'Vérification des cron');
            //$this->checkCronAndCreateIfNecessary("cronMinute", "* * * * *");
            $this->checkCronAndCreateIfNecessary("cronHoraire", "59 * * * *");
            $this->checkCronAndCreateIfNecessary("cronJournalier", "59 23 * * *");
		}

        /*****************
         * PRIVATE METHODS
         *****************/

        private function checkCronAndCreateIfNecessary($cronName, $cronSchedule) {
            $cron = cron::byClassAndFunction("sigri_atome", $cronName);
            if (!is_object($cron)) {
                log::add("sigri_atome", "debug", "Cron ".$cronName." inexistant, il faut le créer");
                $cron = new cron();
                $cron->setClass("sigri_atome");
                $cron->setFunction($cronName);
                $cron->setEnable(1);
                $cron->setDeamon(0);
                $cron->setSchedule($cronSchedule);
                $cron->save();
            } else {
                log::add("sigri_atome", "debug", "Cron ".$cronName." existe déjà");
            }
        }

        private function execCurlLoginCommand($login, $password) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_COOKIEFILE => self::COOKIES_FILE,
                CURLOPT_COOKIEJAR => self::COOKIES_FILE,
                CURLOPT_COOKIESESSION => true,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_ENCODING => "",
                CURLOPT_HTTPHEADER => array(
                    "Cache-Control: no-cache",
                    "Content-Type: application/json"
                ),
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_POSTFIELDS => "{\"email\": \"".$login."\",\"plainPassword\": \"".$password."\"}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_URL => self::URL_LOGIN
            ));

            $response = curl_exec($curl);
            $curlError = curl_error($curl);
            curl_close($curl);
            if ($curlError) {
                log::add("sigri_atome", "error", "execCurlLoginCommand :: cURL Error #:".$curlError);
                die();
            }
            return $response;
        }

        private function execCurlCommand($url) {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_COOKIEFILE => self::COOKIES_FILE,
                CURLOPT_COOKIEJAR => self::COOKIES_FILE,
                CURLOPT_COOKIESESSION => true,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_ENCODING => "",
                CURLOPT_HTTPHEADER => array(
                    "Cache-Control: no-cache",
                    "Content-Type: application/json",
                ),
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_URL => $url
            ));
            $response = curl_exec($curl);
            $curlError = curl_error($curl);
            curl_close($curl);
            if ($curlError) {
                log::add("sigri_atome", "error", "execCurlCommand :: cURL Error #:".$curlError);
                die();
            }
            return $response;
        }

        private function checkWriteRights($response) {
            if (false === is_writable(self::RESOURCES_DIR)) {
                log::add("sigri_atome", "error", "Le dossier ".self::RESOURCES_DIR." n\"est pas accessible en écriture !");
                die();
            }
            if (false === is_writable(self::JSON_CONNECTION)) {
                log::add("sigri_atome", "error", "Le fichier ".self::JSON_CONNECTION." n\"est pas accessible en écriture !");
                die();
            }
            if (file_put_contents(self::JSON_CONNECTION, $response) === false) {
                log::add("sigri_atome", "error", "Impossible d\"écrire dans : ".self::JSON_CONNECTION.". Les droits doivent être en www-data:www-data (774) pour le dossier resources");
                die();
            }
        }

        private function baseSkeleton($period) {
            $eqLogics = eqLogic::byType('sigri_atome');
            foreach ($eqLogics as $eqLogic) {
                if ($eqLogic->getIsEnable() != 1) {
                    log::add("sigri_atome", "error", "Aucun équipement n'est configuré/activé !");
                    die();
                }
                if (!empty($eqLogic->getConfiguration("identifiant")) && !empty($eqLogic->getConfiguration("password"))) {
                    $jsonResponse = $eqLogic->callAtomeLogin($eqLogic->getConfiguration("identifiant"), $eqLogic->getConfiguration("password"));
                    $eqLogic->callAtomeAPI($jsonResponse, $period);
                }
            }
        }
	}

	class sigri_atomeCmd extends cmd {
		public function execute($_options = array()) {
		}
	}
?>