<?php
/**
 * Nextcloud - Replicate
 *
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
 * @copyright Julien Veyssier 2022
 */

namespace OCA\Replicate\AppInfo;

use OCA\Replicate\Listener\ReplicateReferenceListener;
use OCA\Replicate\Reference\ImageReferenceProvider;
use OCA\Replicate\Reference\WhisperReferenceProvider;
use OCA\Replicate\SpeechToText\STTProvider;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\IConfig;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

class Application extends App implements IBootstrap {

	public const APP_ID = 'integration_replicate';
	public const STABLE_DIFFUSION_VERSION = 'f178fa7a1ae43a9a9af01b833b9d2ecf97b1bcb0acfd2dc5dd04895e042863f1';
	public const WHISPER_VERSION = '30414ee7c4fffc37e260fcab7842b5be470b9b840f2b608f5baa9bbef9a259ed';

	public const PROMPT_TYPE_IMAGE = 0;
	public const MAX_PROMPT_PER_TYPE_PER_USER = 5;

	private IConfig $config;

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();
		$this->config = $container->query(IConfig::class);
	}

	public function register(IRegistrationContext $context): void {
		$apiKey = $this->config->getAppValue(self::APP_ID, 'api_key');
		if ($apiKey !== '') {
			$context->registerReferenceProvider(ImageReferenceProvider::class);
			$context->registerReferenceProvider(WhisperReferenceProvider::class);
			$context->registerEventListener(RenderReferenceEvent::class, ReplicateReferenceListener::class);

			if (version_compare($this->config->getSystemValueString('version', '0.0.0'), '27.0.0', '>=')) {
				$context->registerSpeechToTextProvider(STTProvider::class);
			}
		}
	}

	public function boot(IBootContext $context): void {
	}
}

