<template>
	<div id="replicate_prefs" class="section">
		<h2>
			<ReplicateIcon class="icon" />
			{{ t('integration_replicate', 'Replicate integration') }}
		</h2>
		<div id="replicate-content">
			<div class="line">
				<NcTextField
					class="input"
					:value.sync="state.api_key"
					:label="t('integration_replicate', 'Replicate API token')"
					:placeholder="t('integration_replicate', 'Your API token')"
					:show-trailing-button="!!state.api_key"
					@update:value="onInput"
					@trailing-button-click="state.api_key = '' ; onInput()" />
			</div>
			<p class="settings-hint">
				<InformationOutlineIcon :size="20" class="icon" />
				<a href="https://replicate.com" target="_blank">
					{{ t('integration_replicate', 'You can create a free API token on https://replicate.com') }}
				</a>
			</p>
			<div class="line">
				<NcTextField
					class="input"
					:value.sync="state.llm_model_name"
					:label="t('integration_replicate', 'Text generation model name')"
					:disabled="loading"
					:show-trailing-button="!!state.llm_model_name"
					@update:value="onInput"
					@trailing-button-click="state.llm_model_name = '' ; onInput()" />
				<NcButton type="tertiary"
					:title="t('integration_replicate', 'You can find model names on the Replicate website. For example: \'mistralai/mixtral-8x7b-instruct-v0.1\' or \'meta/llama-2-70b-chat\'')">
					<template #icon>
						<HelpCircleIcon />
					</template>
				</NcButton>
			</div>
			<div class="line">
				<NcTextField
					class="input"
					:value.sync="state.llm_model_version"
					:label="t('integration_replicate', 'Text generation model version (only used if model name is empty)')"
					:disabled="loading"
					:show-trailing-button="!!state.llm_model_version"
					@update:value="onInput"
					@trailing-button-click="state.llm_model_version = '' ; onInput()" />
				<NcButton type="tertiary"
					:title="t('integration_replicate', 'For example: \'83b6a56e7c828e667f21fd596c338fd4f0039b46bcfa18d973e8e70e455fda70\'')">
					<template #icon>
						<HelpCircleIcon />
					</template>
				</NcButton>
			</div>
			<div class="line">
				<label for="models">
					{{ t('integration_replicate', 'Whisper model size') }}
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
					input-id="models"
					@option:selected="onModelChanged" />
			</div>
		</div>
	</div>
</template>

<script>
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'
import HelpCircleIcon from 'vue-material-design-icons/HelpCircle.vue'

import ReplicateIcon from './icons/ReplicateIcon.vue'

import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'

const models = {
	tiny: { label: t('integration_replicate', 'Tiny'), value: 'tiny' },
	base: { label: t('integration_replicate', 'Base'), value: 'base' },
	small: { label: t('integration_replicate', 'Small'), value: 'small' },
	medium: { label: t('integration_replicate', 'Medium'), value: 'medium' },
	large: { label: t('integration_replicate', 'Large'), value: 'large' },
}

export default {
	name: 'AdminSettings',

	components: {
		ReplicateIcon,
		InformationOutlineIcon,
		HelpCircleIcon,
		NcSelect,
		NcButton,
		NcTextField,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_replicate', 'admin-config'),
			// to prevent some browsers to fill fields with remembered passwords
			readonly: true,
			modelOptions: Object.values(models),
			model: models[loadState('integration_replicate', 'admin-config').model] ?? models.large,
		}
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		onInput() {
			delay(() => {
				this.saveOptions({
					api_key: this.state.api_key,
					llm_model_name: this.state.llm_model_name,
					llm_model_version: this.state.llm_model_version,
				})
			}, 2000)()
		},
		saveOptions(values) {
			const req = {
				values,
			}
			const url = generateUrl('/apps/integration_replicate/admin-config')
			axios.put(url, req)
				.then((response) => {
					showSuccess(t('integration_replicate', 'Replicate admin options saved'))
				})
				.catch((error) => {
					showError(
						t('integration_replicate', 'Failed to save Replicate admin options')
						+ ': ' + error.response?.request?.responseText,
					)
				})
				.then(() => {
				})
		},
		onModelChanged(newValue) {
			this.state.model = newValue.value
			this.saveOptions({
				model: this.state.model,
			})
		},
	},
}
</script>

<style scoped lang="scss">
#replicate_prefs {
	#replicate-content {
		margin-left: 40px;
	}
	h2,
	.line,
	.settings-hint {
		display: flex;
		align-items: center;
		margin-top: 12px;
		.icon {
			margin-right: 4px;
		}
	}

	h2 .icon {
		margin-right: 8px;
	}

	.line {
		> .input {
			width: 500px;
		}
	}
}
</style>
