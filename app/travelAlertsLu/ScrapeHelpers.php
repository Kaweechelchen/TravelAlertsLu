<?php

  namespace travelAlertsLu;
  use Silex\Application;
  class ScrapeHelpers {

    static public function getData( $app ) {

      // Loop through all the lines defined in the config file to get all the
      // XML data we need
      foreach ($app[ 'XMLAPI' ][ 'lines' ] as $lineNumber => $lineName) {

        // Put together the URL of the XML by taking the baseURL from the config
        // file and the name of the line
        $xmlUrl = $app[ 'XMLAPI' ][ 'base' ] . $lineName;

        // Put the raw data in an multidimensional array
        $rawData[ $lineNumber ] = self::xml2Array( $xmlUrl );

      }

      // return the current state to the controller
      return $rawData;

    }

    static public function xml2Array( $xmlUrl ) {

      // download the XML and remove CDATA attributes
      $xml = simplexml_load_file( $xmlUrl, NULL, LIBXML_NOCDATA);

      // convert the XML to an array
      // In order to get rid of all the stdClasses, we encode the array to JSON
      // and decode it again
      $array = json_decode(
        json_encode( $xml ),
        true
      );

      // Return the array
      return $array;

    }

  }
