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
				:disabled="loading"
				:show-trailing-button="!!query"
				@keydown.enter="generate"
				@trailing-button-click="query = ''" />
		</div>
		<div v-if="reference === null || query === ''"
			class="prompts">
			<NcUserBubble v-for="p in prompts"
				:key="p.id"
				class="prompt-bubble"
				:title="p.value"
				:size="30"
				avatar-image="icon-history"
				:display-name="p.value"
				@click="query = p.value" />
		</div>
		<ImageReferenceWidget v-if="reference !== null"
			:rich-object="reference.richObject"
			orientation="horizontal" />
		<div class="footer">
			<NcButton class="advanced-button"
				type="tertiary"
				:aria-label="t('integration_replicate', 'Show/hide advanced options')"
				@click="showAdvanced = !showAdvanced">
				<template #icon>
					<component :is="showAdvancedIcon" />
				</template>
				{{ t('integration_replicate', 'Advanced options') }}
			</NcButton>
			<NcButton
				type="secondary"
				:aria-label="t('integration_replicate', 'Preview images with Replicate')"
				:disabled="loading || !query"
				@click="generate">
				{{ previewButtonLabel }}
				<template #icon>
					<NcLoadingIcon v-if="loading" />
					<RefreshIcon v-else-if="resultUrl !== null" />
					<EyeIcon v-else />
				</template>
			</NcButton>
			<NcButton v-if="resultUrl !== null"
				type="primary"
				:aria-label="t('integration_replicate', 'Send the current preview')"
				:disabled="loading"
				@click="submit">
				{{ t('integration_replicate', 'Send') }}
				<template #icon>
					<ArrowRightIcon />
				</template>
			</NcButton>
		</div>
		<div v-show="showAdvanced" class="advanced">
			<div class="line">
				<label for="number">
					{{ t('integration_replicate', 'Number of images to generate (1-4)') }}
				</label>
				<div class="spacer" />
				<input
					id="number"
					v-model="imageNumber"
					type="number"
					min="1"
					max="4"
					step="1">
			</div>
			<div class="line">
				<label for="size">
					{{ t('integration_replicate', 'Size of the generated images') }}
				</label>
				<div class="spacer" />
				<select
					id="size"
					v-model="imageSize">
					<option value="512x512">
						512x512 px
					</option>
					<option value="768x768">
						768x768 px
					</option>
				</select>
			</div>
		</div>
	</div>
</template>

<script>
import EyeIcon from 'vue-material-design-icons/Eye.vue'
import RefreshIcon from 'vue-material-design-icons/Refresh.vue'
import ArrowRightIcon from 'vue-material-design-icons/ArrowRight.vue'
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue'
import ChevronDownIcon from 'vue-material-design-icons/ChevronDown.vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcUserBubble from '@nextcloud/vue/dist/Components/NcUserBubble.js'

import ImageReferenceWidget from './ImageReferenceWidget.vue'

import axios from '@nextcloud/axios'
import { generateUrl, generateOcsUrl } from '@nextcloud/router'
import { showError } from '@nextcloud/dialogs'

import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'
import Vue from 'vue'
Vue.directive('tooltip', Tooltip)

export default {
	name: 'ImageCustomPickerElement',

	components: {
		ImageReferenceWidget,
		NcButton,
		NcTextField,
		NcLoadingIcon,
		ArrowRightIcon,
		EyeIcon,
		RefreshIcon,
		NcUserBubble,
		ChevronRightIcon,
		ChevronDownIcon,
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
			resultUrl: null,
			reference: null,
			loading: false,
			inputPlaceholder: t('integration_replicate', 'cyberpunk nextcloud logo with chrismas hats'),
			poweredByTitle: t('integration_replicate', 'by Replicate with stable diffusion'),
			prompts: null,
			showAdvanced: false,
			imageNumber: 1,
			imageSize: '768x768',
		}
	},

	computed: {
		showAdvancedIcon() {
			return this.showAdvanced
				? ChevronDownIcon
				: ChevronRightIcon
		},
		previewButtonLabel() {
			return this.resultUrl !== null
				? t('integration_replicate', 'Regenerate')
				: t('integration_replicate', 'Preview')
		},
	},

	watch: {
	},

	mounted() {
		this.focusOnInput()
		this.getPromptHistory()
	},

	methods: {
		focusOnInput() {
			setTimeout(() => {
				this.$refs['replicate-search-input'].$el.getElementsByTagName('input')[0]?.focus()
			}, 300)
		},
		getPromptHistory() {
			const params = {
				params: {
					type: 0,
				},
			}
			const url = generateUrl('/apps/integration_replicate/prompts')
			return axios.get(url, params)
				.then((response) => {
					this.prompts = response.data
				})
				.catch((error) => {
					console.error(error)
				})
		},
		submit() {
			this.$emit('submit', this.resultUrl)
		},
		generate() {
			if (this.query === '') {
				return
			}
			this.loading = true
			this.reference = null
			const params = {
				prompt: this.query,
				num_outputs: parseInt(this.imageNumber),
				size: this.imageSize,
			}
			const url = generateUrl('/apps/integration_replicate/predictions/image')
			return axios.post(url, params)
				.then((response) => {
					console.debug('predictions response', response.data)
					if (!['failed', 'canceled'].includes(response.data.status)) {
						this.resultUrl = window.location.protocol + '//' + window.location.host
							+ generateUrl('/apps/integration_replicate/i/{id}', { id: response.data.id })
						this.resolveResult()
					} else {
						this.error = response.data.error
					}
				})
				.catch((error) => {
					console.debug('replicate request error', error)
					showError(
						t('integration_replicate', 'Failed to launch image generation')
						+ ': ' + (error.response?.data?.body?.detail ?? error.response?.data?.error)
					)
				})
				.then(() => {
					this.loading = false
				})
		},
		resolveResult() {
			this.loading = true
			this.abortController = new AbortController()
			axios.get(generateOcsUrl('references/resolve', 2) + '?reference=' + encodeURIComponent(this.resultUrl), {
				signal: this.abortController.signal,
			})
				.then((response) => {
					this.reference = response.data.ocs.data.references[this.resultUrl]
				})
				.catch((error) => {
					console.error(error)
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

	.spacer {
		flex-grow: 1;
	}

	.prompts {
		margin-top: 8px;
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		> * {
			margin-right: 8px;
		}
	}

	.prompt-bubble {
		max-width: 250px;
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

	.footer {
		width: 100%;
		display: flex;
		align-items: center;
		justify-content: end;
		margin-top: 12px;
		> * {
			margin-left: 4px;
		}
	}

	.advanced {
		width: 100%;
		padding: 12px 0;
		.line {
			display: flex;
			align-items: center;
			margin-top: 8px;

			input,
			select {
				width: 200px;
			}
		}

		input[type=number] {
			width: 80px;
			appearance: initial !important;
			-moz-appearance: initial !important;
			-webkit-appearance: initial !important;
		}
	}
}
</style>
