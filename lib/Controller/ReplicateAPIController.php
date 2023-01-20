<?php
/**
 * Nextcloud - Replicate
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
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
use OCP\IRequest;

use OCA\Replicate\Service\ReplicateAPIService;

class ReplicateAPIController extends Controller {

	private ReplicateAPIService $replicateAPIService;
	private IInitialState $initialStateService;

	public function __construct(string              $appName,
								IRequest            $request,
								ReplicateAPIService $replicateAPIService,
								IInitialState       $initialStateService,
								?string             $userId) {
		parent::__construct($appName, $request);
		$this->replicateAPIService = $replicateAPIService;
		$this->initialStateService = $initialStateService;
	}

	/**
	 * @NoAdminRequired
	 * @param string $prompt
	 * @return DataResponse
	 */
	public function createWhisperPrediction(bool $translate = true, string $model = 'large'): DataResponse {
		// TODO receive file, store it and generate URL
		// $fileUrl = 'https://replicate.delivery/mgxm/8d32eac0-0a30-47e8-819e-673a751f4b52/OSR_cn_000_0072_8k.wav';
		$fileUrl = 'https://replicate.delivery/mgxm/716614fb-530c-4f66-b0f9-1b6c562716c4/Untitled.wav';
		$response = $this->replicateAPIService->createWhisperPrediction($fileUrl, $translate, $model);
		if (isset($response['error'])) {
			return new DataResponse($response, Http::STATUS_BAD_REQUEST);
		}
		return new DataResponse($response);
	}

	/**
	 * @NoAdminRequired
	 * @param string $prompt
	 * @return DataResponse
	 */
	public function createImagePrediction(string $prompt): DataResponse {
		$response = $this->replicateAPIService->createImagePrediction($prompt);
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
