<?php
/**
 * Nextcloud - Replicate
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
 * @copyright Julien Veyssier 2022
 */

namespace OCA\Replicate\Controller;

use OCA\Replicate\AppInfo\Application;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\DB\Exception;
use OCP\IRequest;

use OCA\Replicate\Service\ReplicateAPIService;

class ReplicateAPIController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private ReplicateAPIService $replicateAPIService,
		private IInitialState $initialStateService,
		private ?string $userId
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $type
	 * @return DataResponse
	 * @throws Exception
	 */
	public function getPromptHistory(int $type): DataResponse {
		$response = $this->replicateAPIService->getPromptHistory($this->userId, $type);
		if (isset($response['error'])) {
			return new DataResponse($response, Http::STATUS_BAD_REQUEST);
		}
		return new DataResponse($response);
	}

	/**
	 * @NoAdminRequired
	 * @param string $audioBase64
	 * @param bool $translate
	 * @return DataResponse
	 */
	public function createWhisperPrediction(string $audioBase64, bool $translate = true): DataResponse {
		$response = $this->replicateAPIService->createWhisperPrediction($audioBase64, $translate);
		if (isset($response['error'])) {
			return new DataResponse($response, Http::STATUS_BAD_REQUEST);
		}
		return new DataResponse($response);
	}

	/**
	 * @NoAdminRequired
	 * @param string $prompt
	 * @param int $num_outputs
	 * @param string $size
	 * @return DataResponse
	 * @throws Exception
	 */
	public function createImagePrediction(string $prompt, int $num_outputs, string $size): DataResponse {
		$response = $this->replicateAPIService->createImagePrediction($prompt, $this->userId, $num_outputs, $size);
		if (isset($response['error'])) {
			return new DataResponse($response, Http::STATUS_BAD_REQUEST);
		}
		return new DataResponse($response);
	}

	/**
	 * @NoAdminRequired
	 * @param string $predictionId
	 * @return DataResponse
	 */
	public function getPrediction(string $predictionId): DataResponse {
		$response = $this->replicateAPIService->getPrediction($predictionId);
		if (isset($response['error'])) {
			return new DataResponse($response, Http::STATUS_BAD_REQUEST);
		}
		return new DataResponse($response);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $predictionId
	 * @param string $url
	 * @return DataDisplayResponse
	 */
	public function getImagePredictionContent(string $predictionId, string $url): DataDisplayResponse {
		$image = $this->replicateAPIService->getPredictionImage($predictionId, $url);
		if ($image !== null && isset($image['body'], $image['headers'])) {
			$response = new DataDisplayResponse(
				$image['body'],
				Http::STATUS_OK,
				['Content-Type' => $image['headers']['Content-Type'][0] ?? 'image/jpeg']
			);
			$response->cacheFor(60 * 60 * 24, false, true);
			return $response;
		}
		return new DataDisplayResponse('', Http::STATUS_NOT_FOUND);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $predictionId
	 * @return TemplateResponse
	 */
	public function getImagePredictionPage(string $predictionId): TemplateResponse {
		$this->initialStateService->provideInitialState('predictionId', $predictionId);
		return new TemplateResponse(Application::APP_ID, 'imagePredictionPage');
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $predictionId
	 * @return TemplateResponse
	 */
	public function getWhisperPredictionPage(string $predictionId): TemplateResponse {
		$this->initialStateService->provideInitialState('predictionId', $predictionId);
		return new TemplateResponse(Application::APP_ID, 'whisperPredictionPage');
	}
}
