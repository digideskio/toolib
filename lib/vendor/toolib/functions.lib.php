<?php
/*
 *  This file is part of PHPLibs <http://phplibs.kmfa.net/>.
 *
 *  Copyright (c) 2010 < squarious at gmail dot com > .
 *
 *  PHPLibs is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  PHPLibs is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with PHPLibs.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace toolib;

/**
 * Sample a part of the text and return the result with three dots at the end (if needed)
 * @param string $text The text to sample it.
 * @param number $length The length of number
 */
function text_sample($text, $length)
{
	$text_length = mb_strlen($text, 'UTF-8');
	if ($text_length < $length)
		return $text;

	return mb_substr($text, 0, $length - 3, 'UTF-8') . '...';
}

/**
 * Search the matched array of a preg_match and remove duplicated named-unamed entries.
 * 
 * The entries that are unamed are left intact, those that are named the numerical entry
 * is removed.
 */
function preg_matches_remove_unamed($matches)
{
	$fmatches = $matches; // Filtered array
	$idx_count = 0;
	foreach($matches as $idx => $match) {
		if ($idx !== $idx_count) {
			unset($fmatches[$idx_count]);
			continue;
		}
		$idx_count++;
	}
	return $fmatches;
}

function gzdecode($data) {
	$temp_fname = tempnam(sys_get_temp_dir(), 'ff');
	@file_put_contents($temp_fname, $data);
	ob_start();
	readgzfile($temp_fname);
	$data = ob_get_clean();
	unlink($temp_fname);
	return $data;
}

/**
 * Get the maximum upload file size.
 */
function get_upload_maxsize()
{
	$val = trim(ini_get('upload_max_filesize'));
	$last = strtolower($val[strlen($val)-1]);
	switch($last) {
		// The 'G' modifier is available since PHP 5.1.0
		case 'g':
			$val *= 1024;
		case 'm':
			$val *= 1024;
		case 'k':
			$val *= 1024;
	}

	return $val;
}
