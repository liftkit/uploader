<?php

	namespace LiftKit\Tests\Stub\Uploader;

	use LiftKit\Uploader\Uploader as BaseUploader;


	class Uploader extends BaseUploader
	{


		protected function moveFile ($src, $dest)
		{
			return rename($src, $dest);
		}
	}