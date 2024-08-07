<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: RIKIPB <dkron@outlook.it>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\ImagePopupPreview\Tests\Unit\Controller;

use OCA\ImagePopupPreview\Controller\NoteApiController;

class NoteApiControllerTest extends NoteControllerTest {
	public function setUp(): void {
		parent::setUp();
		$this->controller = new NoteApiController($this->request, $this->service, $this->userId);
	}
}
