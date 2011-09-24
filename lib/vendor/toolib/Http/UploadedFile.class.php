<?php

namespace \toolib\Http;

/**
 * @brief Manager for uploaded files.
 */
class UploadedFile
{
	/**
	 * @brief The actual name of the file as it was uploaded
	 * @var string
	 */
	private $name;
	
	/**
	 * @brief The mime type of the upload
	 * @var string
	 */
	private $mime;
	
	/**
	 * @brief  The temporary name of the file
	 * @var string
	 */
	private $tmp_name;
	
	/**
	 * @brief The size of the file
	 * @var integer
	 */
	private $size;
	
	/**
	 * @brief Error code for this upload
	 * @var integer
	 */
	private $error;
	
	/**
	 * @brief Construct a new wrapper for $_FILES
	 * @param string $name The actual name of file.
	 * @param string $type The mime type of file.
	 * @param string $tmp_name The temporary filename of file.
	 * @param integer $size The size of file.
	 * @param integer $error The error code of file.
	 */
	public function __construct($name, $mime, $tmp_name, $size, $error)
	{
		$this->name = $name;
		$this->mime = $mime;
		$this->tmp_name = $tmp_name;
		$this->size = $size;
		$this->error = $error;
	}
	
	/**
	 *  The actual name of the file as it was uploaded
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * Get the name of the temporary file
	 */
	public function getTempName()
	{
		return $this->tmp_name;
	}
	
	/**
	 * Get the file size.
	 */
	public function getSize()
	{
		return $this->size;
	}
	
	/**
	 * Get the error code of this upload.
	 */
	public function getError()
	{
		return $this->error;
	}
	
	/**
	 * Move this file to a safe area.
	 * @param string $dest A destination folder to move file
	 */
	public function move($dest)
	{
		return move_uploaded_file($this->getTempName(), $dest);
	} 
	
	/**
	 * Delete temporary file from filesystem
	 */
	public function delete()
	{
		if (is_file($this->getTempName()))
			unlink($this->getTempName());
	}
	
	/**
	 * To string gets filename
	 */
	public function __toString()
	{
		return $this->name;
	}
};