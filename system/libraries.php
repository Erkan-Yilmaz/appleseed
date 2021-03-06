<?php
/**
 * @version      $Id$
 * @package      Appleseed.Framework
 * @subpackage   System
 * @copyright    Copyright (C) 2004 - 2010 Michael Chisari. All rights reserved.
 * @link         http://opensource.appleseedproject.org
 * @license      GNU General Public License version 2.0 (See LICENSE.txt)
 */

// Restrict direct access
defined( 'APPLESEED' ) or die( 'Direct Access Denied' );

/** Library Class
 * 
 * Base class for Libraries
 * 
 * @package     Appleseed.Framework
 * @subpackage  System
 */
class cLibraries extends cBase {

	/**
	 * Constructor
	 *
	 * @access  public
	 */
	public function __construct ( ) {       

 		// Load libraries configuration.
 		$this->_Config = new cConf ();
		$this->_Config->Set ( "Data",  $this->_Config->Load ( "libraries" ) );
		
		return ( true );
	}

}
