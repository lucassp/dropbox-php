<?php

/**
 * Dropbox API class 
 * 
 * @package Dropbox 
 * @copyright Copyright (C) 2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/dropbox-php/wiki/License MIT
 */
class Dropbox_API {

    /**
     * Sandbox root-path
     */
    const ROOT_SANDBOX = 'sandbox';

    /**
     * Dropbox root-path
     */
    const ROOT_DROPBOX = 'dropbox';

    /**
     * OAuth object 
     * 
     * @var Dropbox_OAuth
     */
    protected $auth;
    
    /**
     * Default root-path, this will most likely be 'sandbox' or 'dropbox' 
     * 
     * @var string 
     */
    protected $root;

    /**
     * Constructor 
     * 
     * @param string $consumerKey OAuth consumer key 
     * @param string $consumerSecret OAuth secret key 
     * @param string $root default root path (sandbox or dropbox) 
     */
    public function __construct($consumerKey, $consumerSecret, $root = self::ROOT_SANDBOX) {

        $this->auth = new Dropbox_OAuth($consumerKey, $consumerSecret);
        $this->auth->setup();

        $this->root = $root;

    }

    /**
     * Returns information about the current dropbox account 
     * 
     * @return stdclass 
     */
    public function getAccountInfo() {

        $data = $this->auth->fetch('account/info');
        return json_decode($data);

    }

    /**
     * Returns a file's contents 
     * 
     * @param string $path path 
     * @param string $root Use this to override the default root path (sandbox/dropbox) 
     * @return string 
     */
    public function getFile($path = '', $root = null) {

        if (is_null($root)) $root = $this->root;
        return $this->auth->fetch('http://api-content.dropbox.com/0/files/' . $root . '/' . ltrim($path,'/'));

    }

    /**
     * Uploads a new file
     *
     * @param string $path Target path (including filename) 
     * @param string $file Either a path to a file or a stream resource 
     * @param string $root Use this to override the default root path (sandbox/dropbox)  
     * @return bool 
     */
    public function putFile($path, $file, $root = null) {

        $directory = dirname($path);
        $filename = basename($path);

        if($directory==='.') $directory = '';
        if (is_null($root)) $root = $this->root;

        if (is_string($file)) {

            $file = fopen($file,'r');

        } elseif (!is_resource($file)) {

            throw new Dropbox_Exception('File must be a file-resource or a string');
            
        }

        return $this->multipartFetch('http://api-content.dropbox.com/0/files/' . $root . '/' . trim($directory,'/'), $file, $filename);
    }


    /**
     * Copies a file or directory from one location to another 
     *
     * This method returns the file information of the newly created file.
     *
     * @param string $from source path 
     * @param string $to destination path 
     * @param string $root Use this to override the default root path (sandbox/dropbox)  
     * @return stdclass 
     */
    public function copy($from, $to, $root = null) {

        if (is_null($root)) $root = $this->root;
        $response = $this->auth->fetch('fileops/copy', array('from_path' => $from, 'to_path' => $to, 'root' => $root));

        return json_decode($response);

    }

    /**
     * Creates a new folder 
     *
     * This method returns the information from the newly created directory
     *
     * @param string $path 
     * @param string $root Use this to override the default root path (sandbox/dropbox)  
     * @return stdclass 
     */
    public function createFolder($path, $root = null) {

        if (is_null($root)) $root = $this->root;
        $response = $this->auth->fetch('fileops/create_folder', array('path' => $path, 'root' => $root));
        return json_decode($response);

    }

    /**
     * Deletes a file or folder 
     * 
     * @param string $path Path to new folder 
     * @param string $root Use this to override the default root path (sandbox/dropbox)  
     * @return void
     */
    public function delete($path, $root = null) {

        if (is_null($root)) $root = $this->root;
        return $this->auth->fetch('fileops/delete', array('path' => $path, 'root' => $root));

    }

    /**
     * Moves a file or directory to a new location 
     *
     * This method returns the information from the newly created directory
     *
     * @param mixed $from Source path 
     * @param mixed $to destination path
     * @param string $root Use this to override the default root path (sandbox/dropbox) 
     * @return stdclass 
     */
    public function move($from, $to, $root = null) {

        if (is_null($root)) $root = $this->root;
        $response = $this->auth->fetch('fileops/move', array('from_path' => $from, 'to_path' => $to, 'root' => $root));

        return json_decode($response);

    }

    /**
     * Returns a list of links for a directory
     *
     * The links can be used to securely open files throug a browser. The links are cookie protected
     * so a user is asked to login if there's no valid session cookie.
     *
     * @param string $path Path to directory or file
     * @param string $root Use this to override the default root path (sandbox/dropbox) 
     * @return array 
     */
    public function getLinks($path, $root = null) {

        if (is_null($root)) $root = $this->root;
        
        $response = $this->auth->fetch('links/' . $root . '/' . ltrim($path,'/'));
        return json_decode($response);

    }

    /**
     * Returns file and directory information
     * 
     * @param string $path Path to receive information from 
     * @param bool $list When set to true, this method returns information from all files in a directory. When set to false it will only return infromation from the specified directory.
     * @param string $hash If a hash is supplied, and nothing has changed since the last request, nothing has changed 
     * @param int $fileLimit Maximum number of file-information to receive 
     * @param string $root Use this to override the default root path (sandbox/dropbox) 
     * @return void
     */
    public function getMetaData($path, $list = true, $hash = null, $fileLimit = null, $root = null) {

        if (is_null($root)) $root = $this->root;

        $args = array(
            'list' => $list,
        );

        if (!is_null($hash)) $args['hash'] = $hash; 
        if (!is_null($fileLimit)) $args['file_limit'] = $hash; 

        $response = $this->auth->fetch('metadata/' . $root . '/' . ltrim($path,'/')); 
        return json_decode($response);

    } 

    /**
     * Returns a thumbnail (as a string) for a file path. 
     * 
     * @param string $path Path to file 
     * @param string $size small, medium or large 
     * @param string $root Use this to override the default root path (sandbox/dropbox)  
     * @return string 
     */
    public function getThumbnail($path, $size = 'small', $root = null) {

        if (is_null($root)) $root = $this->root;
        $response = $this->auth->fetch('http://api-content.dropbox.com/0/thumbnails/' . $root . '/' . ltrim($path,'/'),array('size' => $size));

        return json_decode($response);

    }

    /**
     * This method is used to generate multipart POST requests for file upload 
     * 
     * @param string $uri 
     * @param array $arguments 
     * @return bool 
     */
    protected function multipartFetch($uri, $file, $filename) {

        /* random string */
        $boundary = 'R50hrfBj5JYyfR3vF3wR96GPCC9Fd2q2pVMERvEaOE3D8LZTgLLbRpNwXek3';

        $headers = array(
            'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
        );

        $body="--" . $boundary . "\r\n";
        $body.="Content-Disposition: form-data; name=file; filename=".$filename."\r\n";
        $body.="Content-type: application/octet-stream\r\n";
        $body.="\r\n";
        $body.=stream_get_contents($file);
        $body.="\r\n";
        $body.="--" . $boundary . "--";

        // Dropbox requires the filename to also be part of the regular arguments, so it becomes
        // part of the signature. 
        $uri.='?file=' . $filename;
        return $this->auth->fetch($uri, $body, 'POST', $headers);

    }



}