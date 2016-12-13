<?php

	namespace LiftKit\Uploader;

	use LiftKit\Uploader\Exception\Upload as UploadException;
	use LiftKit\Uploader\Exception\Upload;


	class Uploader
	{
		const FILE_SIZE_MB   = 1048576;
		const FILE_SIZE_KB   = 1024;
		const FILE_SIZE_BYTE = 1;

		const ERROR_OTHER    = -1;
		const ERROR_OK       = 0;
		const ERROR_UPLOAD   = 1;
		const ERROR_SIZE     = 2;
		const ERROR_PATH     = 3;
		const ERROR_WRITE    = 4;
		const ERROR_EXISTS   = 5;
		const ERROR_TYPE     = 6;
		const ERROR_NO_FILE  = 7;
		const ERROR_INVALID  = 8;

		protected $upload;
		protected $types;
		protected $max;
		protected $last;
		protected $multi;
		protected $allowEmpty;
		protected $overwrite;


		public function __construct ($file = false)
		{
			$this->clear();
			$this->assign($file);
		}


		public function clear ()
		{
			$this->upload      = false;
			$this->types       = false;
			$this->max         = false;
			$this->multi       = false;
			$this->allowEmpty  = false;
			$this->overwrite   = false;
			$this->last        = false;

			return $this;
		}


		public function assign ($file)
		{
			if (!$file) {
				return false;
			}

			if (is_string($file)) {
				$this->upload = $_FILES[$file];
			} else if (is_array($file)) {
				$this->upload = $file;
			}

			if (is_array($this->upload['error'])) {
				$this->multi = true;
			} else {
				$this->multi = false;
			}

			return $this;
		}


		public function accept ($type)
		{
			if (!is_array($this->types)) {
				$this->types = array();
			}

			foreach (func_get_args() as $type) {
				if (is_array($type)) {
					array_merge($type, $this->types);
				} else {
					array_push($this->types, $type);
				}
			}

			return $this;
		}


		public function allowEmpty ($bool = true)
		{
			$this->allowEmpty = $bool;

			return $this;
		}


		public function execute ($path, $keepName = true)
		{
			if ($this->multi) {
				foreach ($this->upload['error'] as $key => $error) {
					$members  = array(
						'tmp_name',
						'name',
						'type',
						'size',
						'error'
					);
					$thisFile = array();

					foreach ($members as $member) {
						$thisFile[$member] = $this->upload[$member][$key];
					}

					$this->doUpload($thisFile, $path, $keepName, $key);
				}
			} else {
				$this->doUpload($this->upload, $path, $keepName);
			}

			return $this;
		}


		protected function doUpload ($file, $path, $name, $key = null)
		{
			if (!isset($file['error'])) {
				throw new UploadException(
					'An invalid file was supplied.',
					null
				);

			} else if ($this->isMaxSizeError($file)) {
				throw new UploadException(
					'The file '.$file['name'].' was large the the max size '.$this->max.' bytes',
					self::ERROR_SIZE,
					$file
				);

			} else if ($this->isUploadError($file)) {
				throw new UploadException(
					'An upload error occurred for the file '.$file['name'].'.',
					self::ERROR_UPLOAD,
					$file
				);

			} else if ($this->isTypeError($file)) {
				throw new UploadException(
					'The file '.$file['name'].' is not an allowed file type.',
					self::ERROR_TYPE,
					$file
				);

			} else {
				if ($this->isNoFileError($file)) {
					throw new UploadException(
						'No file was supplied.',
						self::ERROR_NO_FILE,
						$file
					);

				} else if ($file['error'] == UPLOAD_ERR_NO_FILE) {
					return;

				} else {
					if (is_dir($path)) {
						if (!$name) {
							$pathinfo = pathinfo($file['name']);
							$name     = uniqid().'.'.$pathinfo['extension'];
						} else if ($name === true) {
							$name = $file['name'];
						}

						if (! $this->overwrite) {
							$name = $this->getUniqueFileName($path, $name);
						}

						$fullPath = rtrim($path, '/') . '/' . $name;

						if ($this->moveFile($file['tmp_name'], $fullPath)) {
							if ($this->multi) {
								$this->last[$key] = $name;
							} else {
								$this->last = $name;
							}

						} else {
							throw new UploadException(
								'The file '.$name.' could not be written to the disk.',
								self::ERROR_WRITE,
								$file
							);
						}
					} else {
						throw new UploadException(
							'No such directory '.$path.'.',
							self::ERROR_PATH,
							$file
						);
					}
				}
			}
		}


		public function lastFileName ()
		{
			return $this->last;
		}


		public function maxSize ($value, $units = self::FILE_SIZE_MB)
		{
			$this->max = $units * $value;

			return $this;
		}


		public function overwriteExisting ($overwrite = true)
		{
			$this->overwrite = $overwrite;

			return $this;
		}

		protected function getUniqueFileName ($path, $name, $suffix = null) {
			if( $suffix === null ){
				if( file_exists($path.'/'.$name) ){
					return $this->getUniqueFileName($path, $name, 1);
				} else {
					return $name;
				}
			} else {
				$pos = strrpos($name, ".");
				$testName = substr($name, 0, $pos).'('.$suffix.')'.substr($name, $pos);

				if( file_exists($path.'/'.$testName) ){
					return $this->getUniqueFileName($path, $name, $suffix+1);
				} else {
					return $testName;
				}
			}
		}


		/**
		 * @param $file
		 *
		 * @return bool
		 */
		protected function isMaxSizeError ($file)
		{
			return $this->max
				&& (
					in_array(
						$file['error'], array(
							UPLOAD_ERR_INI_SIZE,
							UPLOAD_ERR_FORM_SIZE
						)
					)
					|| $file['size'] > $this->max
				);
		}


		/**
		 * @param $file
		 *
		 * @return bool
		 */
		protected function isUploadError ($file)
		{
			return $file['error'] != 0
				&& $file['error'] != UPLOAD_ERR_NO_FILE;
		}


		/**
		 * @param $file
		 *
		 * @return bool
		 */
		protected function isTypeError ($file)
		{
			return is_array($this->types)
				&& !in_array(
					$file['type'], $this->types
				)
				&& $file['error'] != UPLOAD_ERR_NO_FILE;
		}


		/**
		 * @param $file
		 *
		 * @return bool
		 */
		protected function isNoFileError ($file)
		{
			return $file['error'] == UPLOAD_ERR_NO_FILE && $this->allowEmpty == false;
		}


		protected function moveFile ($src, $dest)
		{
			if ($this->overwrite && is_file($dest)) {
				$status = @unlink($dest);

				if (! $status) {
					throw new Upload('Could not overwrite ' . $dest);
				}
			}

			return move_uploaded_file($src, $dest);
		}
	}