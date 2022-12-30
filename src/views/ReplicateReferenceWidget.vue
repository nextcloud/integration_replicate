<!--
  - @copyright Copyright (c) 2022 Julien Veyssier <eneiluj@posteo.net>
  -
  - @author 2022 Julien Veyssier <eneiluj@posteo.net>
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
	<div class="replicate-reference">
		<div v-if="prediction === null" class="loading-prediction">
			{{ t('integration_replicate', 'Loading prediction...') }}
		</div>
		<h3 v-else>
			<ReplicateIcon :size="20" class="icon" />
			{{ t('integration_replicate', 'Replicate prediction') + ': ' }}
			&nbsp;
			<strong>
				{{ prediction?.input?.prompt }}
			</strong>
		</h3>
		<div v-if="predictionIsProcessing" class="processing-prediction">
			<p>{{ t('integration_replicate', 'Prediction is processing...') }}</p>
			<p>{{ formattedLogs }}</p>
		</div>
		<div v-else-if="predictionWasCanceled" class="canceled-prediction">
			{{ t('integration_replicate', 'Prediction was canceled') }}
		</div>
		<div v-else-if="predictionHasFailed" class="failed-prediction">
			{{ t('integration_replicate', 'Prediction has failed') }}
		</div>
		<a v-else-if="predictionSuccess"
			:href="realImageUrl"
			target="_blank"
			class="image-wrapper">
			<div v-if="!isImageLoaded" class="loading-icon">
				<NcLoadingIcon
					:size="44"
					:title="t('integration_replicate', 'Loading image')" />
			</div>
			<img v-show="isImageLoaded"
				class="image"
				:src="proxiedImageUrl"
				@load="isImageLoaded = true">
		</a>
	</div>
</template>

<script>
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import ReplicateIcon from '../components/icons/ReplicateIcon.vue'

export default {
	name: 'ReplicateReferenceWidget',

	components: {
		ReplicateIcon,
		NcLoadingIcon,
	},

	props: {
		richObjectType: {
			type: String,
			default: '',
		},
		richObject: {
			type: Object,
			default: null,
		},
		accessible: {
			type: Boolean,
			default: true,
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
		realImageUrl() {
			if (this.predictionSuccess) {
				return this.prediction?.output[0]
			}
			return ''
		},
		proxiedImageUrl() {
			if (this.predictionSuccess) {
				return generateUrl(
					'/apps/integration_replicate/predictions/{predictionId}/image?url={url}',
					{ predictionId: this.richObject.predictionId, url: this.realImageUrl }
				)
			}
			return ''
		},
		formattedLogs() {
			const logLines = this.prediction.logs.split('\n')
			const lastLine = logLines[logLines.length - 1]
			return lastLine
		},
	},

	mounted() {
		this.getPrediction()
	},

	methods: {
		getPrediction() {
			const url = generateUrl('/apps/integration_replicate/predictions/{predictionId}', { predictionId: this.richObject.predictionId })
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
	},
}
</script>

<style scoped lang="scss">
.replicate-reference {
	width: 100%;
	padding: 12px;
	white-space: normal;

	h3 {
		display: flex;
		margin-top: 0;
		.icon {
			margin-right: 8px;
		}
	}

	.image-wrapper {
		width: 100%;
		display: flex;
		align-items: center;
		justify-content: center;
		position: relative;

		.image {
			max-height: 300px;
			max-width: 100%;
			border-radius: var(--border-radius-large);
		}
	}
}
</style>
