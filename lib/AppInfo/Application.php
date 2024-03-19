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
use OCA\Replicate\TextProcessing\FreePromptProvider;
use OCA\Replicate\TextToImage\TextToImageProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;

use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\IConfig;

class Application extends App implements IBootstrap {

	public const APP_ID = 'integration_replicate';
	public const DEFAULT_IMAGE_GEN_NAME = 'stability-ai/stable-diffusion';
	public const DEFAULT_IMAGE_GEN_VERSION = 'ac732df83cea7fff18b8472768c88ad041fa750ff7682a21affe81863cbe77e4';
	public const WHISPER_VERSION = 'e39e354773466b955265e969568deb7da217804d8e771ea8c9cd0cef6591f8bc';
	public const DEFAULT_LLM_NAME = 'mistralai/mistral-7b-instruct-v0.1';
	public const DEFAULT_LLM_VERSION = '83b6a56e7c828e667f21fd596c338fd4f0039b46bcfa18d973e8e70e455fda70';

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
			$context->registerTextProcessingProvider(FreePromptProvider::class);
		}
	}

	public function boot(IBootContext $context): void {
	}
}
