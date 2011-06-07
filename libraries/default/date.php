<?php
/**
 * @version      $Id$
 * @package      Appleseed.Framework
 * @subpackage   Library
 * @copyright    Copyright (C) 2004 - 2010 Michael Chisari. All rights reserved.
 * @link         http://opensource.appleseedproject.org
 * @license      GNU General Public License version 2.0 (See LICENSE.txt)
 */

// Restrict direct access
defined( 'APPLESEED' ) or die( 'Direct Access Denied' );

/** Date Class
 * 
 * Date Formatting class.
 * 
 * @package     Appleseed.Framework
 * @subpackage  Library
 */
class cDate {

	/**
	 * Constructor
	 *
	 * @access  public
	 */
	public function __construct ( ) {       
	}
	
	public function Format ( $pStamp, $pVerbose = false ) {
		
		$timeStamp = strtotime ( $pStamp );
		$nowStamp = time ( );
		
		$month = date ( "F", $timeStamp );
		$day = date ( "j", $timeStamp );
		$year = date ( "Y", $timeStamp );
		
		$minute = date ( "g", $timeStamp );
		$second = date ( "i", $timeStamp );
		
		$ampm = ucwords ( date ( "a", $timeStamp ) );
		
		$hours = floor(($nowStamp-$timeStamp)/3600);
		$minutes = floor(($nowStamp-$timeStamp)/60);
		$seconds = ($nowStamp-$timeStamp);

		$months = floor(($nowStamp-$timeStamp)/2628000);
		$days = floor(($nowStamp-$timeStamp)/86400);
		$years = floor(($nowStamp-$timeStamp)/31536000);
		
		if ( $pVerbose ) {
			$formatted = __( "Time $month At $ampm", array ( "day" => $day, "year" => $year, "minute" => $minute, "second" => $second ) );
			return ( $formatted );
		}
		
		if ( $seconds < 60 ) {
			$formatted = __( "Time In Seconds Ago", array ( "seconds" => $seconds ) );
		} else if ( $minutes == 1 ) {
			$formatted = __( "Time In Minute Ago", array ( "minutes" => $minutes  ) );
		} else if ( $minutes < 60 ) {
			$formatted = __( "Time In Minutes Ago", array ( "minutes" => $minutes  ) );
		} else if ( $hours == 1 ) {
			$formatted = __( "Time In Hour Ago", array ( "hours" => $hours  ) );
		} else if ( $hours < 24 ) {
			$formatted = __( "Time In Hours Ago", array ( "hours" => $hours  ) );
		} else if ( $days < 365 ) {
			$formatted = __( "Time $month", array ( "day" => $day ) );
		} else {
			$month = date ( "n", $timeStamp );
			$formatted = __( "Time Full $ampm", array ( "day" => $day, "month" => $month, "year" => $year, "minute" => $minute, "second" => $second ) );
		}
		
		return ( $formatted );
		
	}

	public function ToGraph ( $pStamp ) {
		# YYYY-MM-DDTHH:MM:SSZ

		# Convert to GMT/UTC

		$difference = date ( 'O', $pStamp );

		$year = date ( 'Y', $pStamp );
		$month = date ( 'm', $pStamp );
		$day = date ( 'd', $pStamp );

		$return = $year . '-' . $month . '-' . $day . 'T';

		$hour = date ( 'H', $pStamp );
		$minute = date ( 'i', $pStamp );
		$second = date ( 's', $pStamp );

		$return .= $hour . ':' . $minute . ':' . $second . 'Z';

		return ( $return );
	} 

	public function ToMysql ( $pStamp ) {
		# YYYY-MM-DD HH:MM:SS

		$difference = date ( 'O', $pStamp );

		$year = date ( 'Y', $pStamp );
		$month = date ( 'm', $pStamp );
		$day = date ( 'd', $pStamp );

		$return = $year . '-' . $month . '-' . $day . ' ';

		$hour = date ( 'H', $pStamp );
		$minute = date ( 'i', $pStamp );
		$second = date ( 's', $pStamp );

		$return .= $hour . ':' . $minute . ':' . $second . '';

		return ( $return );
	}

}

