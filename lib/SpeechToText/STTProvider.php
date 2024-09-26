<?php

declare(strict_types=1);
// SPDX-FileCopyrightText: Julien Veyssier <julien-nc@posteo.net>
// SPDX-License-Identifier: AGPL-3.0-or-later
namespace OCA\Replicate\SpeechToText;

use OCA\Replicate\Service\ReplicateAPIService;
use OCP\Files\File;
use OCP\IL10N;
use OCP\SpeechToText\ISpeechToTextProvider;
use Psr\Log\LoggerInterface;

class STTProvider implements ISpeechToTextProvider {

	public function __construct(
		private ReplicateAPIService $replicateAPIService,
		private LoggerInterface $logger,
		private IL10N $l,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l->t('Replicate\'s Whisper Speech-To-Text');
	}

	/**
	 * @inheritDoc
	 */
	public function transcribeFile(File $file): string {
		try {
			return $this->replicateAPIService->transcribeFile($file);
		} catch (\Exception $e) {
			$this->logger->warning('Replicate\'s Whisper transcription failed with: ' . $e->getMessage(), ['exception' => $e]);
			throw new \RuntimeException('Replicate\'s Whisper transcription failed with: ' . $e->getMessage());
		}
	}
}
