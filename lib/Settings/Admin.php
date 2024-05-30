<?php

namespace OCA\Replicate\Settings;

use OCA\Replicate\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\Settings\ISettings;

class Admin implements ISettings {

	public function __construct(
		private IConfig $config,
		private IInitialState $initialStateService,
	) {
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$apiKey = $this->config->getAppValue(Application::APP_ID, 'api_key');
		$model = $this->config->getAppValue(Application::APP_ID, 'model', 'large');
		$llmModelName = $this->config->getAppValue(Application::APP_ID, 'llm_model_name', Application::DEFAULT_LLM_NAME);
		$llmModelVersion = $this->config->getAppValue(Application::APP_ID, 'llm_model_version', Application::DEFAULT_LLM_VERSION);
		$llmExtraParams = $this->config->getAppValue(Application::APP_ID, 'llm_extra_params');
		$imageGenModelName = $this->config->getAppValue(Application::APP_ID, 'igen_model_name');
		$imageGenModelVersion = $this->config->getAppValue(Application::APP_ID, 'igen_model_version', Application::DEFAULT_IMAGE_GEN_VERSION);
		$imageGenExtraParams = $this->config->getAppValue(Application::APP_ID, 'igen_extra_params');

		$adminConfig = [
			'api_key' => $apiKey,
			'model' => $model,
			'llm_model_name' => $llmModelName,
			'llm_model_version' => $llmModelVersion,
			'llm_extra_params' => $llmExtraParams,
			'igen_model_name' => $imageGenModelName,
			'igen_model_version' => $imageGenModelVersion,
			'igen_extra_params' => $imageGenExtraParams,
		];
		$this->initialStateService->provideInitialState('admin-config', $adminConfig);

		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	public function getSection(): string {
		return 'ai';
	}

	public function getPriority(): int {
		return 10;
	}
}
