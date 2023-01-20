<template>
	<div class="replicate-picker-content">
		<h2>
			{{ t('integration_replicate', 'Transcribe or translate text') }}
			<a class="attribution"
				target="_blank"
				href="https://replicate.com">
				{{ poweredByTitle }}
			</a>
		</h2>
		<div class="input-wrapper">
			<NcLoadingIcon v-if="loading"
				:size="20"
				:title="t('integration_replicate', 'Loading')" />
			<NcButton v-else @click="onInputEnter">
				{{ t('integration_replicate', 'Submit') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'
import Vue from 'vue'
Vue.directive('tooltip', Tooltip)

export default {
	name: 'WhisperCustomPickerElement',

	components: {
		NcButton,
		NcLoadingIcon,
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
		}
	},

	computed: {
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		onCancel() {
			this.$emit('cancel')
		},
		onSubmit(url) {
			this.$emit('submit', url)
		},
		onInputEnter() {
			this.loading = true
			const params = {
				translate: true,
				model: 'large',
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
		.attribution {
			margin-left: 16px;
		}
	}

	.input-wrapper {
		display: flex;
		align-items: center;
		width: 100%;
		input {
			flex-grow: 1;
		}
	}
}
</style>
