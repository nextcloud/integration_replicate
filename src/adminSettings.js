/**
 * Nextcloud - Replicate
 *
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
 * @copyright Julien Veyssier 2022
 */

import { createApp } from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'

import AdminSettings from './components/AdminSettings.vue'
const view = createApp(AdminSettings)

view.mixin({
	t: translate,
	n: translatePlural,
	OC: window.OC,
	OCA: window.OCA,
})
view.mount('#replicate_prefs')
