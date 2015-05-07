<?php

	namespace LiftKit\Uploader\Exception;

	use Exception;


	class Upload extends Exception
	{
		protected $_file;

		public function __construct ($message, $code = null, $_file = null)
		{
			parent::__construct($message, $code);
			$this->_file = $_file;
		}

		public function getUploadFile ()
		{
			return $this->_file;
		}
	}