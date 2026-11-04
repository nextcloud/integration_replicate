<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Replicate\Tests\unit\Service;

use OCA\Replicate\AppInfo\Application;
use OCA\Replicate\AuditLog\Operation;
use OCA\Replicate\Service\ConfigService;
use OCA\Replicate\Tests\unit\AuditLog\AuditLogHelper;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ConfigServiceTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private IEventDispatcher&MockObject $eventDispatcher;
	private ConfigService $service;
	/** @var list<CriticalActionPerformedEvent> */
	private array $events = [];

	protected function setUp(): void {
		parent::setUp();
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->events = [];
		$this->eventDispatcher->method('dispatchTyped')
			->willReturnCallback(function (object $event): void {
				$this->assertInstanceOf(CriticalActionPerformedEvent::class, $event);
				$this->events[] = $event;
			});
		$this->service = new ConfigService($this->appConfig, $this->eventDispatcher);
	}

	public static function dataKeysWithLoggedValue(): array {
		return [
			['model', 'large'],
			['llm_model_name', 'mistralai/mistral-7b-instruct-v0.1'],
			['llm_model_version', 'abc123'],
			['igen_model_name', 'stability-ai/stable-diffusion'],
			['igen_model_version', 'def456'],
		];
	}

	#[DataProvider('dataKeysWithLoggedValue')]
	public function testSetAdminValueLogsKeyAndValue(string $key, string $value): void {
		$this->appConfig->expects($this->once())
			->method('setValueString')
			->with(Application::APP_ID, $key, $value, true, false)
			->willReturn(true);

		$this->service->setAdminValue($key, $value);

		$this->assertCount(1, $this->events);
		$event = $this->events[0];
		$this->assertSame('Replicate integration setting "%s" was changed to "%s"', $event->getLogMessage());
		$this->assertSame(['key' => $key, 'value' => $value], $event->getParameters());
		$this->assertTrue(AuditLogHelper::hasOperation($event, Operation::AdminConfigUpdated->value));
	}

	public static function dataKeysWithoutLoggedValue(): array {
		return [
			['llm_extra_params'],
			['igen_extra_params'],
			['some_other_key'],
		];
	}

	#[DataProvider('dataKeysWithoutLoggedValue')]
	public function testSetAdminValueLogsOnlyKey(string $key): void {
		$this->appConfig->expects($this->once())
			->method('setValueString')
			->with(Application::APP_ID, $key, '{"token":"secret"}', true, false)
			->willReturn(true);

		$this->service->setAdminValue($key, '{"token":"secret"}');

		$this->assertCount(1, $this->events);
		$event = $this->events[0];
		$this->assertSame('Replicate integration setting "%s" was changed', $event->getLogMessage());
		$this->assertSame(['key' => $key], $event->getParameters());
		$this->assertTrue(AuditLogHelper::hasOperation($event, Operation::AdminConfigUpdated->value));
	}

	public function testSetAdminValueUnchangedDoesNotLog(): void {
		$this->appConfig->method('setValueString')->willReturn(false);

		$this->service->setAdminValue('model', 'large');

		$this->assertSame([], $this->events);
	}

	public function testSetAdminValueFailureDoesNotLog(): void {
		$this->appConfig->method('setValueString')->willThrowException(new \RuntimeException('db failure'));

		try {
			$this->service->setAdminValue('model', 'large');
			$this->fail('Exception expected');
		} catch (\RuntimeException) {
		}

		$this->assertSame([], $this->events);
	}
}
