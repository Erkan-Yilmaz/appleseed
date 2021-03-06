<?php
/**
 * @version      $Id$
 * @package      Appleseed.Components
 * @subpackage   Profile
 * @copyright    Copyright (C) 2004 - 2010 Michael Chisari. All rights reserved.
 * @link         http://opensource.appleseedproject.org
 * @license      GNU General Public License version 2.0 (See LICENSE.txt)
 */

// Restrict direct access
defined( 'APPLESEED' ) or die( 'Direct Access Denied' );

/** Profile Component Reject Controller
 * 
 * Profile Component Reject Controller Class
 * 
 * @package     Appleseed.Components
 * @subpackage  Profile
 */
class cProfileRejectController extends cController {
	
	/**
	 * Constructor
	 *
	 * @access  public
	 */
	public function __construct ( ) {       
		parent::__construct( );
	}
	
	public function Display ( $pView = null, $pData = array ( ) ) {
		
		$this->View = $this->GetView ( $pView ); 
		
		$focus = $this->Talk ( 'User', 'Focus' );
		$current = $this->Talk ( 'User', 'Current' );
		
		// If the user isn't logged in, or we're viewing our own profile, don't display.
		if ( ( $current->Account == $focus->Account ) or ( !$current ) ) {
			return ( false );
		}
		
		if ( $current ) {
			$currentAccount = $current->Username . '@' . $current->Domain;
			$data = array ( "account" => $currentAccount, 'source' => ASD_DOMAIN, 'request' => $currentAccount );
			$currentInfo = $this->GetSys ( "Event" )->Trigger ( "On", "User", "Info", $data );
			$currentInfo->username = $current->Username;
			$currentInfo->domain = $current->Domain;
			$currentInfo->account = $current->Username . '@' . $current->Domain;
		}
		
		$focusAccount = $focus->Username . '@' . $focus->Domain;
		$data = array ( "account" => $focusAccount, 'source' => ASD_DOMAIN, 'request' => $focusAccount );
		$focusInfo = $this->GetSys ( "Event" )->Trigger ( "On", "User", "Info", $data );
		$focusInfo->username = $focus->Username;
		$focusInfo->domain = $focus->Domain;
		$focusInfo->account = $focus->Username . '@' . $focus->Domain;
		
		// If the user is already a friend, show the Remove Friend button.
		$data = array ( "account" => $current->Account, "request" => $focus->Account );
		$this->View->Find ( "[class=profile-remove-friend-link]", 0 )->href = $this->GetSys ( "Event" )->Trigger ( "Create", "Friend", "Removelink", $data );
		
		$this->View->Find ( "[class=profile-send-message-link]", 0 )->href = $this->GetSys ( "Event" )->Trigger ( "Create", "Messages", "Sendlink", $data );
		
		$this->View->Find ( "[class=profile-block-user-link]", 0 )->href = null;
		
		$this->View->Find ( '.profile-block-user-link', 0 )->innertext = __ ( 'Block Contact User', array ( 'fullname' => $focus->Fullname ) );
		list ( $firstname, $lastname ) = explode ( ' ', $focus->Fullname );
		$this->View->Find ( '.profile-ping-user-link', 0 )->innertext = __ ( 'Ping Contact User', array ( 'firstname' => $firstname ) );
		$this->View->Find ( '.profile-send-message-link', 0 )->innertext = __ ( 'Send Message To Contact', array ( 'firstname' => $firstname ) );
		
		if ( in_array ( $currentInfo->account, $focusInfo->friends ) ) {
		} else if ( $currentInfo->account == $focusInfo->account ) {
			// Remove since we're looking at our own account.
			$this->View->Find ( "[class=profile-add-friend]", 0 )->outertext = "";
		} else {
			// Remove "remove from friends" if not friends
			$this->View->Find ( "[class=profile-remove-friend]", 0 )->outertext = "";
		}
		
		
		$this->View->Display();
		
		return ( true );
	}
	
}

