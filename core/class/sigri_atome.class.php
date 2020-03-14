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
        const API_CONSUMPTION = "/consumption.json";
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
                $cmd = $this->getCmd(null,'consominute');
                if (!is_object($cmd)) {
                    $cmd = new sigri_atomeCmd();
                    $cmd->setName('Conso Minute');
                    $cmd->setEqLogic_id($this->getId());
                    $cmd->setLogicalId('consominute');
                    $cmd->setUnite('kWh');
                    $cmd->setType('info');
                    $cmd->setSubType('numeric');
                    $cmd->setIsHistorized(1);
                    $cmd->setEventOnly(1);
                    $cmd->save();
                }
            }

			if ($this->getIsEnable()) {
				$cmd = $this->getCmd(null,'consoheure');
				if (!is_object($cmd)) {
					$cmd = new sigri_atomeCmd();
					$cmd->setName('Conso Horaire');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setLogicalId('consoheure');
					$cmd->setUnite('kWh');
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
        public function cronMinute() {
            log::add("sigri_atome", "debug", "********** Etape 0 - Lancement du cronMinute **********");
            $period = "hour";
            self::baseSkeleton($period);
        }

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

        /**
         * @param $login
         * @param $password
         * @return mixed
         */
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

            // Enregistrement de la connexion au format JSON
            log::add('sigri_atome', 'debug', '** 1.1 - Enregistrement de la connexion au format JSON **');

            // Test de l'écriture sur les fichiers du dossier resources.
            $this->checkWriteRights($response);

			if (!self::COOKIES_FILE) {
				log::add('sigri_atome', 'error', 'Aucun fichier cookies n\'as pu être enregistré !');
                die();
            }

            log::add('sigri_atome', 'debug', '** 1.2 - Connexion réussie, récupération des informations en cours ... **');
            $jsonResponse = json_decode($response);
            return $jsonResponse;
		}

        /**
         * @param $jsonResponse
         * @return mixed
         */
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

        /**
         * @param $jsonResponse
         * @param $period
         * @param $isNewApi
         */
		private function callAtomeAPI($jsonResponse, $period, $isNewApi) {
			// Debug complet de la fonction
			log::add('sigri_atome', 'debug', '********** Etape 2 - Récupération des datas énergie **********');

            // Extraction des infos utilisateurs
            log::add("sigri_atome", "debug", "callAtomeAPI :: Retrieve user details");
            $userDetails = $this->retrieveUserDetails($jsonResponse);

			// Configuration date
			$TODAY = date("Y-m-d");
			$NOW = date("Hi");
			$start_date = date("Y-m-d H:i:s");

            // ********************************************
            // * Etape 2 - Récupération des datas énergie *
            // ********************************************

			// Configuration de la période à récupérer
			log::add('sigri_atome', 'debug', '** 2.1 - Configuration de la période à récupérer **');
            log::add("sigri_atome", "debug", "callAtomeAPI :: Generate url to call");

            // Si newApi, alors on utilise la période SOD pour le fix
            if ($isNewApi) {
                $urlApi = self::URL_API . self::API_COMMON . $userDetails["id"] . "/" . $userDetails["reference"] . self::API_CONSUMPTION . "?period=sod";
			} elseif ($period != null) {
                $urlApi = self::URL_API . "/" . $userDetails["id"] . "/" . $userDetails["reference"] . self::API_DATA . "?period=" . $period;
			} else {
                $urlApi = self::URL_API . "/" . $userDetails["id"] . "/" . $userDetails["reference"] . self::API_DATA;
			}
            log::add("sigri_atome", "debug", "callAtomeAPI :: call API : ".$urlApi);

            $jsonResponse = json_decode($this->execCurlCommand($urlApi));

            if (is_array($jsonResponse)) {
                log::add('sigri_atome', 'error', '$jsonResponse est un array : ' . $jsonResponse);
                //die();
            }

			log::add('sigri_atome', 'debug', '** 2.2 - Récupération des datas énergie en cours ... **');
            // Enregistrement des datas énergie
            log::add('sigri_atome', 'debug', '** 2.3b - Enregistrement des datas énergie **');
            if ($period == "hour") {
                $event_name = "consominute";
                $date_format = "Y-m-d H:i";

                $this->saveAtomeValueEvent($jsonResponse, $event_name, $date_format);
            } elseif ($period == "day") {
                if ($isNewApi) {
                    $event_name = "consoheure";
                    $date_format = "Y-m-d H:i";

                    $this->saveAtomeValueEvent($jsonResponse, $event_name, $date_format);
                } else {
                    for ($i = 0; $i<25; $i++) {
                        // Extraction des data énergie
                        if ($jsonResponse === false) {
                            log::add('sigri_atome', 'debug', '$json_data->data['.$i.'] : ' . print_r($jsonResponse->data[$i], true));
                            die();
                        }

                        $date = substr($jsonResponse->data[$i]->time, 0, 10);
                        $time = substr($jsonResponse->data[$i]->time, 11, 8);
                        // Ajout d'1h pour corriger le fuseau horaire
                        $timestamp = date_timestamp_get(date_create($date . $time)) + 3600;
                        $datetime = date("Y-m-d H:i:s", $timestamp);
                        $totalConsumption = $jsonResponse->data[$i]->totalConsumption;

                        // Debug affichage des values
                        log::add('sigri_atome', 'debug', '************ VALUES ************');
                        log::add('sigri_atome', 'debug', '$datetime : ' . $datetime);
                        log::add('sigri_atome', 'debug', '$totalConsumption : ' . $totalConsumption);

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
                                die();
                            }
                        }
                        */

                        /*
                        // Debug affichage des values
                        log::add('sigri_atome', 'debug', '************ VALUES ************');
                        log::add('sigri_atome', 'debug', '$datetime : ' . $datetime);
                        log::add('sigri_atome', 'debug', '$totalConsumption : ' . $totalConsumption);
                        log::add('sigri_atome', 'debug', '$indexHP : ' . $indexHP);
                        log::add('sigri_atome', 'debug', '$indexHC : ' . $indexHC);
                        log::add('sigri_atome', 'debug', '$costHP : ' . $costHP);
                        log::add('sigri_atome', 'debug', '$costHC : ' . $costHC);
                        log::add('sigri_atome', 'debug', '*********************************');

                        die();
                        */

                        /*
                        // Historisation de la valeur dans Jeedom
                        $cmd = $this->getCmd(null, 'consoheure');
                        $totalConsumption = $totalConsumption / 1000;
                        log::add('sigri_atome', 'debug', 'Date : : ' . $datetime . ' : Indice : ' . $totalConsumption . ' kWh');
                        log::add('sigri_atome', 'debug', '**************** FIN ***************');

                        $cmd->event($totalConsumption, $datetime);
                        */
                    }
                }
            } elseif ($period == "month") {
                if ($isNewApi) {
                    $event_name = "consojour";
                    $date_format = "Y-m-d";

                    $this->saveAtomeValueEvent($jsonResponse, $event_name, $date_format);
                } else {
                    for ($i = 0; $i < 31; $i++) {
                        log::add('sigri_atome', 'debug', '$json_data->data[$i] : ' . $jsonResponse->data[$i]);

                        $date = date("Y-m-d", strtotime(date("Y-m-d", strtotime(substr($jsonResponse->data[$i]->time, 0, 10))) . " +1 day"));
                        $totalConsumption = $jsonResponse->data[$i]->totalConsumption;
                        $indexHP = $jsonResponse->data[$i]->consumption->index2;
                        $indexHC = $jsonResponse->data[$i]->consumption->index1;
                        $costHP = $jsonResponse->data[$i]->consumption->bill2;
                        $costHC = $jsonResponse->data[$i]->consumption->bill1;

                        // Debug affichage des values
                        log::add('sigri_atome', 'debug', $i . ' - ************ VALUES ************');
                        log::add('sigri_atome', 'debug', $i . ' - $date : ' . $date);
                        log::add('sigri_atome', 'debug', $i . ' - $totalConsumption : ' . $totalConsumption);
                        log::add('sigri_atome', 'debug', $i . ' - $indexHP : ' . $indexHP);
                        log::add('sigri_atome', 'debug', $i . ' - $indexHC : ' . $indexHC);
                        log::add('sigri_atome', 'debug', $i . ' - $costHP : ' . $costHP);
                        log::add('sigri_atome', 'debug', $i . ' - $costHC : ' . $costHC);
                        log::add('sigri_atome', 'debug', $i . ' - ********************************');
                        if ($indexHC == "0" && $indexHP == "0") {
                            log::add('sigri_atome', 'debug', '$indexHC && $indexHP sont égaux à 0 !!');
                        }

                        // Historisation de la valeur dans Jeedom
                        $cmd = $this->getCmd(null, 'consojour');
                        $totalConsumption = $totalConsumption / 1000;
                        log::add('sigri_atome', 'debug', $i . ' - Date : ' . $date . ' : Indice : ' . $totalConsumption . ' kWh');
                        $cmd->event($totalConsumption, $date);
                    }
                }
            }
			log::add('sigri_atome', 'debug', '********** Etape 3 - Fin du Cron, tout s\'est bien déroulé ! **********');
			// Enregistrement des values dans Jeedom
			//$this->Save_Atome_Jeedom($period, $response, $start_date);
		}

        /* INSTALLATION DES CRONS */
		public function CronIsInstall() {
			log::add('sigri_atome', 'debug', 'Vérification des cron');
            $this->checkCronAndCreateIfNecessary("cronMinute", "* * * * *");
            $this->checkCronAndCreateIfNecessary("cronHoraire", "59 * * * *");
            $this->checkCronAndCreateIfNecessary("cronJournalier", "59 23 * * *");
		}

        /**
         * @param $cronName
         * @param $cronSchedule
         */
        private function checkCronAndCreateIfNecessary($cronName, $cronSchedule) {
            $cron = cron::byClassAndFunction("sigri_atome", $cronName);
            if (!is_object($cron)) {
                log::add("sigri_atome", "debug", "Cron ".$cronName." inexistant, il faut le créer");
                $cron = new cron();
                $cron->setClass("sigri_atome");
                $cron->setFunction($cronName);
                /*
                if ($cronName !== "cronHoraire") {
                    $cron->setEnable(1);
                } else {
                    $cron->setEnable(0);
                }
                */
                $cron->setEnable(1);
                $cron->setDeamon(0);
                $cron->setSchedule($cronSchedule);
                $cron->save();
            } else {
                log::add("sigri_atome", "debug", "Cron ".$cronName." existe déjà");
            }
        }

        /**
         * @param $login
         * @param $password
         * @return bool|string
         */
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

            $this->checkJsonIntegrity($response, $curlError);

            return $response;
        }

        /**
         * @param $url
         * @return bool|string
         */
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

            $this->checkJsonIntegrity($response, $curlError);

            return $response;
        }

        /**
         * @param $jsonResponse
         * @param null $curlError
         */
        private function checkJsonIntegrity($jsonResponse, $curlError = null) {
            // Gestion des erreurs
            if ($curlError) {
                log::add('sigri_atome', 'debug', 'checkJsonIntegrity :: cURL Error # :' . $curlError);
                die();
            } elseif (strpos($jsonResponse, "No route found for")) {
                log::add('sigri_atome', 'error', 'checkJsonIntegrity :: La route API n\'est pas correcte : ' . $jsonResponse);
                die();
            } elseif (strpos($jsonResponse, "Login failed")) {
                log::add('sigri_atome', 'error', 'checkJsonIntegrity :: Login failed à la connexion API, réessayez plus tard... : ' . $jsonResponse);
            } elseif (strpos($jsonResponse, "Login Failed")) {
                log::add('sigri_atome', 'error', 'checkJsonIntegrity :: Login Failed à la connexion API, réessayez plus tard... : ' . $jsonResponse);
            } elseif (json_decode($jsonResponse) === false) {
                log::add('sigri_atome', 'error', 'checkJsonIntegrity :: JSON false : $jsonResponse : ' . print_r(json_decode($jsonResponse), true));
                die();
            } else {
                log::add('sigri_atome', 'debug', 'checkJsonIntegrity :: $jsonResponse : ' . $jsonResponse);
            }
        }

        /**
         * @param $jsonResponse
         * @param $event_name
         * @param $date_format
         */
        private function saveAtomeValueEvent($jsonResponse, $event_name, $date_format) {
            // On est dans de la récupération LIVE

            // Get datas
            $consoTime = $jsonResponse->time;
            $consoTotal = $jsonResponse->total;
            $consoPrice = $jsonResponse->price;
            $consoStart = $jsonResponse->startPeriod;
            $consoEnd = $jsonResponse->endPeriod;
            $consoImpactCO2 = $jsonResponse->impactCo2;
            log::add("sigri_atome", "debug", "callAtomeAPI :: consoTime=".$consoTime.", consoTotal=".$consoTotal.", consoPrice=".$consoPrice.", consoStart".$consoStart.", consoEnd".$consoEnd.", consoImpactCO2".$consoImpactCO2);

            // Formattage de la date
            try {
                $start_date = new DateTime($consoTime);
            } catch (Exception $e) {
                log::add('sigri_atome', 'debug', 'Exception : ' . $e->getMessage());
                die();
            }
            $jeedom_event_date = $start_date->format($date_format);

            $consoTotal = $consoTotal / 1000; // On passe les Wh en kWh
            log::add('sigri_atome', 'debug', 'Date : '.$jeedom_event_date.' - Indice : '.$consoTotal.' kWh - Prix : '.$consoPrice.' €');

            $cmd = $this->getCmd(null, $event_name);
            $cmd->event($consoTotal, $jeedom_event_date);
        }

        /**
         * @param $response
         */
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

        /**
         * @param $period
         */
        private function baseSkeleton($period) {
            $eqLogics = eqLogic::byType('sigri_atome');
            foreach ($eqLogics as $eqLogic) {
                if ($eqLogic->getIsEnable() != 1) {
                    log::add("sigri_atome", "error", "Aucun équipement n'est configuré/activé !");
                    die();
                }
                if (!empty($eqLogic->getConfiguration("identifiant")) && !empty($eqLogic->getConfiguration("password"))) {
                    $isNewApi = $eqLogic->getConfiguration('isNewApi', 0);

                    // Get values for Debug
                    log::add("sigri_atome", "debug", '$isNewApi : ' . $isNewApi);

                    $jsonResponse = $eqLogic->callAtomeLogin($eqLogic->getConfiguration("identifiant"), $eqLogic->getConfiguration("password"));
                    $eqLogic->callAtomeAPI($jsonResponse, $period, $isNewApi);
                }
            }
        }
	}

	class sigri_atomeCmd extends cmd {
		public function execute($_options = array()) {
		}
	}
?>