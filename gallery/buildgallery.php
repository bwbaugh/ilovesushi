<?php
 /**
  * Airtight Interactive BuildGallery package.
  *
  * SimpleViewer is the free, customizable Flash image viewing application from {@link http://www.airtightinteractive.com/simpleviewer/  Airtight Interactive}
  * Use BuildGallery to create the xml file for SimpleViewer
  *
  * @package buildgallery
  * @author Jack Hardie {@link http://www.jhardie.com}
  * @version 2.0.0 build 090429
  * @copyright Copyright (c) 2007 - 2009, Airtight Interactive
  */
  header('Content-Type: text/html; charset=utf-8');
  error_reporting(E_ALL);
  if( version_compare(phpversion(), '5.0', '>=' ) && version_compare(phpversion(), '6.0', '<=' )) @ini_set('zend.ze1_compatibility_mode', '0');
  $svOptions = array();
  $bgOptions = array();
  
  // Set default options here
  $svOptions['maxImageWidth'] = '900';
  $svOptions['maxImageHeight'] = '900';
  $svOptions['textColor'] = '0xffffff';
  $svOptions['frameColor'] = '0xffffff';
  $svOptions['frameWidth'] = '20';
  $svOptions['stagePadding'] = '40';
  $svOptions['navPadding'] = '40';
  $svOptions['thumbnailColumns'] = '3';
  $svOptions['thumbnailRows'] = '4';
  $svOptions['navPosition'] = 'left';
  $svOptions['title'] = 'Collete Presley Photography';
  $svOptions['enableRightClickOpen'] = true;
  $svOptions['backgroundImagePath'] = '';
  $svOptions['imagePath'] = '';
  $svOptions['thumbPath'] = '';
  $svOptions['hAlign'] = 'center';
  $svOptions['vAlign'] = 'center';
  $bgOptions['xmlPath'] = 'gallery.xml';
  $bgOptions['addLinks'] = true;
  $bgOptions['addCaptions'] = true;
  $bgOptions['underlineLinks'] = true;
  $bgOptions['sortOrder'] = 'rdate';
  $bgOptions['overwriteThumbnails'] = true;
  
  // Constants
  define('MEMORY_LIMIT', 0);
  define('MEMORY_LIMIT_FALLBACK', '8M');
  define('MEMORY_SAFETY_FACTOR', 1.9);
  define('THUMB_DIR_MODE', 0775);
  define('THUMB_SIZE', 65);
  define('THUMB_QUALITY', 85);
  define('XML_PATH', 'gallery.xml');
  define('SV_XML_SETTINGS_TAG', 'simpleviewergallery');
  define('DEFAULT_THUMB_PATH', 'thumbs/');
  define('DEFAULT_IMAGE_PATH', 'images/');
  
  new BuildGallery($svOptions, $bgOptions);
  
 /**
  * Main buildgallery class
  *
  */
  Class BuildGallery
  {
   /**
    * @var array SimpleViewer options for xml file.
    */
    var $svOptions = array();
   
   /**
    * @var array BuildGallery options
    */
    var $bgOptions = array();
      
   /**
    * @var array file names, captions (without links)
    * format is $imageData[] = array('fileName'=>'file1', 'caption'=>'caption1');
    */
    var $imageData = array();

    
   /**
    * Constructs BuildGallery
    * 
    */
    function BuildGallery($svOptions, $bgOptions)
    {
      $errorHandler = &new ErrorHandler();
      $this->svOptions = $svOptions;
      $this->bgOptions = $bgOptions;
      $this->page = &new Page();
      $this->xml = &new Xml();
      print $this->page->getHtmlHead();
      print $this->page->getPageHeader();
      ob_start();
      $_POST = $this->rStripSlashes($_POST);
      $_GET = $this->rStripSlashes($_GET);
      $xmlStruct = $this->xml->parseXml(XML_PATH);
      if ($xmlStruct !== false)
      {
        $this->imageData = $this->xml->parseImageTags($xmlStruct, $this->bgOptions);
        $att = $this->xml->parseAttributes($xmlStruct);
        if ($att !== false && !isset($_GET['defaults']))
        {
          $this->svOptions = array_merge($this->svOptions, $att);
        }
      }
      if (isset($_POST['customizesubmitted']))
      {
        $this->update();  
      }
      print $this->page->getPageContent($this->svOptions, $this->bgOptions);
      $mainOutput = ob_get_clean();
      print $errorHandler->getMessages();
      print $mainOutput;
      print $this->page->getFooter();
    }
    
   /**
    * Update the properties of BuildGallery from user input
    *
    * @access private
    * @return void
    */
    function update()
    {
      $this->customize($_POST);
      $relImagePath = $this->getPathRelGallery($this->svOptions['imagePath'], DEFAULT_IMAGE_PATH);
      $relThumbPath = $this->getPathRelGallery($this->svOptions['thumbPath'], DEFAULT_THUMB_PATH);
      $scannedImageData = $this->scanImageData($relImagePath, $this->bgOptions['sortOrder']);
      if ($scannedImageData === false) return;
      if ($this->bgOptions['addCaptions'])
      {
        $scannedImageData = $this->addCaptions($scannedImageData);
      }
      $newImageData = $this->extractNewImageData($this->imageData, $scannedImageData);
      $this->imageData = array_merge($this->imageData, $newImageData);
      $this->imageData = $this->removeDeletedImages($relImagePath, $relThumbPath, $this->imageData);
      $this->imageData = $this->sortImages($this->imageData, $this->bgOptions['sortOrder']);
      $thumbNails = &new ThumbNails();
      $thumbCount = $thumbNails->makeThumbs($relThumbPath, $relImagePath, $this->imageData, $this->bgOptions['overwriteThumbnails']);
      trigger_error('Created '.$thumbCount.' thumbnails.', E_USER_NOTICE);
      $this->xml->writeXml($this->svOptions, $this->bgOptions, $relImagePath, $this->imageData);
    }
    

   /**
    * Clean form data and update class properties
    *
    * @return array
    * @param array as in preferences file
    */
    function customize($newSettings)
    {
      $newSettings = array_map('trim', $newSettings);
      $this->svOptions['title'] = strip_tags($newSettings['title'], '<a><b><i><u><font><br><br />');
      $this->svOptions['frameWidth'] = max(0, $newSettings['frameWidth']);
      $this->svOptions['stagePadding'] = max(0, $newSettings['stagePadding']);
      $this->svOptions['navPadding'] = max(0, $newSettings['navPadding']);
      $this->svOptions['thumbnailColumns'] = max(0, $newSettings['thumbnailColumns']);
      $this->svOptions['thumbnailRows'] = max(0, $newSettings['thumbnailRows']);
      $this->svOptions['navPosition'] = $newSettings['navPosition'];
      $this->svOptions['maxImageWidth'] = max(0, $newSettings['maxImageWidth']);
      $this->svOptions['maxImageHeight'] = max(0, $newSettings['maxImageHeight']);
      $this->svOptions['enableRightClickOpen'] = (isset($newSettings['enableRightClickOpen'])) ? 'true' : 'false';
      $this->svOptions['textColor'] = $this->cleanHex($newSettings['textColor'], 6);
      $this->svOptions['frameColor'] = $this->cleanHex($newSettings['frameColor'], 6);
      $this->svOptions['backgroundImagePath'] = $newSettings['backgroundImagePath'];
      $validAlignments = array('center', 'top', 'bottom', 'left', 'right');
      $alignments = explode("_", $newSettings['alignment']);
      $this->svOptions['vAlign'] = (in_array(strtolower($alignments[0]), $validAlignments)) ? strtolower($alignments[0]) : 'center';
      $this->svOptions['hAlign'] = (in_array(strtolower($alignments[1]), $validAlignments)) ? strtolower($alignments[1]) : 'center';
      $imagePath = rtrim($newSettings['imagePath'], '\\/');
      $this->svOptions['imagePath'] = ($imagePath == '') ? '' : $imagePath.'/';  
      $thumbPath = rtrim($newSettings['thumbPath'], '\\/');
      $this->svOptions['thumbPath'] = ($thumbPath == '') ? '' : $thumbPath.'/';
      $this->bgOptions['addLinks'] = isset($newSettings['addLinks']);
      $this->bgOptions['underlineLinks'] = isset($newSettings['underlineLinks']);
      $this->bgOptions['addCaptions'] = isset($newSettings['addCaptions']);
      $this->bgOptions['overwriteThumbnails'] = isset($newSettings['overwriteThumbnails']);
      $this->bgOptions['sortOrder'] = $newSettings['sortOrder'];
      return true;
    }
    
   /**
    * return a properly formatted hex color string
    *
    * @access private
    * @return string
    * @param string containing hex color
    * @param integer required length of hex number in characters
    */
    function cleanHex($hex, $length = 6)
    {
      $hex = strtolower($hex);
      $hex = str_replace('#', '', $hex);
      $hex = str_replace('0x', '', $hex); 
      return '0x'.str_pad(dechex(hexdec(  substr(trim($hex), 0, $length)  )), $length, '0', STR_PAD_LEFT);
    }
  
   /**
    * recursive function to strip slashes
    * see www.php.net/stripslashes strip_slashes_deep function
    *
    * @access public
    * @return array
    * @parameter array
    */
    function rStripSlashes($value)
    {
      if (!get_magic_quotes_gpc()) return $value;
      $value = is_array($value) ? array_map(array($this, 'rStripSlashes'), $value) : stripslashes($value);
      return $value;
    }
     
   /**
    * returns array of image file paths and names
    *
    * @access private
    * @return array
    * @param string sort order ['alpha' | 'ralpha' | 'date' | 'rdate']
    */
    function scanImageData($relImagePath, $sortOrder='rdate')
    {
      $imageData = array();
      if (@!is_dir($relImagePath))
      {
        trigger_error('the image directory <span class="filename">'.$relImagePath.'</span> cannot be found', E_USER_ERROR);
        return false;
      }
      $handle = @opendir($relImagePath);
      if ($handle === false)
      {
        trigger_error('cannot open the <span class="filename">'.$relImagePath.'</span> directory &ndash; check the file permissions', E_USER_ERROR);
        return false;
      }
      while(false !== ($fileName = readdir($handle)))
      {
      	if (!$this->isImage($fileName)) {continue;}
      	$imageData[] = array('fileName'=>$fileName, 'caption'=>'');
      }
      closedir($handle);   
      $imageData = $this->sortImages($imageData, $sortOrder);
      return $imageData;
    }
    
   /**
    * Extract new image data from scan containing all current image data
    *
    * @access private
    * @return array new image data
    * @param array old image data
    * @param array scanned image data
    */
    function extractNewImageData($oldImageData, $scannedImageData)
    {
      if (count($oldImageData) == 0) return $scannedImageData;
      $newImageData = array();
      foreach ($scannedImageData as $key=>$imageArray)
      {
        if($this->fileInImageData($imageArray['fileName'], $oldImageData) === false)
        {
          $newImageData[] = $imageArray;
        }
      }
      return $newImageData;
    }
       
  /** Test if fileName already present in the imageData array
    *
    * @access private
    * @return integer array key
    * @param string needle
    * @param array haystack
    */
    function fileInImageData($fileName, $imageData)
    {
      foreach ($imageData as $key=>$imageArray)
      {
        if ($imageData[$key]['fileName'] === $fileName) return $key;
      }
      return false;
    }
    
   /**
    * Remove deleted images
    *
    * @access private
    * @return array image data
    * @param string relative path to image directory
    * @param array image data
    */
    function removeDeletedImages($relImagePath, $relThumbPath, $imageData)
    {
      if (count($imageData) == 0) return $imageData;
      $newImageData = array();
      foreach ($imageData as $key=>$image)
      {
        if (file_exists($relImagePath.$image['fileName']))
        {
          $newImageData[] = $image;
        }
        else if (file_exists($relThumbPath.$image['fileName']))
        {
          @unlink($relThumbPath.$image['fileName']);
        }
      }
      return $newImageData;
    }

   
   /**
    * test for jpg
    *
    * Note that preg_match_all returns a number and false for badly formed utf-8
    * version including swf is (0 == preg_match_all('(.*\.((jpe?g)|(swf))$)ui', $fileName, $matches))
    *
    * @return boolean true if filename ends in jpg or jpeg (case insensitive)
    * @parameter string file name
    * @access private
    */
    function isImage($fileName)
    {
      return (0 != preg_match_all('(.*\.((jpe?g)|(png)|(gif))$)ui', $fileName, $matches));
    }
    
    
   /**
    * Sort images
    *
    * @access private
    * @return array sorted image data;
    * @param array to sort
    * @param string sort order ['alpha' | 'ralpha' | 'date' | 'rdate']
    */
    function sortImages($imageData, $sortOrder = 'rdate')
    {
      $relImagePath = $this->getPathRelGallery($this->svOptions['imagePath'], DEFAULT_IMAGE_PATH);
      $fileName = array();
      $caption = array();
      $fileMTime = array();
      foreach ($imageData as $key => $row)
      {
        $fileName[$key]  = $row['fileName'];
        $caption[$key] = $row['caption'];
        $fileMTime[$key] = @filemtime($relImagePath.$fileName[$key]);
        if ($fileMTime[$key] === false)
        trigger_error('cannot read time last modified for <span class="filename">'.$fileName[$key].'</span>', E_USER_WARNING);
      }
      switch($sortOrder)
      {
        case 'alpha':
          array_multisort($fileName, SORT_ASC, $imageData);
        break;
        case 'ralpha':
          array_multisort($fileName, SORT_DESC, $imageData);
        break;
        case 'date':
          array_multisort($fileMTime, SORT_ASC, SORT_NUMERIC, $imageData);
        break;
        case 'rdate':
          array_multisort($fileMTime, SORT_DESC, SORT_NUMERIC, $imageData);
        break;
      }
      return $imageData;
     }
     
    /**
     * Adds captions
     *
     * @access private
     * @return array image data
     * @param array image data
     */
     function addCaptions($imageData)
     {
       foreach ($imageData as $key => $image)
       {
         $imageData[$key]['caption'] = preg_replace('(\.((jpe?g)|(png)|(gif))$)ui', '', $image['fileName']);  
       }
       return $imageData;
     }
     
     /**
   * Get path relative to gallery
   * Assumes target directory (or file) is inside gallery
   *
   * @access private
   * @return string directory (with trailing slash) or file, relative to gallery
   * @param string relative path, absolute path, http path or empty string
   * @param string default path
   */
   function getPathRelGallery($path, $default='')
   {
     $path = rtrim($path, '\\/');
     if ($path == '')
     {
       return $default;
     }
     return basename($path).'/';
   }
  }
  
 /**
  * Creates thumbnails
  *
  * @package BuildGallery
  */
  class ThumbNails
  {   
   /**
    * creates new thumbnails
    *
    * @access public
    * @param string path with trailing separator
    * @param string path with trailing separator
    * @param array file names and captions
    * @param boolean overwrite existing thumbnails (only relevant for rebuild)
    * @return integer number of thumbnails created or zero on error
    */
    function makeThumbs($thumbPath, $imagePath, $imageData, $overwrite = true)
    {
      $thumbCount = 0;
      $memoryLimit = (ini_get('memory_limit') == '') ? MEMORY_LIMIT_FALLBACK : ini_get('memory_limit');
      $maxImageBytes = (MEMORY_LIMIT == 0) ? $this->getBytes($memoryLimit) : MEMORY_LIMIT * pow(2,20);
      $thumbDir = rtrim($thumbPath, '\\/');
      if (!is_dir($thumbDir))
      {
        if(@!mkdir($thumbDir))
        {
          trigger_error('the thumbnail directory '.$thumbDir.' cannot be created, check parent folder permissions', E_USER_WARNING);
          return 0;
        }
        if (@!chmod($thumbDir, THUMB_DIR_MODE))
        {
          trigger_error('Unable to set permissions for '.$thumbDir, E_USER_NOTICE);
          return 0;
        }
      }
      $gdVersion = $this->getGDversion();
      if (version_compare($gdVersion, '2.0', '<'))
      {
        trigger_error('the GD imaging library was not found on this server or it is an old version that does not support jpeg images. Thumbnails will not be created. Either upgrade to a later version of GD or create the thumbnails yourself in a graphics application such as Photoshop.', E_USER_WARNING);
        return 0;
      }
      foreach ($imageData as $key=>$imageArray)
      {
        $fileName = $imageArray['fileName'];
        $image = $imagePath.$fileName;
        $thumb = $thumbPath.$fileName;
        if (@file_exists($thumb) && !$overwrite) {continue;}
        if (@!file_exists($image))
        {
          trigger_error('image '.$image.' cannot be found', E_USER_WARNING);
          continue;
        }
        $imageInfo = GetImageSize($image);
        // $imageInfo['channels'] is not set for png images so just guess at 3
        $channels = 3;
        $memoryNeeded = Round(($imageInfo[0] * $imageInfo[1] * $imageInfo['bits'] * $channels / 8 + Pow(2, 16)) * MEMORY_SAFETY_FACTOR);
        if ($memoryNeeded > $maxImageBytes)
        {
        	trigger_error('image '.$image.' is too large to create a thumbnail', E_USER_WARNING);
        	continue;
        }
        if ($this->createThumb($image, $thumb))
        {
          $thumbCount ++;
        }
        else
        {
          trigger_error('Thumbnail for '.$fileName.' could not be created', E_USER_WARNING);
        }
      }
      return $thumbCount;
    }
    
    /**
    * function createThumb creates and saves one thumbnail image.
    *
    * @access private
    * @return boolean success
    * @param string $filePath path to source image
    * @param string $thumbPath path to new thumbnail
    */
  
    function createThumb($filePath, $thumbPath)
    {
      $success = false;
      $thumbSize = THUMB_SIZE;
      $quality = THUMB_QUALITY;	
    	$dimensions = @getimagesize($filePath);
      if ($dimensions === false)
      {
        trigger_error('could not get image size for '.$filePath, E_USER_WARNING);
        return false;
      }
    	$width		= $dimensions[0];
    	$height		= $dimensions[1];
      $smallerSide = min($width, $height);
      // Calculate offset of square portion of image
      // offsets will both be zero if original image is square
      $deltaX = ($width - $smallerSide)/2;
      $deltaY = ($height - $smallerSide)/2;
      // get image identifier for source image
      switch($dimensions[2])
      {
        case IMAGETYPE_GIF :
          $imageSrc  = @imagecreatefromgif($filePath);
          break;
        case IMAGETYPE_JPEG :
          $imageSrc = @imagecreatefromjpeg($filePath);
          break;
        case IMAGETYPE_PNG :
          $imageSrc = @imagecreatefrompng($filePath);
          break;
        default :
          trigger_error('unidentified image type '.$filePath, E_USER_WARNING);
          return false;
      }
      if ($imageSrc === false)
      {
        trigger_error('could not get image identifier for '.$filePath, E_USER_WARNING);
        return false;
      }
      // Create an empty thumbnail image. 
      $imageDest = @imagecreatetruecolor($thumbSize, $thumbSize);
      if ($imageDest === false)
      {
        trigger_error('could not create true color image', E_USER_WARNING);
        return false;
      }
      if(!$success = @imagecopyresampled($imageDest, $imageSrc, 0, 0, $deltaX, $deltaY, $thumbSize, $thumbSize, $smallerSide, $smallerSide))
      {
        trigger_error('could not create thumbnail using imagecopyresampled', E_USER_WARNING);
        return false;
      }
      // save the thumbnail image into a file.
  		if (!$success = @imagejpeg($imageDest, $thumbPath, $quality))
      {
        trigger_error('could not save thumbnail', E_USER_WARNING);
      }
  		// Delete both image resources.
  		@imagedestroy($imageSrc);
  		@imagedestroy($imageDest);
      unset ($imageSrc, $imageDest);
    	return $success;
    }
    
   /**
    * Convert ini-style G, M, kbytes to bytes
    * Note that switch statement drops through without breaks
    *
    * @access private
    * @return integer bytes
    * @param string
    */
    function getBytes($val)
    {
      $val = trim($val);
      $last = strtolower($val{strlen($val)-1});
      switch($last)
      {
        case 'g':
          $val *= 1024;
        case 'm':
          $val *= 1024;
        case 'k':
          $val *= 1024;
      }
      return $val;
    }

  
   /**
    * Get which version of GD is installed, if any.
    *
    * @access private
    * @return string version vector or '0' if GD not installed
    */
    function getGdVersion()
    {
      if (! extension_loaded('gd')) { return '0'; }
      // Use the gd_info() function if possible.
      if (function_exists('gd_info'))
      {
        $versionInfo = gd_info();
        preg_match("/[\d\.]+/", $versionInfo['GD Version'], $matches);
        return $matches[0];
      }
      // If phpinfo() is disabled return false...
      if (preg_match('/phpinfo/', ini_get('disable_functions')))
      {
        return '0';
      }
      // ...otherwise use phpinfo().
      ob_start();
      @phpinfo(8);
      $moduleInfo = ob_get_contents();
      ob_end_clean();
      if (preg_match("/\bgd\s+version\b[^\d\n\r]+?([\d\.]+)/i", $moduleInfo,$matches))
      {
        $gdVersion = $matches[1];
      }
      else
      {
        $gdVersion = '0';
      }
      return $gdVersion;
    }
  }
  
 /**
  * Reads and writes xml file
  *
  * @package BuildGallery
  */
  class Xml
  {
   /**
    * Read xml file and parse into structure
    *
    * @access private
    * @returns array of $vals and $index created by xml_parse_into_struct. False if no xml file.
    * @param string path to xml file
    */
    function parseXml($xmlPath)
    {
      if (!file_exists($xmlPath))
      {
        return false;
      }
      $xmlSource = @file_get_contents($xmlPath);
      if ($xmlSource === false)
      {
        trigger_error('cannot read <span class="filename">'.$xmlPath.'</span>, default settings will be used.', E_USER_WARNING);
        return false;
      }
      $check = &new XML_check;
      if(!$check->check_string($xmlSource))
      {
        trigger_error('XML in '.$xmlPath.' is not well-formed. '.$check->get_full_error(), E_USER_WARNING);
        return false;
      }
      $p = xml_parser_create('UTF-8');
      xml_parser_set_option($p, XML_OPTION_CASE_FOLDING, 0);
      xml_parser_set_option($p, XML_OPTION_SKIP_WHITE, 0);
      if (0 === @xml_parse_into_struct($p, $xmlSource, $vals, $index))
      {
        trigger_error('failed to parse the xml file ('.$xmlSource.') default settings will be used. ', E_USER_WARNING);
        return false;
      }       
      xml_parser_free($p);
      return array('vals'=>$vals, 'index'=>$index);
    }
    
   /**
    * Extract attributes of <simpleviewergallery> tag from xml structured array.
    * Tags with no attributes are ignored
    * If tag occurs more than once then attributes of the first occurence will be returned
    *
    * @access private
    * @return array
    * @param values array containing values and index arrays as generated by xml_parse_into_struct
    */
    function parseAttributes($xmlStruct)
    {
      $vals = $xmlStruct['vals'];
      $index = $xmlStruct['index'];
      if (is_array($index))
      {
        $indexLc = array_change_key_case($index, CASE_LOWER);
      }
      if (!isset($indexLc[SV_XML_SETTINGS_TAG]))
      {
        trigger_error('missing or incorrect &lt;'.SV_XML_SETTINGS_TAG.'&gt; tag in gallery xml file, default settings will be used.', E_USER_WARNING);
        return false;
      }
      foreach($indexLc[SV_XML_SETTINGS_TAG] as $i)
      {
        if (isset($vals[$i]['attributes']))
        {
          return $vals[$i]['attributes'];
        }
      }
      trigger_error('problem reading settings from xml file, defaults will be used', E_USER_WARNING);
      return false;
    }
  
   /**
    * Extract file names and captions from xml structured array
    *
    * Any empty image tags are silently ignored
    * @access private
    * @return array containing filenames and captions
    * @param values array as generated by xml_parse_into_struct
    */
    function parseImageTags($xmlStruct, $bgOptions)
    {
      $htmlEntityDecode = &new HtmlEntityDecode();
      $okTags = '<font><b><i><br><br />';
      if (!$bgOptions['addLinks'])
      {
        $okTags = $okTags.'<a>';
      }
      if (!$bgOptions['underlineLinks'])
      {
        $okTags = $okTags.'<u>';
      }
      $vals = $xmlStruct['vals'];
      $imageData = array();
      $imageTagOpen = false;
      $fileName = '';
      $caption = '';
      foreach ($vals as $tagInfo)
      {
        $imageTagOpen = ((strtolower($tagInfo['tag']) == 'image' && $tagInfo['type'] == 'open') || $imageTagOpen);
        if (strtolower($tagInfo['tag']) == 'filename')
        {
          $fileName = $htmlEntityDecode->decode($tagInfo['value']);
        }
        if (strtolower($tagInfo['tag']) == 'caption')
        {
          $caption = strip_tags($tagInfo['value'], $okTags);
        }
        if ($imageTagOpen && strtolower($tagInfo['tag']) == 'image' && $tagInfo['type'] == 'close')
        {
          if ($fileName != '')
          {
            $imageData[] = array('fileName' => $fileName, 'caption' => $caption);
            $fileName = '';
            $caption = '';
            $imageTagOpen = false;
          }
        }
      }
      return $imageData;
    }

   /**
    * Construct xml string and write to file
    *
    * @access private
    * @param string file path
    * @param array attributes of simpleviewerGallery tag as they will be written
    * @param string image path relative to gallery
    * @param array image file names and captions
    * @return boolean
    */
    function writeXml($attributes, $bgOptions, $relImagePath, $imageData)
    {
      $xmlPath = $bgOptions['xmlPath'];
      $addLinks = $bgOptions['addLinks'];
      $openTag = $bgOptions['underlineLinks'] ? '<u>' : '';
      $closeTag = $bgOptions['underlineLinks'] ? '</u>' : '';
      $xml = '<?xml version="1.0" encoding="UTF-8"'. "?>";
      $xml .= '
<'.SV_XML_SETTINGS_TAG;
      $attributes['title'] = htmlspecialchars($attributes['title'], ENT_QUOTES, 'UTF-8');
      $attributes['backgroundImagePath'] = htmlspecialchars($attributes['backgroundImagePath'],  ENT_QUOTES, 'UTF-8');
  
      foreach ($attributes as $name => $value)
      {
         $xml .= ' '.$name.' = "'.$value.'"';
      }
      $xml .= '>';
      foreach ($imageData as $key=>$imageArray)
      {
        $fileName = htmlspecialchars($imageArray['fileName'], ENT_QUOTES, 'UTF-8');
        $caption = $imageArray['caption'];
        $xml .= '
  <image>
    <filename>'.$fileName.'</filename>';
        if (strlen($caption) == 0)
        {
          $xml .= '
  </image>';
          continue;
        }
        if ($addLinks)
        {
          $xml .= '
    <caption><![CDATA[<a href="'.$relImagePath.$fileName.'" target="showimage">'.$openTag.$caption.$closeTag.'</a>]]></caption>';
        }
        else
        {
          $xml .= '<caption><![CDATA['.$caption.']]></caption>';
        }
        $xml .= '
  </image>';
      }
      $xml .= '
</'.SV_XML_SETTINGS_TAG.'>';
      $fileHandle = @fopen($xmlPath,"w");
      if ($fileHandle == false)
      { 
  	    trigger_error('cannot open <span class="filename">'.$xmlPath. '</span>, for writing, check permissions.', E_USER_WARNING);
  	    return false; 
      }
      
      if (!@fwrite($fileHandle, $xml))
      { 
  	    trigger_error('cannot write to <span class="filename">'.$xmlPath.'</span>, check permissions', E_USER_WARNING);
  	    return false;   
      }
  	  trigger_error('Saved settings to <span class="filename">'.$xmlPath.'</span>.', E_USER_NOTICE);   
      @fclose($fileHandle);
      @chmod($xmlPath, 0777);
      return true; 
    }
  }
  
 /**
  * A class to check if documents are well formed.
  *
  * XML reporting error msg,line and col if not or statistics about the document if it is well formed.
  * Mods by JH
  * Call time pass-by-reference removed
  * typos in function _init() corrected
  * text of get_full_error changed
  * @package BuildGallery
  * @version 1.0, last modified 07-10-2002
  * @author Luis Argerich {@link mailto:lrargerich@yahoo.com}
  */
  class XML_check {
    var $error_code;
    var $error_line;
    var $error_col;
    var $error_msg;
    var $size;
    var $elements;
    var $attributes;
    var $texts;
    var $text_size;
    
    function get_error_code() {
      return $this->error_code; 
    }
    
    function get_error_line() {
      return $this->error_line; 
    }
    
    function get_error_column() {
      return $this->error_col; 
    }
    
    function get_error_msg() {
      return $this->error_msg; 
    }
    
    function get_full_error() {
      return $this->error_msg." at line:".$this->error_line ." column:".$this->error_col;
    }
    
    function get_xml_size() {
      return $this->size; 
    }
    
    function get_xml_elements() {
      return $this->elements; 
    }
    
    function get_xml_attributes() {
      return $this->attributes; 
    }
    
    function get_xml_text_sections() {
      return $this->texts; 
    }
    
    function get_xml_text_size() {
      return $this->text_size; 
    }
    
    function check_url($url) {
      $this->_init();
      $this->parser = xml_parser_create_ns("",'^');
      xml_set_object($this->parser,$this);
      xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
      xml_set_element_handler($this->parser, "_startElement", "_endElement");
      xml_set_character_data_handler($this->parser,"_data");
      if (!($fp = fopen($url, "r"))) {
        $this->error="Cannot open $rddl";
        return false;
      }
      while ($data = fread($fp, 4096)) {
        $this->size+=strlen($data);
        if (!xml_parse($this->parser, $data, feof($fp))) {
          $this->error_code = xml_get_error_code($this->parser);
          $this->error_line = xml_get_current_line_number($this->parser);
          $this->error_col = xml_get_current_column_number($this->parser);
          $this->error_msg = xml_error_string($this->error_code);
          return false;                    
        }
      }
      xml_parser_free($this->parser); 
      return true;
    }
    
    function _init() {
      $this->error_code = '';
      $this->error_line = '';
      $this->error_col = '';
      $this->error_msg = '';
      $this->size = 0;
      $this->elements = 0;
      $this->attributes = 0;
      $this->texts = 0;
      $this->text_size = 0; 
    }
    
    function _startElement($parser,$name,$attrs) {
      $this->elements++;
      $this->attributes+=count($attrs);
    }
    
    function _endElement($parser,$name) {
      
    }
    
    function _data($parser,$data) {
      $this->texts++;
      $this->text_size+=strlen($data);
    }
    
    function check_string($xml) {
      $this->_init();
      $this->parser = xml_parser_create_ns("",'^');
      xml_set_object($this->parser,$this);
      xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, false);
      xml_set_element_handler($this->parser, "_startElement", "_endElement");
      xml_set_character_data_handler($this->parser,"_data");
      $this->size+=strlen($xml);
      if (!xml_parse($this->parser, $xml, true)) {
        $this->error_code = xml_get_error_code($this->parser);
        $this->error_line = xml_get_current_line_number($this->parser);
        $this->error_col = xml_get_current_column_number($this->parser);
        $this->error_msg = xml_error_string($this->error_code);
        return false;                    
      }
      xml_parser_free($this->parser); 
      return true;
    }   
  }
 
 /**
  * UTF-8 and php4 safe version of html_entity_decode()
  *
  * @package svManager
  */
  class HtmlEntityDecode
  {
   /**
    * Decode html special characters
    *
    * @access public
    * @return string
    * @param string
    */
    function decode($string)
    {
      if (version_compare(phpversion(), "5.0", ">="))
      {
        return html_entity_decode($string, ENT_QUOTES, 'UTF-8');
      }
      else
      {
        return ($this->html_entity_decode_utf8($string));
      }  
    }

   /**
    * Fix for bug in php4 "Warning: cannot yet handle MBCS in html_entity_decode()!"
    *
    * @author laurynas dot butkus at gmail dot com
    * @access private
    * @return string utf decoded string
    * @param string with html entities to decode
    */
    function html_entity_decode_utf8($string)
    {
      static $trans_tbl;
     
      // replace numeric entities
      $string = preg_replace('~&#x([0-9a-f]+);~ei', '$this->code2utf(hexdec("\\1"))', $string);
      $string = preg_replace('~&#([0-9]+);~e', '$this->code2utf(\\1)', $string);
  
      // replace literal entities
      if (!isset($trans_tbl))
      {
          $trans_tbl = array();
         
          foreach (get_html_translation_table(HTML_ENTITIES) as $val=>$key)
              $trans_tbl[$key] = utf8_encode($val);
      }
     
      return strtr($string, $trans_tbl);
    }

   /**
    * Support function for fix for bug in php4
    *
    * Returns the utf string corresponding to the unicode value
    * @author from php.net, courtesy - romans@void.lv
    * @access private
    * @return string utf decoded string
    * @param string with html entities to decode
    */
    function code2utf($num)
    {
        if ($num < 128) return chr($num);
        if ($num < 2048) return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
        if ($num < 65536) return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
        if ($num < 2097152) return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
        return '';
    }
  }

 
 /**
  * Creates html page
  *
  * @package BuildGallery
  */
  class Page
  {   
   /**
    * Constructs Page class
    */
    function Page()
    {
    }
    
   /**
    * Formats html for select form element
    *
    * @access private
    * @param string form element name
    * @param string form element id
    * @param array form element options as value=>option
    * @param string value for selected element
    */
    function htmlSelect($name, $id, $tabindex, $options, $selected)
    {
      $html = '<select name="'.$name.'" id="'.$id.'" tabindex="'.$tabindex.'">';
      foreach ($options as $value=>$option)
      {
        $selectString = ($value == $selected) ? 'selected="selected"' : '';
        $html .= '<option value="'.$value.'" '.$selectString.'>'.$option.'</option>';
      }
      $html .= '</select>';
      return $html;
    }
    
   /**
    * Returns html header, css styles and page heading
    *
    * @return string
    */
    function getHtmlHead()
    {
      $header = <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <title>BuildGallery</title>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <style type="text/css">
      * {
        margin: 0;
        padding: 0;
        outline: none; /* hide dotted outline in Firefox */
      }
      body {
        background-color: #EEEEEE;
        color: #333333;
        font-family: arial, helvetica, sans-serif;
        font-size: 75%;
      }
      #wrapper {
      	width: 100%;
      	color: #333333;
      	background: #EEEEEE;
      }
      #header {
        float: left;
        width: 100%;
        background: #999999;
      }    
      #content {
      	margin: 16px 0 0 20px;
        padding: 24px 0 0 20px;
        min-width: 660px;
      	width: 55em;
      	min-height: 395px;
        border: 1px solid;
        border-color: #EEEEEE #999999 #666666 #999999;
        clear: both;
        float: left;
        display: inline;
        color: #333333;
        background-color: #FFFFFF;
      }
      #footer {
        width: 100%;
        color: #333333;
        background: #EEEEEE;
        height: 46px;
        clear: both;
      }
      #footer p {
        font-size: .8em;
        margin: 0 0 0 41px;
        padding: 1em 0 0 0; 
      }
      #externalnav {
        float: left;
        display: inline;
        margin: 9px 0 0 20px;
        width: 680px;
        height: 50px;
      }
      #externalnav ul {
        float: right;
        list-style: none;
      }
      #externalnav ul li {
        float: left;
        color: #EEEEEE;
      }
      #externalnav ul li a {
        color: #EEEEEE;
        float: left;
        font-weight: normal;
        font-size: 0.9em;
      }
      .clearboth { /* see http://www.pixelsurge.com/experiment/clearers.htm */
      	clear: both;
      	height: 0;
      	margin: 0;
      	font-size: 1px;
      	line-height: 0;
      }
      img {
        border: none;
      }
      h1 {
        font-size: 1.8em;
        font-weight: bold;
        color: #EEEEEE;
      }
      h2 {
        font-size: 1.5em;
        font-weight: bold;
        padding: 13px 0 2px 0;
        color: #666666;
        width: 554px;
      }
      h3 {
        color: #666666;
        margin: 0 0 0.5em 0;
        font-size: 1.2em;
        padding-bottom: 1em;
      }
      h3 a {
        font-weight: normal;
      }
      p, ol li, ul li {
        font-size: 1.2em;
      }
      p {
        margin: 0 0 1em 0;
      }
      ol {
        margin-left: 20px;
      }
      ol.messages {
        margin: 0 0 12px 0;
        width: 618px;
      }
      ol.messages li {
        padding-top: 5px;
        list-style-type: none;
        background-color: #FFFFFF;
      }
      ol.messages li.notice {
        color: #0000AA;
      }
      ol.messages li.warning {
        color: #990000;
      }
      ol.messages li.error {
        color: #990000;
      }

      pre {
        font-size: 12px;
      }
      em {
        font-style: italic;
        color: #BB0000;
      }
      .filename {
        font-style: italic;
      }
      a:link, a:visited {
        color: #3c5c7c;
      }
      a:hover {
        color: #6699CC;
      }
      table {
        font-size: 1.2em;
        padding-bottom: 1em;
      }
      th {
        font-weight: bold;
        text-align: left;
        vertical-align: top;
        white-space: nowrap;
        padding: 0 0 0.5em 0;
      }
      #settings1 {
        width: 634px;
      }
      #settings2 {
        width: 303px;
        float: left;
      }
      #settings3 {
        width: 303px;
        float: right;
        display: inline;
        margin-right: 30px;
      }
      td {
        padding: 0 0 5px 0;
        line-height: 20px;
        height: 32px;
        vertical-align: top;
      }
      td.label {
        width: 142px;
      }
      select {
        width: 148px;
        height: 22px;
      }
      form, form input, form checkbox, form select {
        font-size: 1em;
      }
      input.text, input.colorpicker, select {
        border: 1px solid;
        border-color: #666666 #CCCCCC #EEEEEE #CCCCCC;
      }
      input {
        padding: 3px;
      }
      input.formbutton {
        width: 5em;
        height: 2em;
      }
      input.checkbox {
        width: 20px;
        min-height: 20px;
      }
      label {
        cursor: pointer;
      }
      input.text, input.colorpicker {
        width: 140px;
      }
      #title {
        width: 467px;
      }
    </style>
    <!--[if lte IE 6]>
      <style type="text/css">
        #content {
          width: 660px;
          height: 395px;
        }
      </style>
    <![endif]-->
    <script type="text/javascript">
      /* <![CDATA[ */
        window.onload = function ()
        {
          resetButton = document.getElementById("reset");
          resetButton.onclick = function()
          {
            window.location = self.location.protocol + '//' + self.location.host + self.location.pathname;
          };
          if (!document.getElementsByTagName) return;
          var anchors = document.getElementsByTagName("a");
          for (var i=0; i<anchors.length; i++)
          {
            var anchor = anchors[i];
            if (anchor.getAttribute("href") && anchor.getAttribute("rel") == "external") anchor.target = "_blank";
            if (anchor.getAttribute("href") && anchor.getAttribute("rel") == "gallery") anchor.target = "gallery";
          }
        };	
      /* ]]> */
    </script>
  </head>

EOD;
    return $header;
  }
 
 /**
  * Returns opening html for page body
  *
  * @return string
  */
  function getPageHeader()
  {    
    $pageHeader = <<<EOD
  <body>
    <div id="wrapper">
      <div id="header">
        <div id="externalnav">
          <ul>
            <li id="view"><a href="index.html" rel="gallery" title="View gallery in new window/tab">View gallery</a>&nbsp;&nbsp;|&nbsp;&nbsp;</li>
            <li id="help"><a href="http://www.airtightinteractive.com/simpleviewer/svmanager/buildgalleryhelp.html" rel="external" title="View Buildgallery help page in new window/tab">Help</a>&nbsp;&nbsp;|&nbsp;&nbsp;</li>
            <li id="defaults"><a href="buildgallery.php?defaults=true" title="Load default settings">Default settings</a>&nbsp;&nbsp;|&nbsp;&nbsp;</li>
            
            <li id="upgrade"><a href="http://www.airtightinteractive.com/simpleviewer/svmanager/" rel="external" title="svManager offers many more features">Upgrade to svManager</a></li>
          </ul>
          <h1>BuildGallery</h1>
        </div>       
      </div>
      <br class="clearboth" />
      <div id="content">
EOD;
    return $pageHeader;
  }
  
 /** Returns page content
  *
  * @return string
  */
  function getPageContent($attributes, $bgOptions)
  {
    $addLinksChecked = $bgOptions['addLinks'] ? 'checked="checked"' : '';
    $enableRightClickOpenChecked = ($attributes['enableRightClickOpen'] == 'true') ? 'checked="checked"' : '';
    $addCaptionsChecked = $bgOptions['addCaptions'] ? 'checked="checked"' : '';
    $overwriteThumbnailsChecked = $bgOptions['overwriteThumbnails'] ? 'checked="checked"' :'';
    $underlineLinksChecked = $bgOptions['underlineLinks'] ? 'checked="checked"' :'';
    $textColor = str_replace('0x', '', $attributes['textColor']);
    $frameColor = str_replace('0x', '', $attributes['frameColor']);
    $imagePath = rtrim($attributes['imagePath'], '\\/');
    $thumbPath = rtrim($attributes['thumbPath'], '\\/');
    $backgroundImagePath = rtrim($attributes['backgroundImagePath'], '\\/');
    $selectOptions = array('top_left'=>'top left', 'top_center'=>'top center', 'top_right'=>'top right', 'center_left'=>'center left', 'center_center'=>'centered', 'center_right'=>'center right', 'bottom_left'=>'bottom left', 'bottom_center'=>'bottom center', 'bottom_right'=>'bottom right');
    $alignmentHtml = $this->htmlSelect('alignment', 'alignment', '7', $selectOptions, $attributes['vAlign'].'_'.$attributes['hAlign']);
    $selectOptions = array('left'=>'left', 'right'=>'right', 'top'=>'top', 'bottom'=>'bottom');
    $navPositionHtml = $this->htmlSelect('navPosition', 'navposition', '8', $selectOptions, $attributes['navPosition']);
    $selectOptions = array('0'=>'0', '1'=>'1', '2'=>'2', '3'=>'3', '4'=>'4', '5'=>'5', '6'=>'6', '7'=>'7', '8'=>'8', '9'=>'9', '10'=>'10');
    $thumbnailRowsHtml = $this->htmlSelect('thumbnailRows', 'thumbnailrows', '9', $selectOptions, $attributes['thumbnailRows']);
    $selectOptions = array('0'=>'0', '1'=>'1', '2'=>'2', '3'=>'3', '4'=>'4', '5'=>'5', '6'=>'6', '7'=>'7', '8'=>'8', '9'=>'9', '10'=>'10');
    $thumbnailColumnsHtml = $this->htmlSelect('thumbnailColumns', 'thumbnailcolumns', '10', $selectOptions, $attributes['thumbnailColumns']);
    $options = array('alpha'=>'file name A&hellip;z', 'ralpha'=>'file name Z&hellip;a', 'date'=>'oldest first', 'rdate'=>'newest first');
      $sortOrderHtml = $this->htmlSelect('sortOrder', 'sortorder', '11', $options, $bgOptions['sortOrder']);

$html = <<<EOD

        <form class="public" action = "{$_SERVER['PHP_SELF']}" id="customizeform" method="post">
          <table id="settings1" cellspacing="0">
            <tr id="titleentry">
              <td class="label"><label for="title">Gallery title</label></td><td><input type="text" id="title" tabindex="1" class="text" name="title" value="{$attributes['title']}" /></td>
            </tr>
          </table>
        
          <table id="settings2" cellspacing="0">
            <tr id="textcolorentry">
              <td class="label"><label for="textcolor">Text color</label></td><td><input type="text" id="textcolor" tabindex="5" class="colorpicker"  name="textColor" value="{$textColor}" /></td>
            </tr>
            <tr id="framecolorentry">
              <td class="label"><label for="framecolor">Frame color</label></td><td><input type="text" id="framecolor" tabindex="6" class="colorpicker" name="frameColor" value="{$frameColor}" /></td>
            </tr>
            <tr id="alignmententry">
              <td class="label"><label for="alignment">Gallery alignment</label></td><td>{$alignmentHtml}</td>
            </tr>
            <tr id="navpositionentry">
              <td class="label"><label for="navposition">Navigate position</label></td><td>{$navPositionHtml}</td>
            </tr>
            <tr id="thumbnailrowsentry">
              <td class="label"><label for="thumbnailrows">Thumbnail rows</label></td><td>{$thumbnailRowsHtml}</td>
            </tr>
            <tr id="thumbnailcolumnsentry">
              <td class="label"><label for="thumbnailcolumns">Thumbnail col&rsquo;s</label></td><td>{$thumbnailColumnsHtml}</td>
            </tr>
            <tr id="sortorderentry">
              <td class="label"><label for="sortorder">Sort order</label></td><td>{$sortOrderHtml}</td>
            </tr>
            <tr id="imagepathentry">
              <td class="label"><label for="imagepath">Image path</label></td><td><input type="text" id="imagepath" tabindex="2" class="text" name="imagePath" value="{$imagePath}" /></td>
            </tr>
            <tr id="thumbpathentry">
              <td class="label"><label for="thumbpath">Thumb path</label></td><td><input type="text" id="thumbpath" tabindex="3" class="text" name="thumbPath" value="{$thumbPath}" /></td>
            </tr>
            <tr id="backgroundimagepathentry">
              <td class="label"><label for="backgroundimagepath">Background image</label></td><td><input type="text" id="backgroundimagepath" tabindex="4" class="text" name="backgroundImagePath" value="{$backgroundImagePath}" /></td>
            </tr>
            <tr>
              <td>&nbsp;</td>
            </tr>
          </table>
        
          <table id="settings3" cellspacing="0">
            <tr id="framewidthentry">
              <td class="label"><label for="framewidth">Frame width, px</label></td><td><input type="text" id="framewidth" tabindex="12" class="text" name="frameWidth" value="{$attributes['frameWidth']}" /></td>
            </tr>
            <tr id="stagepaddingentry">
              <td class="label"><label for="stagepadding">Stage padding, px</label></td><td><input type="text" id="stagepadding" tabindex="13" class="text" name="stagePadding" value="{$attributes['stagePadding']}" /></td>
            </tr>
            <tr id="navpaddingentry">
              <td class="label"><label for="navpadding">Nav padding, px</label></td><td><input type="text" id="navpadding" tabindex="14" class="text" name="navPadding" value="{$attributes['navPadding']}" /></td>
            </tr>
            <tr id="maximagewidthentry">
              <td class="label"><label for="maximagewidth">Max image width, px</label></td><td><input type="text" id="maximagewidth" tabindex="15" class="text" name="maxImageWidth" value="{$attributes['maxImageWidth']}" /></td>
            </tr>
            <tr id="maximageheightentry">
              <td class="label"><label for="maximageheight">Max image height, px</label></td><td><input type="text" id="maximageheight" tabindex="16" class="text" name="maxImageHeight" value="{$attributes['maxImageHeight']}" /></td>
            </tr>
            <tr id="addcaptionssentry">
              <td class="label"><label for="addcaptions">Add captions</label></td><td><input type="checkbox" class="checkbox" id="addcaptions" tabindex="17" {$addCaptionsChecked} name="addCaptions" /></td>
            </tr>
            <tr id="addlinksentry">
              <td class="label"><label for="addlinks">Add caption links</label></td><td><input type="checkbox" class="checkbox" id="addlinks" tabindex="18" {$addLinksChecked} name="addLinks" /></td>
            </tr>
            <tr id="underlinelinkssentry">
              <td class="label"><label for="underlinelinks">Underline links</label></td><td><input type="checkbox" class="checkbox" id="underlinelinks" tabindex="19" {$underlineLinksChecked} name="underlineLinks" /></td>
            </tr>
            <tr id="enablerightclickopenentry">
              <td class="label"><label for="enablerightclickopen">Right-click download</label></td><td><input type="checkbox" class="checkbox" id="enablerightclickopen" tabindex="20" {$enableRightClickOpenChecked} name="enableRightClickOpen" /></td>
            </tr>
            <tr id="overwritethumbnailsentry">
              <td class="label"><label for="overwritethumbnails">Overwrite thumbnails</label></td><td><input type="checkbox" class="checkbox" id="overwritethumbnails" tabindex="21" {$overwriteThumbnailsChecked} name="overwriteThumbnails" /></td>
            </tr>
            <tr id="submitreset">
              <td colspan="2"><input type="hidden" name="customizesubmitted" value="true" /><input type="submit" name="submit" class="formbutton" value="Update" />&nbsp;<input type="reset" id="reset" value="Reset" class="formbutton" /></td>
            </tr>
          </table>
        </form>
EOD;
    return $html;
  }

    
   /**
    * Returns closing html tags
    *
    * @return string
    */
    function getFooter()
    {
      $versionString = phpversion();
      $safeMode = (@ini_get("safe_mode") == 'On') || (@ini_get("safe_mode") == 1) ? 'on' : 'off';
      $footer = <<<EOD

      </div>
      <div id="footer">
        <p>&copy; 2007&ndash;2009 Airtight Interactive. BuildGallery, version 2.0.0 build 090429. PHP {$versionString}, safe mode {$safeMode}.</p> 
      </div>
    </div>
  </body>
</html>
EOD;
    return $footer;
    }
  }
 /**
  * Error handler class
  *
  * @package svManager
  */
  Class ErrorHandler
  {
   /**
    * @var array Warning messages that do not stop execution are stored here
    * One warning message per array element
    */
    var $messages=array();
     
   /**
    * Constructs ErrorHandler
    *
    * $this must be passed by reference as below
    * Also $errorhandler = &new ErrorHandler() when the class is instantiated
    */
    function ErrorHandler()
    {
      set_error_handler(array(&$this, 'handleError'));
    }
     
    /**
     * Custom error handler
     *
     * Errors suppressed with @ are not reported error_reporting() == 0
     * @return boolean true will suppress normal error messages
     * @param int error level
     * @param string error message
     * @param string php script where error occurred
     * @param int line number in php script
     * @param array global variables
     */
    function handleError($errLevel, $errorMessage, $errFile, $errLine, $errContext)
    {
      switch($errLevel)
      {
        case E_USER_NOTICE :
          $this->messages[] = array('notice', $errorMessage);
          break;
        case E_NOTICE :
          if (error_reporting() != 0)
          {
            $this->messages[] = array('notice', 'Notice: '.$errorMessage.' (line '.$errLine.')');
          }
          break;
        case E_USER_WARNING :
          $this->messages[] = array('warning', 'Warning: '.$errorMessage.' (line '.$errLine.')');
          break;
        case E_WARNING :
          if (error_reporting() != 0)
          {
            $this->messages[] = array('warning', 'Warning: '.$errorMessage.' (line '.$errLine.')');
          }
          break;
        default :
          $this->messages[] = array('error', 'Error: '.$errorMessage.' (line '.$errLine.')');
      }
      return true;
    }
  
   /**
    * returns user messages
    *
    * @access public
    * @returns string
    */
    function getMessages()
    {
      if (count ($this->messages) == 0) return '';
      $messageHtml = '<ol class="messages">';
      foreach ($this->messages as $message)
      {
        $messageHtml .= '<li class="'.$message[0].'">'.$message[1].'</li>';
      }
      $messageHtml .= '</ol>';    
      return $messageHtml;
    }
  }
 


?>