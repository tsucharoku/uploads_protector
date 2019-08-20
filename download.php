<?php

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class UploadsProtectorDownload{

  private $fileUrl;
  private $lastModifiedTime;
  private $etag;

  public function __construct($filePathFromUploadsDir){

    if(!is_user_logged_in()){
      $this->failed();
    }

    $this->file = $this->getUploadsUrl(). '/' . $filePathFromUploadsDir;
    
    if ( !is_file($this->file)) {
      $this->failed();
    }

    $this->lastModifiedTime = gmdate( 'D, d M Y H:i:s', filemtime( $this->file ) );
    $this->etag = '"' . md5( $this->lastModifiedTime ) . '"';

    $this->writeHeaders();
    $this->return304IfNotUpdated();

    readfile( $this->file );
    exit;
  }

  private function getUploadsUrl(){
    $upload_dir = wp_upload_dir();
    return $upload_dir[ 'basedir' ];
  }

  private function getMimetype(){
    $file = $this->file;
    $mime = wp_check_filetype( $file );
    if( false === $mime[ 'type' ] && function_exists( 'mime_content_type' ) )
     $mime[ 'type' ] = mime_content_type( $file );

    if( $mime[ 'type' ] )
     $mimetype = $mime[ 'type' ];
    else
     $mimetype = 'image/' . substr( $file, strrpos( $file, '.' ) + 1 );
     return $mimetype;
  }

  private function writeHeaders(){

    header( 'Content-Type: ' . $this->getMimetype() );
    if ( false === strpos( $_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS' ) ){
      header( 'Content-Length: ' . filesize( $this->file ) );
    }
    header( "Last-Modified: ".$this->lastModifiedTime." GMT" );
    header( 'ETag: ' . $this->etag );
    header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );
  }

  private function return304IfNotUpdated(){

    $client_etag = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? stripslashes( $_SERVER['HTTP_IF_NONE_MATCH'] ) : false;

    if( ! isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) )
     $_SERVER['HTTP_IF_MODIFIED_SINCE'] = false;

    $client_last_modified = trim( $_SERVER['HTTP_IF_MODIFIED_SINCE'] );

    $client_modified_timestamp = $client_last_modified ? strtotime( $client_last_modified ) : 0;

    $modified_timestamp = strtotime($this->lastModifiedTime);

    if ( ( $client_last_modified && $client_etag )
     ? ( ( $client_modified_timestamp >= $modified_timestamp) && ( $client_etag == $this->etag ) )
     : ( ( $client_modified_timestamp >= $modified_timestamp) || ( $client_etag == $this->etag ) )
     ) {
       $this->notUpdated();
    }
  }

  private function failed(){
    status_header( 404 );
    die();
  }

  private function notUpdated(){
    status_header( 304 );
    die();
  }
}
