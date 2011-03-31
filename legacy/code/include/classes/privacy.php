<?php
  // +-------------------------------------------------------------------+
  // | Appleseed Web Community Management Software                       |
  // | http://appleseed.sourceforge.net                                  |
  // +-------------------------------------------------------------------+
  // | FILE: privacy.php                             CREATED: 05-04-2006 + 
  // | LOCATION: /code/include/classes/             MODIFIED: 05-04-2006 +
  // +-------------------------------------------------------------------+
  // | Copyright (c) 2004-2008 Appleseed Project                         |
  // +-------------------------------------------------------------------+
  // | This program is free software; you can redistribute it and/or     |
  // | modify it under the terms of the GNU General Public License       |
  // | as published by the Free Software Foundation; either version 2    |
  // | of the License, or (at your option) any later version.            |
  // |                                                                   |
  // | This program is distributed in the hope that it will be useful,   |
  // | but WITHOUT ANY WARRANTY; without even the implied warranty of    |
  // | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the     |
  // | GNU General Public License for more details.                      |	
  // |                                                                   |
  // | You should have received a copy of the GNU General Public License |
  // | along with this program; if not, write to:                        |
  // |                                                                   |
  // |   The Free Software Foundation, Inc.                              |
  // |   59 Temple Place - Suite 330,                                    | 
  // |   Boston, MA  02111-1307, USA.                                    |
  // |                                                                   |
  // |   http://www.gnu.org/copyleft/gpl.html                            |
  // +-------------------------------------------------------------------+
  // | AUTHORS: Michael Chisari <michael.chisari@gmail.com>              |
  // +-------------------------------------------------------------------+
  // | VERSION:      0.7.9                                               |
  // | DESCRIPTION:  Privacy class definition.                           |
  // +-------------------------------------------------------------------+

  // Privacy class.
  class cPRIVACYCLASS extends cDATACLASS {
    var $Error;
    var $Errorlist;
    var $Message;
    var $Result;
    var $PageContext;
    var $TableName;
    var $LastIncrement;
    var $FieldNames;
    var $FieldCount;
    var $ErrorList;
    var $FieldDefinitions;
    var $PrimaryKey;
    var $ForeignKey;

    var $friendCircles_sID;
    var $Access;

    // Determine the privacy level of the authorized user.
    function Determine ($pLOCKUSER, $pKEYUSER, $pFOREIGNKEY, $pFOREIGNVAL) {

      // Owner of section being viewed.
      global $$pLOCKUSER;

      // User attempting to view section.
      global $$pKEYUSER;
      
      global $gTABLEPREFIX;

      $ptable = $this->TableName;
      $UserAccounts = $gTABLEPREFIX . "UserAccounts";
      $friendCircles = $gTABLEPREFIX . "friendCircles";
      $friendCirclesList = $gTABLEPREFIX . "friendCirclesList";
      $friendInfo = $gTABLEPREFIX . "FriendInformation";

      if ($$pKEYUSER->Anonymous) {
        // Anonymous user
        $query = "SELECT   MIN(" . $ptable . ".Access) AS FinalAccess " .
                 "FROM     " . $ptable . ", $UserAccounts  " .
                 "         WHERE    " . $ptable . ".Account_FK = $UserAccounts.Account_PK " .
                 "         AND      $UserAccounts.Account_PK= " . $$pLOCKUSER->Account_PK .
                 "         AND      " . $ptable . ".friendCircles_sID = " . USER_EVERYONE .
                 "         AND      " . $ptable . "." . $pFOREIGNKEY . " = " . $pFOREIGNVAL;

      } else {
        // Logged in User
        $query = "SELECT   MIN(" . $ptable . ".Access) AS FinalAccess " .
                 "FROM     " . $ptable . ", $UserAccounts,  " .
                 "         $friendCircles, $friendCirclesList, $friendInfo " .
                 "         WHERE    " . $ptable . ".Account_FK = $UserAccounts.Account_PK " .
                 "         AND      $friendCircles.Account_FK = $UserAccounts.Account_PK " .
                 "         AND      $UserAccounts.Account_PK= " . $$pLOCKUSER->Account_PK .
                 "         AND      " . $ptable . ".friendCircles_sID = $friendCircles.sID  " .
                 "         AND      " . $ptable . "." . $pFOREIGNKEY . " = " . $pFOREIGNVAL .
                 "         AND      $friendCircles.tID = $friendCirclesList.friendCircles_tID " .
                 "         AND      $friendInfo.Username = '" . $$pKEYUSER->Username . "'" .
                 "         AND      $friendInfo.Friend_PK = $friendCirclesList.friendInformation_tID";

      } // if

      // Select privacy settings.
      $this->Query ($query);
      $this->FetchArray ();

      $result = $this->FinalAccess;

      // If no result was returned, user is not a friend.
      if ($result == NULL) {
        $query = "SELECT   MIN(" . $ptable . ".Access) AS FinalAccess " .
                 "FROM     " . $ptable . ", $UserAccounts,  " .
                 "         $friendCircles, $friendCirclesList, $friendInfo " .
                 "         WHERE    " . $ptable . ".Account_FK = $UserAccounts.Account_PK " .
                 "         AND      $friendCircles.Account_FK = $UserAccounts.Account_PK " .
                 "         AND      $UserAccounts.Account_PK= " . $$pLOCKUSER->Account_PK .
                 "         AND      (" . $ptable . ".friendCircles_sID = " . USER_LOGGEDIN .
                 "         OR       " . $ptable . ".friendCircles_sID = " . USER_EVERYONE . ") " .
                 "         AND      " . $ptable . "." . $pFOREIGNKEY . " = " . $pFOREIGNVAL .
                 "         AND      $friendCircles.tID = $friendCirclesList.friendCircles_tID " .
                 "         AND      $friendInfo.Friend_PK = $friendCirclesList.friendInformation_tID";
        // Select privacy settings.
        $this->Query ($query);
        $this->FetchArray ();
  
        $result = $this->FinalAccess;

      } // if 

      return ($result);

    } // Determine

    function BufferOptions ($pREFERENCEFIELD, $pREFERENCEID, $pSTYLE) {

      global $zFOCUSUSER, $zHTML, $zOLDAPPLE;
      global $gFRAMELOCATION;

      global $gPRIVACYSTYLE;
 
      $gPRIVACYSTYLE = $pSTYLE;

      $returnbuffer = "";

      // Create the privacy options list.
      $returnbuffer = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/common/privacy.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      // Everyone
      $privacycriteria = array ("Account_FK"    => $zFOCUSUSER->Account_PK,
                                "friendCircles_sID" => 1000,
                                $pREFERENCEFIELD  => $pREFERENCEID);

      $this->SelectByMultiple ($privacycriteria);
      $this->FetchArray ();
      $returnbuffer .= $this->PrivacyOptions ( __("Everyone"), 1000, $this->Access, PRIVACY_RESTRICT); 

      // Logged in users.
      $privacycriteria = array ("Account_FK"    => $zFOCUSUSER->Account_PK,
                                "friendCircles_sID" => 2000,
                                $pREFERENCEFIELD  => $pREFERENCEID);
                                
      $this->SelectByMultiple ($privacycriteria);
      $this->FetchArray ();
      $returnbuffer .= $this->PrivacyOptions ( __("Logged In Users"), 2000, $this->Access, PRIVACY_SCREEN); 

      $CIRCLES = new cFRIENDCIRCLES;

      // Loop through friend circles list.
      $CIRCLES->Select ("Account_FK", $zFOCUSUSER->Account_PK);
      while ($CIRCLES->FetchArray () ) {
        $privacycriteria = array ("Account_FK"    => $zFOCUSUSER->Account_PK,
                                  "friendCircles_sID" => $CIRCLES->sID,
                                  $pREFERENCEFIELD  => $pREFERENCEID);
                                
        $this->Access = NULL;
        $this->SelectByMultiple ($privacycriteria);
        $this->FetchArray();
        $returnbuffer .= $this->PrivacyOptions ($CIRCLES->Name, $CIRCLES->sID, $this->Access, PRIVACY_ALLOW); 
      } // while

      $returnbuffer .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/common/privacy.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      return ($returnbuffer);

    } // BufferOptions

    // Output the privacy options list row.
    function PrivacyOptions ($pLABEL, $pCIRCLESID, $pSELECTED, $pDEFAULT = PRIVACY_ALLOW) {
      global $gCIRCLESID, $gLABEL, $gSELECTED;
      global $gFRAMELOCATION;

      global $gPRIVACYALLOW, $gPRIVACYSCREEN, $gPRIVACYRESTRICT, $gPRIVACYBLOCK, $gPRIVACYHIDE;
      $gPRIVACYALLOW = FALSE; $gPRIVACYSCREEN = FALSE;
      $gPRIVACYRESTRICT = FALSE; $gPRIVACYBLOCK = FALSE;
      $gPRIVACYHIDE = FALSE;

      global $zOLDAPPLE;

      switch ($pDEFAULT) {
        case PRIVACY_SCREEN:
          $gPRIVACYSCREEN = TRUE;
        break;

        case PRIVACY_RESTRICT:
          $gPRIVACYRESTRICT = TRUE;
        break;

        case PRIVACY_BLOCK:
          $gPRIVACYBLOCK = TRUE;
        break; 

        case PRIVACY_HIDE:
          $gPRIVACYHIDE = TRUE;
        break; 

        case PRIVACY_ALLOW:
        default:
          $gPRIVACYALLOW = TRUE;
        break; 

      } // switch

      $gSELECTED = $pSELECTED;
      $gLABEL = $pLABEL;
      $gCIRCLESID = $pCIRCLESID;

      $return = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/common/privacy.middle.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      unset ($gCIRCLESID);
      unset ($gLABEL);

      unset ($gPRIVACYALLOW); unset ($gPRIVACYSCREEN); 
      unset ($gPRIVACYRESTRICT); unset ($gPRIVACYBLOCK); 

      return ($return);
    } // PrivacyOptions

    function SaveSettings ($pPRIVACY, $pREFERENCEFIELD, $pREFERENCEID) {

      global $zFOCUSUSER;

      $TARGETDATA = new cDATACLASS;
      $TARGETDATA->TableName = $this->TableName;

      // Update the privacy settings.
      foreach ($pPRIVACY as $sID => $Access) {
        $this->friendCircles_sID = $sID;

        $this->friendCircles_sID = $sID;
        $this->$pREFERENCEFIELD = $pREFERENCEID;
        $this->Account_FK = $zFOCUSUSER->Account_PK;
        $this->Access = $Access;

        //Find the table ID of the exact record we're updating.
        $targetcriteria = array ("Account_FK"      => $zFOCUSUSER->Account_PK,
                                 $pREFERENCEFIELD    => $pREFERENCEID,
                                 "friendCircles_sID" => $sID);

        $TARGETDATA->SelectByMultiple ($targetcriteria);
        $TARGETDATA->FetchArray ();
        $this->tID = $TARGETDATA->tID;

        // Check whether we're updating or adding a record.
        if ($TARGETDATA->CountResult () > 0) {
          $this->Update ();
        } else {
          $this->Add ();
        } // if

      } // foreach

      unset ($TARGETDATA);

      return (TRUE);

    } // SaveSettings

  } // cPRIVACYCLASS

?>
