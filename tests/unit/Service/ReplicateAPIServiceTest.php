<?php

namespace OCA\Replicate\Tests;

use OCA\Replicate\AppInfo\Application;
use Test\TestCase;

class ReplicateAPIServiceTest extends TestCase {

	public function testDummy() {
		$app = new Application();
		$this->assertEquals('integration_replicate', $app::APP_ID);
	}
}
