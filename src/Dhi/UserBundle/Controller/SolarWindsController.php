<?php
namespace Dhi\UserBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\UserBundle\Entity\Solarwinds;

class SolarWindsController extends Controller {

	protected $apiKey;
	protected $host;
	protected $container;

	function __construct($container){
		$this->container   = $container;
		$this->apiKey      = $container->getParameter('solarwinds_api_key');
		$this->host        = $container->getParameter('solarwinds_api_url');

	}

	public function generateTicket($ticket){
		$response = array("status" => "", "message" => "", "data" => array());

		if (!empty($this->apiKey) && !empty($this->host)) {

			// Check client exists or not
			$clientId = 0;
			$urlParams = "qualifier=(email='".$ticket['email']."')";
			$clientResponse = $this->requestCall("Clients", array(), $urlParams);
			$clientResponseArr = json_decode($clientResponse['response']);
			if (!empty($clientResponseArr)) {
				$clientId = $clientResponseArr[0]->id;
				$response['status'] = 'success';
			}

			if (empty($clientId)) {
				$params = array(
					"type"      => "Client",
					"email"     => $ticket['email'],
					"firstName" => $ticket['firstName'],
					"lastName"  => $ticket['lastName'],
					"phone"     => $ticket['phone'],
					"phone2"    => "",
					"username"  => strtolower($ticket['firstName'].'-'.$ticket['lastName']),
					"password"  => strtolower($ticket['firstName'].'-'.$ticket['lastName'])
				);

				$createdUserResponse = $this->requestCall("Clients", $params);
				$createdUserResponseArr = json_decode($createdUserResponse['response']);

				if ($createdUserResponse['serviceAvailable']) {
					if (!empty($createdUserResponseArr->id) && is_numeric($createdUserResponseArr->id)) {
						$clientId = $createdUserResponseArr->id;
						$response['status']  = "success";
					}else{
						$response['status']  = "error";
						$response['message'] = "Error occured while creating new Client: ".$createdUserResponse['response'];
					}
				}else{
					$response['status']  = "error";
					$response['message'] = (!empty($createdUserResponse['message']) ? $createdUserResponse['message'] : "#1007, Internal server error! Could not create client");
				}
			}

			if (!empty($response['status']) && $response['status'] == 'success' && !empty($clientId)) {
				$message = $this->getBody($ticket);

				// Create Ticket
				$params = array(
					"subject"       => $ticket['siteName'] . ": ".$ticket['category'],
					"detail"        => $message,
					"customFields" => array(
						array("definitionId" => 1,	"restValue" => "Researching Issue")
					),
					"clientReporter" => array(
						"type"	=> "Client", "id" => $clientId
					),
					"problemtype" => array("type" => "ProblemType", "id" => $ticket['solarwindId'])
				);

				$ticketResponse = $this->requestCall("Tickets", $params);
				$ticketResponseArr = json_decode($ticketResponse['response']);

				if ($ticketResponse['serviceAvailable']) {
					if (!empty($ticketResponseArr->id) && is_numeric($ticketResponseArr->id)) {
						$response['status']  = "success";
						$response['message'] = "Ticket created successfully Ticket Id: ".$ticketResponseArr->id;
						$response['data']    = $ticketResponseArr;
					}else{
						$response['status']  = "error";
						$response['message'] = "Error occured while creating new Ticket: ".$ticketResponse['response'];
					}
				}else{
					$response['status']  = "error";
					$response['message'] = "Internal Server Error! Please try again";
				}
			} else {
				$response['status']  = "error";
				if (!empty($response['message'])) {
					$response['message'] = (!empty($createdUserResponse['message']) ? $createdUserResponse['message'] : "#1007, Internal server error! Could not create client");
				}
			}

		}else{
			$response['status']  = "error";
			$response['message'] = "#1005, Invalid Solar winds configuration.";
		}
		return $response;
	}

	private function requestCall($action, $params = array(), $urlParams = ''){
		$ch = curl_init();

		$host = $this->host.$action.'?apiKey='.$this->apiKey.(!empty($urlParams) ? "&".$urlParams : '');

		curl_setopt($ch, CURLOPT_URL, $host);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		if (!empty($params)) {
			$params   = json_encode($params);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($params))); 
		}

		$curlResponse = curl_exec($ch);
		$info         = curl_getinfo($ch);
		$response     = array();

		$this->createLog(
			array(
				'request'       => $params, 
				'response'      => $curlResponse, 
				'action'        => $action, 
				'httpCode'      => $info['http_code'],
				'urlParameters' => $urlParams
			)
		);

		if ($info['http_code'] != '404') {
			if (empty($curlResponse)) {
				$response                     = array();
				$response['serviceAvailable'] = '0';
				$response['status']           = '0';
				$response['message']          = '(' . curl_errno($ch) . ') ' . curl_error($ch);
				$response['response']         = '';
			}else{
				$response['serviceAvailable'] = '1';
				$response['response']         = $curlResponse;
			}
		}else{
			$response['serviceAvailable'] = '0';
			$response['status']           = 0;
			$response['response']         = '';
			$response['message']          = '#1006, Internal Server Error.';
		}

		return $response;
	}

	private function getBody($ticket){
		$message  = "Site: ". $ticket['siteName'];
		$message .= "\nSite Domain: ". (($ticket['isSecure']) ? 'https://' : 'http://') . $ticket['domain'];
		$message .= "\n\nUser: ". $ticket['firstName'] . ' ' . $ticket['lastName'];
		$message .= "\nPhone: " . $ticket['phone'];
		$message .= "\nEmail: " . $ticket['email'];
		$message .= "\n\nLocation: " . $ticket['location'];
		$message .= "\nCountry: " . $ticket['country'];
		$message .= "\nBuilding/LSA: " . $ticket['building'];
		$message .= "\nRoom Number: " . $ticket['roomNumber'];
		$message .= "\nService: " . $ticket['service'];
		$message .= "\nCategory: " . $ticket['category'];
		$message .= "\nBest time to be contacted: " . $ticket['time'];
		$message .= "\n\nMessage: " . $ticket['message'];

		return $message;
	}

	private function createLog($params){
		$em        = $this->container->get('doctrine')->getManager();
		$ipAddress = $this->container->get('session')->get('ipAddress');

		$ticketId = '';
		if (!empty($params['response'])) {
			$response = json_decode($params['response']);
			if (!empty($response->id) && is_numeric($response->id)) {
				$ticketId = $response->id;
			}
		}

		$solarwinds = new Solarwinds();
		$solarwinds->setTicketId($ticketId);
		$solarwinds->setRequest(json_encode($params['request']));
		$solarwinds->setResponse(json_encode($params['response']));
		$solarwinds->setAction($params['action']);
		$solarwinds->setIpAddress($ipAddress);
		$solarwinds->setHttpCode($params['httpCode']);
		$solarwinds->setEndpoint($this->host);
		$solarwinds->setUrlParameters($params['urlParameters']);


		$em->persist($solarwinds);
		$em->flush();
	}

	private function getRequestTypes($locationId){
		$urlParams = "locationId=".$locationId."";
		$response  = $this->requestCall("RequestTypes", array(), $urlParams);
		if ($response['serviceAvailable'] == 0) {
			$response['message'] = $response['message'];
			$response['status']  = 'fail';
		}else{
			$requestTypeArr = json_decode($response['response']);
			if (!empty($requestTypeArr)) {
				$response['status'] = 'success';
				$response['id']     = $requestTypeArr[0]->id;
			}
		}
		return $response;
	}
}