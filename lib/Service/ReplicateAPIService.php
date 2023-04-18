<?php
/**
 * Nextcloud - Replicate
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier
 * @copyright Julien Veyssier 2022
 */

namespace OCA\Replicate\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\Replicate\AppInfo\Application;
use OCP\Http\Client\IClient;
use OCP\IConfig;
use OCP\IL10N;
use Psr\Log\LoggerInterface;
use OCP\Http\Client\IClientService;
use Throwable;

/**
 * Service to make requests to Replicate REST API
 */
class ReplicateAPIService {

	private IClient $client;

	public function __construct (string $appName,
								private LoggerInterface $logger,
								private IL10N $l10n,
								private IConfig $config,
								IClientService $clientService) {
		$this->client = $clientService->newClient();
	}

	/**
	 * @param string $audioFileUrl
	 * @param bool $translate
	 * @param string $model
	 * @return array|string[]
	 */
	public function createWhisperPrediction(string $audioFileUrl, bool $translate = false, string $model = 'large'): array {
		$params = [
			'version' => Application::WHISPER_VERSION,
			'input' => [
				'audio' => $audioFileUrl,
				'translate' => $translate,
				'model' => $model,
			],
		];
		return $this->request('predictions', $params, 'POST');
	}

	/**
	 * @param string $prompt
	 * @return array|string[]
	 */
	public function createImagePrediction(string $prompt): array {
		$params = [
			'version' => Application::STABLE_DIFFUSION_VERSION,
			'input' => [
				'prompt' => $prompt,
			],
			'num_outputs' => 1,
		];
		return $this->request('predictions', $params, 'POST');
	}

	/**
	 * @param string $predictionId
	 * @return array request result
	 */
	public function getPrediction(string $predictionId): array {
		return $this->request('predictions/' . $predictionId);
	}

	/**
	 * @param string $predictionId
	 * @param string $url
	 * @return array|null
	 * @throws Exception
	 */
	public function getPredictionImage(string $predictionId, string $url): ?array {
		$prediction = $this->getPrediction($predictionId);
		if (!isset($prediction['error']) && isset($prediction['id']) && $prediction['id'] === $predictionId) {
			$outputs = $prediction['output'] ?? [];
			if (in_array($url, $outputs)) {
				$imageResponse = $this->client->get($url);
				return [
					'body' => $imageResponse->getBody(),
					'headers' => $imageResponse->getHeaders(),
				];
			}
		}
		return null;
	}

	/**
	 * Make an HTTP request to the Replicate API
	 * @param string $endPoint The path to reach
	 * @param array $params Query parameters (key/val pairs)
	 * @param string $method HTTP query method
	 * @return array decoded request result or error
	 */
	public function request(string $endPoint, array $params = [], string $method = 'GET'): array {
		try {
			$apiKey = $this->config->getAppValue(Application::APP_ID, 'api_key');
			if ($apiKey === '') {
				return ['error' => 'No API key'];
			}

			$url = 'https://api.replicate.com/v1/' . $endPoint;
			$options = [
				'headers' => [
					'User-Agent' => 'Nextcloud Replicate integration',
					'Content-Type' => 'application/json',
					'Authorization' => 'Token ' . $apiKey,
				],
			];

			if (count($params) > 0) {
				if ($method === 'GET') {
					$paramsContent = http_build_query($params);
					$url .= '?' . $paramsContent;
				} else {
					$options['body'] = json_encode($params);
				}
			}

			if ($method === 'GET') {
				$response = $this->client->get($url, $options);
			} else if ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} else if ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} else if ($method === 'DELETE') {
				$response = $this->client->delete($url, $options);
			} else {
				return ['error' => $this->l10n->t('Bad HTTP method')];
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return json_decode($body, true) ?: [];
			}
		} catch (ClientException | ServerException $e) {
			$responseBody = $e->getResponse()->getBody();
			$parsedResponseBody = json_decode($responseBody, true);
			if ($e->getResponse()->getStatusCode() === 404) {
				$this->logger->debug('Replicate API error : ' . $e->getMessage(), ['response_body' => $responseBody, 'app' => Application::APP_ID]);
			} else {
				$this->logger->warning('Replicate API error : ' . $e->getMessage(), ['response_body' => $responseBody, 'app' => Application::APP_ID]);
			}
			return [
				'error' => $e->getMessage(),
				'body' => $parsedResponseBody,
			];
		} catch (Exception | Throwable $e) {
			$this->logger->warning('Replicate API error : ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
