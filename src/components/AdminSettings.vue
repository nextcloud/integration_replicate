<template>
	<div id="replicate_prefs" class="section">
		<h2>
			<ReplicateIcon class="icon" />
			{{ t('integration_replicate', 'Replicate integration') }}
		</h2>
		<div id="replicate-content">
			<div class="line">
				<label for="replicate-api-key">
					<KeyIcon :size="20" class="icon" />
					{{ t('integration_replicate', 'Replicate API token') }}
				</label>
				<input id="replicate-api-key"
					v-model="state.api_key"
					type="password"
					:readonly="readonly"
					:placeholder="t('integration_replicate', 'your API token')"
					@input="onInput"
					@focus="readonly = false">
			</div>
			<p class="settings-hint">
				<InformationOutlineIcon :size="20" class="icon" />
				<a href="https://replicate.com" target="_blank">
					{{ t('integration_replicate', 'You can create a free API token on https://replicate.com') }}
				</a>
			</p>
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
					input-id="models"
					@option:selected="onModelChanged" />
			</div>
		</div>
	</div>
</template>

<script>
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'
import KeyIcon from 'vue-material-design-icons/Key.vue'
import HelpCircleIcon from 'vue-material-design-icons/HelpCircle.vue'

import ReplicateIcon from './icons/ReplicateIcon.vue'

import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'

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
		KeyIcon,
		InformationOutlineIcon,
		HelpCircleIcon,
		NcSelect,
		NcButton,
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
						+ ': ' + error.response?.request?.responseText
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
		> label {
			width: 300px;
			display: flex;
			align-items: center;
		}
		> input {
			width: 300px;
		}
	}
}
</style>
