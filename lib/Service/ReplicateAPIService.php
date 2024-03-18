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
use OCA\Replicate\Db\PromptMapper;
use OCP\Files\File;
use OCP\Files\NotPermittedException;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Lock\LockedException;
use Psr\Log\LoggerInterface;
use Throwable;

class ReplicateAPIService {

	private IClient $client;

	public function __construct(
		string $appName,
		private LoggerInterface $logger,
		private IL10N $l10n,
		private IConfig $config,
		private PromptMapper $promptMapper,
		IClientService $clientService
	) {
		$this->client = $clientService->newClient();
	}

	/**
	 * @param string $userId
	 * @param int $type
	 * @return array
	 * @throws \OCP\DB\Exception
	 */
	public function getPromptHistory(string $userId, int $type): array {
		return $this->promptMapper->getPromptsOfUser($userId, $type);
	}

	/**
	 * @param string $audioFileUrl
	 * @param bool $translate
	 * @return array|string[]
	 */
	public function createWhisperPrediction(string $audioFileUrl, bool $translate = false): array {
		$model = $this->config->getAppValue(Application::APP_ID, 'model', 'large');
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
	 * Create a prediction and wait for it to complete to return the text result
	 *
	 * @param File $file
	 * @param bool $translate
	 * @return string
	 * @throws LockedException
	 * @throws NotPermittedException
	 */
	public function transcribeFile(File $file, bool $translate = false): string {
		$prediction = $this->createWhisperPrediction($file->getContent(), $translate);
		if (isset($prediction['id'])) {
			$predictionId = $prediction['id'];

			while (isset($prediction['status'])) {
				if ($prediction['status'] === 'succeeded') {
					return $translate
						? $prediction['output']['translation']
						: $prediction['output']['transcription'];
				} elseif ($prediction['status'] === 'failed') {
					throw new Exception('Error transcribing file "' . $file->getName() . '": remote job failed');
				} elseif ($prediction['status'] === 'canceled') {
					throw new Exception('Error transcribing file "' . $file->getName() . '": remote job was canceled');
				} elseif ($prediction['status'] !== 'starting' && $prediction['status'] !== 'processing') {
					throw new Exception('Error transcribing file "' . $file->getName() . '": unknown prediction status');
				}
				sleep(2);
				$prediction = $this->getPrediction($predictionId);
			}
		}
		throw new Exception('Error transcribing file "' . $file->getName() . '"');
	}

	public function createTextGenerationPrediction(string $prompt): array {
		$params = [
			'version' => Application::TEXT_GEN_MODEL_VERSION,
			'input' => [
				'prompt' => $prompt,
			],
		];
		return $this->request('predictions', $params, 'POST');
	}

	/**
	 * @param string $prompt
	 * @param string|null $userId
	 * @param int $numOutputs
	 * @param string $size
	 * @return array|string[]
	 * @throws \OCP\DB\Exception
	 */
	public function createImagePrediction(string $prompt, ?string $userId, int $numOutputs, string $size): array {
		$params = [
			'version' => Application::STABLE_DIFFUSION_VERSION,
			'input' => [
				'prompt' => $prompt,
				'num_outputs' => $numOutputs,
				'image_dimensions' => $size,
			],
		];
		if ($userId !== null) {
			$this->promptMapper->createPrompt(Application::PROMPT_TYPE_IMAGE, $userId, $prompt);
		}
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
			} elseif ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} elseif ($method === 'PUT') {
				$response = $this->client->put($url, $options);
			} elseif ($method === 'DELETE') {
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
