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
 
 
//! Image handler
class Image
{
    //! The filename of the photo
    private $filename = null;

    //! The path in file system
    private $filepath = null;
    
    //! Image data
    private $image_data = null;
    
    //! Image handler
    private $image = null;

    //! Image information
    private $meta = null;
    
    //! Image options
    private $options = array();
    
    //! Construct an image object
    /**
     * @param $input The image file path or raw data of the image. If you are
     *  providing raw data set option 'input' => @b 'data'.
     * @param $options An associative array of options to be passed at image.
     *  - @b 'input': Set the type of input. Acceptable values are:
     *      -@b 'file': The input is a path to a filename.
     *      -@b 'data': The input is a string of image data.
     *      .
     *  .        
     */
    public function __construct($input, $options = array())
    {
        // Append default options
        $this->options = array_merge(array(
            'input' => 'file',
            'matte_color' => null,
        ), $options);
        
        if ($this->options['input'] === 'file')
        {
            $this->filepath = $input;
            $this->filename = basename($this->filepath);
        }
        else
            $this->image_data = $input;
    }
    
    //! Clean up destructor
    public function __destruct()
    {
        if ($this->image !== null)
            imagedestroy($this->image);
    }

    //! Load meta data from file
    private function analyze_image()
    {
        if ($this->meta !== null)
            return; // Already analyzed
            
        // If it is raw data we must open file to get info
        if ($this->options['input'] === 'data')
        {
            $this->meta['type'] = 'string';
            $this->open_image();
            return;
        }
            
        // File info
        $image_info = getimagesize($this->filepath);
        if ($image_info[2] === IMAGETYPE_JPEG)
            $this->meta['type'] = 'jpeg';
        else if ($image_info[2] === IMAGETYPE_GIF)
            $this->meta['type'] = 'gif';
        else if ($image_info[2] === IMAGETYPE_PNG)
            $this->meta['type'] = 'png';
        else
            throw new RuntimeException('Unknown image type');

        $this->meta['width'] = $image_info[0];
        $this->meta['height'] = $image_info[1];
    }
    
    //! Dynamic image loading
	private function open_image()
    {
        if ($this->image !== null)
            return;
            
        // Analyze image
        $this->analyze_image();

        if ($this->meta['type'] === 'jpeg')
            $this->image = imagecreatefromjpeg($this->filepath);
        else if ($this->meta['type'] === 'png')
            $this->image = imagecreatefrompng($this->filepath);
        else if ($this->meta['type'] === 'gif')
            $this->image = imagecreatefromgif($this->filepath);
        else if ($this->meta['type'] === 'string')
            $this->update_image(imagecreatefromstring($this->image_data));
    }
    
    //! Update image with a new instance
    private function update_image($handler)
    {
        // Free previous image
        if ($this->image)
            imagedestroy($this->image);
        // Update to new one
        $this->image = $handler;
        $this->meta['width'] = imagesx($this->image);
        $this->meta['height'] = imagesy($this->image);
    }
    
    //! Create a new image based on this one
    private function create_new_image($width, $height)
    {
        $img = imagecreatetruecolor($width, $height);
        imagealphablending($img, false);
        if ($this->meta['type'] == 'gif')
        {
            $trans_color = imagecolorsforindex($this->image, imagecolortransparent($this->image));
            $trans_index = imagecolorallocatealpha(
                $img,
                $trans_color['red'],
                $trans_color['green'], 
                $trans_color['blue'],
                $trans_color['alpha']
            );
            imagecolortransparent($img, $trans_index);
        }
        return $img;
    }
    
    //! Get gd resource handle
    public function gd_handle()
    {
        $this->open_image();
        return $this->image;
    }
    
    //! Get image meta information
    /**
     * @param $field 
     *  - @b null If you want to get an array with all meta information.
     *  - @b string The name of the meta field you want to get the value.
     *  .
     */
    public function get_meta_info($field = null)
    {
        $this->analyze_image();
        if ($field === null)
            return $this->meta;

        return $this->meta[$field];
    }

    //! Resize image with constant aspect ratio
    /**
     * @param $width The desired width of thumbnail or 0 if you want to follow aspect
     *  ratio of original image.
     * @param $height The desired height of thumbnail or 0 if you want to follow aspect
     *  ratio of original image.
     * @return The same instance ($this) of Image.
     */
    public function resize($width, $height)
    {
        $this->open_image();
        
        $orig_img = $this->image;
        $orig_cwidth = $orig_width = $this->meta['width'];
        $orig_cheight = $orig_height = $this->meta['height'];
        $orig_ratio = $orig_width / $orig_height;

        // Calculate other side of dynamic thumbs
        $thumb_width = (($width == 0)?$height * $orig_ratio:$width);
        $thumb_height = (($height == 0)?$width / $orig_ratio:$height);
            
        $thumb_img = $this->create_new_image($thumb_width, $thumb_height);
        $thumb_ratio = $thumb_width / $thumb_height;

        // Calculate crop area if ratio is different
        $orig_x = $orig_y = 0;
        if ($thumb_ratio > $orig_ratio)
        {
            $orig_cheight = $orig_cwidth /  $thumb_ratio;
            $orig_y = ($orig_height - $orig_cheight) /2;
        }
        else
        {
            $orig_cwidth = $orig_cheight *  $thumb_ratio;
            $orig_x = ($orig_width - $orig_cwidth) /2;
        }

        // Calculate original crop area for retaining aspect ratio
        imagecopyresampled(
            $thumb_img,     //  Dst image
            $orig_img,      //  Source image
            0,              //  Dst_x
            0,              //  Dst_y
            $orig_x,        //  Src_x,
            $orig_y,        //  Src_y,
            $thumb_width,   //  Dst_width
            $thumb_height,  //  Dst_height
            $orig_cwidth,   //  Src_width
            $orig_cheight   //  Src_height
        );
        
        // Save information
        $this->update_image($thumb_img);
        return $this;
    }
    
    //! Flip image horizontally
    /**
     * @param $direction The direction to flip image
     *  - @b hor: Flip image horizontally.
     *  - @b ver: Flip image vertically.
     *  - @b both: Flip image in both directions.
     *  .
     * @return The same instance ($this) of Image.
     */
    public function flip($direction)
    {
        $this->open_image();

        switch($direction)
        {
        case 'hor':
            $src_x = $this->meta['width'] - 1;
            $src_y = 0;
            $src_width = - $this->meta['width']; 
            $src_height = $this->meta['height'];
            break;
        case 'ver':
            $src_x = 0;
            $src_y = $this->meta['height'] - 1;
            $src_width = $this->meta['width']; 
            $src_height = -$this->meta['height'];
            break;
        case 'both':
            $src_x = $this->meta['width'] - 1;
            $src_y = $this->meta['height'] - 1;
            $src_width = - $this->meta['width']; 
            $src_height = -$this->meta['height'];
            break;
        default:
            throw new InvalidArgumentException("Invalid Image::flip() direction \"{$direction}\"!");
        }
        
        $flipped = $this->create_new_image($this->meta['width'], $this->meta['height']);
        
        imagecopyresampled(
            $flipped,
            $this->image,
            0,
            0,
            $src_x,
            $src_y,
            $this->meta['width'],
            $this->meta['height'],
            $src_width,
            $src_height
        );
        
        $this->update_image($flipped);
        return $this;
    }
    
    //! Generate image output
    private function generate_output($options = array(), $dump_headers, $to_file = null)
    {
        $this->open_image();
        
        // Normalize options
        $options = array_merge(array(
            'quality' => null,
            'format' => null
        ), $options);
        if ($options['quality'] !== null)
        {
            $options['quality'] = ($options['quality']>100?100:$options['quality']);
            $options['quality'] = ($options['quality']<0?0:$options['quality']);
        }
        
        // Decide output image type
        $imagetype = $options['format'];
        if (!in_array($imagetype, array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, null), true))
            throw new InvalidArgumentException("Invalid image type \"${imagetype}\"!");

        if ($imagetype == null)
        {
            if ($this->meta['type'] === 'string')
                $imagetype = IMAGETYPE_PNG;
            else if ($this->meta['type'] === 'png')
                $imagetype = IMAGETYPE_PNG;
            else if ($this->meta['type'] === 'jpeg')
                $imagetype = IMAGETYPE_JPEG;
            else if ($this->meta['type'] === 'gif')
                $imagetype = IMAGETYPE_GIF;
        }

        // Dump content type headers
        if ($dump_headers)
            header('Content-Type: ' . image_type_to_mime_type($imagetype));

        // Dump headers
        if ($imagetype === IMAGETYPE_JPEG)
        {
            $quality = ($options['quality'] === null?75:$options['quality']);
            imagejpeg($this->image, $to_file, $quality);
        }
        else if ($imagetype === IMAGETYPE_PNG)
        {
            $quality = ($options['quality'] === null?100:$options['quality']);
            $quality = round((100 - $quality) * 0.09);
            imagesavealpha($this->image, true);
            imagepng($this->image, $to_file);
        }
        else if ($imagetype === IMAGETYPE_GIF)
        {
            imagesavealpha($this->image, true);
            imagegif($this->image, $to_file);
        }
    }
    
    //! Dump this image to output
    /**
     * @param $options An associative array with options:
     *
     *  - @b format [Default = null]: The format of image to create.
     *      - @b null: Use same image type as the original
     *      - @b IMAGETYPE_JPEG: JPEG compression
     *      - @b IMAGETYPE_PNG: PNG compression
     *      - @b IMAGETYPE_GIF': GIF compression
     *      .
     *  - @b quality [Default = null] The quality of compression from @b 0 (full comp) to @b 100 (no comp).
     */
    public function dump($options = array(), $dump_headers = true)
    {
        return $this->generate_output($options, $dump_headers);
    }
    
    //! Save this image to a file
    /**
     * @return true on success.
     */
    public function save($filename, $options = array())
    {
        return $this->generate_output($options, false, $filename);
    }
}

?>
