<?php

namespace OCA\Replicate\Tests;

use OCA\Replicate\AppInfo\Application;

class ReplicateAPIServiceTest extends \PHPUnit\Framework\TestCase {

	public function testDummy() {
		$app = new Application();
		$this->assertEquals('integration_replicate', $app::APP_ID);
	}
}
