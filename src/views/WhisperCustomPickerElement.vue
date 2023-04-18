<template>
	<div class="replicate-picker-content">
		<h2>
			{{ t('integration_replicate', 'AI speech-to-text') }}
		</h2>
		<a class="attribution"
			target="_blank"
			href="https://replicate.com">
			{{ poweredByTitle }}
		</a>
		<audio-recorder
			class="recorder"
			:attempts="1"
			:time="120"
			:show-download-button="true"
			:show-upload-button="false"
			:after-recording="onRecordEnd" />
		<div class="form-wrapper">
			<div class="line">
				<label>
					{{ t('integration_replicate', 'Action') }}
				</label>
				<div class="spacer" />
				<div class="radios">
					<NcCheckboxRadioSwitch
						:checked.sync="mode"
						type="radio"
						value="transcribe"
						name="mode">
						{{ t('integration_replicate', 'Transcribe') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch
						:checked.sync="mode"
						type="radio"
						value="translate"
						name="mode">
						{{ t('integration_replicate', 'Translate (only to English)') }}
					</NcCheckboxRadioSwitch>
				</div>
			</div>
			<div class="line">
				<label for="models">
					{{ t('integration_replicate', 'Model') }}
				</label>
				<NcButton type="tertiary"
					:title="t('integration_replicate', 'Larger model size gives better results but uses more credit')">
					<template #icon>
						<HelpCircleIcon />
					</template>
				</NcButton>
				<div class="spacer" />
				<NcSelect
					v-model="model"
					:options="modelOptions"
					input-id="models" />
			</div>
			<div class="line">
				<label for="result-types">
					{{ t('integration_replicate', 'Result format') }}
				</label>
				<div class="spacer" />
				<NcSelect
					v-model="type"
					:options="typeOptions"
					input-id="result-types" />
			</div>
		</div>
		<div class="footer">
			<span v-if="error" class="error">
				{{ error }}
			</span>
			<NcButton
				type="primary"
				:disabled="loading || looping || !model || audio === null || !type"
				@click="onInputEnter">
				<template #icon>
					<NcLoadingIcon v-if="loading || looping"
						:size="20" />
					<ArrowRightIcon v-else />
				</template>
				{{ t('integration_replicate', 'Submit') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import ArrowRightIcon from 'vue-material-design-icons/ArrowRight.vue'
import HelpCircleIcon from 'vue-material-design-icons/HelpCircle.vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import VueAudioRecorder from 'vue2-audio-recorder'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'

import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'
import Vue from 'vue'
Vue.directive('tooltip', Tooltip)
Vue.use(VueAudioRecorder)

export default {
	name: 'WhisperCustomPickerElement',

	components: {
		NcButton,
		NcLoadingIcon,
		NcCheckboxRadioSwitch,
		NcSelect,
		ArrowRightIcon,
		HelpCircleIcon,
	},

	props: {
		providerId: {
			type: String,
			required: true,
		},
		accessible: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			loading: false,
			looping: false,
			poweredByTitle: t('integration_replicate', 'by Replicate with Whisper'),
			mode: 'transcribe',
			modelOptions: [
				{ label: t('integration_replicate', 'Tiny'), value: 'tiny' },
				{ label: t('integration_replicate', 'Base'), value: 'base' },
				{ label: t('integration_replicate', 'Small'), value: 'small' },
				{ label: t('integration_replicate', 'Medium'), value: 'medium' },
				{ label: t('integration_replicate', 'Large'), value: 'large' },
			],
			typeOptions: [
				{ label: t('integration_replicate', 'Text'), value: 'text' },
				{ label: t('integration_replicate', 'Internal link/widget'), value: 'link' },
			],
			model: { label: t('integration_replicate', 'Large'), value: 'large' },
			type: { label: t('integration_replicate', 'Text'), value: 'text' },
			audio: null,
			error: null,
		}
	},

	computed: {
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		async onRecordEnd(e) {
			const readBlob = (blob) => {
				const reader = new FileReader()
				return new Promise((resolve) => {
					reader.addEventListener('load', () => {
						resolve(reader.result)
					})
					reader.readAsDataURL(blob)
				})
			}

			try {
				this.audio = await readBlob(e.blob)
			} catch (e) {
				console.warn(e.message)
			}
		},
		onSubmit(url) {
			this.$emit('submit', url)
		},
		onInputEnter() {
			this.loading = true
			this.error = null
			const params = {
				translate: this.mode === 'translate',
				model: this.model.value,
				audioBase64: this.audio,
			}
			const url = generateUrl('/apps/integration_replicate/predictions/whisper')
			return axios.post(url, params)
				.then((response) => {
					console.debug('predictions response', response.data)
					if (!['failed', 'canceled'].includes(response.data.status)) {
						if (this.type.value === 'link') {
							const internalUrl = window.location.protocol + '//' + window.location.host
								+ generateUrl('/apps/integration_replicate/w/{id}', { id: response.data.id })
							this.onSubmit(internalUrl)
						} else {
							this.looping = true
							this.checkPredictionLoop(response.data.id)
						}
					} else {
						this.error = response.data.error
					}
				})
				.catch((error) => {
					console.debug('replicate request error', error)
					showError(
						t('integration_replicate', 'Failed launch image generation')
						+ ': ' + (error.response?.data?.body?.detail ?? error.response?.data?.error)
					)
				})
				.then(() => {
					this.loading = false
				})
		},
		checkPredictionLoop(predictionId) {
			const url = generateUrl('/apps/integration_replicate/predictions/{predictionId}', { predictionId })
			axios.get(url)
				.then((response) => {
					const prediction = response.data
					if (['starting', 'processing'].includes(prediction?.status)) {
						setTimeout(() => {
							this.checkPredictionLoop(predictionId)
						}, 2000)
					} else {
						this.looping = false
						if (prediction?.status === 'failed') {
							this.error = response.data.error
						} else if (prediction?.status === 'canceled') {
							this.error = t('integration_replicate', 'Prediction was cancelled')
						} else if (prediction?.status === 'succeeded') {
							this.onSubmit(prediction?.output?.translation ?? prediction?.output?.transcription)
						} else {
							this.error = t('integration_replicate', 'Unknown error')
						}
					}
				})
		},
	},
}
</script>

<style scoped lang="scss">
.replicate-picker-content {
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;

	h2 {
		display: flex;
		align-items: center;
	}

	.spacer {
		flex-grow: 1;
	}

	.attribution {
		padding-bottom: 12px;
	}

	.form-wrapper {
		display: flex;
		flex-direction: column;
		align-items: center;
		width: 100%;
		margin: 8px 0;
		.radios {
			display: flex;
			> * {
				margin: 0 16px;
			}
		}
	}

	.line {
		display: flex;
		align-items: center;
		margin-top: 8px;
		width: 100%;
	}

	.footer {
		display: flex;
		align-items: center;
		justify-content: end;
		width: 100%;

		.error {
			margin-right: 12px;
		}
	}

	::v-deep .recorder {
		.ar-recorder__time-limit {
			position: unset !important;
		}
		.ar-player__time {
			font-size: 14px;
		}
		.ar-records {
			height: unset !important;
		}
	}
}
</style>
