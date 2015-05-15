<?php

  namespace travelAlertsLu;
  use Silex\Application;
  class ScrapeHelpers {

    static public function getData( $app ) {

      // Get the data XML data
      $rawData = self::getRawData( $app );

      // remove all the unnecessary things
      $cleanData = self::cleanData( $rawData );

      // return the current state to the controller
      return $cleanData;

    }

    static public function getRawData( $app ) {

      // Loop through all the lines defined in the config file to get all the
      // XML data we need
      foreach ($app[ 'XMLAPI' ][ 'lines' ] as $lineNumber => $lineName) {

        // Put together the URL of the XML by taking the baseURL from the config
        // file and the name of the line
        $xmlUrl = $app[ 'XMLAPI' ][ 'base' ] . $lineName;

        // Put the raw data in an multidimensional array
        $rawData[ $lineNumber ] = self::xml2Array( $xmlUrl );

      }

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

    static public function cleanData( $rawData ) {

      // remove metda data (like the website and copyright info) from the array
      $cleanData = self::removeMetaData( $rawData );

      // return the cleaned array
      return $cleanData;

    }

    static public function removeMetaData( $rawData ) {

      // Loop through all the lines
      foreach ($rawData as $line => $lineData) {

        // Check if there is information on the furrent line
        if ( array_key_exists( 'item', $lineData[ 'channel' ] ) ) {

          // Put the line information in the minified array of data
          $minifiedData[ $line ] = $lineData[ 'channel' ][ 'item' ];

        } else {

          // if there's no information available, put an empty array here
          $minifiedData[ $line ] = array();

        }

      }

      // Return the minified version of the array
      return $minifiedData;

    }

  }
