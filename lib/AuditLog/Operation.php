<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Replicate\AuditLog;

/**
 * Operations the Replicate integration writes to the admin audit log, in the form `app.entity.action`.
 * The values are matched by log consumers (filters, alert rules, SIEM imports), so changing one is a breaking change for them.
 * Case names may be renamed.
 */
enum Operation: string {
	case AdminConfigUpdated = 'integration_replicate.admin_config.updated';

	public function description(): string {
		return match ($this) {
			self::AdminConfigUpdated => 'An admin changed the value of a non-secret setting of the app.',
		};
	}
}
