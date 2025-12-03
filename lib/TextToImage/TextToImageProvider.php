<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Julien Veyssier <julien-nc@posteo.net>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Replicate\TextToImage;

use OCA\Replicate\AppInfo\Application;
use OCA\Replicate\Service\ReplicateAPIService;
use OCA\Replicate\Service\WatermarkingService;
use OCP\Http\Client\IClientService;
use OCP\IL10N;
use OCP\TextToImage\IProvider;
use Psr\Log\LoggerInterface;

class TextToImageProvider implements IProvider {

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

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l->t('Replicate\'s stable diffusion Text-To-Image');
	}

	/**
	 * @inheritDoc
	 */
	public function generate(string $prompt, array $resources): void {
		$nbOutputs = count($resources);
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
				throw new \RuntimeException('Replicate\'s text to image generation failed: ' . ($prediction['error'] ?? 'unknown error'));
			}
			if (in_array($prediction['status'], ['failed', 'canceled'])) {
				throw new \RuntimeException('Replicate\'s text to image generation failed with status: ' . $prediction['status']);
			}
			if (!isset($prediction['output'])) {
				throw new \RuntimeException('Replicate\'s text to image generation failed, no output in ' . json_encode($prediction));
			}
			if ($waitingForAlready >= $maxWaitTime) {
				throw new \RuntimeException('Replicate\'s text to image generation failed, waited more than ' . $maxWaitTime . ' seconds');
			}
			// success
			$urls = is_array($prediction['output']) ? $prediction['output'] : [$prediction['output']];
			$urls = array_filter($urls, static function (?string $url) {
				return $url !== null;
			});
			$urls = array_filter($urls, static function (?string $url) {
				return $url !== null;
			});
			$urls = array_values($urls);
			if (empty($urls)) {
				$this->logger->warning('Replicate\'s text to image generation failed: no image returned');
				throw new \RuntimeException('Replicate\'s text to image generation failed: no image returned');
			}

			$client = $this->clientService->newClient();
			// just in case $resources is not 0-based indexed, we know $urls is
			$i = 0;
			foreach ($resources as $resource) {
				if (isset($urls[$i])) {
					$url = $urls[$i];
					$imageResponse = $client->get($url);
					$image = $imageResponse->getBody();
					$image = $this->watermarkingService->markImage($image);
					fwrite($resource, $image);
				}
				$i++;
			}
		} catch (\Exception $e) {
			$this->logger->warning('Replicate\'s text to image generation failed with: ' . $e->getMessage(), ['exception' => $e]);
			throw new \RuntimeException('Replicate\'s text to image generation failed with: ' . $e->getMessage());
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getExpectedRuntime(): int {
		return 60 * 60 * 24;
	}
}
