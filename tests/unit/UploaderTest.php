<?php


	namespace LiftKit\Tests\Unit\Uploader;

	use LiftKit\Tests\Stub\Uploader\Uploader;
	use PHPUnit_Framework_TestCase;


	class UploadTest extends PHPUnit_Framework_TestCase
	{
		/**
		 * @var Uploader
		 */
		protected $uploader;

		protected $tmpPath;
		protected $destPath;

		protected $file1;
		protected $file2;
		protected $file3;


		public function setUp ()
		{
			$this->tmpPath = dirname(__DIR__) . '/tmp/';
			$this->destPath = dirname(__DIR__) . '/dest/';

			$this->file1 = $this->tmpPath . '~file1.txt';
			$this->file2 = $this->tmpPath . '~file2.txt';
			$this->file3 = $this->tmpPath . '~file3.txt';

			touch($this->file1);
			touch($this->file2);
			touch($this->file3);
		}


		/**
		 * @param $files
		 * @param $expectedFiles
		 *
		 * @dataProvider filesArrayProvider
		 */
		public function testUpload ($files, $expectedFiles)
		{
			$this->uploader = new Uploader($files);

			$this->uploader->execute($this->destPath);

			foreach ($expectedFiles as $expectedFile) {
				if (!is_file($expectedFile)) {
					die;
				}
				$this->assertTrue(is_file($expectedFile));
			}
		}


		public function tearDown ()
		{
			foreach (scandir($this->tmpPath) as $file) {
				if (in_array($file, ['..', '.', '.gitkeep'])) {
					continue;
				}

				unlink($this->tmpPath . $file);
			}

			foreach (scandir($this->destPath) as $file) {
				if (in_array($file, ['..', '.', '.gitkeep'])) {
					continue;
				}

				unlink($this->destPath . $file);
			}
		}


		public function filesArrayProvider ()
		{
			$this->setUp();

			return [
				[
					[
						'error' => 0,
						'tmp_name' => $this->file1,
						'name' => 'file1.txt',
						'size' => filesize($this->file1),
						'type' => 'text/plain',
					],
					[
						$this->destPath . 'file1.txt',
					]
				],
				[
					[
						'error' => [
							0,
							0,
							0,
						],
						'tmp_name' => [
							$this->file1,
							$this->file2,
							$this->file3,
						],
						'name' => [
							'file1.txt',
							'file2.txt',
							'file3.txt',
						],
						'size' => [
							filesize($this->file1),
							filesize($this->file2),
							filesize($this->file3),
						],
						'type' => [
							'text/plain',
							'text/plain',
							'text/plain',
						],
					],
					[
						$this->destPath . 'file1.txt',
						$this->destPath . 'file2.txt',
						$this->destPath . 'file3.txt',
					]
				],
			];
		}
	}