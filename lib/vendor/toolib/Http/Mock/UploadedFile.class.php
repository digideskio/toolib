<?php


namespace toolib\Http\Mock;
use toolib\Http;

/**
* @brief Specialization of UploadedFile for Mock package.
*/
class UploadedFile extends Http\UploadedFile
{
	public function move($dest_dir)
	{
		if (!is_dir($dest_dir))
			return false;
		if (!is_writable($dest_dir))
			return false;

		if (!is_file($this->getTempName()))
			return false;
		
		return rename($this->getTempName(), $dest_dir);
	}
} 