<template>
	<div class="replicate-picker-content">
		<h2>
			{{ t('integration_replicate', 'Transcribe or translate text') }}
		</h2>
		<a class="attribution"
			target="_blank"
			href="https://replicate.com">
			{{ poweredByTitle }}
		</a>
		<div class="form-wrapper">
			<div class="line">
				<label for="models">
					{{ t('integration_replicate', 'Model') }}
				</label>
				<NcSelect
					v-model="model"
					:options="modelOptions"
					input-id="models" />
			</div>
			<NcCheckboxRadioSwitch
				:checked.sync="translate">
				{{ t('integration_replicate', 'Translate') }}
			</NcCheckboxRadioSwitch>
		</div>
		<audio-recorder
			:attempts="1"
			:time="120"
			:show-download-button="true"
			:show-upload-button="false"
			:after-recording="onRecordEnd" />
		<div class="footer">
			<NcButton
				type="primary"
				:disabled="loading || !model"
				@click="onInputEnter">
				<template #icon>
					<NcLoadingIcon v-if="loading"
						:size="20"
						:title="t('integration_replicate', 'Loading')" />
				</template>
				{{ t('integration_replicate', 'Submit') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import VueAudioRecorder from 'vue2-audio-recorder'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

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
			poweredByTitle: t('integration_replicate', 'Powered by Replicate'),
			translate: false,
			modelOptions: [
				{ label: t('integration_replicate', 'tiny'), value: 'tiny' },
				{ label: t('integration_replicate', 'base'), value: 'base' },
				{ label: t('integration_replicate', 'small'), value: 'small' },
				{ label: t('integration_replicate', 'medium'), value: 'medium' },
				{ label: t('integration_replicate', 'large'), value: 'large' },
			],
			model: 'large',
			audio: null,
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
		onCancel() {
			this.$emit('cancel')
		},
		onSubmit(url) {
			this.$emit('submit', url)
		},
		onInputEnter() {
			this.loading = true
			const params = {
				translate: this.translate,
				model: this.model.value,
				audioBase64: this.audio,
			}
			const url = generateUrl('/apps/integration_replicate/predictions/whisper')
			return axios.post(url, params)
				.then((response) => {
					console.debug('predictions response', response.data)
					if (!['failed', 'canceled'].includes(response.data.status)) {
						const internalUrl = window.location.protocol + '//' + window.location.host
							+ generateUrl('/apps/integration_replicate/w/{id}', { id: response.data.id })
						this.onSubmit(internalUrl)
					} else {
						this.error = response.data.error
					}
				})
				.catch((error) => {
					console.debug('replicate request error', error)
				})
				.then(() => {
					this.loading = false
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
	//padding: 16px;

	h2 {
		display: flex;
		align-items: center;
	}

	.attribution {
		padding-bottom: 8px;
	}

	.form-wrapper {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
	}

	.line {
		display: flex;
		align-items: center;
		label {
			margin-right: 8px;
		}
	}

	.footer {
		display: flex;
		align-items: center;
		justify-content: end;
		width: 100%;
	}
}
</style>
