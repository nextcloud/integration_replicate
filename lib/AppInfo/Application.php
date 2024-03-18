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
use OCA\Replicate\TextToImage\TextToImageProvider;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\IConfig;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

class Application extends App implements IBootstrap {

	public const APP_ID = 'integration_replicate';
	public const STABLE_DIFFUSION_VERSION = 'db21e45d3f7023abc2a46ee38a23973f6dce16bb082a930b0c49861f96d1e5bf';
	public const WHISPER_VERSION = 'e39e354773466b955265e969568deb7da217804d8e771ea8c9cd0cef6591f8bc';

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

			$context->registerSpeechToTextProvider(STTProvider::class);
			$context->registerTextToImageProvider(TextToImageProvider::class);
		}
	}

	public function boot(IBootContext $context): void {
	}
}

