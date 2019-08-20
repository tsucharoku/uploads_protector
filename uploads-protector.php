<?php

/*
 Plugin Name: Uploads Protector
 Plugin URI:
 Description: Protect files in uploads directory.
 Version: 1.0
 Author: Rokuta Okanishi
 Author URI:
 Text Domain: uploads-protector
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class UploadsProtector{

    private $pluginSlug;
    private $pluginName;

    public function __construct(){
      $this->pluginName = 'Uploads Protector';
   		$this->pluginSlug = 'uploads-protector';
      register_activation_hook( __FILE__, array($this, 'writeToHtaccess'));
      register_deactivation_hook( __FILE__,array($this, 'deleteFromHtaccess'));
      add_action( 'init', array($this, 'download'));
    }

    public function download(){
      if ( is_admin() ) {
          return;
      }
      if(!empty($_GET['uploads-protector-download'])){
        $fileUrl = $_GET['uploads-protector-download'];
        require_once(dirname(__FILE__).'/download.php');
        new UploadsProtectorDownload($fileUrl);
      }
    }

    public function writeToHtaccess(){
      insert_with_markers($this->getHtaccessFilePath(), $this->pluginName, array(
        '<IfModule mod_rewrite.c>',
        'RewriteEngine On',
        'RewriteBase '. $this->getHomeUrlPath() .'/',
        'RewriteCond %{REQUEST_FILENAME} -s',
        'RewriteRule ^'.$this->getUploadsUrlFromHome().'/(.*)$ '. $this->getHomeUrlPath() .'/?'.$this->pluginSlug.'-download=$1 [QSA,L]',
        '</IfModule>'
      ));
    }

    public function deleteFromHtaccess(){
      insert_with_markers($this->getHtaccessFilePath(), $this->pluginName, array(''));
    }

    private function getHtaccessFilePath(){
      return get_home_path() . '.htaccess';
    }

    private function getHomeUrlPath(){
      $parcedHomeUrl = parse_url(home_url());
      return $parcedHomeUrl['path'];
    }

    private function getUploadsUrlFromHome(){
      $uploadDirArray = wp_upload_dir();
      return str_replace(home_url('/'),'',$uploadDirArray['baseurl']);
    }

    private function getPluginUrlFromHome(){
      return str_replace(home_url('/'),'',plugins_url().'/'.$this->pluginSlug);
    }

}


new UploadsProtector();
