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
	<div class="prediction">
		<div v-if="prediction === null" class="title">
			{{ t('integration_replicate', 'Loading transcription/translation...') }}
		</div>
		<span v-else class="title">
			<ReplicateIcon :size="20" class="icon" />
			<strong v-if="prediction.output?.translation">
				{{ t('integration_replicate', 'Replicate Whisper translation') }}
			</strong>
			<strong v-else>
				{{ t('integration_replicate', 'Replicate Whisper transcription') }}
			</strong>
		</span>
		<div v-if="predictionIsProcessing" class="processing-prediction">
			{{ t('integration_replicate', 'Transcription/translation is processing...') }}
		</div>
		<div v-else-if="predictionWasCanceled" class="canceled-prediction">
			{{ t('integration_replicate', 'Transcription/translation was canceled') }}
		</div>
		<div v-else-if="predictionHasFailed" class="failed-prediction">
			{{ t('integration_replicate', 'Transcription/translation has failed') }}
		</div>
		<div v-else-if="predictionSuccess">
			<span v-if="prediction.output?.translation">
				{{ prediction.output.translation }}
			</span>
			<span v-else>
				{{ prediction.output?.transcription }}
			</span>
		</div>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import ReplicateIcon from '../components/icons/ReplicateIcon.vue'

export default {
	name: 'WhisperPrediction',

	components: {
		ReplicateIcon,
	},

	props: {
		predictionId: {
			type: String,
			required: true,
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
	},
}
</script>

<style scoped lang="scss">
.prediction {
	width: 100%;
	padding: 12px;
	white-space: normal;

	.title {
		display: flex;
		margin-top: 0;
		.icon {
			align-self: start;
			margin: 2px 8px 0 0;
		}
	}
}
</style>
