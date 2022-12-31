<?php
/**
 * Nextcloud - Replicate
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 * @copyright Julien Veyssier 2022
 */

return [
	'routes' => [
		['name' => 'config#setAdminConfig', 'url' => '/admin-config', 'verb' => 'PUT'],
		['name' => 'replicateAPI#createPrediction', 'url' => '/predictions', 'verb' => 'POST'],
		['name' => 'replicateAPI#getPredictionPage', 'url' => '/p/{predictionId}', 'verb' => 'GET'],
		['name' => 'replicateAPI#getPrediction', 'url' => '/predictions/{predictionId}', 'verb' => 'GET'],
		['name' => 'replicateAPI#getPredictionImage', 'url' => '/predictions/{predictionId}/image', 'verb' => 'GET'],
	],
];
