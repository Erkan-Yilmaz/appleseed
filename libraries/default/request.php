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

/** Request Class
 * 
 * Handles POST/GET/REQUEST data.
 * 
 * @package     Appleseed.Framework
 * @subpackage  Library
 */
class cRequest {
	
	protected $_Request;
	protected $_Files = false;
	protected $_Unassigned;
	protected $_Method;
	protected $_URI;
	
	/**
	 * Constructor
	 *
	 * @access  public
	 */
	public function __construct ( ) {       
		eval ( GLOBALS );
		
		$purifier = $zApp->GetSys ( "Purifier" );

		$this->_ParseURI();

		$this->_Method = $_SERVER['REQUEST_METHOD'];

		switch ( $this->_Method ) {
			case 'GET':
				if ( $_GET ) 
					$Data = $_GET;
				else
					parse_str(file_get_contents('php://input'), $Data);
			break;
			
			case 'POST':
				if ( $_POST ) 
					$Data = $_POST;
				else
					parse_str(file_get_contents('php://input'), $Data);
			break;
			break;
			
			case 'OPTIONS':
				parse_str(file_get_contents('php://input'), $Data);
			break;
			
			case 'PUT':
				parse_str(file_get_contents('php://input'), $Data);
			break;
			
			case 'DELETE':
				$Source = '_DELETE';
				parse_str(file_get_contents('php://input'), $Data);
			break;
			
			case 'HEAD':
				$Source = '_HEAD';
				parse_str(file_get_contents('php://input'), $Data);
			break;
		}

		/*
		 * Loop through the file data and store in the framework.
		 */
		if ( count ( $_FILES ) > 0 ) {
			foreach ( $_FILES as $f => $file ) {
				if ( !$file['tmp_name'][0] ) continue;
				if ( $file['error'][0] == 4 ) continue;
				$this->_Files[] = $file;
			}
		}

		foreach ( $Data as $key => $value ) {
			$lowerkey = strtolower ( $key );
			$this->_Raw[$lowerkey] = $Data[$key];
			if ( is_array ( $Data[$key] ) ) {
				$requests = $Data[$key];
				foreach ( $requests as $r => $request ) {
					/*
					 * @todo If we have nested arrays, this will break it.
					 * @todo On the other hand, why are you using nested arrays in a REQUEST in the first place?
					 *
					 */
					$requests[$r] = $purifier->Purify ( $request );
				}
				$this->_Request[$lowerkey] = $requests;
			} else {
				$this->_Request[$lowerkey] = $purifier->Purify ( $Data[$key] );
			}
		}

		$this->_Unassigned = array ();
		
		return ( true );
	}

	public function Get ( $pVariable = null , $pDefault = null ) {
		
		// Makes all request variable names case insensitive.
		$variable = strtolower ( rtrim ( ltrim ( $pVariable ) ) );
		
		if ( $pVariable === null ) return ( $this->_Request );
		
		if ( !isset ( $this->_Request[$variable] ) ) return ( $pDefault );
		
		return ( $this->_Request[$variable] );

	}

	public function Files ( ) {
		return ( $this->_Files );
	}

	public function Method ( ) {
		return ( $this->_Method );
	}
	
	public function URI ( ) {
		return ( $this->_URI );
	}
	
	protected function _ParseURI ( ) {
		eval ( GLOBALS );
		
		$this->_URI = substr ( $_SERVER['REQUEST_URI'], 1, strlen ( $_SERVER['REQUEST_URI'] ) );

		// Remove all GET variables from the URI
		list ( $this->_URI ) = explode ( '?', $this->_URI, 2 );

		$pattern = $zApp->GetSys ( 'Router' )->Get ( 'Route' );

		if ( !$pattern ) return ( false );
		
		$this->_URI = preg_replace ( '/\/$/', '', $this->_URI );
		preg_match ( $pattern, $this->_URI, $returns );
		
		// Discard the first element, which is the full string.
		unset ( $returns[0] );
		
		foreach ( $returns as $r => $return ) {
		
			$matches = explode ( '/', $return );
		
			foreach ( $matches as $m => $match ) {
				if ( strstr ( $match, ',' ) ) {
					list ( $key, $value ) = explode ( ',', $match, 2 );
					$key = strtolower ( $key );
					$this->_Request[$key] = strip_tags ( $value );
				} else {
					$this->_Unassigned[] = $match;
					$key = count ( $this->_Unassigned ) - 1;
					$this->_Request[$key] = $match;
				}
			}
		} 

		return ( true );
	}
	
	public function Set ( $pVariable, $pValue ) {
		
		$variable = strtolower ( rtrim ( ltrim ( $pVariable ) ) );
		
		$this->_Request[$variable] = $pValue;
		
		return ( true );
	}
		
	
}
