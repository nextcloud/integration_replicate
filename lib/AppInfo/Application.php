<?php
/**
 * Nextcloud - Replicate
 *
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2022
 */

namespace OCA\Replicate\AppInfo;

use OCA\Replicate\Listener\ReplicateReferenceListener;
use OCA\Replicate\Reference\ReplicateReferenceProvider;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\IConfig;

use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

class Application extends App implements IBootstrap {

	public const APP_ID = 'integration_replicate';
	public const STABLE_DIFFUSION_VERSION = 'f178fa7a1ae43a9a9af01b833b9d2ecf97b1bcb0acfd2dc5dd04895e042863f1';

	/**
	 * @var IConfig
	 */
	private $config;

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();
		/** @var IConfig config */
		$this->config = $container->query(IConfig::class);
	}

	public function register(IRegistrationContext $context): void {
		$apiKey = $this->config->getAppValue(self::APP_ID, 'api_key');
		if ($apiKey !== '') {
			$context->registerReferenceProvider(ReplicateReferenceProvider::class);
			$context->registerEventListener(RenderReferenceEvent::class, ReplicateReferenceListener::class);
		}
	}

	public function boot(IBootContext $context): void {
	}
}

