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
			<h3>
				{{ t('integration_replicate', 'Text generation') }}
			</h3>
			<div class="line">
				<NcTextField
					class="input"
					:value.sync="state.llm_model_name"
					:label="t('integration_replicate', 'Text generation model name')"
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
				<NcTextField
					class="input"
					:value.sync="state.llm_extra_params"
					:label="t('integration_replicate', 'Extra model parameters')"
					:show-trailing-button="!!state.llm_extra_params"
					@update:value="onInput"
					@trailing-button-click="state.llm_extra_params = '' ; onInput()" />
				<NcButton type="tertiary"
					:title="llmExtraParamHint">
					<template #icon>
						<HelpCircleIcon />
					</template>
				</NcButton>
			</div>
			<h3>
				{{ t('integration_replicate', 'Image generation') }}
			</h3>
			<div class="line">
				<NcTextField
					class="input"
					:value.sync="state.igen_model_name"
					:label="t('integration_replicate', 'Image generation model name (only used if model version is empty)')"
					:show-trailing-button="!!state.igen_model_name"
					@update:value="onInput"
					@trailing-button-click="state.igen_model_name = '' ; onInput()" />
				<NcButton type="tertiary"
					:title="t('integration_replicate', 'For example: \'stability-ai/stable-diffusion\'')">
					<template #icon>
						<HelpCircleIcon />
					</template>
				</NcButton>
			</div>
			<div class="line">
				<NcTextField
					class="input"
					:value.sync="state.igen_model_version"
					:label="t('integration_replicate', 'Image generation model version')"
					:show-trailing-button="!!state.igen_model_version"
					@update:value="onInput"
					@trailing-button-click="state.igen_model_version = '' ; onInput()" />
				<NcButton type="tertiary"
					:title="t('integration_replicate', 'For example: \'ac732df83cea7fff18b8472768c88ad041fa750ff7682a21affe81863cbe77e4\'')">
					<template #icon>
						<HelpCircleIcon />
					</template>
				</NcButton>
			</div>
			<div class="line">
				<NcTextField
					class="input"
					:value.sync="state.igen_extra_params"
					:label="t('integration_replicate', 'Extra model parameters')"
					:show-trailing-button="!!state.igen_extra_params"
					@update:value="onInput"
					@trailing-button-click="state.igen_extra_params = '' ; onInput()" />
				<NcButton type="tertiary"
					:title="igenExtraParamHint">
					<template #icon>
						<HelpCircleIcon />
					</template>
				</NcButton>
			</div>
			<h3>
				{{ t('integration_replicate', 'Speech-to-text') }}
			</h3>
			<div class="line">
				<label for="models">
					{{ t('integration_replicate', 'Whisper model size') }}
				</label>
				<NcSelect
					v-model="model"
					:options="modelOptions"
					:aria-label-combobox="t('integration_replicate', 'Whisper model size')"
					input-id="models"
					@option:selected="onModelChanged" />
				<NcButton type="tertiary"
					:title="t('integration_replicate', 'Larger model size gives better results but uses more credit')">
					<template #icon>
						<HelpCircleIcon />
					</template>
				</NcButton>
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
			modelOptions: Object.values(models),
			model: models[loadState('integration_replicate', 'admin-config').model] ?? models.large,
			igenExtraParamHint: t('integration_replicate', 'Extra parameters are model-specific. For example: {"width":1920,"height":1080}'),
			llmExtraParamHint: t('integration_replicate', 'Extra parameters are model-specific. For example: {"max_new_tokens":128,"temperature":0.7}'),
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
					llm_extra_params: this.state.llm_extra_params,
					igen_model_name: this.state.igen_model_name,
					igen_model_version: this.state.igen_model_version,
					igen_extra_params: this.state.igen_extra_params,
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

	h3 {
		font-weight: bold;
		margin-top: 24px;
	}

	.line {
		gap: 8px;
		> .input {
			width: 500px;
		}
	}
}
</style>
