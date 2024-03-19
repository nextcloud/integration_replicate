<?php

declare(strict_types=1);

namespace OCA\Replicate\TextProcessing;

use OCA\Replicate\Service\ReplicateAPIService;
use OCP\IL10N;
use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\IProviderWithExpectedRuntime;
use OCP\TextProcessing\IProviderWithUserId;
use Psr\Log\LoggerInterface;

/**
 * @implements IProviderWithExpectedRuntime<FreePromptTaskType>
 * @implements IProviderWithUserId<FreePromptTaskType>
 */
class FreePromptProvider implements IProviderWithExpectedRuntime, IProviderWithUserId {

	public function __construct(
		private ReplicateAPIService $replicateAPIService,
		private IL10N $l10n,
		private LoggerInterface $logger,
		private ?string $userId,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Replicate integration');
	}

	public function process(string $prompt): string {
		$waitingForAlready = 0;
		$maxWaitTime = 60 * 60;
		try {
			$prediction = $this->replicateAPIService->createTextGenerationPrediction($prompt);

			while ($waitingForAlready < $maxWaitTime && isset($prediction['status'], $prediction['id']) && in_array($prediction['status'], ['starting', 'processing'], true)) {
				sleep(2);
				error_log('WAITING for TP prediction ' . $prediction['id'] . ' status ' . $prediction['status']);
				$waitingForAlready += 2;
				$prediction = $this->replicateAPIService->getPrediction($prediction['id']);
			}

			if (!isset($prediction['status'], $prediction['id'])) {
				throw new \RuntimeException('Replicate\'s text generation failed: ' . ($prediction['error'] ?? 'unknown error'));
			}
			if (in_array($prediction['status'], ['failed', 'canceled'])) {
				throw new \RuntimeException('Replicate\'s text generation failed with status: ' . $prediction['status']);
			}
			if (!isset($prediction['output']) || !is_array($prediction['output'])) {
				throw new \RuntimeException('Replicate\'s text generation failed, no output in ' . json_encode($prediction));
			}
			if ($waitingForAlready >= $maxWaitTime) {
				throw new \RuntimeException('Replicate\'s text generation failed, waited more than ' . $maxWaitTime . ' seconds');
			}
			// success
			return implode('', $prediction['output']);
		} catch(\Exception $e) {
			$this->logger->warning('Replicate\'s text generation failed with: ' . $e->getMessage(), ['exception' => $e]);
			throw new \RuntimeException('Replicate\'s text generation failed with: ' . $e->getMessage());
		}
	}

	public function getTaskType(): string {
		return FreePromptTaskType::class;
	}

	public function getExpectedRuntime(): int {
		return 60 * 60 * 24;
	}

	public function setUserId(?string $userId): void {
		$this->userId = $userId;
	}
}
