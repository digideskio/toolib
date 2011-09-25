<?php

namespace toolib\Http;

/**
 * @brief Base class for interfacing uploaded files.
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
	 * @brief Creating an uploaded file object
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
	 *  @brief The actual name of the file as it was uploaded
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * @brief Get the name of the temporary file
	 */
	public function getTempName()
	{
		return $this->tmp_name;
	}
	
	/**
	 * @brief Get the file size.
	 */
	public function getSize()
	{
		return $this->size;
	}
	
	/**
	 * @brief Get the error code of this upload.
	 */
	public function getError()
	{
		return $this->error;
	}
	
	/**
	 * @brief Check if this file is submitted or false
	 */
	public function isSubmitted()
	{
		return $this->getError() != UPLOAD_ERR_NO_FILE;
	}
	
	/**
	* @brief Check if this file has been uploaded properly.
	*/
	public function isValid()
	{
		return $this->getError() == UPLOAD_ERR_OK;
	}
	
	/**
	 * @brief Move this file to a safe area.
	 * @param string $dest_dir A destination folder to move file
	 */
	public function move($dest_dir)
	{
		return move_uploaded_file($this->getTempName(), $dest_dir);
	} 
	
	/**
	 * @brief Delete temporary file from filesystem
	 */
	public function delete()
	{
		if (is_file($this->getTempName()))
			unlink($this->getTempName());
	}
	
	/**
	 * @brief To string gets filename
	 */
	public function __toString()
	{
		return $this->name;
	}
};