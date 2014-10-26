<?php
/*
 * Copyright (c) 2014, Sgt. Kabukiman, https://bitbucket.org/sgt-kabukiman/
 *
 * This file is released under the terms of the MIT license. You can find the
 * complete text in the attached LICENSE file or online at:
 *
 * http://www.opensource.org/licenses/mit-license.php
 */

namespace horaro\WebApp\Validator;

use horaro\Library\Entity\Schedule;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ScheduleImportValidator extends BaseValidator {
	public function validate(Request $request, Schedule $ref) {
		$this->result = ['_errors' => false];

		$upload = $request->files->get('file');

		// do not compare with "if (!$upload)", because of PHP bug #65213
		if (!($upload instanceof UploadedFile)) {
			return $this->addError('file', 'No file was uploaded.');
		}

		if (!$upload->isValid()) {
			return $this->addError('file', $this->getUploadErrorMessage($upload));
		}

		$type = $this->getImportType($upload);

		if ($type === null) {
			return $this->addError('file', 'Could not recognize the file format.');
		}

		$this->setFilteredValue('type',   $type);
		$this->setFilteredValue('upload', $upload);

		return $this->result;
	}

	/**
	 * Better than Symfony's version. No babbling about "upload_max_filesize ini directive" and stuff.
	 */
	private function getUploadErrorMessage(UploadedFile $upload) {
		$errors = [
			UPLOAD_ERR_INI_SIZE   => 'The file "%s" exceeds the upload limit.',
			UPLOAD_ERR_PARTIAL    => 'The file "%s" was only partially uploaded.',
			UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
			UPLOAD_ERR_CANT_WRITE => 'The file "%s" could not be written on disk.',
			UPLOAD_ERR_NO_TMP_DIR => 'File could not be uploaded: missing temporary directory.',
			UPLOAD_ERR_EXTENSION  => 'File upload was stopped by a PHP extension.'
		];

		$errorCode = $upload->getError();
		$message   = isset($errors[$errorCode]) ? $errors[$errorCode] : 'The file "%s" was not uploaded due to an unknown error.';
		$filename  = $upload->getClientOriginalName();

		if (mb_strlen($filename) > 50) {
			$filename = mb_substr($filename, 0, 50).'…';
		}

		return sprintf($message, $filename);
	}

	private function getImportType(UploadedFile $file) {
		$mime = $file->getMimeType();

		// hooray if the mime magic on this system was actually this specific
		if (preg_match('#^(text/json|application/json)#', $mime)) return 'json';
		if (preg_match('#^text/csv#',                     $mime)) return 'csv';

		// we at least need something that resembles text
		if (!preg_match('#^text/#', $mime)) {
			return null;
		}

		// go by the file extension
		$ext = $file->getClientOriginalExtension();

		return in_array($ext, ['csv', 'json'], true) ? $ext : null;
	}
}
