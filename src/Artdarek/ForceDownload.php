<?php
/**
 * @author     Dariusz Prząda <artdarek@gmail.com>
 * @copyright  Copyright (c) 2014
 * @license    http://www.opensource.org/licenses/mit-license.html MIT License
 */

namespace Artdarek;

use Exception;

class ForceDownload {

    /**
     * Download Base directory
     * @var string
     */
    private $_dir;

    /**
     * Download File name
     * @var string
     */
    private $_file;

    /**
     * Save as
     * @var string
     */
    private $_as;

    /**
     * Configuration
     * @var array
     */
    private $_config = array(
        'allowed_referer'   => '',
        'allowed_extensions' => array(
                                // archives
                                    'zip' => 'application/zip',
                                // documents
                                    'pdf' => 'application/pdf',
                                    'doc' => 'application/msword',
                                    'xls' => 'application/vnd.ms-excel',
                                    'ppt' => 'application/vnd.ms-powerpoint',
                                // executables
                                    'exe' => 'application/octet-stream',
                                // images
                                    'gif' => 'image/gif',
                                    'png' => 'image/png',
                                    'jpg' => 'image/jpeg',
                                    'jpeg'=> 'image/jpeg',
                                // audio
                                    'mp3' => 'audio/mpeg',
                                    'wav' => 'audio/x-wav',
                                // video
                                    'mpeg'=> 'video/mpeg',
                                    'mpg' => 'video/mpeg',
                                    'mpe' => 'video/mpeg',
                                    'mov' => 'video/quicktime',
                                    'avi' => 'video/x-msvideo',
                            )
    );

    /**
     * Construct
     *
     * @param  array $config
     */
    public function __construct( $config = array() )
    {
        $this->_config = array_merge($this->_config, $config);
        $this->_dir = $_GET['dir'];
        $this->_file = $_GET['file'];
        $this->_as = $_GET['as'];
    }

    /**
     * Sets download directory name
     *
     * @param  string $dir
     * @return self
     */
    public function setDir( $dir )
    {
        $this->_dir = $dir;
        return $this;
    }

    /**
     * Sets download file name
     *
     * @param  string $file
     * @return self
     */
    public function setFile( $file )
    {
        $this->_file = $file;
        return $this;
    }

    /**
     * Sets download new file name
     *
     * @param  string $as
     * @return self
     */
    public function setAs( $as )
    {
        $this->_as = $as;
        return $this;
    }


    /**
     * Download
     * @return [type] [description]
     */
    public function download()
    {

        // check if refferer is allowed
        $this->isRefererAllowed( $this->_config['allowed_referer'] );
        
        // Make sure program execution doesn't time out
        // Set maximum script execution time in seconds (0 means no limit)
        set_time_limit(0);

        // check if filename adn dir is set
        if (!isset($this->_dir) || empty($this->_file)) {
            throw new Exception('File name for download is not specified.');
        }

        // Get real file name (Remove any path info to avoid hacking by adding relative path, etc.)
        $file = $this->getFileInfo($this->_file);

        // check if file extension is allowed
        $this->isFileExtensionAllowed( $file['extension'] );
        
        // name for a downloaded file
        $new_file_name = $this->saveAs( $this->_as );

        // set headers
        $this->setheaders(array(
                'mime_type' => $file['mime'],
                'file_name' => $new_file_name,
                'file_size' => $file['size']
            )
        );

        // get file content from source file
        $this->getFileContent($file['path']);
    }

     /**
     * Get file info
     * @param string $filename
     * @return array
     */
    public function getFileInfo($filename)
    {
        // Get real file name (Remove any path info to avoid hacking by adding relative path, etc.)
        $file['name'] = basename($filename);
        // get full file path (including subfolders)
        $file['path'] = $this->getFullFilePath($file['name']);
        // file size in bytes
        $file['size'] = filesize($file['path']);
        // file extension
        $file['extension'] = $this->getFileExtension( $file['name'] );    
        // get file mimetype
        $file['mime'] = $this->getMimeType( $file['path'], $file['extension'] );

        return $file;
    }   

    /**
     * Get file content from source file
     * @param string $file_path
     * @return void
     */
    public function getFileContent($file_path)
    {
        // download
        // @readfile($file_path);
        $file = @fopen($file_path,"rb");
        if ($file) {
          while(!feof($file)) {
            print(fread($file, 1024*8));
            flush();
            if (connection_status()!=0) {
              @fclose($file);
              die();
            }
          }
          @fclose($file);
        }

    }

    /**
     * get full file path (including subfolders)
     * @param  string $filename
     * @return string $file_path
     */
    public function getFullFilePath($filename)
    {
        $file_path = '';
        $this->findFile($this->_dir, $filename, $file_path);
        if (!is_file($file_path)) {
            throw new Exception('File does not exist. Make sure you specified correct file name.');
        }
        return $file_path;
    }


    /**
     * Set headers
     * @param  array $attribs
     * @return void
     */
    public function setheaders($attribs = array())
    {
        // set headers
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Cache-Control: public');
        header('Content-Description: File Transfer');
        header('Content-Type: '.$attribs['mime_type']);
        header('Content-Disposition: attachment; filename="'.$attribs['file_name'].'"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '. $attribs['file_size']);
    }

    /**
     * Set target name for downloading file
     * @param  string $filename
     * @return string $new_file_name
     */
    public function saveAs($filename = null) {
        // Browser will try to save file with this filename, regardless original filename.
        // You can override it if needed.
        if (!isset($filename) || empty($filename)) {
          $new_file_name = $this->_file;
        }
        else {
          // remove some bad chars
          $new_file_name = str_replace(array('"',"'",'\\','/'), '', $filename);
          if ($new_file_name === '') $new_file_name = 'NoName';
        }
        return $new_file_name;
    }

    /**
     * Get file extension from filename
     * @param  string $filename
     * @return string $extension
     */
    public function getFileExtension( $filename )
    {
        $extension = strtolower(substr(strrchr($filename,"."),1));
        return $extension;
    }

    /**
     * Check if extension is allowed
     * @param  string  $extension
     * @return boolean
     */
    public function isFileExtensionAllowed( $extension )
    {
        if (!array_key_exists($extension, $this->_config['allowed_extensions'])) {
            throw new Exception('"Not allowed file type.');
        }
    }


    /**
     * Check if refferer is allowed
     * @param  string  $referrer
     * @return boolean
     */
    public function isRefererAllowed( $referer )
    {
        // If hotlinking not allowed then make hackers think there are some server problems
        if ($referer !== ''
            && (!isset($_SERVER['HTTP_REFERER']) || strpos(strtoupper($_SERVER['HTTP_REFERER']),strtoupper($referer)) === false)
        ) {
            throw new Exception('Internal server error. Please contact system administrator.');
        }
    }

    /**
     * Get file Mime Type
     * @param string $file_path
     * @param string $extension
     * @return string $mtype
     */
    public function getMimeType( $file_path, $extension )
    {
        // get mime type
        if ($this->_config['allowed_extensions'][$extension] == '') {
          $mtype = '';
          // mime type is not set, get from server settings
          if (function_exists('mime_content_type')) {
            $mtype = mime_content_type($file_path);
          }
          else if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME); // return mime type
            $mtype = finfo_file($finfo, $file_path);
            finfo_close($finfo);
          }
          if ($mtype == '') {
            $mtype = "application/force-download";
          }
        }
        else {
          // get mime type defined by admin
          $mtype = $this->_config['allowed_extensions'][$extension];
        }
        return $mtype;
    }

    /**
     * Check if the file exists (Check in subfolders too)
     * @param string $dirname
     * @param string $filename
     * @param string $file_path
     */
    public function findFile($dirname, $fname, &$file_path)
    {
        $dir = opendir($dirname);

        while ($file = readdir($dir)) {
            if (empty($file_path) && $file != '.' && $file != '..') {
                if (is_dir($dirname.'/'.$file)) {
                    $this->findFile($dirname.'/'.$file, $fname, $file_path);
                }
                else {
                    if (file_exists($dirname.'/'.$fname)) {
                        $file_path = $dirname.'/'.$fname;
                        return;
                    }
                }
            }
        }

    }


}