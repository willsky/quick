<?php
namespace Quick\Network;
use \Quick\Core\Logger;

class Curl{
    public static function request($url, array $options = array()){
        $curl = curl_init($url);
        $timeout = 10;

        if ( !isset($options['method']) ) {
            $options['method'] = 'GET';
        }

        if ( !isset($options['timeout']) ) {
            $options['timeout'] = $timeout;
        }

        if ( in_array(strtoupper($options['method']), array('GET', 'DELETE')) ) {
            if ( isset($options['content']) ) {
                unset($options['content']);
            }
        }

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($options['method']));
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, intval($options['timeout']));
        curl_setopt($curl, CURLOPT_TIMEOUT, intval($options['timeout']));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,FALSE);
        $headers = array();

        if ( isset($options['follow_location']) ) {
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $options['follow_location']);
        }

        if ( isset($options['max_redirects']) ) {
            curl_setopt($curl, CURLOPT_MAXREDIRS, $options['max_redirects']);
        }

        if ( isset($options['content']) ) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $options['content']);
        }

        if ( isset($options['headers']) ) {
            $headers = $options['headers'];
        }

        if ( isset($headers['User-Agent']) ) {
            curl_setopt($curl, CURLOPT_USERAGENT, $headers['User-Agent']);
            unset($headers['User-Agent']);
        }

        if ( count($headers) > 0 ) {
            $header = array();

            foreach($headers as $field => $value) {
                array_push($header, sprintf('%s: %s', $field, $value));
            }

            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
            unset($headers, $header);
        }

        $headers=array();
        curl_setopt($curl,CURLOPT_HEADERFUNCTION,
            // Callback for response headers
            function($curl,$line) use(&$headers) {
                if ($trim=trim($line))
                    $headers[]=$trim;
                return strlen($line);
            }
        );

        $body = curl_exec($curl);
        $status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        Logger::debug('HTTP', $body);        
        return new Response($status_code, $headers, $body);
    }
}
