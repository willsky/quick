<?php
namespace Quick\Network;

class Mime{
    private static $maps = array(
        'au'=>'audio/basic',
        'avi'=>'video/avi',
        'bmp'=>'image/bmp',
        'bz2'=>'application/x-bzip2',
        'css'=>'text/css',
        'dtd'=>'application/xml-dtd',
        'doc'=>'application/msword',
        'gif'=>'image/gif',
        'gz'=>'application/x-gzip',
        'hqx'=>'application/mac-binhex40',
        'html?'=>'text/html',
        'jar'=>'application/java-archive',
        'jpe?g'=>'image/jpeg',
        'js'=>'application/x-javascript',
        'midi'=>'audio/x-midi',
        'mp3'=>'audio/mpeg',
        'mpe?g'=>'video/mpeg',
        'ogg'=>'audio/vorbis',
        'pdf'=>'application/pdf',
        'png'=>'image/png',
        'ppt'=>'application/vnd.ms-powerpoint',
        'ps'=>'application/postscript',
        'qt'=>'video/quicktime',
        'ram?'=>'audio/x-pn-realaudio',
        'rdf'=>'application/rdf',
        'rtf'=>'application/rtf',
        'sgml?'=>'text/sgml',
        'sit'=>'application/x-stuffit',
        'svg'=>'image/svg+xml',
        'swf'=>'application/x-shockwave-flash',
        'tgz'=>'application/x-tar',
        'tiff'=>'image/tiff',
        'txt'=>'text/plain',
        'wav'=>'audio/wav',
        'xls'=>'application/vnd.ms-excel',
        'xml'=>'application/xml',
        'zip'=>'application/x-zip-compressed'
    );

    const DEFAULT_CONTENT_TYPE = 'application/octet-stream';

    public static function getContentType($filePath) {
        $file_extension = self::getFileExtension($filePath);

        if ( $file_extension && isset(self::$maps[$file_extension]) ) {
            return self::$maps[$file_extension];
        }

        return DEFAULT_CONTENT_TYPE;
    }

    public static function getFileExtension($filePath) {
        $fileName = basename($filePath);
        $fileInfo = pathinfo($fileName);
        return isset($fileInfo['extension']) ? $fileInfo['extension'] : null;
    }
}
