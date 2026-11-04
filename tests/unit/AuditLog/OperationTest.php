<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Replicate\Tests\unit\AuditLog;

use OCA\Replicate\AuditLog\Operation;
use Test\TestCase;

class OperationTest extends TestCase {
	public function testOperationsAreWellFormedAndDescribed(): void {
		foreach (Operation::cases() as $operation) {
			$this->assertMatchesRegularExpression(
				'/^integration_replicate\.[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$/',
				$operation->value,
				$operation->name,
			);
			$this->assertNotSame('', trim($operation->description()), $operation->name);
		}
	}
}
