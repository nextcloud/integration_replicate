<template>
	<div v-tooltip.top="{ content: image.title }"
		class="result"
		@keydown.enter="$emit('click')"
		@click="$emit('click')">
		<div v-if="!isLoaded" class="loading-icon">
			<NcLoadingIcon
				:size="44"
				:title="t('integration_replicate', 'Loading image')" />
		</div>
		<img v-show="isLoaded"
			class="image"
			:src="image.url"
			@load="isLoaded = true">
	</div>
</template>

<script>
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

export default {
	name: 'PickerResult',

	components: {
		NcLoadingIcon,
	},

	props: {
		image: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			isLoaded: false,
		}
	},

	watch: {
	},

	mounted() {
	},

	methods: {
	},
}
</script>

<style scoped lang="scss">
.result {
	display: flex;
	flex-direction: column;
	align-items: center;

	> * {
		cursor: pointer;
	}

	.loading-icon {
		display: flex;
		align-items: center;
		width: 100%;
		height: 100%;
	}

	.image {
		height: 100%;
		width: 100%;
		object-fit: cover;
		border-radius: var(--border-radius);
	}
}
</style>
