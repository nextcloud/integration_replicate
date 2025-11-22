<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Julien Veyssier <julien-nc@posteo.net>
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\Replicate\TaskProcessing;

use OCA\OpenAi\Service\WatermarkingService;
use OCA\Replicate\AppInfo\Application;
use OCA\Replicate\Service\ReplicateAPIService;
use OCP\IL10N;
use OCP\TaskProcessing\Exception\ProcessingException;
use OCP\TaskProcessing\ISynchronousProvider;
use OCP\TaskProcessing\TaskTypes\AudioToText;
use Psr\Log\LoggerInterface;

class SpeechToTextProvider implements ISynchronousProvider {

	public function __construct(
		private ReplicateAPIService $replicateAPIService,
		private LoggerInterface $logger,
		private IL10N $l,
	) {
	}

	public function getName(): string {
		return $this->l->t('Replicate\'s Whisper Speech-To-Text');
	}

	public function process(?string $userId, array $input, callable $reportProgress): array {
		try {
			return [ 'output' => $this->replicateAPIService->transcribeFile($input['input']) ];
		} catch (\Exception $e) {
			$this->logger->warning('Replicate\'s Whisper transcription failed with: ' . $e->getMessage(), ['exception' => $e]);
			throw new ProcessingException('Replicate\'s Whisper transcription failed with: ' . $e->getMessage());
		}
	}

	public function getId(): string
	{
		return Application::APP_ID . '-stt';
	}

	public function getTaskTypeId(): string {
		return AudioToText::ID;
	}

	public function getExpectedRuntime(): int
	{
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
