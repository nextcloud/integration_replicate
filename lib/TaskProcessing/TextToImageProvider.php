<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Julien Veyssier <julien-nc@posteo.net>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Replicate\TaskProcessing;

use OCA\OpenAi\Service\WatermarkingService;
use OCA\Replicate\AppInfo\Application;
use OCA\Replicate\Service\ReplicateAPIService;
use OCP\Http\Client\IClientService;
use OCP\IL10N;
use OCP\TaskProcessing\Exception\ProcessingException;
use OCP\TaskProcessing\TaskTypes\TextToImage;
use OCP\TextToImage\IProvider;
use Psr\Log\LoggerInterface;

class TextToImageProvider implements \OCP\TaskProcessing\ISynchronousProvider {

	public function __construct(
		private ReplicateAPIService $replicateAPIService,
		private LoggerInterface $logger,
		private IL10N $l,
		private IClientService $clientService,
		private WatermarkingService $watermarkingService,
	) {
	}

	public function getId(): string {
		return Application::APP_ID . '_image_generation';
	}

	public function getName(): string {
		return $this->l->t('Replicate\'s stable diffusion Text-To-Image');
	}

	public function getTaskTypeId(): string {
		return TextToImage::ID;
	}

	public function process(?string $userId, array $input, callable $reportProgress): array {
		$prompt = $input['input'];
		$nbOutputs = $input['numberOfImages'];
		$waitingForAlready = 0;
		$maxWaitTime = 60 * 60;
		try {
			$prediction = $this->replicateAPIService->createImagePrediction($prompt, $nbOutputs);

			while ($waitingForAlready < $maxWaitTime && isset($prediction['status'], $prediction['id']) && in_array($prediction['status'], ['starting', 'processing'], true)) {
				sleep(2);
				$this->logger->debug('WAITING for prediction ' . $prediction['id'] . ' status ' . $prediction['status']);
				$waitingForAlready += 2;
				$prediction = $this->replicateAPIService->getPrediction($prediction['id']);
			}

			if (!isset($prediction['status'], $prediction['id'])) {
				throw new ProcessingException('Replicate\'s text to image generation failed: ' . ($prediction['error'] ?? 'unknown error'));
			}
			if (in_array($prediction['status'], ['failed', 'canceled'])) {
				throw new ProcessingException('Replicate\'s text to image generation failed with status: ' . $prediction['status']);
			}
			if (!isset($prediction['output'])) {
				throw new ProcessingException('Replicate\'s text to image generation failed, no output in ' . json_encode($prediction));
			}
			if ($waitingForAlready >= $maxWaitTime) {
				throw new ProcessingException('Replicate\'s text to image generation failed, waited more than ' . $maxWaitTime . ' seconds');
			}
			// success
			$urls = is_array($prediction['output']) ? $prediction['output'] : [$prediction['output']];
			$urls = array_filter($urls, static function (?string $url) {
				return $url !== null;
			});
			$urls = array_values($urls);
			if (empty($urls)) {
				$this->logger->warning('Replicate\'s text to image generation failed: no image returned');
				throw new ProcessingException('Replicate\'s text to image generation failed: no image returned');
			}

			$client = $this->clientService->newClient();
			$images = [];
			foreach ($urls as $url) {
				$imageResponse = $client->get($url);
				$image = $imageResponse->getBody();
				$images[] = $this->watermarkingService->markImage($image);
			}

			return ['images' => $images];
		} catch (\Exception $e) {
			$this->logger->warning('Replicate\'s text to image generation failed with: ' . $e->getMessage(), ['exception' => $e]);
			throw new ProcessingException('Replicate\'s text to image generation failed with: ' . $e->getMessage());
		}
	}

	public function getExpectedRuntime(): int {
		return 60;
	}

	public function getOptionalInputShape(): array
	{
		return [];
	}

	public function getOptionalOutputShape(): array
	{
		return [];
	}

	public function getInputShapeEnumValues(): array
	{
		return [];
	}

	public function getInputShapeDefaults(): array
	{
		return [];
	}

	public function getOptionalInputShapeEnumValues(): array
	{
		return [];
	}

	public function getOptionalInputShapeDefaults(): array
	{
		return [];
	}

	public function getOutputShapeEnumValues(): array
	{
		return [];
	}

	public function getOptionalOutputShapeEnumValues(): array
	{
		return [];
	}
}
