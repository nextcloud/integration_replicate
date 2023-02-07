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
		</div>
	</div>
</template>

<script>
import InformationOutlineIcon from 'vue-material-design-icons/InformationOutline.vue'
import KeyIcon from 'vue-material-design-icons/Key.vue'

import ReplicateIcon from './icons/ReplicateIcon.vue'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'AdminSettings',

	components: {
		ReplicateIcon,
		KeyIcon,
		InformationOutlineIcon,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_replicate', 'admin-config'),
			// to prevent some browsers to fill fields with remembered passwords
			readonly: true,
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

	.settings-hint {
		margin-top: 12px;
	}
}
</style>
