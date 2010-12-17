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

use toolib\Image;
require_once dirname(__FILE__) .  '/../path.inc.php';

class ImageTest extends PHPUnit_Framework_TestCase
{

    static public function compare_image_files($file1, $file2, $maxerror = null)
    {   $equal = false;
        if ($maxerror === null)
            $maxerror = 0.04;
            
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );
        $proc = proc_open("compare -alpha on -metric RMSE $file1 $file2 /dev/null", 
            $descriptorspec,
            $pipes,
            dirname(__FILE__) . '/samples'
        );
        
        $reply = stream_get_contents($pipes[2]);
        foreach($pipes as $pipe)
            fclose($pipe);
            
        $proc_status = proc_get_status($proc);
        proc_close($proc);

        if ((!$proc_status['running']) && ($proc_status['exitcode'] === 0))
        {
            if (preg_match_all('/\((?P<factor>[\d\.]+)\)/', $reply, $matches))
            {
                $factor = (float)$matches['factor'][0];
                if ($factor < $maxerror)
                    $equal = true;   
            }
        }

        return $equal;
    }
    
    static public function compare_imgobj_file(Image $img, $file, $maxerror = null)
    {
        $tmpfile = tempnam(sys_get_temp_dir(), 'phplibs-imagetest-');
        $img->save($tmpfile, array('quality' => 100));
        $response = self::compare_image_files($tmpfile, $file, $maxerror);
        unlink($tmpfile);
        return $response;
    }
    
    static public function image_create($input, $options = array())
    {
        return new Image($input, $options);
    }
    
    public function dataCompareFunctionSamples()
    {
        return array(
            array('comp/orig_1.png', 'comp/diff_1.png', false),
            array('comp/orig_1.png', 'comp/diff_2.png', false),
            array('comp/orig_1.png', 'comp/diff_3.png', false),
            array('comp/orig_1.png', 'comp/diff_4.png', false),
            array('comp/orig_1.png', 'comp/diff_5.png', false),
            array('comp/orig_1.png', 'comp/same_1.png', true),
        );
    }
    
    /**
     * @dataProvider dataCompareFunctionSamples
     */
    public function testCompareFunction($file1, $file2, $equal)
    {
        $this->assertSame($equal, self::compare_image_files($file1, $file2));
        $this->assertSame($equal, self::compare_imgobj_file(new Image(dirname(__FILE__) . '/samples/' . $file1), $file2));
        $this->assertSame($equal, self::compare_imgobj_file(new Image(
            file_get_contents(dirname(__FILE__) . '/samples/' . $file1), array('input' => 'data'))
            , $file2
        ));
    }
    
    public function testWriteFunctions()
    {
        $img = new Image(dirname(__FILE__) . '/samples/orig_500x250.png');
        $tmpfile = tempnam(sys_get_temp_dir(), 'phplibs-imagetest-');
        $img->save($tmpfile);
        
        $this->assertEquals(file_get_contents($tmpfile), $img->data());
        unlink($tmpfile);
        
        ob_start();
        $img->dump(array(), false);
        $output = ob_get_contents();
        ob_end_clean();
        
        $this->assertEquals($output, $img->data());
    }
    
    public function originalFiles()
    {
        $files = array();
        
        foreach(array('orig_250x250.png', 'orig_500x250.png', 'orig_500x250.gif', 'orig_250x250.jpg') as $file)
        {
           $files[] = array($file, dirname(__FILE__) . '/samples/'. $file, array());
           $files[] = array($file, file_get_contents(dirname(__FILE__) . '/samples/' . $file), array('input' => 'data'));
        }
        return $files;
    }
    
    public function dataFlipFunctions()
    {
        $data = array();
        foreach($this->originalFiles() as $image)
        {
            $file = $image[0];
            $input = $image[1];
            $options = $image[2];
            
            $data[] = array(
                self::image_create($input, $options)->flip('hor'),
                dirname(__FILE__) . '/samples/flip/hor_' . $file,
                true
            );
            
            $data[] = array(
                self::image_create($input, $options)->flip('ver'),
                dirname(__FILE__) . '/samples/flip/ver_' . $file,
                true
            );
            
            $data[] = array(
                self::image_create($input, $options)->flip('both'),
                dirname(__FILE__) . '/samples/flip/both_' . $file,
                true
            );
        }
        return $data;
    }
    
    /**
     * @dataProvider dataFlipFunctions
     */
    public function testFlipFunctions($img, $file, $equal)
    {   
        $this->assertSame($equal, self::compare_imgobj_file($img, $file));
    }
    
    //! Provide rotate functions
    public function dataRotateFunctions()
    {
        $data = array();
        foreach($this->originalFiles() as $image)
        {
            $file = $image[0];
            $input = $image[1];
            $options = $image[2];
            
            foreach(array('90', '180', '270') as $rotate)
                $data[] = array(
                    self::image_create($input, $options)->rotate($rotate),
                    dirname(__FILE__) . "/samples/rotate/{$rotate}_$file",
                    true
                );
        }
        return $data;
    }
    
    /**
     * @dataProvider dataRotateFunctions
     */
    public function testRotateFunctions($img, $file, $equal)
    {   
        $this->assertSame($equal, self::compare_imgobj_file($img, $file));
    }
    
    public function dataCropValidBoundries()
    {
        return array(
            array(0, 0, 500, 250),
            array(1, 1, 499, 249),
            array(499, 249, 1, 1),
            array(50, 50, 450, 200),
        );
    }
    
    /**
     * @dataProvider dataCropValidBoundries
     */
    public function testCropValidBoundries($left, $top, $width, $height)
    {
        $img = new Image(dirname(__FILE__) . '/samples/orig_500x250.gif');
        $img->crop($left, $top, $width, $height);
        $this->assertEquals($img->getMetaInfo('height'), $height);
        $this->assertEquals($img->getMetaInfo('width'), $width);
    }
    
    
    public function dataCropInvalidBoundries()
    {
        return array(
            array(0, 0, 501, 250),
            array(0, 0, 500, 251),
            array(-1, 0, 500, 250),
            array(0, -1, 500, 250),
            array(50, 50, 451, 200),
            array(50, 50, 450, 201),
            array(50, 50, 0, 0),
            array(500, 250, 0, 0),
        );
    }
    
    /**
     * @dataProvider dataCropInvalidBoundries
     * @expectedException InvalidArgumentException
     */
    public function testCropInvalidBoundries($left, $top, $width, $height)
    {
        $img = new Image(dirname(__FILE__) . '/samples/orig_500x250.gif');
        $img->crop($left, $top, $width, $height);
        $this->assertEquals($img->getMetaInfo('height'), $height);
        $this->assertEquals($img->getMetaInfo('width'), $width);
    }

    public function dataCropFunctions()
    {
        $data = array();
        foreach($this->originalFiles() as $image)
        {
            $file = $image[0];
            $input = $image[1];
            $options = $image[2];
            
            if (strstr($file, '500'))
                $data[] = array(
                    self::image_create($input, $options)->crop(235, 50, 115, 145),
                    dirname(__FILE__) . '/samples/crop/235_50_115_145_' . $file,
                    true
                );
            else
                $data[] = array(
                    self::image_create($input, $options)->crop(20, 30, 115, 145),
                    dirname(__FILE__) . '/samples/crop/20_30_115_145_' . $file,
                    true
                );
        }
        return $data;
    }
    
    /**
     * @dataProvider dataCropFunctions
     */
    public function testCropFunctions($img, $file, $equal)
    {   
        $this->assertSame($equal, self::compare_imgobj_file($img, $file));
    }
    
    public function dataResizeFunctions()
    {
        $data = array();
        foreach($this->originalFiles() as $image)
        {
            $file = $image[0];
            $input = $image[1];
            $options = $image[2];
            

            $data[] = array(
                self::image_create($input, $options)->resize(100, 100, false),
                dirname(__FILE__) . '/samples/resize/100x100_N_' . $file,
                true
            );
            
            $data[] = array(
                self::image_create($input, $options)->resize(130, 100, false),
                dirname(__FILE__) . '/samples/resize/130x100_N_' . $file,
                true
            );

            $data[] = array(
                self::image_create($input, $options)->resize(100, 130, false),
                dirname(__FILE__) . '/samples/resize/100x130_N_' . $file,
                true
            );
            
            $data[] = array(
                self::image_create($input, $options)->resize(100, 100, true),
                dirname(__FILE__) . '/samples/resize/100x100_C_' . $file,
                true
            );
            
            $data[] = array(
                self::image_create($input, $options)->resize(130, 100, true),
                dirname(__FILE__) . '/samples/resize/130x100_C_' . $file,
                true
            );
            
            $data[] = array(
                self::image_create($input, $options)->resize(100, 130, true),
                dirname(__FILE__) . '/samples/resize/100x130_C_' . $file,
                true
            );
        }
        return $data;
    }
    
    /**
     * @dataProvider dataResizeFunctions
     */
    public function testResizeFunctions($img, $file, $equal)
    {   
        $this->assertSame($equal, self::compare_imgobj_file($img, $file, 0.15));
    }
    
    
    public function dataInvalidResize()
    {
        return array(
            array(0, 0, false),
            array(0, 0, true),
            array(5, 0, true),
            array(0, 5, true),            
        );
        
    }
    
    /**
     * @dataProvider dataInvalidResize
     * @expectedException InvalidArgumentException
     */
    public function testInvalidResizeArguments($w, $h, $crop)
    {
        $img = new Image(dirname(__FILE__) . '/samples/orig_500x250.gif');
        $img->resize($w, $h, $crop);
    }
}
?>
