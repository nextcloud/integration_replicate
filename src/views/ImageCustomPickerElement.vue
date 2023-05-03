<template>
	<div class="replicate-picker-content">
		<h2>
			{{ t('integration_replicate', 'AI image generation') }}
		</h2>
		<a class="attribution"
			target="_blank"
			href="https://replicate.com">
			{{ poweredByTitle }}
		</a>
		<div class="input-wrapper">
			<NcTextField
				ref="replicate-search-input"
				:value.sync="query"
				:label="inputPlaceholder"
				:show-trailing-button="!!query"
				@keydown.enter="onInputEnter"
				@trailing-button-click="query = ''" />
		</div>
		<NcButton
			class="submit-button"
			type="primary"
			:disabled="loading || !query"
			@click="onInputEnter">
			{{ t('integration_replicate', 'Submit') }}
			<template #icon>
				<NcLoadingIcon v-if="loading" />
				<ArrowRightIcon v-else />
			</template>
		</NcButton>
	</div>
</template>

<script>
import ArrowRightIcon from 'vue-material-design-icons/ArrowRight.vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'

import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'
import Vue from 'vue'
Vue.directive('tooltip', Tooltip)

export default {
	name: 'ImageCustomPickerElement',

	components: {
		NcButton,
		NcTextField,
		NcLoadingIcon,
		ArrowRightIcon,
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
			query: '',
			loading: false,
			inputPlaceholder: t('integration_replicate', 'cyberpunk nextcloud logo with chrismas hats'),
			poweredByTitle: t('integration_replicate', 'by Replicate with stable diffusion'),
		}
	},

	computed: {
	},

	watch: {
	},

	mounted() {
		this.focusOnInput()
	},

	methods: {
		focusOnInput() {
			setTimeout(() => {
				this.$refs['replicate-search-input'].$el.getElementsByTagName('input')[0]?.focus()
			}, 300)
		},
		onSubmit(url) {
			this.$emit('submit', url)
		},
		onInputEnter() {
			if (this.query === '') {
				return
			}
			this.loading = true
			const params = {
				prompt: this.query,
			}
			const url = generateUrl('/apps/integration_replicate/predictions/image')
			return axios.post(url, params)
				.then((response) => {
					console.debug('predictions response', response.data)
					if (!['failed', 'canceled'].includes(response.data.status)) {
						const internalUrl = window.location.protocol + '//' + window.location.host
							+ generateUrl('/apps/integration_replicate/i/{id}', { id: response.data.id })
						this.onSubmit(internalUrl)
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
	padding: 12px 16px 16px 16px;

	h2 {
		display: flex;
		align-items: center;
	}

	.attribution {
		padding-bottom: 8px;
	}

	.input-wrapper {
		display: flex;
		align-items: center;
		width: 100%;
		input {
			flex-grow: 1;
		}
	}

	.submit-button {
		align-self: end;
		margin-top: 8px;
	}
}
</style>
