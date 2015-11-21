<?php

  namespace travelAlertsLu;
  use Silex\Application;
  class Issues {

    static public function getLineIssues( $app ) {

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

      // remove meta data (like the website and copyright info) from the array
      $minifiedData = self::removeMetaData( $rawData );

      // remove newlines from the descriptions
      $cleanData = self::removeNewLines( $minifiedData );

      // return the cleaned array
      return $cleanData;

    }

    static public function removeMetaData( $rawData ) {

      // Loop through all the lines
      foreach ($rawData as $line => $lineData) {

        // Check if there is information on the furrent line
        if ( array_key_exists( 'item', $lineData[ 'channel' ] ) ) {

          // Put the issues data in a variable
          $lineIssues = $lineData[ 'channel' ][ 'item' ];

          // loop through the different lineIssues
          foreach ($lineIssues as $lineIssueNumber => $lineIssue) {

            // Check if the lineIssue is an array of multiple issues
            if ( is_array( $lineIssue ) ) {

              // Put the line information in the minified array of data
              $minifiedData[ $line ][ $lineIssueNumber ] = $lineIssue;

            } else {

              // Put the line information in the minified array of data
              $minifiedData[ $line ][ 0 ] = $lineIssues;

            }

          }

        } else {

          // if there's no information available, put an empty array here
          $minifiedData[ $line ] = array();

        }

      }

      // Return the minified version of the array
      return $minifiedData;

    }

    static public function removeNewLines( $minifiedData ) {

      // Loop through all the lines
      foreach ($minifiedData as $line => $lineData) {

        // Loop through all the issues on that line
        foreach ($lineData as $issueNumber => $issueData) {

          // put the issue in the $issue variable
          $issue = $minifiedData[ $line ][ $issueNumber ];

          // Set up the pattern to extract the dates and the description of the
          // issue
          $descriptionPattern = '/([^\n]+)([\n]+)(.+[^\s]+)/s';

          // apply the regular expression and output the result to
          // $descriptionMatches
          preg_match(
            $descriptionPattern,
            strip_tags( $issue[ 'description' ] ),
            $descriptionMatches
          );

          // Refactor the dates and convert them to unix timestamps
          $issue = self::reFactorDates( $issue, $descriptionMatches[ 1 ] );

          // replace the description with only the text part of it
          $issue[ 'description' ] = $descriptionMatches[ 3 ];

          // replace html breaks by newlines
          $issue[ 'description' ] = str_replace(
            '<br />',
            "\n",
            $issue[ 'description' ]
          );

          // decode html entities
          $issue[ 'description' ] = html_entity_decode(
            $issue[ 'description' ],
            ENT_HTML5 || ENT_COMPAT
          );

          // Add the refactored issue to the array
          $cleanData[ $line ][ $issueNumber ] = $issue;

        }

      }

      // Return the minified version of the array
      return $cleanData;

    }

    static public function reFactorDates( $issue, $date ) {

      // Set up the pattern to extract the different parts of start and end date
      // and/or time
      $datePattern = '/(\d{1,2}\.\d{1,2}\.\d{4})?(\ (\d{1,2}\:\d{2}))?(\ -(\ (\d{1,2}\.\d{1,2}\.\d{4}))?(\ (\d{1,2}\:\d{2}))?)?/s';

      // apply the regular expression and output the result to $dateMatches
      preg_match(
        $datePattern,
        $date,
        $dateMatches
      );

      // convert the end datetime to a unix timestamp
      $issue[ 'start' ]    = strtotime(
        $dateMatches[ 1 ] . ' ' . $dateMatches[ 3 ]
      );

      // convert the end datetime to a unix timestamp
      $dateEnd = '';

      if ( array_key_exists( 6 , $dateMatches) ) {

        $dateEnd .= $dateMatches[ 6 ];

        if ( array_key_exists( 8 , $dateMatches) ) {

          $dateEnd .= ' ' . $dateMatches[ 8 ];

        }

      } elseif ( array_key_exists( 8 , $dateMatches) ) {

        $dateEnd .= $dateMatches[ 8 ];

      }

      $issue[ 'end' ]     = strtotime( $dateEnd );

      // convert the publication datetime to a unix timestamp
      $issue[ 'pubDate' ] = strtotime( $issue[ 'pubDate' ] );

      // return the refactored dates
      return $issue;

    }

    static public function getCurrent() {

      return json_decode(
        file_get_contents(
          'current.json'
        ),
        true
      );

    }

    static public function getAll( $app ) {

      $getAllIssues = 'SELECT *
        FROM      issues
        ORDER BY  id';

      $issues = $app[ 'db' ]->fetchAll(
        $getAllIssues
      );

      return $issues;

    }

    static public function getList( $app ) {

       $getAllIssues = 'SELECT *
        FROM      issues
        ORDER BY  title';

      $issues = $app[ 'db' ]->fetchAll(
        $getAllIssues
      );

      foreach ( $issues as $issue ) {

        echo $issue[ 'title' ] . ' >>>> ' . $issue[ 'description' ] . '<br />';

      }

      die();

      return $issues;

    }

    static public function getByLine( $app, $line ) {

      $getIssues = 'SELECT *
        FROM      issues
        WHERE     line = ?
        ORDER BY  id';

      $issues = $app[ 'db' ]->fetchAll(
        $getIssues,
        array(
          'CFL' . ( int ) $line
        )
      );

      return $issues;

    }

  }
