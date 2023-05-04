<?php
/**
 * Nextcloud - Replicate
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
 * @copyright Julien Veyssier 2022
 */

return [
	'routes' => [
		['name' => 'config#setAdminConfig', 'url' => '/admin-config', 'verb' => 'PUT'],
		['name' => 'replicateAPI#getPromptHistory', 'url' => '/prompts', 'verb' => 'GET'],
		['name' => 'replicateAPI#createImagePrediction', 'url' => '/predictions/image', 'verb' => 'POST'],
		['name' => 'replicateAPI#createWhisperPrediction', 'url' => '/predictions/whisper', 'verb' => 'POST'],
		['name' => 'replicateAPI#getImagePredictionPage', 'url' => '/i/{predictionId}', 'verb' => 'GET'],
		['name' => 'replicateAPI#getWhisperPredictionPage', 'url' => '/w/{predictionId}', 'verb' => 'GET'],
		['name' => 'replicateAPI#getPrediction', 'url' => '/predictions/{predictionId}', 'verb' => 'GET'],
		['name' => 'replicateAPI#getImagePredictionContent', 'url' => '/predictions/{predictionId}/image', 'verb' => 'GET'],
	],
];
