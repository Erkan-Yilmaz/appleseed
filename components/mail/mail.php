<?php
/**
 * @version      $Id$
 * @package      Appleseed.Components
 * @subpackage   Mail
 * @copyright    Copyright (C) 2004 - 2010 Michael Chisari. All rights reserved.
 * @link         http://opensource.appleseedproject.org
 * @license      GNU General Public License version 2.0 (See LICENSE.txt)
 */

// Restrict direct access
defined( 'APPLESEED' ) or die( 'Direct Access Denied' );

/** Mail Component
 * 
 * Mail Component Entry Class
 * 
 * @package     Appleseed.Components
 * @subpackage  Mail
 */
class cMail extends cComponent {
	
	/**
	 * Constructor
	 *
	 * @access  public
	 */
	public function __construct ( ) {       
		parent::__construct();
	}
	
	public function AddToProfileTabs ( $pData = null ) {
		
		$return = array ();
		
		// NOTE: Temporarily disabled
		//$return[] = array ( 'id' => 'mail', 'title' => 'Mail Tab', 'link' => '/messages/', 'owner' => true );
		
		return ( $return );
	} 
	
	
}
