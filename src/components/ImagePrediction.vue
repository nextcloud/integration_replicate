<!--
  - @copyright Copyright (c) 2022 Julien Veyssier <julien-nc@posteo.net>
  -
  - @author 2022 Julien Veyssier <julien-nc@posteo.net>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div class="prediction">
		<div v-if="prediction === null" class="title">
			<ReplicateIcon :size="20" class="icon" />
			<strong>
				{{ t('integration_replicate', 'Loading prediction...') }}
			</strong>
		</div>
		<span v-else class="title">
			<ReplicateIcon :size="20" class="icon" />
			<strong>
				{{ t('integration_replicate', 'Replicate prediction') + ':' }}
			</strong>
			&nbsp;
			<span>
				{{ prediction?.input?.prompt }}
			</span>
		</span>
		<div v-if="predictionIsProcessing" class="processing-prediction">
			<p>{{ t('integration_replicate', 'Prediction is processing...') }}</p>
			<pre>{{ formattedLogs }}</pre>
		</div>
		<div v-else-if="predictionWasCanceled" class="canceled-prediction">
			{{ t('integration_replicate', 'Prediction was canceled') }}
		</div>
		<div v-else-if="predictionHasFailed" class="failed-prediction">
			{{ t('integration_replicate', 'Prediction has failed') }}
		</div>
		<div v-else-if="predictionSuccess"
			:class="{ images: true, vertical: orientation === 'vertical' }">
			<a v-for="realImageUrl in prediction.output"
				:key="realImageUrl"
				:href="realImageUrl"
				target="_blank"
				class="image-wrapper">
				<ReplicateImage :direct-link="realImageUrl"
					:src="getProxiedImageUrl(realImageUrl)" />
			</a>
		</div>
	</div>
</template>

<script>
import ReplicateIcon from '../components/icons/ReplicateIcon.vue'
import ReplicateImage from './ReplicateImage.vue'

import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'

export default {
	name: 'ImagePrediction',

	components: {
		ReplicateImage,
		ReplicateIcon,
	},

	props: {
		predictionId: {
			type: String,
			required: true,
		},
		orientation: {
			type: String,
			default: 'vertical',
			validator: val => ['horizontal', 'vertical'].includes(val),
		},
	},

	data() {
		return {
			prediction: null,
			isImageLoaded: false,
		}
	},

	computed: {
		predictionIsProcessing() {
			return ['starting', 'processing'].includes(this.prediction?.status)
		},
		predictionHasFailed() {
			return this.prediction?.status === 'failed'
		},
		predictionWasCanceled() {
			return this.prediction?.status === 'canceled'
		},
		predictionSuccess() {
			return this.prediction?.status === 'succeeded'
		},
		formattedLogs() {
			if (this.prediction.logs) {
				const logLines = this.prediction.logs.split('\n')
				const lastLine = logLines[logLines.length - 1]
				return lastLine
			}
			return ''
		},
	},

	mounted() {
		this.getPrediction()
	},

	methods: {
		getPrediction() {
			const url = generateUrl('/apps/integration_replicate/predictions/{predictionId}', { predictionId: this.predictionId })
			axios.get(url)
				.then((response) => {
					this.prediction = response.data
					if (['starting', 'processing'].includes(this.prediction?.status)) {
						setTimeout(() => {
							this.getPrediction()
						}, 2000)
					}
				})
		},
		getProxiedImageUrl(realImageUrl) {
			return generateUrl(
				'/apps/integration_replicate/predictions/{predictionId}/image?url={url}',
				{ predictionId: this.predictionId, url: realImageUrl }
			)
		},
	},
}
</script>

<style scoped lang="scss">
.prediction {
	width: 100%;
	padding: 12px;
	white-space: normal;

	.title {
		margin-top: 0;
		.icon {
			display: inline;
			position: relative;
			top: 4px;
			// margin: 2px 8px 0 0;
		}
	}

	.images {
		margin-top: 12px;
		display: flex;

		.image-wrapper {
			width: 100%;
			display: flex;
			align-items: center;
			justify-content: center;
			position: relative;
			margin: 8px 4px 0 4px;
		}

		&.vertical {
			flex-direction: column;
			.image-wrapper {
				margin-left: 0px;
				margin-right: 0px;
			}
		}
	}
}
</style>
