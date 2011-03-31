<?php
  // +-------------------------------------------------------------------+
  // | Appleseed Web Community Management Software                       |
  // | http://appleseed.sourceforge.net                                  |
  // +-------------------------------------------------------------------+
  // | FILE: base.php                                CREATED: 02-25-2005 + 
  // | LOCATION: /code/include/classes/BASE/        MODIFIED: 05-05-2008 +
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
  // | Part of the Appleseed BASE API                                    |
  // | VERSION:      0.7.9                                               |
  // | DESCRIPTION:  Base class definitions. Reusable functions not      |
  // |               specifically tied to Appleseed.                     |
  // +-------------------------------------------------------------------+
 
  // Base data class that others extend from.
  class cBASEDATACLASS {
    var $Cache;
    var $Error;
    var $Errorlist;
    var $Message;
    var $Result;
    var $PageContext;
    var $Cascade;
    var $TableName;
    var $LastIncrement;
    var $FieldNames;
    var $FieldCount;
    var $FieldDefinitions;
    var $PrimaryKey;
    var $ForeignKey;
    var $Statement;

    function cBASEDATACLASS ($pDEFAULTCONTEXT = "", $pDEFAULTTABLENAME = "") {
      global $gTABLEPREFIX;

      $this->TableName = $gTABLEPREFIX . $pDEFAULTTABLENAME;
      $this->LastIncrement = '';
      $this->Error = '';
      $this->Message = '';
      $this->Result = '';
      $this->PageContext = '';
      $this->Cascade = '';
      $this->Errorlist = array ('' => '');
      $this->FieldNames = array (0 => '');
      $this->ErrorList  = array ('' => '');
      $this->FieldCount = 0;
      $this->PrimaryKey = 'tID';
      $this->ForeignKey = '';
      $this->Statement = NULL;

      // Assign context from paramater.
      $this->PageContext = $pDEFAULTCONTEXT;

      // Create extended field definitions.
      $this->FieldDefinitions = array ('' => '');

      return (TRUE);
    } // Constructor

    // Move data ID up or down in database.
    function Move ($pDIRECTION, $pFROMIDVALUE) {
      
      if ($pDIRECTION == UP) {
        // Can't move up anywhere from 1.
        if ($pFROMIDVALUE < 2) {
          // Pull page title from database settings
          $this->Message = __("Cannot move up");
          $this->Error = -1;
          $this->Rollback();
          return (0);
        } // if
        $toidvalue = $pFROMIDVALUE - 1;
      } else {
        $toidvalue = $pFROMIDVALUE + 1;
      } // if

      $keyname = $this->PrimaryKey;

      // Begin db transaction.
      $this->Begin ();

      // First move the 'to' value to 999999.    
      $this->Statement = "UPDATE $this->TableName SET " .
                         "$keyname = 999999 WHERE " .
                         "$keyname = $toidvalue";

      // Rollback and quit if error occurs.
      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        $this->Rollback();
        return (-1);
      } // if

      // Now move the 'from' value to the 'to' value.
      $this->Statement = "UPDATE $this->TableName SET " .
                   "$keyname = $toidvalue WHERE " .
                   "$keyname = $pFROMIDVALUE";

      // Rollback and quit if error occurs.
      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        $this->Rollback();
        return (-1);
      } // if

      // Now move the 'to' value back from 999999 to the 'from' value.
      $this->Statement = "UPDATE $this->TableName SET " .
                   "$keyname = $pFROMIDVALUE WHERE " .
                   "$keyname = 999999";

      // Rollback and quit if error occurs.
      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        $this->Rollback();
        return (-1);
      } // if

      // Commit db changes.
      $this->Commit ();

      return (0);

    } // Move

    // Move data ID up or down in database within certain parameters.
    function MoveWithin ($pDIRECTION, $pFROMIDVALUE, $pPRIMARYKEY, $pDEFINITIONS) {
      
      if ($pDIRECTION == UP) {
        // Can't move up anywhere from 1.
        if ($pFROMIDVALUE < 2) {
          // Pull page title from database settings
          $this->Message = __("Cannot Move Up");
          $this->Error = -1;
          $this->Rollback();
          return (0);
        } // if
        $toidvalue = $pFROMIDVALUE - 1;
      } else {
        $toidvalue = $pFROMIDVALUE + 1;
      } // if

      $keyname = $pPRIMARYKEY;

      $wherestatement = "AND ";

      // Loop through the definitions array.
      foreach ($pDEFINITIONS as $dkey => $dvalue) {

        $fname = mysql_real_escape_string ($dkey);
        $fval = mysql_real_escape_string ($dvalue);

        // Check if we're at the end of the definitions list.
        if ($def_count == $def_size) {
          $wherestatement .= "$fname = '$fval' ";
        } else {
          $wherestatement .= "$fname = '$fval' AND ";
        } // if

        $def_count++;
      } // foreach

      // Begin db transaction.
      $this->Begin ();

      // First move the 'to' value to 999999.    
      $this->Statement = "UPDATE $this->TableName SET " .
                   "$keyname = 999999 WHERE " .
                   "$keyname = $toidvalue $wherestatement";

      // Rollback and quit if error occurs.
      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        $this->Rollback();
        return (-1);
      } // if

      // Now move the 'from' value to the 'to' value.
      $this->Statement = "UPDATE $this->TableName SET " .
                   "$keyname = $toidvalue WHERE " .
                   "$keyname = $pFROMIDVALUE $wherestatement";

      // Rollback and quit if error occurs.
      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        $this->Rollback();
        return (-1);
      } // if

      // Now move the 'to' value back from 999999 to the 'from' value.
      $this->Statement = "UPDATE $this->TableName SET " .
                   "$keyname = $pFROMIDVALUE WHERE " .
                   "$keyname = 999999 $wherestatement";

      // Rollback and quit if error occurs.
      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        $this->Rollback();
        return (-1);
      } // if

      // Commit db changes.
      $this->Commit ();

      return (0);

    } // MoveWithin

    // Adjust a sort listing to start from 1.
    function AdjustSort ($pKEYNAME, $pFIELD, $pVALUE) {

      // Begin Transaction.
      $this->Begin ();

      // Select the listing and order by KEYNAME
      $lookup = new cBASEDATACLASS;
      $lookup->TableName = $this->TableName;
      $lookup->FieldNames = $this->FieldNames;
      $lookup->Select ($pFIELD, $pVALUE, $pKEYNAME);

      $change = new cBASEDATACLASS;
      $change->TableName = $this->TableName;
      $change->FieldNames = $this->FieldNames;

      $primarykey = $this->PrimaryKey;

      $count = 1;
      while ($lookup->FetchArray () ) {
        $change->Select ($primarykey, $lookup->$primarykey);
        $change->FetchArray ();
        $change->$pKEYNAME = $count;
        $change->Update ();
        $count++;
      } // while

      unset ($lookup);
      unset ($change);

      // Commit transaction.
      $this->Commit ();

      return (0);

    } // AdjustSort

    // Commit the database transaction.
    function Commit () {
      
      $this->Query ('COMMIT');
      
      return (TRUE);
    } // Commit

    // Begin the database transaction.
    function Begin () {
      
      $this->Query ('BEGIN');
      
      return (TRUE);
    } // Commit

    // Rollback the database transaction.
    function Rollback () {
      
      $this->Query ('ROLLBACK');
      
      return (TRUE);
    } // Commit

    // Count the number of rows in a table.
    function CountRows ($pWHERECLAUSE = '') {
      
      if ($pWHERECLAUSE) {
        $this->Statement = 'SELECT COUNT(*) FROM ' .  $this->TableName . 
                     ' WHERE ' . $pWHERECLAUSE;
      } else {
        $this->Statement = 'SELECT COUNT(*) FROM ' .  $this->TableName;
      } // if

      $query = $this->Query($this->Statement);
      $result = mysql_fetch_row($query);
      $rows = $result[0];

      return ($rows);
    } // CountRows

    // Syncs data from GET/POST headers to CLASS->Variables
    function Synchronize () {

      global $zHTML;

      foreach ($this->FieldNames as $fieldname) {
        if ($fieldname != $this->PrimaryKey) {
          switch ($this->FieldDefinitions[$fieldname]['datatype']) {
            case 'DATETIME':
            case 'DATE':
            case 'TIME':
              $varname = strtoupper ($fieldname);
              $this->$fieldname = $zHTML->joinDate ($varname);
            break;
            default:
              $varname = 'g' . strtoupper ($fieldname);
              global $$varname;
              $this->$fieldname = $$varname;
            break;
          } // switch
        } else {
          $varname = 'g' . $this->PrimaryKey;
          global $$varname;
          $this->$fieldname = $$varname;
        } // if
      } // foreach

    } // Synchronize

    // Pull fieldnames from database and put into FieldNames array.
    function Fields () {

      global $zCACHE, $zDEBUG;

      if (isset ($zCACHE->ColumnCache[$this->TableName])) {
        // Cache is already set, pull from cache.
        $this->FieldDefinitions = $zCACHE->ColumnCache[$this->TableName];
        $this->FieldNames = $zCACHE->FieldCache[$this->TableName];
      } else {
        $fieldstatement = "SHOW CREATE TABLE $this->TableName";
        
        $fieldresult = $this->Query($fieldstatement);
        $fieldcount = 0;
  
        // Check if successful.
        if (!$fieldresult) {
          $this->Error = -1;
          $this->Message = mysql_error();
          return (-1);
        }
        // Retrieve the create table data.
        $fieldarray = mysql_fetch_assoc ($fieldresult);
  
        // Retrieve the create information.
        $create = $fieldarray['Create Table'];
  
        // NOTE: Regular expressions would probably work better here.
  
        // Retrieve the list of foreign keys.
        $keystring = explode ('CONSTRAINT ', $create);
  
        // Get rid of the create information we don't need.
        unset ($keystring[0]);
  
        // Reverse the array to preference the first definitions.
        $keystring = array_reverse ($keystring);
  
        foreach ($keystring as $keycount => $constraint) {
          $foreignlist = explode ('FOREIGN KEY', $keystring[$keycount]);
          $references = explode ('REFERENCES ', $foreignlist[1]);
  
          $keynames = $references[0];
  
          $keynames = str_replace ('(`', '', $keynames);
          $keynames = str_replace ('`)', '', $keynames);
          $keynames = str_replace ('`', '', $keynames);
          $keynames = str_replace (' ', '', $keynames);
  
          $keylist = explode (',', $keynames);
  
          // Retrieve their references.
          $tablenames = preg_split ('/` \(`/', $references[1]);
          $tablestring = $tablenames[0];
          $tablestring = str_replace ('`', '', $tablestring);
          $tablestring = str_replace ('`)', '', $tablestring);
  
          // List of Tables
          $tablelist = explode (', ', $tablestring);
  
          // Retrieve their reference fields.
          $fieldnames = explode (' ON DELETE', $tablenames[0]);
          $fieldstring = $fieldnames[0];
          $fieldstring = str_replace ('`', '', $fieldstring);
          $fieldstring = str_replace (')', '', $fieldstring);
  
          // List of Tables
          $fieldlist = explode (', ', $fieldstring);
          
          // Loop through the key list.
          foreach ($keylist as $kcount => $kkey) {
  
            $this->FieldDefinitions[$kkey]['foreign'] = TRUE;
            $this->FieldDefinitions[$kkey]['foreigntable'] = $tablelist[0];
            $this->FieldDefinitions[$kkey]['foreignfield'] = $fieldlist[$kcount];
            
          } // foreach

        } // foreach

        // Unset the NULL item that keeps popping in there.
        unset ($this->FieldDefinitions['']);
  
        // Retrieve a list of field names from the table.
        $fieldstatement = "SHOW COLUMNS FROM $this->TableName";
      
        $fieldresult = $this->Query($fieldstatement);
        $fieldcount = 0;

        // Check if successful.
        if (!$fieldresult) {
          $this->Error = -1;
          $this->Message = mysql_error();
          return (-1);
        }

        // Use this to check for the first string value;
        $foundstring = FALSE;

        // Loop through the retrieved list.
        while ($fieldarray = mysql_fetch_assoc ($fieldresult)) {

          $fieldname = $fieldarray['Field'];
          // Check if this field has already been defined.
          if (isset($this->FieldDefinitions[$fieldname])) {
            // Assume user defined types are correct, unless unassigned.
  
            // Check if the NULL value was not assigned.
            if (!isset($this->FieldDefinitions[$fieldname]['null'])) {
              if ($fieldarray['Null'] == 'YES') 
                $this->FieldDefinitions[$fieldname]['null'] = YES;
              else 
                $this->FieldDefinitions[$fieldname]['null'] = NO;
            } // if
  
            // Check if the DATATYPE value was not assigned.
            if (!isset($this->FieldDefinitions[$fieldname]['datatype'])) {
              // Determine the type and size of the field.
              $ftype = $fieldarray['Type'];
              $finaltype = preg_replace ("/\(\w+\)/", "", $ftype);
              $finaltype = preg_replace ("/ unsigned/", "", $finaltype);
    
              switch ($finaltype) {
    
                case 'text':
                  $this->FieldDefinitions[$fieldname]['datatype'] = "STRING";
                break;
    
                case 'char':
                case 'varchar':
                  $this->FieldDefinitions[$fieldname]['datatype'] = "STRING";
                break;
    
                case 'float':
                  $this->FieldDefinitions[$fieldname]['datatype'] = "FLOAT";
                break;
    
                case 'datetime':
                  $this->FieldDefinitions[$fieldname]['datatype'] = "DATETIME";
                break;
    
                default:
                  $this->FieldDefinitions[$fieldname]['datatype'] = "INTEGER";
                break;
    
              } // switch
            } // if
  
            // Check if field is a primary key.
            if ($fieldarray['Key'] == 'PRI') {
              $this->FieldDefinitions[$fieldname]['primary'] = TRUE;
            } else {
              $this->FieldDefinitions[$fieldname]['primary'] = FALSE;
            } // if
  
          } else {
            // Assign values according to database.
  
            // Check if NULL values are allowed.
            if ($fieldarray['Null'] == 'YES') 
              $this->FieldDefinitions[$fieldname]['null'] = YES;
            else 
              $this->FieldDefinitions[$fieldname]['null'] = NO;

            // Determine the type and size of the field.
            $ftype = $fieldarray['Type'];
            $finaltype = preg_replace ("/\(\w+\)/", "", $ftype);
            $finaltype = preg_replace ("/ unsigned/", "", $finaltype);
  
            switch ($finaltype) {
  
              case 'text':
                $this->FieldDefinitions[$fieldname]['datatype'] = "STRING";
                $this->FieldDefinitions[$fieldname]['max'] = 65536;
                $this->FieldDefinitions[$fieldname]['min'] = 0;
              break;
  
              case 'char':
              case 'varchar':
                if (!$foundstring) {
                  $this->PrimaryIdentifier = $fieldname;
                  $foundstring = TRUE;
                } // if
                $this->FieldDefinitions[$fieldname]['datatype'] = "STRING";
                preg_match ("/([0-9]+)/", $ftype, $matches);
                $this->FieldDefinitions[$fieldname]['max'] = $matches[0];
                $this->FieldDefinitions[$fieldname]['min'] = 0;
              break;
  
              case 'float':
                $this->FieldDefinitions[$fieldname]['datatype'] = "FLOAT";
                preg_match ("/([0-9]+)/", $ftype, $matches);
                $this->FieldDefinitions[$fieldname]['max'] = $matches[0];
                $this->FieldDefinitions[$fieldname]['min'] = 0;
              break;
  
              case 'datetime':
                $this->FieldDefinitions[$fieldname]['datatype'] = "DATETIME";
              break;
  
              default:
                $this->FieldDefinitions[$fieldname]['datatype'] = "INTEGER";
                preg_match ("/([0-9]+)/", $ftype, $matches);
                // NOTE:
                $maximum = pow ($matches[0], 10);
                // $maximum = $maximum ^ 10;
                $this->FieldDefinitions[$fieldname]['max'] = $maximum;
                $this->FieldDefinitions[$fieldname]['min'] = 0;
              break;
  
            } // switch
  
            // Check if field is a primary key.
            if ($fieldarray['Key'] == 'PRI') {
              $this->FieldDefinitions[$fieldname]['primary'] = TRUE;
            } else {
              $this->FieldDefinitions[$fieldname]['primary'] = FALSE;
            } // if
  
          } // if 
  

          $this->FieldNames[$fieldcount] = $fieldname;
          $fieldcount++;
          $zCACHE->ColumnCache[$this->TableName][$fieldname] = $this->FieldDefinitions[$fieldname];
        } // while
        $this->FieldCount = $fieldcount;
        $zCACHE->FieldCache[$this->TableName] = $this->FieldNames;
        $zCACHE->FieldCount[$this->TableName] = $this->FieldCount;
      } // if
   
      return (0);

    } // Fields

    // Update a record in the table.
    function Update ($pUPDATEKEY = NULL, $pUPDATEVALUE = NULL) {

      global $gDEBUG;

      // Step through the results and create the final string.
      $statement_left = "UPDATE $this->TableName SET ";
      $statement_right = ""; 
      $totalrows = count ($this->FieldNames);

      // Loop through the table fieldnames.
      foreach ($this->FieldNames as $fieldcount => $fieldname) {
 
        // If the value is set to SKIP or field is calculated then skip it.
        if ( ($this->$fieldname == SQL_SKIP) or ($this->FieldDefinitions[$fieldname]['relation'] == 'calculated' ) ) {

          // If we're at the end, remove the last comma from the string 
          if ($fieldcount == $totalrows - 1) {
            $statementsize = strlen ($statement_left);
            $statement_left = substr ($statement_left, 0, $statementsize - 2);
            $statement_left .= " ";
          } // if

          // Continue through the loop.
          continue;
        } // if

        // Check if field is a password field
        if (strtoupper($this->FieldDefinitions[$fieldname]['relation']) == 'PASSWORD') {
          $salt = substr(md5(uniqid(rand(), true)), 0, 16);
          $sha512 = hash ("sha512", $salt . $this->$fieldname);
          $newpass = $salt . $sha512;
          $queryfield = "'" . mysql_real_escape_string ($newpass) . "'"; 
        } else {
          $queryfield = "'" . mysql_real_escape_string ($this->$fieldname) . "'";
        } // if

        // If specified, set the time stamp to NOW().
        if ($this->$fieldname == SQL_NOW) {
          $queryfield = "NOW()";
        } // if

        // If specified, set the field to NULL.
        if ($this->$fieldname == NULL) {
          $queryfield = "NULL";
        } // if

        // If field is not a primary key, then put it in the query.
        if ($fieldname != $this->PrimaryKey) {
          if ($fieldcount < $totalrows - 1) {
            $statement_left .= $fieldname . " = " . $queryfield . ", ";
          } else {
            $statement_left .= $fieldname . " = " . $queryfield . " ";
          } // if
        } // if
        $fieldcount++;
      } // foreach

      if ($pUPDATEKEY) {
        $primarykey = $pUPDATEKEY;
        $primarykey_val = $pUPDATEVALUE;
      } else {
        $primarykey = $this->PrimaryKey;
        $primarykey_val = $this->$primarykey;
      } // if
      $statement_right .= "WHERE $primarykey = '$primarykey_val'";
      $this->Statement = $statement_left . $statement_right;

      if ($gDEBUG['echostatement'] == TRUE) {
        echo "<!-- SQL: $this->Statement -->\n";
      } // if

      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        return (-1);
      } // if

      return (0);
    } // Update

    // Add a record to the table.
    function Add () {
      
      global $gDEBUG;

      // Retrieve a list of field names from the table.
      $fieldstatement = "SHOW COLUMNS FROM $this->TableName";
      
      $fieldresult = $this->Query($fieldstatement);

      // Check if successful.
      if (!$fieldresult) {
        $this->Error = -1;
        $this->Message = mysql_error();
        return (-1);
      } // if

      // Step through the results and create the final string.
      $statement_left = "INSERT INTO $this->TableName (";
      $statement_right = "VALUES (";
      $totalrows = mysql_num_rows ($fieldresult);
      $fieldcount = 1;

      if ($this->PrimaryKey == $this->ForeignKey) {
        $primarykey = $this->PrimaryKey;
        $primarykeyval = $this->$primarykey;
        $statement_left .= $primarykey . ", ";
        $statement_right .= $primarykeyval . ", ";
      } // if 
 
      if ($totalrows > 0) {
        while ($row = mysql_fetch_assoc($fieldresult)) {
          $fieldname = $row['Field'];

          // Create a string type-casted variable for comparison.
          $comparevalue = (string)$this->$fieldname;

          // If the value is set to SQL_SKIP then skip it.
          if ($comparevalue == SQL_SKIP) {

            // If it's a date, then set it to 'NOW()';
            if (strtoupper($this->FieldDefinitions[$fieldname]['datatype']) == 'DATETIME') {
              $statement_left .= $fieldname . ", ";
              $statement_right .= "NOW(), ";
            } // if

            // If we're at the end, remove the last comma from the string 
            if ($fieldcount >= $totalrows) {
              $statementsize = strlen ($statement_left);
              $statement_left = substr ($statement_left, 0, $statementsize - 2);
              $statement_left .= ") ";
              $statementsize = strlen ($statement_right);
              $statement_right = substr ($statement_right, 0, $statementsize - 2);
              $statement_right .= ") ";
            } // if

            $fieldcount++;
            continue;
          } // if

          // Check if field is a password field
          if (strtoupper($this->FieldDefinitions[$fieldname]['relation']) == 'PASSWORD') {
            $salt = substr(md5(uniqid(rand(), true)), 0, 16);
            $sha512 = hash ("sha512", $salt . $this->$fieldname);
            $newpass = $salt . $sha512;
            $queryfield = "'" . mysql_real_escape_string ($newpass) . "'"; 
          } else {
            $queryfield = "'" . mysql_real_escape_string ($this->$fieldname) . "'";
          } // if

          // If specified, set the time stamp to NOW().
          if ($comparevalue == SQL_NOW) {
            $queryfield = "NOW()";
          } // if

          // If specified, set the field to NULL.
          if ($comparevalue == NULL) {
            $queryfield = "NULL";
          } // if

          if ($fieldname != $this->PrimaryKey) {
            if ($fieldcount < $totalrows) {
              $statement_left .= $fieldname . ", ";
              stripslashes ($this->$fieldname);
              $statement_right .= $queryfield .  ", ";
            } else {
              stripslashes ($this->$fieldname);
              $statement_right .= $queryfield .  ")";
              $statement_left .= $fieldname . ") ";
            } // if
          } // if
          $fieldcount++;
        } // while
      } // if

      // Join the full statement together.
      $this->Statement = $statement_left . $statement_right;

      // Cascade through internal classes and Add a record.

      if ($gDEBUG['echostatement'] == TRUE) {
        echo "<!-- SQL: $this->Statement -->\n";
      } // if

      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        return (-1);
      } // if

      // Grab the Account_PK of the recently created UserAccounts record.
      $this->AutoIncremented();

      $this->Account_PK = $this->LastIncrement;

      if ( (is_array ($this->Cascade) ) and (isset ($this->Cascade) ) ) {

        foreach ($this->Cascade as $internal) {
          $this->$internal->Account_FK = $this->Account_PK;
          foreach ($this->$internal->FieldNames as $fieldname) {
            // If the data is blank, skip over it to use the default database value.
            if ($this->$internal->$fieldname == '') $this->$internal->$fieldname = SQL_SKIP;
          } // foreach
          $this->$internal->Add ();
        } // foreach
     } // if

      return (0);
    } // Add

    // Send a direct query to the database.
    function Query ($pSTATEMENT) {
      global $zDEBUG;
      global $gDEBUG;
      
      if ($gDEBUG['echostatement'] == TRUE) {
        echo "\n<!-- SQL: $pSTATEMENT -->\n";
      } // if

      $zDEBUG->BenchmarkStart('STATEMENT');

      // Submit the query
      if ($this->Result = mysql_query($pSTATEMENT)) {
        $zDEBUG->BenchmarkStop('STATEMENT');
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
      } // if
      
      // Add SQL statement to DEBUG list.
      $zDEBUG->RememberStatement ($pSTATEMENT, get_class ($this), $zDEBUG->Benchmark('STATEMENT'));
      
      $this->Statement = $pSTATEMENT;

      return ($this->Result);
    } // Query

    // Select based on one field/value pair.
    function Select ($pFIELD = "", $pVALUE = "", $pORDERBY = "", $pLIKE = NULL) {
      
      if ( (is_array ($this->Cascade) ) and (isset ($this->Cascade) ) ) {
        $return = $this->CascadeSelect ($pFIELD, $pVALUE, $pORDERBY, $pLIKE);
        return ($return);
      } // if

      global $gDEBUG;

      $this->Statement = "";
      if ($pFIELD == "") {
        $this->Statement = "SELECT * FROM $this->TableName";
      } else {
        $fname = mysql_real_escape_string ($pFIELD);
        if ($pLIKE) {
          $this->Statement = "SELECT * FROM $this->TableName " .
                       "WHERE $fname like '%$pVALUE%'";
        } else {
          $this->Statement = "SELECT * FROM $this->TableName " .
                       "WHERE $fname = '$pVALUE'";
        } // if
      } // if


      if ($pORDERBY) {
        $this->Statement .= " ORDER BY " . $pORDERBY;
      } // if
      
      if ($gDEBUG['echostatement'] == TRUE) {
        echo "<!-- SQL: $this->Statement -->\n";
      } // if

      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        return (-1);
      } // if

      return (0);

    } // Select 
    
    // Create a JOIN statement to select cascading tables.
    function CascadeSelect ($pFIELD = "", $pVALUE = "", $pORDERBY = "", $pLIKE = "") {
  
      $thistable = $this->TableName;
      
      foreach ($this->FieldNames as $fieldname) {
        $fieldarray[] = $this->TableName . '.' . $fieldname . ' AS ' . $this->TableName . '__' . $fieldname;
      } // foreach
      
      foreach ($this->Cascade as $cascadeclass) {
        foreach ($this->$cascadeclass->FieldNames as $fieldname) {
          $fieldarray[] = $this->$cascadeclass->TableName . '.' . $fieldname . ' AS ' . $this->$cascadeclass->TableName . '__' . $fieldname;
        } // foreach
        
        $joinarray[] = "LEFT JOIN " .  $this->$cascadeclass->TableName . 
                       " ON " . $this->$cascadeclass->TableName . ".Account_FK=$thistable.Account_PK ";
      } // foreach
      
      $fields = join (", \n", $fieldarray);
      $leftjoin = join ("\n", $joinarray);
      
      if ($pFIELD) {
        if ($pLIKE) {
          $where = "WHERE $thistable.$pFIELD like '%$pVALUE%'";
        } else {
          $where = "WHERE $thistable.$pFIELD = '$pVALUE'";
        } // if
      } else {
        $where = "GROUP BY $thistable.Account_PK";
      } // if
      
      $this->Statement = "
        SELECT $fields
        FROM $thistable
        $leftjoin
        $where
      ";
      
      if ($pORDERBY) {
        $this->Statement .= " ORDER BY " . $pORDERBY;
      } // if
      
      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        return (-1);
      } // if
      
      return (TRUE);
    } // CascadeSelect
    
    // Select using a custom Where clause.
    function SelectWhere ($pWHERECLAUSE, $pORDERBY = "") {

      if ( (is_array ($this->Cascade) ) and (isset ($this->Cascade) ) ) {
        $return = $this->CascadeSelectWhere ($pWHERECLAUSE, $pORDERBY);
        return ($return);
      } // if
      
      global $gDEBUG;

      $this->Statement = "SELECT * FROM $this->TableName WHERE " . $pWHERECLAUSE;

      if ($pORDERBY) {
       $this->Statement .= " ORDER BY " . $pORDERBY;
      } // if

      if ($gDEBUG['echostatement'] == TRUE) {
        echo "<!-- SQL: $this->Statement -->\n";
      } // if

      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        return (-1);
      } // if

      return (0);

    } // SelectWhere
    
    function CascadeSelectWhere ($pWHERECLAUSE, $pORDERBY = "") {
  
      $thistable = $this->TableName;
      
      foreach ($this->FieldNames as $fieldname) {
        $fieldarray[] = $this->TableName . '.' . $fieldname . ' AS ' . $this->TableName . '__' . $fieldname;
      } // foreach
      
      foreach ($this->Cascade as $cascadeclass) {
        foreach ($this->$cascadeclass->FieldNames as $fieldname) {
          $fieldarray[] = $this->$cascadeclass->TableName . '.' . $fieldname . ' AS ' . $this->$cascadeclass->TableName . '__' . $fieldname;
        } // foreach
        
        $joinarray[] = "LEFT JOIN " .  $this->$cascadeclass->TableName . 
                       " ON " . $this->$cascadeclass->TableName . ".Account_FK=$thistable.Account_PK ";
      } // foreach
      
      $fields = join (", \n", $fieldarray);
      $leftjoin = join ("\n", $joinarray);
      
      $this->Statement = "
        SELECT $fields
        FROM $thistable
        $leftjoin
        WHERE $pWHERECLAUSE
      ";
      
      if ($pORDERBY) {
        $this->Statement .= " ORDER BY " . $pORDERBY;
      } // if
      
      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        return (-1);
      } // if
      
      return (TRUE);
    } // CascadeSelectWhere

    // Search table using all fields as criteria.
    function SelectByAll ($pCRITERIA = "", $pORDERBY = "", $pLIKE = "") {

      if ( (is_array ($this->Cascade) ) and (isset ($this->Cascade) ) ) {
        $return = $this->CascadeSelectByAll ($pCRITERIA, $pORDERBY, $pLIKE);
        return ($return);
      } // if
      
      $finaldef = array ();

      // Loop through the field names and create the where clause.
      foreach ($this->FieldNames as $fieldname) {
          $finaldef[$fieldname] = $pCRITERIA;
      } // foreach
      
      $resulting = $this->SelectByMultiple ($finaldef, $pORDERBY, $pLIKE, "OR");

      return ($resulting);
      
    } // SelectByAll
    
    function CascadeSelectByAll ($pCRITERIA = "", $pORDERBY = "", $pLIKE = "") {

      $finaldef = array ();

      // Loop through the field names and create the where clause.
      foreach ($this->Cascade as $tablename) {
        foreach ($this->FieldNames as $fieldname) {
           $finalfieldname = $this->TableName . '.' . $fieldname;
       	   $finaldef[$finalfieldname] = $pCRITERIA;
        } // foreach
      } // foreach
      
      $resulting = $this->SelectByMultiple ($finaldef, $pORDERBY, $pLIKE, "OR");

      return ($resulting);
      
    } // CascadeSelectByAll
    

    function SelectByMultiple ($pDEFINITIONS, $pORDERBY = "", $pLIKE = "", $pANDOR = "AND") {

      global $gDEBUG;
      
      if ( (is_array ($this->Cascade) ) and (isset ($this->Cascade) ) ) {
        $return = $this->CascadeSelectByMultiple ($pDEFINITIONS, $pORDERBY, $pLIKE, $pANDOR);
        return ($return);
      } // if

      $this->Statement = "SELECT * FROM $this->TableName WHERE ";

      // Find out how many definitions are listed.
      $def_size = sizeof ($pDEFINITIONS);
      $def_count = 1;

      // Loop through the definitions array.
      foreach ($pDEFINITIONS as $dkey => $dvalue) {

        $fname = mysql_real_escape_string ($dkey);
        $fval = mysql_real_escape_string ($dvalue);

        $equals = "=";

        switch (substr ($fval, 0, 2)) {
          case SQL_NOT:
            $fval = substr ($fval, 2, strlen ($fval) - 2);
            $equals = "<>";
          break;
          case SQL_GT:
            $fval = substr ($fval, 2, strlen ($fval) - 2);
            $equals = ">";
          break;
          case SQL_LT:
            $fval = substr ($fval, 2, strlen ($fval) - 2);
            $equals = "<";
          break;
          case SQL_LIKE:
            $fval = substr ($fval, 2, strlen ($fval) - 2);
            $equals = " LIKE ";
          break;
        } // switch

        if (substr ($fval, 0, 2) == SQL_NOT) {
          $fval = substr ($fval, 2, strlen ($fval) - 2);
          $equals = "<>";
        } // if
        
        $noquote = FALSE;
        // If specified, set the time stamp to NOW().
        if ($fval == SQL_NOW) {
          $fval = "NOW()";
          $noquote = TRUE;
        } // if

        // If specified, set the field to NULL.

        // Check if we're at the end of the definitions list.
        if ($def_count == $def_size) {

          // Append the 'like' statement, if available.
          if ($pLIKE) {
            $this->Statement .= "$fname LIKE '%$fval%' ";
          } else {
            if ($noquote) {
              $this->Statement .= "$fname $equals $fval ";
            } else {
              $this->Statement .= "$fname $equals '$fval' ";
            } // if
          } // if
          
        } else {

          // Append the 'like' statement, if available.
          if ($pLIKE) {
            $this->Statement .= "$fname LIKE '%$fval%' $pANDOR ";
          } else {
            $this->Statement .= "$fname $equals '$fval' $pANDOR ";
          } // if

        } // if

        $def_count++;
      } // foreach

      // Append the order by/sort information.
      if ($pORDERBY) {
        $this->Statement .= " ORDER BY " . $pORDERBY;
      } // if

      if ($gDEBUG['echostatement'] == TRUE) {
        echo "<!-- SQL: $this->Statement -->\n";
      } // if

      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        return (-1);
      } // if

      return (0);
    } // SelectByMultiple

    function CascadeSelectByMultiple ($pDEFINITIONS, $pORDERBY = "", $pLIKE = "", $pANDOR = "AND") {

      global $gDEBUG;
      
      $thistable = $this->TableName;
      
      foreach ($this->Cascade as $cascadeclass) {
        foreach ($this->$cascadeclass->FieldNames as $fieldname) {
          $fieldarray[] = $this->$cascadeclass->TableName . '.' . $fieldname . ' AS ' . $this->$cascadeclass->TableName . '__' . $fieldname;
        } // foreach
        
        $joinarray[] = "LEFT JOIN " .  $this->$cascadeclass->TableName . 
                       " ON " . $this->$cascadeclass->TableName . ".Account_FK=$thistable.Account_PK ";
      } // foreach
      
      $fields = join (", \n", $fieldarray);
      $leftjoin = join ("\n", $joinarray);
      
      $this->Statement = "
        SELECT $fields
        FROM $thistable
        $leftjoin
        WHERE 
      ";
      
      // Find out how many definitions are listed.
      $def_size = sizeof ($pDEFINITIONS);
      $def_count = 1;

      // Loop through the definitions array.
      foreach ($pDEFINITIONS as $dkey => $dvalue) {

        $fname = mysql_real_escape_string ($dkey);
        $fval = mysql_real_escape_string ($dvalue);

        $equals = "=";

        switch (substr ($fval, 0, 2)) {
          case SQL_NOT:
            $fval = substr ($fval, 2, strlen ($fval) - 2);
            $equals = "<>";
          break;
          case SQL_GT:
            $fval = substr ($fval, 2, strlen ($fval) - 2);
            $equals = ">";
          break;
          case SQL_LT:
            $fval = substr ($fval, 2, strlen ($fval) - 2);
            $equals = "<";
          break;
          case SQL_LIKE:
            $fval = substr ($fval, 2, strlen ($fval) - 2);
            $equals = " LIKE ";
          break;
        } // switch

        if (substr ($fval, 0, 2) == SQL_NOT) {
          $fval = substr ($fval, 2, strlen ($fval) - 2);
          $equals = "<>";
        } // if
        
        $noquote = FALSE;
        // If specified, set the time stamp to NOW().
        if ($fval == SQL_NOW) {
          $fval = "NOW()";
          $noquote = TRUE;
        } // if

        // If specified, set the field to NULL.

        // Check if we're at the end of the definitions list.
        if ($def_count == $def_size) {

          // Append the 'like' statement, if available.
          if ($pLIKE) {
            $this->Statement .= "$fname LIKE '%$fval%' ";
          } else {
            if ($noquote) {
              $this->Statement .= "$fname $equals $fval ";
            } else {
              $this->Statement .= "$fname $equals '$fval' ";
            } // if
          } // if
          
        } else {

          // Append the 'like' statement, if available.
          if ($pLIKE) {
            $this->Statement .= "$fname LIKE '%$fval%' $pANDOR ";
          } else {
            $this->Statement .= "$fname $equals '$fval' $pANDOR ";
          } // if

        } // if

        $def_count++;
      } // foreach

      // Append the order by/sort information.
      if ($pORDERBY) {
        $this->Statement .= " ORDER BY " . $pORDERBY;
      } // if

      if ($gDEBUG['echostatement'] == TRUE) {
        echo "<!-- SQL: $this->Statement -->\n";
      } // if

      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        return (-1);
      } // if

      return (0);
    } // CascadeSelectByMultiple

    function DeleteByMultiple ($pDEFINITIONS, $pLIKE = "", $pANDOR = "AND") {
      
      global $gDEBUG;

      $this->Statement = "DELETE FROM $this->TableName WHERE ";

      // Find out how many definitions are listed.
      $def_size = sizeof ($pDEFINITIONS);
      $def_count = 1;

      // Loop through the definitions array.
      foreach ($pDEFINITIONS as $dkey => $dvalue) {

        $fname = mysql_real_escape_string ($dkey);
        $fval = mysql_real_escape_string ($dvalue);

        // Check if we're at the end of the definitions list.
        if ($def_count == $def_size) {

          // Append the 'like' statement, if available.
          if ($pLIKE) {
            $this->Statement .= "$fname LIKE '%$fval%' ";
          } else {
            $this->Statement .= "$fname = '$fval' ";
          } // if

        } else {

          // Append the 'like' statement, if available.
          if ($pLIKE) {
            $this->Statement .= "$fname LIKE '%$fval%' $pANDOR ";
          } else {
            $this->Statement .= "$fname = '$fval' $pANDOR ";
          } // if

        } // if

        $def_count++;
      } // foreach

      if ($gDEBUG['echostatement'] == TRUE) {
        echo "<!-- SQL: $this->Statement -->\n";
      } // if

      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        return (-1);
      } // if

      return (0);
    } // DeleteByMultiple

    // Fetch the results of a query.
    function FetchArray () {

      if ( (is_array ($this->Cascade) ) and (isset ($this->Cascade) ) ) {
        $return = $this->CascadeFetchArray ();
        return ($return);
      } // if

	
      // Check if result is a valid resource.
	  if (!is_resource($this->Result)) return (false);
	  
      if ($resultarray = mysql_fetch_array($this->Result, MYSQL_ASSOC)) {
        foreach ($resultarray as $tbl => $data) {
          $this->$tbl = $data;
          stripslashes ($this->$tbl);
        } // foreach

        return (true);
      } else {
        return (false);
      } // if

    } // FetchArray
    
    // Fetch a cascaded mysql result
    function CascadeFetchArray () {
      
      if (!$resultarray = mysql_fetch_array($this->Result, MYSQL_ASSOC)) {
        return (FALSE);
      } // if
      
      foreach ($resultarray as $tbl => $data) {
        stripslashes ($data);
        list ($tablename, $fieldname) = explode ('__', $tbl);
        $dataarray[$tablename][$fieldname] = $data;
      } // foreach
      
      foreach ($this->Cascade as $classname) {
        foreach ($dataarray as $key => $val) {
          if ($this->$classname->TableName == $key) {
            foreach ($val as $field => $value) {
              $this->$classname->$field = $value;
            } // foreach
          } // if
          if ($this->TableName == $key) {
            foreach ($val as $field => $value) {
              $this->$field = $value;
            } // foreach
          } // if
        } // foreach
        // Synchronize the foreign key even if no values were found.
        $this->$classname->Account_FK = $this->Account_PK;
      } // foreach
      
      return (TRUE);

    } // CascadeFetchArray

    // Count the number of results from a query.
    function CountResult () {

      // Pull the results from the database.
      $rows = mysql_num_rows($this->Result);

      return ($rows);

    } // CountResult

    // Delete a record/records from the table.
    function Delete ($pWHERECLAUSE = "") {

      global $gDEBUG;

      unset ($this->Statement);

      if ($pWHERECLAUSE) {
        $this->Statement = "DELETE FROM $this->TableName WHERE " . $pWHERECLAUSE;
      } else {
        $primarykey = $this->PrimaryKey;
        $primarykey_val = $this->$primarykey;
        $this->Statement = "DELETE FROM $this->TableName WHERE $primarykey = '$primarykey_val'";
      } // if

      if ($gDEBUG['echostatement'] == TRUE) {
        echo "<!-- SQL: $this->Statement -->\n";
      } // if

      if ($this->Result = $this->Query($this->Statement)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        return (-1);
      } // if

      return (0);
    } // Delete

    // Seek to a position in the results.
    function Seek ($pPOINTER) {

      if (mysql_data_seek ($this->Result, $pPOINTER)) {
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
      } // if

      return (0);
    } // Seek

    // Sanity check variables based on predetermined parameters.
    function Sanity ($pCHECKUNIQUE = CHECK_UNIQUE) {

      global $zOLDAPPLE;

      // Reset this object's message value.
      $this->Message = "";

      // Loop through the table fieldnames.
      foreach ($this->FieldNames as $fieldcount => $fieldname) {

        if ($this->FieldDefinitions[$fieldname]['sanitize'] == NO) continue;
        if ($this->$fieldname == SQL_SKIP) continue;

        // Pull size definitions from FieldDefinitions array.
        $defmaxsize = $this->FieldDefinitions[$fieldname]['max'];
        $defminsize = $this->FieldDefinitions[$fieldname]['min'];

        // STEP 1: Check to see if data is NULL.
        if (($this->FieldDefinitions[$fieldname]['null'] == 'NO') and
            ($this->$fieldname == "") and 
            ($fieldname != $this->PrimaryKey) ) {

          // Pull error string and create global variables.
          global $gFIELDNAME;
          $gFIELDNAME= $fieldname;

          $this->Message = __("Error On Page");
          $this->Errorlist[$fieldname] = __("Do Not Leave Field Blank", array ( 'name' => $gFIELDNAME ) );
          $this->Error = -1;
          unset ($gFIELDNAME);

          // Continue through the loop.
          continue;
        } // if

        // STEP 2: Check if data is unique, unless requested not to.
        $defrelation = $this->FieldDefinitions[$fieldname]['relation'];

        if ( ($defrelation == 'unique') and ($pCHECKUNIQUE) ) {
          // Create a temporary class for pulling data from table.
          $matchagainst = new cBASEDATACLASS ($this->PageContext, $this->TableName);

          // Find the primary key of the current table.
          $primarykey = $this->PrimaryKey;

          // Select matching info in database.
          $matchagainst->Select ($fieldname, $this->$fieldname);  

          // Loop through the results.
          while ($matchagainst->FetchArray ()) {

            // Skip if primary keys match, ie, updating and not adding.
            if ( (strtoupper($matchagainst->$fieldname) == strtoupper($this->$fieldname)) and
                 ($matchagainst->$primarykey != $this->$primarykey) ) {
              global $gFIELDNAME;
              $gFIELDNAME= $fieldname;
    
              // Produce an error message and exit.
              $this->Message = __("Error On Page");
              $this->Errorlist[$fieldname] = __("Error Duplicate Field", array ( 'name' => $gFIELDNAME ) );
              $this->Error = -1;

              unset ($gFIELDNAME);

            } // if
          } // while

          // Destroy the temporary class.
          unset ($matchagainst);

        } // if

        // Check if data is specific to this user.
        if ( ($defrelation == 'specific') and ($pCHECKUNIQUE) ) {
          // Create a temporary class for pulling data from table.
          $matchagainst = new cBASEDATACLASS ($this->PageContext, $this->TableName);

          // Find the primary key of the current table.
          $primarykey = $this->PrimaryKey;
          $foreignkey = $this->ForeignKey;

          // Select matching info in database.
          $criteria = array ($fieldname => $this->$fieldname,
                             $foreignkey => $this->$foreignkey);
          $matchagainst->SelectByMultiple ($criteria);  

          // Loop through the results.
          while ($matchagainst->FetchArray ()) {

            // Skip if primary keys match, ie, updating and not adding.
            if ( (strtoupper($matchagainst->$fieldname) == strtoupper($this->$fieldname)) and
                 ($matchagainst->$primarykey != $this->$primarykey) ) {
              global $gFIELDNAME;
              $gFIELDNAME= $fieldname;
    
              // Produce an error message and exit.
              $this->Message = __("Error On Page");
              $this->Errorlist[$fieldname] = __("Error Duplicate Field", array ( 'name' => $gFIELDNAME ) );
              $this->Error = -1;

              unset ($gFIELDNAME);

            } // if
          } // while

          // Destroy the temporary class.
          unset ($matchagainst);

        } // if

        // STEP 3: Check for illegal characters
        $defillegal = explode (' ', $this->FieldDefinitions[$fieldname]['illegal']);

        foreach ($defillegal as $illegalchar) {
          // Check for spaces.
          if ($illegalchar == "%20") $illegalchar = " ";

          if ($illegalchar) {
            if (strpos ($this->$fieldname, $illegalchar)) {
              // Pull error string and create global variables.
              global $gILLEGALCHAR, $gFIELDNAME;
              $gILLEGALCHAR = $illegalchar; $gFIELDNAME= $fieldname;

              $this->Message = __("Error On Page");
              $this->Errorlist[$fieldname] = __("Error Illegal Character", array ( 'name' => $gFIELDNAME, 'char' => $gILLEGALCHAR ) );
              $this->Error = -1;

              unset ($gFIELDNAME); unset ($gILLEGALCHAR);
            } // if
          } // if
        } // foreach

        // STEP 4: Check for required characters.
        $defrequired = explode (' ', $this->FieldDefinitions[$fieldname]['required']);

        foreach ($defrequired as $requiredchar) {
          // Check for spaces.
          if ($requiredchar == "%20") $requiredchar = " ";

          if ($requiredchar) {
            if (strpos ($this->$fieldname, $requiredchar)) {
            } else {
              // Pull error string and create global variables.
              global $gREQUIREDCHAR, $gFIELDNAME;
              $gREQUIREDCHAR = $requiredchar; $gFIELDNAME= $fieldname;

              $this->Message = __("Error On Page");
              $this->Errorlist[$fieldname] = __("Error Required Character", array ( 'name' => $gFIELDNAME, 'char' => $gREQUIREDCHAR ) );
              $this->Error = -1;

              unset ($gFIELDNAME); unset ($gREQUIREDCHAR);
            } // if
          } // if
        } // foreach

        // STEP 5: Check to see if data is valid for specific datatype.
        switch ($this->FieldDefinitions[$fieldname]['datatype']) {
          case "INTEGER":

            // Check if type is correct.
            if (!is_int ($zOLDAPPLE->ConvertType ($this->$fieldname))) {
              // If field is primary key, skip.
              if ($fieldname == $this->PrimaryKey) break;

              global $gFIELDNAME;
              $gFIELDNAME = $fieldname;
  
              // Lookup main error message.
              $this->Message = __("Error On Page");

              // Lookup field error message.
              $this->Errorlist[$fieldname] = __("Error Integer");

              // Confirm error was found.
              $this->Error = -1;
  
              unset ($gFIELDNAME); 

              // Exit out of switch.
              break;
            } // if

            // Check to see if numeric data is too large.
            if ( ($this->$fieldname > $defmaxsize) and
                 ($defmaxsize != '') ) {
              // Pull error string and create global variables.
              global $gMAXSIZE, $gFIELDNAME;
              $gMAXSIZE = $defmaxsize; $gFIELDNAME= $fieldname;
  
              $this->Message = __("Error On Page");
              $this->Errorlist[$fieldname] = __("Error Too Large", array ( 'name' => $gFIELDNAME, 'max' => $gMAXSIZE) );
              $this->Error = -1;
  
              unset ($gFIELDNAME); unset ($gMAXSIZE);
            } // if

            // Check to see if numeric data is too small.

            // Skip if it's the primary key.
            if ($fieldname != $this->PrimaryKey) {

              if ( ($this->$fieldname < $defminsize) and
                   ($defminsize != '') ) {
                // Pull error string and create global variables.
                global $gMINSIZE, $gFIELDNAME;
                $gMINSIZE = $defminsize; $gFIELDNAME= $fieldname;
    
                $this->Message = __("Error On Page");
                $this->Errorlist[$fieldname] = __("Error Too Small", array ( 'name' => $gFIELDNAME, 'min' => $gMINSIZE) );
                $this->Error = -1;
    
                unset ($gFIELDNAME); unset ($gMINSIZE);
              } // if
            } // if

          break;

          case "CURRENCY":
            // Check if a valid currency value was given.
          break;

          case "FLOAT":
            // Check if type is correct.
            if (!is_float ($zOLDAPPLE->ConvertType ($this->$fieldname))) {
              // If field is primary key, skip.
              if ($fieldname == $this->PrimaryKey) break;

              global $gFIELDNAME;
              $gFIELDNAME = $fieldname;
  
              // Lookup main error message.
              $this->Message = __("Error On Page");

              // Lookup field error message.
              $this->Errorlist[$fieldname] = __("Error Integer");

              // Confirm error was found.
              $this->Error = -1;
  
              unset ($gFIELDNAME); 

              // Exit out of switch.
              break;
            } // if

            // Check to see if numeric data is too large.
            if ( ($this->$fieldname > $defmaxsize) and
                 ($defmaxsize != '') ) {
              // Pull error string and create global variables.
              global $gMAXSIZE, $gFIELDNAME;
              $gMAXSIZE = $defmaxsize; $gFIELDNAME= $fieldname;
  
              $this->Message = __("Error On Page");
              $this->Errorlist[$fieldname] = __("Error Too Large", array ( 'name' => $gFIELDNAME, 'max' => $gMAXSIZE) );
              $this->Error = -1;
  
              unset ($gFIELDNAME); unset ($gMAXSIZE);
            } // if

            // Check to see if numeric data is too small.

            // Skip if it's the primary key.
            if ($fieldname != $this->PrimaryKey) {

              if ( ($this->$fieldname < $defminsize) and
                   ($defminsize != '') ) {
                // Pull error string and create global variables.
                global $gMINSIZE, $gFIELDNAME;
                $gMINSIZE = $defminsize; $gFIELDNAME= $fieldname;
    
                $this->Message = __("Error On Page");
                $this->Errorlist[$fieldname] = __("Error Too Small", array ( 'name' => $gFIELDNAME, 'min' => $gMINSIZE) );
                $this->Error = -1;
    
                unset ($gFIELDNAME); unset ($gMINSIZE);
              } // if
            } // if

          break;

          case "EMAIL":
            // Check for a valid email address.
            if (!$zOLDAPPLE->CheckEmail ($this->$fieldname)) {
              // Pull error string and create global variables.
              global $gFIELDNAME;
              $gFIELDNAME= $fieldname;
  
              $this->Message = __("Error On Page");
              $this->Errorlist[$fieldname] = __("Error Invalid Email", array ( 'name' => $gFIELDNAME ) );
              $this->Error = -1;

              unset ($gFIELDNAME);
            } // if
          
          case "FILENAME":
          case "DOMAIN":
            // NOTE: Write this as a seperate option.
          case "STRING":

            // Check to see if character data is too long
            if ( (strlen($this->$fieldname) > $defmaxsize) and 
                 ($defmaxsize != '') ) {
              // Pull error string and create global variables.
              global $gMAXSIZE, $gFIELDNAME;
              $gMAXSIZE = $defmaxsize; $gFIELDNAME= $fieldname;
  
              $this->Message = __("Error On Page");
              $this->Errorlist[$fieldname] = __("Error Too Long", array ( 'name' => $gFIELDNAME, 'max' => $gMAXSIZE) );
              $this->Error = -1;

              unset ($gFIELDNAME); unset ($gMAXSIZE);
            } // if

            // Check to see if character data is too short
            if ( (strlen($this->$fieldname) < $defminsize) and
                 ($this->$fieldname != SQL_SKIP) ) {
              // Pull error string and create global variables.
              global $gMINSIZE, $gFIELDNAME;
              $gMINSIZE = $defminsize; $gFIELDNAME= $fieldname;
  
              $this->Message = __("Error On Page");
              $this->Errorlist[$fieldname] = __("Error Too Short", array ( 'name' => $gFIELDNAME, 'min' => $gMINSIZE) );
              $this->Error = -1;
  
              unset ($gFIELDNAME); unset ($gMINSIZE);
            } // if

          break;

          case "DATE":
          case "TIME":
          case "DATETIME":
            // Check if a valid date was given.

          break;

          case "PASSWORD":
            // Check to see if password length is too long
            if ( (strlen($this->$fieldname) > $defmaxsize) and 
                 ($defmaxsize != '') ) {
              // Pull error string and create global variables.
              global $gMAXSIZE, $gFIELDNAME;
              $gMAXSIZE = $defmaxsize; $gFIELDNAME= $fieldname;
  
              $this->Message = __("Error On Page");
              $this->Errorlist[$fieldname] = __("Error Too Long", array ( 'name' => $gFIELDNAME, 'max' => $gMAXSIZE) );
              $this->Error = -1;

              unset ($gFIELDNAME); unset ($gMAXSIZE);
            } // if

            // Check to see if password length is too short
            if ( (strlen($this->$fieldname) < $defminsize) and
                 ($this->$fieldname != SQL_SKIP) ) {
              // Pull error string and create global variables.
              global $gMINSIZE, $gFIELDNAME;
              $gMINSIZE = $defminsize; $gFIELDNAME= $fieldname;
  
              $this->Message = __("Error On Page");
              $this->Errorlist[$fieldname] = __("Error Too Small", array ( 'name' => $gFIELDNAME, 'min' => $gMINSIZE) );
              $this->Error = -1;
  
              unset ($gFIELDNAME); unset ($gMINSIZE);
            } // if

            // Check if a good password was chosen.

          break;

          default:
          break;

        } // switch
      } // foreach

      return ($this->Error);

    } // Sanity

    // Sanity check a single variable based on set parameters.
    function Sanitize ($pVARIABLENAME) {

      // NOTE:  Not written yet.

    } // Sanitize

    // Broadcast any error messages to the browser.
    function CreateBroadcast ($pCLASS = "", $pFIELDERROR = "", $pUNIQUEID = "") {

      global $gFRAMELOCATION;
      global $zOLDAPPLE;

      $output = "";

      // Determine CSS id of message.
      if ($this->Error == 0) {
        $file = "message";
      } else {
        $file = 'error';
      } // if

      // If we're using multiple forms, check if we're presenting the right ID.
      $primarykey = $this->PrimaryKey;
      if ($pUNIQUEID) {
        if ($this->$primarykey != $pUNIQUEID) {
          return (0);
        } // if
      } // if

      // Begin the CSS class/id.
        $output_begin = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/common/broadcast.$file.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
      
      // End the CSS class/id.
        $output_end = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/common/broadcast.$file.bottom.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
    
      // Echo the message.
      if ($pFIELDERROR) {
        // Echo the field message if exists.
        if ($this->Errorlist[$pFIELDERROR]) 
          $output = $output_begin . $this->Errorlist[$pFIELDERROR] . $output_end;
      } else {
        // Echo the object message if exists.
        if ($this->Message) 
          $output = $output_begin . $this->Message . $output_end;
      } // if

      return ($output);

    } // CreateBroadcast
   
    function Broadcast ($pCLASS = "", $pFIELDERROR = "", $pUNIQUEID = "") {

      echo $this->CreateBroadcast ($pCLASS, $pFIELDERROR, $pUNIQUEID);
      
    } // Broadcast

    // Return the last ID generated by AUTO_INCREMENT
    function AutoIncremented () {
      global $gDBLINK;

      $this->LastIncrement = mysql_insert_id ($gDBLINK);

      return ($this->LastIncrement);

    } // AutoIncremented

    // Retrieve hashed array listing of two elements
    function Listing ($pVALUE, $pLABEL) {

      $internalResult = '';

      $this->Statement = "SELECT $pVALUE, $pLABEL FROM " .  $this->TableName;

      if ($internalResult = $this->Query($this->Statement)) {
      } else {
        return (0);
      } // if

      while ($resultarray = mysql_fetch_array($internalResult, MYSQL_ASSOC)) {
        $returnval = $resultarray[$pVALUE];
        $returnlabel = $resultarray[$pLABEL];
        $returnarray[$returnval] = $returnlabel;
      } // if

      return ($returnarray);
    } // Listing

    // Find the max value.
    function Max ($pCOUNTKEY, $pWHEREKEY, $pWHEREDATA) {

      // Create the select statement.
      $this->Statement = "SELECT MAX($pCOUNTKEY) as MAXVALUE FROM $this->TableName WHERE $pWHEREKEY='" . $pWHEREDATA . "'";
 
      // Return the value or an error.
      if ($result = $this->Query($this->Statement)) {
        $resultarray = mysql_fetch_array ($result);
        return ($resultarray['MAXVALUE']);
      } else {
        $this->Error = -1;
        $this->Message = mysql_error();
        $this->Rollback();
        return (-1);
      } // if

    } // Max

    function FormatDate ($pVARNAME) {
 
      $currently = strtotime ("now");
      $messagestamp = strtotime ($this->$pVARNAME);

      $currently_year = date ("Y", $currently);
      $messagestamp_year = date ("Y", $messagestamp);

      $newvar = 'f' . $pVARNAME;

      // If this message is from another year, show the whole date.
      if ($messagestamp_year < $currently_year) {
        $this->$newvar = date ("m-d-Y", $messagestamp);
        return (0);
      } // if

      // Calculate the difference between now and then.
      $difference = $currently - $messagestamp;

      // No remainder found.
      if ($difference % 86400 <= 0) {
        $days = $difference / 86400;
      } // if
      
      // Remainder found.
      if ($difference % 86400 > 0) {   
        $rest = ($difference % 86400);
        $days = ($difference - $rest) / 86400;
      } // if

      // Basic date format.
      $this->$newvar = date ("M d", strtotime ($this->$pVARNAME) );

      // Hourly Format.
      if ($days < 1) {
        $this->$newvar = date ("g:ia", $messagestamp);
      } // if

    } // FormatDate

    function FormatVerboseDate ($pVARNAME) {
 
      $currently = strtotime ("now");
      $messagestamp = strtotime ($this->$pVARNAME);

      $currently_year = date ("Y", $currently);
      $messagestamp_year = date ("Y", $messagestamp);

      $newvar = 'f' . $pVARNAME;
      $this->$newvar = date ("M d, Y @ g:ma", $messagestamp);

      return (TRUE);

    } // FormatVerboseDate

    // Automatically generate a generic form based on table data.
    function GenerateForm ($pPRIMARYVALUE, $pROWSIZE = 12) {

      global $zHTML, $zOPTIONS;

      // Broadcast any known errors.
      $this->Broadcast ('generated');

      echo "<form method='POST'>\n";
      echo "<div class='generated' name='content'>\n";

      // Loop through the definitions.
      foreach ($this->FieldNames as $fieldcount => $fieldname) {

        $inputname = 'g' . strtoupper ($fieldname);
        global $$inputname;

        // Skip if this is field is hidden from public.
        if ($this->FieldDefinitions[$fieldname]['hidden'] == TRUE) continue;

        // Skip the primary key.
        if ( ($this->FieldDefinitions[$fieldname]['primary'] == TRUE) &&
             ($this->FieldDefinitions[$fieldname]['foreign'] == FALSE) ) 
        {
          // Check if we're updating a value.
          if ($pPRIMARYVALUE) {
            $inputname = 'g' . $fieldname;
            // Updating, primary key exists, so push it into a hidden form.
            $zHTML->Hidden ($inputname, $pPRIMARYVALUE);
            $zHTML->Hidden ('gACTION', 'UPDATE');
          } else {
            $zHTML->Hidden ('gACTION', 'ADD');
          } // if

          continue;
        } // if

        if ($this->FieldDefinitions[$fieldname]['identifier']) {
          $label = $this->FieldDefinitions[$fieldname]['identifier'];
        } else {
          $label = $fieldname;
        } // if

        echo "<div class='generated' name='label'>\n";
        echo $label. ":\n";
        echo "</div> <!-- ## generated.label -->\n\n";


        echo "<div class='generated' name='field'>\n";
        switch ($this->FieldDefinitions[$fieldname]['datatype']) {

          case 'FLOAT':
          case 'INTEGER':
            if ($this->FieldDefinitions[$fieldname]['foreign']) {
              // Create a menu using the foreign data.
              $zFOREIGN = new cBASEDATACLASS;
              $zFOREIGN->TableName = $this->FieldDefinitions[$fieldname]['foreigntable'];
              $zFOREIGN->Fields();
              $optionlist = $zFOREIGN->Listing ($this->FieldDefinitions[$fieldname]['foreignfield'], $zFOREIGN->PrimaryIdentifier);
              $zHTML->Menu ($inputname, $optionlist, NULL, $$inputname, FALSE);
              unset ($zFOREIGN);
            } else {
              $max = $this->FieldDefinitions[$fieldname]['max'];
              $min = $this->FieldDefinitions[$fieldname]['min'];
              if ( ($max) && ($min) && ($max - $min < 20) ) {
              } else {
                $zHTML->TextBox ($inputname, 64, $$inputname);
              } // if
            } // if
          break;

          case 'TIME':
            $zHTML->TimeMenu ($inputname, 12, 0);
          break;

          case 'DATE':
            $zHTML->DateMenu ($inputname, 12, 31, 1999);
          break;

          case 'DATETIME':
            $zHTML->DateMenu ($inputname, 12, 31, 1999);
            echo OUTPUT_NBSP;
            $zHTML->TimeMenu ($inputname, 12, 0);
          break;

          case 'STRING':
            if ($this->FieldDefinitions[$fieldname]['max'] == 65536) {
              $zHTML->TextArea ($inputname, $pROWSIZE, $$inputname);
            } else {
              // Small input form.
              $zHTML->TextBox ($inputname, 64, $$inputname);
            } // if
          break;
          case 'OPTION':
              $concern = $this->FieldDefinitions[$fieldname]['option'];
              $zOPTIONS->Menu ($concern, $this->$$fieldname);
          break;
          case 'MENU':
              $zHTML->Menu ($inputname, $this->FieldDefinitions[$fieldname]['menu'], $this->$$fieldname, $this->$$fieldname);
          break;
        } // switch
        $this->Broadcast ('generated', $fieldname);
        echo "</div> <!-- ## generated.field -->";

      } // foreach

        echo "<div class='generated' id='submit'>";
         $zHTML->Button ("submit");
        echo "</div> <!-- ## generated.submit -->";
       echo "</form>\n";
      echo "</div> <!-- ## generated.content -->";

      return (TRUE);

    } // GenerateForm

    function Bruce () {

      // "There is a powerful craving in most of us to see ourselves 
      // as instruments in the hands of others and, thus, free ourselves 
      // from responsibility for acts which are prompted by our own 
      // questionable inclinations and impulses. Both the strong and the 
      // weak grasp at this alibi. The latter hide their malevolence under 
      // the virtue of obedience. The strong, too, claim absolution by 
      // proclaiming themselves the instruments of a higher power - God, 
      // history, fate, nation, or humanity."
      // 
      // -Bruce Lee

      define ("TAO", !TRUE && TRUE && !FALSE && FALSE);

      return (TAO);

    } // Bruce

  } // cBASEDATACLASS

  // Cache storage class for table information.
  class cBASEDATACACHE {
    var $ServerCache;
    var $ColumnCache;
    var $FieldCache;
    var $FieldCount;

    function cBASEDATACACHE () {
    	
      global $gTABLEPREFIX;

      $this->ColumnCache = array ();
      $this->FieldCache = array ();
      $this->FieldCount = array ();
      $this->ServerCache = array ();
      
      $this->NodeCache = new cBASEDATACLASS ();
      $this->NodeCache->TableName = $gTABLEPREFIX . "cacheNodes";
      $this->NodeCache->Fields();

      return (TRUE);
    } // Constructor

  } // cBASEDATACACHE

  // HTML Class.
  class cOLDHTML {

    var $Output;
    var $ScriptList;

    // Constructor.
    function cOLDHTML () {

      $Output = "";
      $ScriptList = array ();

    } // Constructor

    function GetBrowserAgent () {
      global $gBROWSERAGENT;
      global $gBROWSERVER;
      global $gBROWSEROS;

      unset ($gBROWSERAGENT);
      unset ($gBROWSERVER);
      unset ($gBROWSEROS);
    
      if (!isset($HTTP_USER_AGENT)) $HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];
   
      if (preg_match( '/MSIE ([0-9].[0-9]{1,2})/',$HTTP_USER_AGENT,$log_version)) {
        $gBROWSERVER=$log_version[1];
        if (strstr ($HTTP_USER_AGENT, "Opera")) $gBROWSERAGENT = 'Opera';
        $gBROWSERAGENT='IE';
      } elseif (preg_match( '/Opera ([0-9].[0-9]{1,2})/',$HTTP_USER_AGENT,$log_version)) {
        $gBROWSERVER=$log_version[1];
        $gBROWSERAGENT='OPERA';
      } elseif (preg_match( '/Mozilla\/([0-9].[0-9]{1,2})/',$HTTP_USER_AGENT,$log_version)) {
        $gBROWSERVER=$log_version[1];
        $gBROWSERAGENT='MOZILLA';
      } else {
        $gBROWSERVER=0;
        $gBROWSERAGENT='OTHER';
      } // if
      
     /* Determine platform */
     
      if (strstr($HTTP_USER_AGENT,'Win')) {
        $gBROWSEROS='Win';
      } else if (strstr($HTTP_USER_AGENT,'Mac')) {
        $gBROWSEROS='Mac';
      } else if (strstr($HTTP_USER_AGENT,'Linux')) {
        $gBROWSEROS='Linux';
      } else if (strstr($HTTP_USER_AGENT,'Unix')) {
        $gBROWSEROS='Unix';
      } else {
        $gBROWSEROS='Other';
      } // if
   
      /* Do An Extra Opera Check */
      if (strstr($HTTP_USER_AGENT,'Opera')) {
        $gBROWSERAGENT='OTHER';
      } // if
   
      return $gBROWSERAGENT;
    } // GetBrowserAgent
    
    function GetBrowserVersion() {
      global $gBROWSERVER;
      return $gBROWSERVER;
    } // GetBrowserVersion
    
    function GetBrowserPlatform() {
      global $gBROWSEROS;
      return $gBROWSEROS;
    } // GetBrowserPlatform
    
    function BrowserIsMac() {
      if ($this->GetBrowserPlatform()=='Mac') {
       return true;
      } else {
       return false;
      } // if
    } // BrowserIsMac
    
    function BrowserIsWindows() {
      if ($this->GetBrowserPlatform()=='Win') {
       return true;
      } else {
       return false;
      } // if
    } // BrowserIsWindows
    
    function BrowserIsIE() {
      if ($this->GetBrowserAgent()=='IE') {
       return true;
      } else {
       return false;
      } // if
    } // BrowserIsIE
    
    function BrowserIsNetscape() {
      if ($this->GetBrowserAgent()=='MOZILLA') {
       return true;
      } else {
       return false;
      } // if
    } // BrowserIsNetscape
    
    // Output a Hidden element.
    function Hidden ($pINPUTNAME, $pINPUTVALUE) {

      // Add a 'g' at the beginning to signify a global variable.
      if ($pINPUTNAME[0] != 'g') $pINPUTNAME = 'g' . $pINPUTNAME;

      // Escape the string.
      $pINPUTVALUE = htmlspecialchars ($pINPUTVALUE);

      // Generate HTML
      $this->Output = "<input type='hidden' name='$pINPUTNAME' id = '$pINPUTNAME' " .
                      "value=\"$pINPUTVALUE\" />\n";

      echo ($this->Output);

      return (0);

    } // Hidden

    // Output a TextBox element.
    function TextBox ($pINPUTNAME, $pINPUTMAX = 64, $pINPUTVALUE = "", $pPASSWORD = FALSE, $pDISABLED = FALSE) {

      // Add a 'g' at the beginning to signify a global variable.
      if ($pINPUTNAME[0] != 'g') $pINPUTNAME = 'g' . $pINPUTNAME;

      global $$pINPUTNAME;

      // Check if a POST value is available.
      if ( ($$pINPUTNAME) and (!is_array ($$pINPUTNAME)) ) {
        $pINPUTVALUE = $$pINPUTNAME;
      } // if

      // Check if POST variable is an array.
      if (strstr ($pINPUTNAME, '[')) {
        // Cannot use as a $$ reference unless you strip away the [] part.
        list ($newinput, $right) = explode ('[', $pINPUTNAME);

        // Retrieve array reference into $listid.
        list ($listid, $right) = explode (']', $right);

        // Use a reference to access information.
        global $$newinput;
        $array = &$$newinput;
        $value = $array[$listid];

        // Check if the POST variable is set.
        if (isset ($value)) {
          $pINPUTVALUE = $value;
        } // if
      } // if

      // Escape the string.
      $pINPUTVALUE = htmlspecialchars ($pINPUTVALUE);

      // Generate HTML
      if ($pPASSWORD) {
        $type = 'password';
      } else {
        $type = 'text';
      } // if
      
      $disabled = "";
      if ($pDISABLED) $disabled = "disabled=disabled";

      if ($pINPUTNAME[0] == 'g') {
        $classname = strtolower (substr ($pINPUTNAME, 1, strlen ($pINPUTNAME)-1));
      } else {
        $classname = strtolower ($pINPUTNAME);
      } // if

      $style = strtolower ($pINPUTNAME);

      $this->Output = "<span class='$style'>";
      $this->Output .= "<input $disabled type='$type' name='$pINPUTNAME' " .
                       "class='$classname' " . 
                       "maxlength='$pINPUTMAX' value=\"$pINPUTVALUE\" />\n";
      $this->Output .= "</span>";

      echo ($this->Output);

      return (0);

    } // TextBox

    // Output a FileBox element.
    function FileBox ($pINPUTNAME, $pINPUTSIZE = 24, $pINPUTMAX = 64, $pMAXFILESIZE = 4096, $pDISABLED = ENABLED) {

      $style = strtolower ($pINPUTNAME);

      // Add a 'g' at the beginning to signify a global variable.
      if ($pINPUTNAME[0] != 'g') $pINPUTNAME = 'g' . $pINPUTNAME;

      global $$pINPUTNAME;

      if ($$pINPUTNAME != "") {
        $pINPUTVALUE = $$pINPUTNAME;
      }
      // Escape the string.
      $pINPUTVALUE = htmlspecialchars ($pINPUTVALUE);

      // Check if input is disabled.
      if ($pDISABLED == DISABLED) $disabled = "disabled='disabled'";

      // NOTE: Put this in an object.

      // Set the max file size.
      $this->Output = "<input type='hidden' name='MAX_FILE_SIZE' value='$pMAXFILESIZE' />\n";

      $this->Output .= "<span class='$style'>";
      $this->Output .= "<input type='file' name='$pINPUTNAME' " .
                       "id='$pINPUTNAME' size='$pINPUTSIZE' " . 
                       "$disabled maxlength='$pINPUTMAX' />\n";
      $this->Output .= "</span> <!-- .$style  -->";

      echo ($this->Output);

      return (0);

    } // FileBox

    // Output a TextArea element.
    function TextArea ($pINPUTNAME, $pINPUTVALUE = "") {

      // Add a 'g' at the beginning to signify a global variable.
      if ($pINPUTNAME[0] != 'g') $pINPUTNAME = 'g' . $pINPUTNAME;

      global $$pINPUTNAME;

      // Check if the POST value has been set, and we are not dealing with an array.
      if ( ($$pINPUTNAME != "") and (!is_array ($$pINPUTNAME)) ) {
        $pINPUTVALUE = $$pINPUTNAME;
      } // if

      // Check if POST variable is an array.
      if (strstr ($pINPUTNAME, '[')) {
        // Cannot use as a $$ reference unless you strip away the [] part.
        list ($newinput, $right) = explode ('[', $pINPUTNAME);

        // Retrieve array reference into $listid.
        list ($listid, $right) = explode (']', $right);

        // Use a reference to access information.
        global $$newinput;
        $array = &$$newinput;
        $value = $array[$listid];

        // Check if the POST variable is set.
        if (isset ($value)) {
          $pINPUTVALUE = $value;
        } // if
      } // if

      // Escape the string.
      $pINPUTVALUE = htmlspecialchars ($pINPUTVALUE);

      // Determine CSS element name
      if ($pINPUTNAME[0] == 'g') {
        $classname = strtolower (substr ($pINPUTNAME, 1, strlen ($pINPUTNAME)-1));
      } else {
        $classname = strtolower ($pINPUTNAME);
      } // if

      // Replace any occurances of [] in array input variables.
      if ( strstr ($classname, ']') OR strstr ($classname, '[') ) {
       $classname = str_replace (']', $classname);
       $classname = str_replace ('[', $classname);
      };

      // Generate HTML
      $this->Output = "<textarea name='$pINPUTNAME' class='$classname' >$pINPUTVALUE</textarea>\n";

      echo ($this->Output);

      return (0);
    } // TextArea

    function SwitchAlternate ($pSTYLEPREFIX = NULL, $pSTYLESUFFIX = NULL, $pADDITIONALCLASS = NULL) {
      global $gALTERNATE;
      global $gSTYLEID;

      // Switch the alternate bit, and determine which style id to use.
      if ($gALTERNATE == 0) {
        $gSTYLEID = $pSTYLEPREFIX . 'even' . $pSTYLESUFFIX;
        $gALTERNATE = 1;
      } else {
        $gSTYLEID = $pSTYLEPREFIX . 'odd' . $pSTYLESUFFIX;
        $gALTERNATE = 0;
      } // if
      
      if ($pADDITIONALCLASS) $gSTYLEID .= " " . $pADDITIONALCLASS;

      return (TRUE);
    } // SwitchAlternate
    
    function GetAlternate () {
      global $gALTERNATE;
      
      if ($gALTERNATE == 0) {
        $return = 'even';
      } else {
        $return = 'odd';
      } // if
      
      $this->SwitchAlternate ();
      
      return ($return);
    } // GetAlternate

    // Alternate listing
    function Alternate ($pSTYLEPREFIX = NULL, $pSTYLESUFFIX = NULL, $pADDITIONALCLASS = NULL) {
      global $gFRAMELOCATION;
      global $gALTERNATE;
      global $gSTYLEID;

      global $zOLDAPPLE;

      $this->SwitchAlternate ($pSTYLEPREFIX, $pSTYLESUFFIX, $pADDITIONALCLASS);
      // Load alternate.top object
      $output = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/common/alternate.top.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      $return = $gSTYLEID;
      
      // Unset style variables.
      unset ($gSTYLEID);

      // Echo output.
      return $return;

    } // Alternate

    // Output a checkbox element.
    function CheckBox ($pINPUTNAME, $pINPUTVALUE, $pINPUTLIST = FALSE, $pCHECKED = FALSE) {

      global $gFRAMELOCATION;
      global $gSTYLEID, $gCHECKNAME, $gCHECKVALUE, $gCHECKED;

      global $zOLDAPPLE;

      // Set the CSS id.
      $gSTYLEID = strtolower ($pINPUTNAME);

      // Add a 'g' at the beginning to signify a global variable.
      if ($pINPUTNAME[0] != 'g') $pINPUTNAME = 'g' . $pINPUTNAME;

      // Load the specified global value within scope.
      global $$pINPUTNAME;

      // Check if we are using a list or a single value.
      if ($pINPUTLIST) {
        // Check if the global value exists.
        if ($$pINPUTNAME) {
          // Loop through POST array.
          foreach ($$pINPUTNAME as $number => $value) {
            if ($value == $pINPUTVALUE) $pCHECKED = "checked='checked'";
          } // if
        } // if
        $gCHECKVALUE = "value='$pINPUTVALUE'";
      } else {
        if ($$pINPUTNAME) $pCHECKED = "checked='checked'";
      } // if

      if ($pCHECKED) $pCHECKED = "checked='checked'";

      $gCHECKNAME = $pINPUTNAME;
      if ($pINPUTLIST) $gCHECKNAME = $pINPUTNAME . '[]';

      $gCHECKED = $pCHECKED;

      $this->Output = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/common/checkbox.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      echo ($this->Output);

      unset ($gCHECKNAME); 
      unset ($gCHECKVALUE); unset ($gCHECKED);

      return (0);

    } // CheckBox

    // Output a radio element.
    function Radio ($pINPUTNAME, $pINPUTVALUE, $pRETURNVALUE, $pINPUTLIST = FALSE, $pCHECKED = FALSE) {
      global $gFRAMELOCATION;

      global $gCHECKVALUE, $gCHECKNAME, $gCHECKED;

      global $zOLDAPPLE;

      // Add a 'g' at the beginning to signify a global variable.
      if ($pINPUTNAME[0] != 'g') $pINPUTNAME = 'g' . $pINPUTNAME;

      global $$pINPUTNAME;

      $gCHECKVALUE = "value='$pRETURNVALUE'";

      // NOTE: Put this into a function, since TextBox uses it too?

      // Check if POST variable is an array.
      if (strstr ($pINPUTNAME, '[')) {
        // Cannot use as a $$ reference unless you strip away the [] part.
        list ($newinput, $right) = explode ('[', $pINPUTNAME);

        // Retrieve array reference into $listid.
        list ($listid, $right) = explode (']', $right);

        // Use a reference to access information.
        global $$newinput;
        $array = &$$newinput;
        $value = $array[$listid];

        // Check if the POST variable is set.
        if (isset ($value)) {
          $pINPUTVALUE = $value;
        } // if
      } // if

      // Check if we are using a list or a single value.
      if ($pINPUTLIST) {
        // Loop through POST array.
        if ($$pINPUTNAME) {
          foreach ($$pINPUTNAME as $number => $value) {
            if ($value == $pRETURNVALUE) $pCHECKED = "checked='checked'";
          } // if
        } else {
          if ($pINPUTVALUE == $pRETURNVALUE) {
            $pCHECKED = "checked='checked'";
          } else {
            if ( ($pCHECKED) and ($pINPUTVALUE == "") )  $pCHECKED = "checked='checked'";
          } // if
        } // if
        $gCHECKVALUE = "value='$pRETURNVALUE'";
      } else {
        if ($$pINPUTNAME) {
          if ($$pINPUTNAME == $pRETURNVALUE) $pCHECKED = "checked='checked'";
        } else {
          if ($pINPUTVALUE == $pRETURNVALUE) { 
            $pCHECKED = "checked='checked'";
          } else {
            if ($pCHECKED) $pCHECKED = "checked='checked'";
          } // if
        }
      } // if

      $gCHECKED = $pCHECKED;

      $gCHECKNAME = $pINPUTNAME;
      if ($pINPUTLIST) $gCHECKNAME = $pINPUTNAME;

      $this->Output = $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/common/radio.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);

      echo ($this->Output);

      unset ($gCHECKNAME); unset ($gCHECKVALUE);

      return (0);

    } // Radio

    // Output a normal link that does not use POST.
    function NakedLink ($pLINK, $pTEXT, $pSTYLECLASS = "", $pTARGET = "") {

      if ($pTARGET) $pTARGET = "target='$pTARGET'";
      if ($pSTYLECLASS) $pSTYLECLASS = "style='$pSTYLECLASS'";

      $finalstring = "<a $pSTYLECLASS $pTARGET href='$pLINK'>$pTEXT</a>";

      return ($finalstring);
       
    } // NakedLink

    // Output a link that uses POST to move data.
    function Link ($pTARGET, $pTEXT, $pDATALIST = array (), $pSTYLECLASS = "") {
 
      $this->CreateLink ($pTARGET, $pTEXT, $pDATALIST);

      echo ($this->Output);
     
      return (0);

    } // Link

    // Output an image link that uses POST to move data.
    function ImageLink ($pTARGET, $pDATALIST = array (), $pIMAGE = "", $pWIDTH = "", $pHEIGHT = "") {

      $this->CreateImageLink ($pTARGET, $pDATALIST, $pIMAGE, $pWIDTH, $pHEIGHT);

      echo ($this->Output);
     
      return (0);

    } // ImageLink

    // Create an image link that uses POST to move data.
    function CreateImageLink ($pTARGET, $pDATALIST = array (), $pIMAGE = "", $pWIDTH = "", $pHEIGHT = "", $pCONFIRM = "") {

      $this->CreateLink ($pTARGET, "", $pDATALIST, "", $pIMAGE, $pWIDTH, $pHEIGHT, $pCONFIRM);

      return ($this->Output);
     
    } // ImageLink

    // Output a user profile link.
    function UserLink ($pUSERNAME, $pDOMAIN = "", $pLINKICON = TRUE) {

      $output = $this->CreateUserLink ($pUSERNAME, $pDOMAIN, $pLINKICON);

      echo ($output);

      return (0);
      
    } // UserLink

    // Create a user profile link.
    function CreateUserLink ($pUSERNAME, $pDOMAIN = NULL, $pLINKICON = TRUE) {
    	
      global $gUSERTHEME, $gTHEMELOCATION;
      global $gLINKICON;
      global $gLINKDOMAIN;
      global $gSITEDOMAIN;

      global $zAUTHUSER;

      global $zOLDAPPLE;

      $output = "";

      $zOLDAPPLE->SetTag ('LINKUSERNAME',$pUSERNAME);

      if (!$pDOMAIN) $pDOMAIN = $gSITEDOMAIN;
      $zOLDAPPLE->SetTag ('LINKDOMAIN',$pDOMAIN);

      if ($pUSERNAME == ANONYMOUS) {
      	  $output = __("Anonymous");
      } else {
        $usericon = '.noicon';
        if ($pLINKICON) { 
          $usericon = NULL;
          global $gLINKICON, $gLINKFULLNAME, $gONLINENOW;
          $gLINKICON = 'http://' . $pDOMAIN . '/icon/' . $pUSERNAME . '/';
          $online = NULL;
          $gONLINENOW = NULL;
          $gLINKFULLNAME = $pUSERNAME;
          $gONLINENOW = '';
        } // if
        if ($pDOMAIN != $gSITEDOMAIN) {
          if ((!$zAUTHUSER->Anonymous) and ($zAUTHUSER->Domain) and ($pDOMAIN != $zAUTHUSER->Domain)) {
            // Redirect to home domain for remote authentication.
            $target = $zAUTHUSER->Username . '@' . $zAUTHUSER->Domain;
            $location = "/profile/" . $pUSERNAME . "/";
            $gLINKUSERTARGET =  'http://' . $pDOMAIN . '/profile/' . $pUSERNAME . '/' . '?_bounce=' . $target;
          } else {
            $gLINKUSERTARGET =  'http://' . $pDOMAIN . '/profile/' . $pUSERNAME . '/';
          } // if
          $gLINKFULLNAME = $pUSERNAME . '@' . $pDOMAIN;
          $output = "<a class='remoteuser' href='$gLINKUSERTARGET'>$gLINKFULLNAME</a></span>";
          
          $output .= $zOLDAPPLE->IncludeFile ("$gTHEMELOCATION/objects/buttons/remoteuser$usericon.aobj", INCLUDE_SECURITY_BASIC, OUTPUT_BUFFER);
        } else {
          $gLINKUSERTARGET = '/profile/' . $pUSERNAME . '/';
          $gLINKFULLNAME = $pUSERNAME;
          $output = "<a class='localuser' href='$gLINKUSERTARGET'>$gLINKFULLNAME</a></span>";
        } // if
      } // if
  
      return ($output);
      
    } // CreateUserLink

    // Create a group link.
    function CreateGroupLink ($pGROUPNAME, $pDOMAIN = NULL) {

      global $gUSERTHEME, $gTHEMELOCATION;
      global $gLINKGROUPTARGET;
      global $gLINKGROUPNAME;
      global $gLINKICON;
      global $gSITEDOMAIN;

      global $zAUTHUSER;

      global $zOLDAPPLE;

      $output = "";

      $gLINKGROUPNAME = $pGROUPNAME;
      if (!$pDOMAIN) $pDOMAIN = $gSITEDOMAIN;

      if ($pDOMAIN != $gSITEDOMAIN) {
        if (($zAUTHUSER->Domain) and ($pDOMAIN != $zAUTHUSER->Domain)) {
          // Redirect to home domain for remote authentication.
          $target = $zAUTHUSER->Username . '@' . $zAUTHUSER->Domain;
          $location = "/group/" . $pGROUPNAME . "/";
          $gLINKGROUPTARGET = 'http://' . $pDOMAIN . '/group/' . $pGROUPNAME . '/' . '?_bounce=' . $target;
        } else {
          $gLINKGROUPTARGET = 'http://' . $pDOMAIN . '/group/' . $pGROUPNAME . '/';
        } // if
        $output .= $zOLDAPPLE->IncludeFile ("$gTHEMELOCATION/objects/icons/remotegroup.aobj", INCLUDE_SECURITY_BASIC, OUTPUT_BUFFER);
        $output .= $zOLDAPPLE->IncludeFile ("$gTHEMELOCATION/objects/buttons/remotegroup.aobj", INCLUDE_SECURITY_BASIC, OUTPUT_BUFFER);
        $output = str_replace("\n", "", $output);
        $output = str_replace("\r", "", $output);
      } else {
        $gLINKGROUPTARGET = '/group/' . $pGROUPNAME . '/';
        $output .= $zOLDAPPLE->IncludeFile ("$gTHEMELOCATION/objects/icons/localgroup.aobj", INCLUDE_SECURITY_BASIC, OUTPUT_BUFFER);
        $output .= $zOLDAPPLE->IncludeFile ("$gTHEMELOCATION/objects/buttons/localgroup.aobj", INCLUDE_SECURITY_BASIC, OUTPUT_BUFFER);
        $output = str_replace("\n", "", $output);
        $output = str_replace("\r", "", $output);
      } // if
  
      unset ($gLINKGROUPNAME);
      unset ($gLINKGROUPTARGET);
      return ($output);
      
    } // CreateGroupLink

    // Create a pop up link.
    function CreatePopup ($pTARGET, $pTEXT, $pPOPWIDTH, $pPOPHEIGHT, $pSTYLECLASS = "", $pIMAGE = "", $pWIDTH = "", $pHEIGHT = "") {

      // If there's no link image or text, just return a space.
      if ( ($pTEXT == "") and ($pIMAGE == "") ) {
        $this->Output = "&nbsp;";
        return ($this->Output);
      } // if

      if ($pSTYLECLASS) {
        $styledef = "class='$pSTYLECLASS' ";
      } else {
        $styledef = "";
      } // if

      if ($pWIDTH) $pWIDTH = "width='$pWIDTH' ";
      if ($pHEIGHT) $pHEIGHT = "height='$pHEIGHT' ";

      if ($pIMAGE) {
        $this->Output = "<a href='$_SERVER[REQUEST_URI]#' $styledef onClick=\"javascript:jPOPUP('$pTARGET', '$pPOPWIDTH', '$pPOPHEIGHT'); return false;\"><img src='$pIMAGE' $pWIDTH $pHEIGHT border='0' /></a>";
      } else {
        $this->Output = "<a href='$_SERVER[REQUEST_URI]#' $styledef onClick=\"javascript:jPOPUP('$pTARGET', '$pPOPWIDTH', '$pPOPHEIGHT'); return false;\">$pTEXT</a>";
      } // if

      return ($this->Output);

    } // CreatePopup

    // Display a pop up link.
    function Popup ($pTARGET, $pTEXT, $pPOPWIDTH, $pPOPHEIGHT, $pSTYLECLASS = "", $pIMAGE = "", $pWIDTH = "", $pHEIGHT = "") {

      $this->CreatePopup ($pTARGET, $pTEXT, $pPOPWIDTH, $pPOPHEIGHT, $pSTYLECLASS, $pIMAGE, $pWIDTH, $pHEIGHT);
  
      echo $this->Output;
      return (0);

    } // Popup

    // Format a link that uses POST to move data.
    function CreateLink ($pTARGET, $pTEXT, $pDATALIST = array (), $pSTYLECLASS = "", $pIMAGE = "", $pWIDTH = "", $pHEIGHT = "", $pCONFIRM = NULL) {

      // NOTE:  This severely needs to be cleaned up and commented.

      global $gPOSTDATA;

      // If there's no link image or text, just return a space.
      if ( ($pTEXT == "") and ($pIMAGE == "") ) {
        $this->Output = "&nbsp;";
        return ($this->Output);
      } // if

      // Loop through the data

      $finalstring = NULL;

      // Append data from pDATALIST
      if (isset ($pDATALIST)) {
        foreach ($pDATALIST as $listkey => $listvalue) {
          // Add the hidden 'g' to signify a global variable.
          $fullkeyname = "g" . $listkey;

          if (is_array ($listvalue) ) {
            foreach ($listvalue as $sublistkey => $sublistvalue) {

              // If this key was previously declared, then continue on.
              if (isset($pDATALIST[$fullkeyname])) continue;

              // Add slashes to prevent ' from breaking jPOSTLINK
              // NOTE: Still doesn't pass single quotes back to browser right.
              $sublistvalue = addslashes ($sublistvalue);
  
              $finalstring .= "$fullkeyname" . "[" . $sublistkey . "]=$sublistvalue&";

            } // foreach
          } else {
            // If this key was previously declared, then continue on.
            if (isset($pDATALIST[$fullkeyname])) continue;

            $finalstring .= "$fullkeyname=$listvalue&";

            // Add slashes to prevent ' from breaking jPOSTLINK
            // NOTE: Still doesn't pass single quotes back to browser right.
            $listvalue = addslashes ($listvalue);
  
          } // if

        } // foreach

      } // if

      // Append global data from gPOSTDATA
      if (isset ($gPOSTDATA)) {
        foreach ($gPOSTDATA as $postkey => $postvalue) {

          if (isset($pDATALIST[$postkey])) continue;
          // Add the hidden 'g' to signify a global variable.
          $fullkeyname = "g" . $postkey;

          if (is_array ($postvalue) ) {
            foreach ($postvalue as $subpostkey => $subpostvalue) {

              // If this key was previously declared, then continue on.
              if (($pDATALIST[$fullkeyname] != "") or ($pDATALIST[$fullkeyname] == "0")) continue;

              $finalstring .= "$fullkeyname" . "[" . $subpostkey . "]=$subpostvalue&";

            } // foreach
          } else {
            // If this key was previously declared, then continue on.
            if (isset($pDATALIST[$fullkeyname])) continue;

            $finalstring .= "$fullkeyname=$postvalue&";

          } // if

          // Add slashes to prevent ' from breaking jPOSTLINK
          // NOTE: Still doesn't pass single quotes back to browser right.
          if (!is_array ($postvalue)) $postvalue = addslashes ($postvalue);

        } // foreach

      } // if

      if ($pWIDTH)  $pWIDTH  = "width='$pWIDTH' ";
      if ($pHEIGHT) $pHEIGHT = "height='$pHEIGHT' ";

      if ($pSTYLECLASS) {
        $styledef = "class='$pSTYLECLASS' ";
      } else {
        $styledef = "";
      } // if

      if ($pIMAGE) {
        //$this->Output = "<a " . $styledef . "href=\"$pTARGET\" onClick=\"javascript:jPOSTLINK('$pTARGET', '$finalstring', '$pCONFIRM'); return false;\" ><img src='$pIMAGE' $pWIDTH $pHEIGHT border='0' /></a>";
        $this->Output = "<a " . $styledef . "href=\"$pTARGET?$finalstring\" confirm=\"$pCONFIRM\"><img src='$pIMAGE' $pWIDTH $pHEIGHT border='0' /></a>";
      } else {
        //$this->Output = "<a " . $styledef . "href=\"$pTARGET\" onClick=\"jPOSTLINK('$pTARGET', '$finalstring', '$pCONFIRM'); return false;\">$pTEXT</a>";
        $this->Output = "<a " . $styledef . "href=\"$pTARGET?$finalstring\" confirm=\"$pCONFIRM\">$pTEXT</a>";
      } // if

      return ($this->Output);

    } // CreateLink

    function ButtonLink ($pBUTTONNAME, $pTARGET, $pCONFIRMATION = "", $pDISABLED = ENABLED, $pACTION = "", $pACTIONNAME) {
      global $gTHEMELOCATION;
      global $gCONFIRM;  $gCONFIRM = $pCONFIRMATION;
      global $gBUTTONACTION, $gBUTTONNAME;
      global $gACTIONNAME;

      global $zOLDAPPLE;

      $style = strtolower ($pBUTTONNAME);

      if ($pACTION)
        $gBUTTONACTION = $pACTION;
      else
        $gBUTTONACTION = strtoupper ($pBUTTONNAME);

      if ($pACTIONNAME)
        $gACTIONNAME = $pACTIONNAME;
      else
        $gACTIONNAME = 'ACTION';

      if ($pDISABLED == DISABLED) $pBUTTONNAME .= ".disabled";

      $gBUTTONNAME = str_replace ('_', ' ', $gBUTTONACTION);
      $gBUTTONNAME = ucwords(strtolower($gBUTTONNAME));

      $filelocation = "$gTHEMELOCATION/objects/buttonlinks/$pBUTTONNAME.aobj";

      global $gPOSTDATA;
      $gPOSTDATA[$gACTIONNAME] = $pBUTTONNAME;

      $image = $zOLDAPPLE->IncludeFile ($filelocation, INCLUDE_SECURITY_BASIC, OUTPUT_BUFFER);
      echo $this->CreateImageLink ($pTARGET, $gPOSTDATA, $image, NULL, NULL, $pCONFIRMATION);

      unset ($gCONFIRM);

      return (0);
    } // ButtonLink

    // Output a button object.
    // NOTE: This should wrap CreateButton.
    function Button ($pBUTTONNAME, $pCONFIRMATION = "", $pDISABLED = ENABLED, $pACTION = "", $pACTIONNAME = "") {
      
      $button = $this->CreateButton ($pBUTTONNAME, $pCONFIRMATION, $pDISABLED, $pACTION, $pACTIONNAME);
      
      echo $button;
      
      return (true);
    } // Button

    // Format a button object.
    // Note:  Button should encapsulate this function.
    function CreateButton ($pBUTTONNAME, $pCONFIRMATION = "", $pDISABLED = ENABLED, $pACTION = NULL, $pACTIONNAME = "") {
      
      global $gTARGET;
      global $gTHEMELOCATION;
      global $gCONFIRM;  $gCONFIRM = $pCONFIRMATION;
      global $gBUTTONACTION, $gBUTTONNAME;
      global $gACTIONNAME;

      global $zOLDAPPLE;

      $style = strtolower ($pBUTTONNAME);
      
      $disabled = false;
      if ($pDISABLED == DISABLED) $disabled = 'disabled="disabled"';
      
      $confirm = false;
      if ($pCONFIRMATION) $confirm = "onClick='return jCONFIRM(\"$pCONFIRMATION\")'";
      
      if ($pACTION)
        $gBUTTONACTION = $pACTION;
      else
        $gBUTTONACTION = strtoupper ($pBUTTONNAME);

      if ($pACTIONNAME)
        $gACTIONNAME = 'g' . $pACTIONNAME;
      else
        $gACTIONNAME = 'gACTION';

      // Output a generic HTML button.
      $return  = " <button $disabled $confirm type='submit' name='$gACTIONNAME' value='$gBUTTONACTION'>";
      $return .= __($pBUTTONNAME);
      $return .= "</button>";
      
      unset ($gCONFIRM);
      
      return ($return);
      
      global $gTHEMELOCATION;
      global $gCONFIRM;  $gCONFIRM = $pCONFIRMATION;
      global $gBUTTONACTION, $gBUTTONNAME, $gACTIONNAME; 
      
      global $zOLDAPPLE, $zHTML;

      $style = strtolower ($pBUTTONNAME);

      if ($pACTION)
        $gBUTTONACTION = $pACTION;
      else
        $gBUTTONACTION = strtoupper ($pBUTTONNAME);

      if ($pACTIONNAME)
        $gACTIONNAME = 'g' . $pACTIONNAME;
      else
        $gACTIONNAME = 'gACTION';

      $gBUTTONNAME = str_replace ('_', ' ', $gBUTTONACTION);
      $gBUTTONNAME = ucwords(strtolower($gBUTTONNAME));

      $filelocation = "$gTHEMELOCATION/objects/buttons/$pBUTTONNAME.aobj";

      // Check if the button we're requesting exists or not.
      if (!file_exists ($filelocation) ) {
        // Output a generic HTML button.
        $returnoutput  = "<span class='$style'>\n";
        $returnoutput .= " <input type='submit' name='$pBUTTONNAME' value='$pBUTTONNAME' />\n";
        $returnoutput .= "</span> <!-- .$style -->\n";
      } else {
        $returnoutput = $zOLDAPPLE->IncludeFile ($filelocation, INCLUDE_SECURITY_BASIC, OUTPUT_BUFFER);
      } // if

      unset ($gCONFIRM);

      return ($returnoutput);
    } // CreateButton

    // Output a title list according to class definition.
    // NOTE: Remove and replace with frame objects/css.
    function Titles ($pCLASSREF, $pSTYLEID, $pTARGET) {
      global $gSORT;
      global $gPOSTDATA;
      global $$pCLASSREF;

      // NOTE: Encapsulate HTML into an object.

      echo "<div id='$pSTYLEID'>\n";

        foreach ($$pCLASSREF->FieldDefinitions as $fieldname => $fielddef) {
          $dbfield = $fieldname;
          $identifier = $fielddef['identifier'];
  
          $fieldid  = strtolower ($fieldname);
          echo "<span class='$fieldid'>\n";
  
            if ($gSORT == $dbfield) {
              $sortfield = $dbfield . " DESC";
              $identifier = $identifier . " +";
            } elseif ($gSORT == "$dbfield DESC") {
              $sortfield = $dbfield;
              $identifier = $identifier . " -";
            } else {
              $sortfield = $dbfield;
            } // if

            $gEXTRAPOSTDATA['SORT'] = $sortfield;
            $this->Link ($pTARGET, $identifier, $gEXTRAPOSTDATA);
            echo "\n";

          echo "</span>";

        } // foreach

      echo "</div> <!-- #$pSTYLEID -->\n";

    } // Titles

    // Output hidden data from datalist.
    function Post ($pDATALIST) {
      echo $this->PostData ($pDATALIST);
       
      return (0);
    } // Post

    // Format hidden data from datalist.
    function PostData ($pDATALIST) {
      $this->Output = "";
      foreach ($pDATALIST as $dkey => $dvalue) {
        if (is_array ($pDATALIST[$dkey]) ) {
          foreach ($pDATALIST[$dkey] as $vkey => $vvalue) {
            $this->Output .= "<input type='hidden' name='g$dkey" . "[" . $vkey . "]' value='$vvalue' />\n";  
          } // foreach
        } else {
          $this->Output .= "<input type='hidden' name='g$dkey' value='$dvalue' />\n";  
        } // if
      } // foreach

      return ($this->Output);
    } // PostData

    // Calculate the scroll values.
    function CalcScroll ($pCONTEXT) {
      global $gMAXPAGES, $gSCROLLMAX, $gSCROLLSTEP;
      global $gCURRENTPAGE, $gSCROLLSTART;

      // If no starting point is set, start at '0'
      if (!isset($gSCROLLSTART[$pCONTEXT])) $gSCROLLSTART[$pCONTEXT] = 0;
      
      // Determine the Maximum and Current page amounts.
      $gMAXPAGES = ceil ($gSCROLLMAX[$pCONTEXT] / $gSCROLLSTEP[$pCONTEXT]);
      $gCURRENTPAGE = ceil ($gSCROLLSTART[$pCONTEXT] / $gSCROLLSTEP[$pCONTEXT]) + 1;

      // Check if we're over the max number of pages.
      if ($gMAXPAGES < $gCURRENTPAGE) $gMAXPAGES = $gCURRENTPAGE;

      return (0);
    } // CalcScroll

    // Output scroll buttons
    function Scroll ($pTARGET, $pCONTEXT, $pSCROLLTYPE = "", $pPREVWIDTH = "", $pFOOTWIDTH = "", $pNEXTWIDTH = "") {
    	
      global $gSCROLLMAX, $gSCROLLSTART, $gSCROLLSTEP;
      global $gSORT, $gCURRENTPAGE, $gMAXPAGES;
      global $gPOSTDATA;
      
      // If gSCROLLSTART[$pCONTEXT] isn't declared, set it to start at 0.
      if ($gSCROLLSTART[$pCONTEXT] == "") $gSCROLLSTART[$pCONTEXT] = 0;
 
      // Calculate scroll values.
      $this->CalcScroll ($pCONTEXT);
      
      if ($gMAXPAGES == 1) return (false);
      
      /*
      echo $gSCROLLMAX[$pCONTEXT], "<br />";
      echo $gSCROLLSTART[$pCONTEXT], "<br />";
      echo $gSCROLLSTEP[$pCONTEXT], "<br />";
      echo $gCURRENTPAGE, "<br />";
      echo $gMAXPAGES, "<br />";
      exit;
      */
      
      $previouspage = $gCURRENTPAGE - 1;
      if ($previouspage < 1) $previouspage = 1;
      $previouspage = ( ($previouspage- 1) * $gSCROLLSTEP[$pCONTEXT]);
      
      $nextpage = $gCURRENTPAGE + 1;
      if ($nextpage > $gMAXPAGES) $nextpage = $gMAXPAGES;
      $nextpage = ( ($nextpage- 1) * $gSCROLLSTEP[$pCONTEXT]);
      
      $lastpage = ( ($gMAXPAGES- 1) * $gSCROLLSTEP[$pCONTEXT]);
      global $zHTML;
      
      echo '<form name="scroll" method="POST" action="' . $pTARGET . '">';
      echo $this->PostData($gPOSTDATA); 
      echo '<nav class="scroll"> ';
      echo '  <ol> ';
      echo '    <li><span>' . $zHTML->CreateButton ('First', NULL, "", '0', 'SCROLLSTART[' . $pCONTEXT . ']') . '</span></li> ' . "\n";
      echo '    <li><span>' . $zHTML->CreateButton ('Previous', NULL, "", $previouspage, 'SCROLLSTART[' . $pCONTEXT . ']') . '</span></li> ' . "\n";
      
      $step = 1;
      if ($gMAXPAGES > 20) $step = 5;
      if ($gMAXPAGES > 100) $step = 10;
      
      // Put the page numbers together.
      for ($p = 1; $p <= $gMAXPAGES; $p += $step) {
        $pagetarget = ( ($p - 1) * $gSCROLLSTEP[$pCONTEXT]) - 1;
        
      	$pagenumberlist[$p] = '    <li><span>' . $zHTML->CreateButton ($p, NULL, "", $pagetarget, 'SCROLLSTART[' . $pCONTEXT . ']') . '</span></li> ';
      }
      
      // Add the current page, in case we skipped it.
      $currentpagetarget = ( ($gCURRENTPAGE - 1) * $gSCROLLSTEP[$pCONTEXT]) - 1;
      $pagenumberlist[$gCURRENTPAGE] = '    <li class="selected"><span>' . $zHTML->CreateButton ($gCURRENTPAGE, NULL, "", $currentpagetarget, 'SCROLLSTART[' . $pCONTEXT . ']') . '</span></li> ';
      
      ksort ($pagenumberlist);
      
      // Echo the page numbers
      foreach ($pagenumberlist as $p => $pnuml) {
      	echo $pnuml . "\n";
      } 
      
      echo '    <li><span>' . $zHTML->CreateButton ('Next', NULL, "", $nextpage, 'SCROLLSTART[' . $pCONTEXT . ']') . '</span></li> ' . "\n";
      echo '    <li><span>' . $zHTML->CreateButton ('Last', NULL, "", $lastpage, 'SCROLLSTART[' . $pCONTEXT . ']') . '</span></li> ' . "\n";
      echo '  </ol> ';
      echo '</nav> ';
      echo '</form>';

      return (true);
     
    } // Scroll
    
    // Output the Show All button.
    function ShowAll ($pTARGET = "") {
      global $gCRITERIA;
      global $gSHOWTARGET;

      if ($pTARGET) {
        $gSHOWTARGET = $pTARGET;
      } else {
        $gSHOWTARGET = $_SERVER[REQUEST_URI];
      } // if

      if ($gCRITERIA) {
        $output = $this->CreateButton ('Reset Search');
      } else {
        $output = $this->CreateButton ('Reset Search', null, DISABLED);
      } // if

      echo $output;

      return (0);

    } // Showall

    // Output the header object.
    function Header () {
      global $gTHEMELOCATION;
      global $zOLDAPPLE;
      global $zAUTHUSER, $zLOCALUSER;

      $zLOCALUSER->Access (FALSE, FALSE, FALSE, "/admin/");
  
      if ( !$zAUTHUSER->Anonymous) {
        // Choose which logged in header to use.
        if ($zLOCALUSER->userAccess->r == TRUE) {
          // Push the admin header.
          $zOLDAPPLE->IncludeFile ("$gTHEMELOCATION/objects/header/admin.aobj");
        } else {
          if ($zAUTHUSER->Remote) {
            // Push the local logged-in header.
            $zOLDAPPLE->IncludeFile ("$gTHEMELOCATION/objects/header/remote.aobj", INCLUDE_SECURITY_NONE);
          } else {
            // Push the local logged-in header.
            $zOLDAPPLE->IncludeFile ("$gTHEMELOCATION/objects/header/focus.aobj");
          } // if
        } 
      } else {
        // Push the anonymous header.
        $zOLDAPPLE->IncludeFile ("$gTHEMELOCATION/objects/header/main.aobj");
      } // if
  
    } // Header

    // Output the footer object.
    function Footer () {
      global $gTHEMELOCATION;
      global $zLOCALUSER, $zAUTHUSER, $zOLDAPPLE;
  
      $zLOCALUSER->Access (FALSE, FALSE, FALSE, "/admin/");
  
      if ( !$zAUTHUSER->Anonymous) {
        if ($zLOCALUSER->userAccess->r == TRUE) {
          // Push the admin footer.
          $zOLDAPPLE->IncludeFile ("$gTHEMELOCATION/objects/footer/admin.aobj");
        } else {
        // Push the logged-in footer.
          $zOLDAPPLE->IncludeFile ("$gTHEMELOCATION/objects/footer/focus.aobj");
        } 
      } else {
        // Push the footer.
        $zOLDAPPLE->IncludeFile ("$gTHEMELOCATION/objects/footer/main.aobj");
      } // if
  
    } // Footer
    
    // Add a script to be loaded at runtime.
    function AddScript ($pSCRIPTNAME) {
      global $gFRAMELOCATION;
      $url = $gFRAMELOCATION . "javascript/" . $pSCRIPTNAME;
      $this->ScriptList[] = $url;
      
      return (TRUE);
    } // AddScript

    // Output the title object.
    function Title ($pPAGETITLE = NULL, $pUSERDEFINEDSTYLE = NULL) {

      global $gTHEMELOCATION, $gFRAMELOCATION, $gSITEURL, $gPAGETITLE, $gPAGESUBTITLE;
      global $zOLDAPPLE;

      global $gSTYLELOCATION;
      global $gUSERDEFINEDSTYLE;
      
      global $bSCRIPTLIST;
      
      $bSCRIPTLIST = null;
      if (count($this->ScriptList) > 0) {
        foreach ($this->ScriptList as $scriptname) {
          global $gSCRIPTNAME;
          $gSCRIPTNAME = $scriptname;
          $bSCRIPTLIST .= $zOLDAPPLE->IncludeFile ("$gFRAMELOCATION/objects/common/script.aobj", INCLUDE_SECURITY_NONE, OUTPUT_BUFFER);
        } // foreach
      } else {
       $bSCRIPTLIST = ' ';
      } // if

      // Determine directory for CSS file.
      $stylecontextarray = explode ('.', $zOLDAPPLE->Context);
      $gSTYLELOCATION = $stylecontextarray[0];

      if ($pPAGETITLE) {
        $gPAGETITLE = $pPAGETITLE;
      } // if

      if ($gPAGESUBTITLE) {
        $gPAGETITLE .= $gPAGESUBTITLE;
      } // if 

      if ($pUSERDEFINEDSTYLE) {
        $gUSERDEFINEDSTYLE = $pUSERDEFINEDSTYLE;
      } // if
  
      $zOLDAPPLE->IncludeFile ("$gTHEMELOCATION/objects/common/title.aobj", INCLUDE_SECURITY_NONE);
  
    } // Title  

    // Output a menu based on a datalist.
    function Menu ($pVARNAME, $pDATALIST, $pDEFAULT, $pSELECTED = "", $pAUTOSUBMIT = FALSE, $pACTION = "") {

      unset ($this->Output);

      if (!$pDATALIST) {
        echo '&nbsp;';
        return (FALSE);
      }

      if ($pVARNAME[0] == 'g') {
        $classname = strtolower (substr ($pVARNAME, 1, strlen ($pVARNAME)-1));
      } else {
        $classname = strtolower ($pVARNAME);
      } // if

      // Add a 'g' at the beginning to signify a global variable.
      if ($pVARNAME[0] != 'g') $pVARNAME = 'g' . $pVARNAME;
      $nameGlobal = $pVARNAME;

      if ($pAUTOSUBMIT) {
        if ($pACTION) {
          $autosubmit = " onChange='JavaScript:jACTIONSUBMIT(\"$pACTION\", \"$pFORM\");' ";
        } else {
          $autosubmit = " onChange='JavaScript:submit();' ";
        } // if 
      } // if
      
      $this->Output  = "<span class='$classname'>";
      $this->Output .= "<select class='$classname' name='" . $pVARNAME . "' $autosubmit >\n";
  
      if (count($pDATALIST) == 0) {
        $this->Output = "No Data Found";
        return (-1);
      } // if
  
      global $$nameGlobal;
      foreach ($pDATALIST as $datavalue => $datalabel) {
        $hValue = htmlspecialchars ($datavalue);
  
        $disabled = "";
        if (substr ($datalabel, 0, 2) == MENU_DISABLED) {
          $datalabel = substr_replace ($datalabel, "", 0, 2);
          $disabled = "disabled";
        } // if

        if ($$nameGlobal) {
          // Using a post variable
          if ($datavalue == $$nameGlobal) {
            $this->Output .= "<option $disabled selected value=\"$hValue\">" .
                              "$datalabel</option>\n";
          } else {
            $this->Output .= "<option $disabled value=\"$hValue\">" .
                             "$datalabel</option>\n";
          } // if
        } elseif ($pSELECTED != "") {
          // Using a selected value.
          if ($datavalue == "$pSELECTED" ) {
            $this->Output .= "<option $disabled selected value=\"$hValue\">" .
                              "$datalabel</option>\n";
          } else {
            $this->Output .= "<option $disabled value=\"$hValue\">" .
                             "$datalabel</option>\n";
          } // if
        } else {
          // Using a default value.
          if ($pDEFAULT == 1) {
            $this->Output .= "<option $disabled selected value=\"$hValue\">" .
                              "$datalabel</option>\n";
          } else {
            $this->Output .= "<option $disabled value=\"$hValue\">" .
                             "$datalabel</option>\n";
          } // if
        } // if
  
      } // foreach
      $this->Output .= "</select>\n";
      $this->Output .= "</span> <!-- .$classname -->\n";
 
      echo $this->Output;
 
     } // Menu

     function DateMenu ($pVARNAME, $pMONTH, $pDAY, $pYEAR) {
        
       $vName_mo = $pVARNAME . "_MO";
       $vName_yr = $pVARNAME . "_YR";
       $vName_dy = $pVARNAME . "_DY";

       // Output the month menu.
       $OPTION = new cSYSTEMOPTIONS;
       $OPTION->Menu ("MONTH", $pMONTH, $vName_mo);

       // Create a day list from 1 to 31.
       for ($daycount = 1; $daycount < 32; $daycount++) {
         $daylist[$daycount] = $daycount;
       };

       // Output the day menu.
       $this->Menu ($vName_dy, $daylist, FALSE, $pDAY);

       // Create a year list from 2000 to 1900.
       for ($yearcount = 2032; $yearcount > 1999; $yearcount--) {
         $yearlist[$yearcount] = $yearcount;
       };

       // Output the year menu.
       $this->Menu ($vName_yr, $yearlist, FALSE, $pYEAR);
       
       return (0);

     } // DateMenu

     function TimeMenu ($pVARNAME, $pHOUR, $pMINUTE) {
        
       $vName_hr = $pVARNAME . "_HR";
       $vName_mn = $pVARNAME . "_MN";
       $vName_sc = $pVARNAME . "_SC";

       // Create a hour list from 01 to 24.
       for ($hourcount = 1; $hourcount < 25; $hourcount++) {
         if ($hourcount < 10) {
           $hourlist[$hourcount] = "0" . $hourcount;
         } else {
           $hourlist[$hourcount] = $hourcount;
         } // if
       };

       // Output the day menu.
       $this->Menu ($vName_hr, $hourlist, FALSE, $pHOUR);

       // Create a minute list from 00 to 59.
       for ($minutecount = 0; $minutecount < 60; $minutecount++) {
         if ($minutecount < 10) {
           $minutelist[$minutecount] = "0" . $minutecount;
         } else {
           $minutelist[$minutecount] = $minutecount;
         } // if
       };

       // Output the minute menu.
       $this->Menu ($vName_mn, $minutelist, FALSE, $pMINUTE);
       
       return (0);

     } // DateMenu

     function BirthDateMenu ($pVARNAME, $pMONTH, $pDAY, $pYEAR) {
        
       $vName_mo = $pVARNAME . "_MO";
       $vName_yr = $pVARNAME . "_YR";
       $vName_dy = $pVARNAME . "_DY";

       // Output the month menu.
       $OPTION = new cSYSTEMOPTIONS;
       $OPTION->Menu ("MONTH", $pMONTH, $vName_mo);

       // Create a day list from 1 to 31.
       for ($daycount = 1; $daycount < 32; $daycount++) {
         $daylist[$daycount] = $daycount;
       };

       // Output the day menu.
       $this->Menu ($vName_dy, $daylist, FALSE, $pDAY);

       // Create a year list from 2000 to 1900.
       for ($yearcount = 2000; $yearcount > 1900; $yearcount--) {
         $yearlist[$yearcount] = $yearcount;
       };

       // Output the year menu.
       $this->Menu ($vName_yr, $yearlist, FALSE, $pYEAR);
       
       return (0);

     } // DateMenu

     // Join a date together from form data.
     function JoinDate ($pVARNAME) {

       $vName_mo = "g" . $pVARNAME . "_MO";
       $vName_yr = "g" . $pVARNAME . "_YR";
       $vName_dy = "g" . $pVARNAME . "_DY";
       $vName_hr = "g" . $pVARNAME . "_HR";
       $vName_mn = "g" . $pVARNAME . "_MN";

       global $$vName_mo, $$vName_yr, $$vName_dy;
       global $$vName_hr, $$vName_mn;

       // Pad value with zeros, place in database format.
       $fulldate = $$vName_yr . "-" . sprintf ("%02s", trim ($$vName_mo) ) . 
                   "-" .  sprintf ("%02s", trim ($$vName_dy) );

       if ($$vName_hr) {
         $fulldate .= " " . $$vName_hr . ":" . $$vName_mn . ":00";
       } // if

       return ($fulldate);

     } // JoinDate

     // Split a database date into month/day/year
     function SplitDate ($pDATEVAL) {
       $datelist = explode (' ', date ("m d Y H i", strtotime ($pDATEVAL)));

       $datearray['MONTH'] = $datelist[0];
       $datearray['DAY'] = $datelist[1];
       $datearray['YEAR'] = $datelist[2];
       $datearray['HOUR'] = $datelist[3];
       $datearray['MINUTE'] = $datelist[4];

       return ($datearray);
     } // SplitDate

     // Refresh the page to specified location.
     function Refresh ($pLOCATION) {
       global $gREFRESHWAIT;
   
       $returnvalue = "  <meta http-equiv='Refresh' content='$gREFRESHWAIT;url=$pLOCATION'>";
   
       return ($returnvalue);
   
     } // Refresh

  } // cOLDHTML
  
  class cIMAGE {

    var $Error, $Message, $Resource, $PageContext, $Type;

    function cIMAGE ($pDEFAULTCONTEXT = "") {
      $this->Error = 0;
      $this->Errorlist = array ('' => '');
      $this->Message = '';
      $this->Resource = '';
      $this->Type = '';
      $this->Width = '';
      $this->Height = '';
      $this->PageContext = $pDEFAULTCONTEXT;

    } // Constructor

    // Validate the image.
    function Validate ($pFILENAME, $pERROR, $pMAXWIDTH = "", $pMAXHEIGHT = "") {
      global $gMAXSIZE, $MAX_FILE_SIZE;

      // Check for an error in the upload.
      switch ($pERROR) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
          $gMAXSIZE = $MAX_FILE_SIZE / 1000 . "k";
          $this->Errorlist['image'] = __("File Too Big Error", array ("maxsize" => $gMAXSIZE));
          $this->Error = -1;
          unset ($gMAXSIZE);
          return (-1);
        break;

        case UPLOAD_ERR_PARTIAL:
          $this->Errorlist['image'] = __("Partial File Upload Error");
          $this->Error = -1;
          return (-1);
        break;

        case UPLOAD_ERR_NO_FILE:
          $this->Errorlist['image'] = __("No File Uploaded Error");
          $this->Error = -1;
          return (-1);
        break;

        case UPLOAD_ERR_NO_TMP_DIR:
          $this->Errorlist['image'] = __("No Temporary Directory Error");
          $this->Error = -1;
          return (-1);
        break;

        case UPLOAD_ERR_OK:
        default:
        break;

      } // switch

      // Check if file is an uploaded file.
      if (!is_uploaded_file ($pFILENAME) ) {
        $this->Error = -1;
        $this->Errorlist['image'] = __("No Upload Error");
        return (-1);
      } // if

      // Check if the file is a valid image file.
      if (!getimagesize($pFILENAME)) {
        $this->Error = -1;
        $this->Errorlist['image'] = __("Invalid Image Error");
        return (-1);
      } // if

      // Retrieve file attributes.
      list($width, $height, $type, $attr) = getimagesize($pFILENAME);

      // Check if the file is not the proper type.
      if ( ($type != IMAGETYPE_PNG) and ($type != IMAGETYPE_GIF) and
           ($type != IMAGETYPE_JPEG) and ($type != IMAGETYPE_WBMP) ) {
        $this->Errorlist['image'] = __("Wrong Image Type Error");
        $this->Error = -1;
        return (-1);
      } // if

      // If MAXWIDTH and MAXHEIGHT are set, validate sizes.
      if ( ($pMAXWIDTH) and ($pMAXHEIGHT) ) {
        // Check if the file is too wide/high.
        if ( ($width > $pMAXWIDTH) or ($height > $pMAXHEIGHT) ) {
          $this->Errorlist['image'] = __("Wrong Image Size Error", array ("maxwidth" => $pMAXWIDTH, "maxheight" => $pMAXHEIGHT));
          $this->Error = -1;
          return (-1);
        } // if
      } // if

      return (0);
    } // Validate

    // Set the attributes for an image.
    function Attributes ($pFILENAME) {

      // Retrieve file attributes.
      list($width, $height, $type, $attr) = getimagesize($pFILENAME);

      // Assign Values
      $this->Width = $width;
      $this->Height = $height;
      $this->Type = $type;

      return (0);
    } // Attributes

    // Convert the file to a an image resource.
    function Convert ($pFILENAME) {

      // Retrieve file attributes.
      list($width, $height, $type, $attr) = getimagesize($pFILENAME);

      // Determine which type of file to convert from.
      switch ($type) {
        case IMAGETYPE_PNG:
          $src_img = imagecreatefrompng ($pFILENAME);
        break;

        case IMAGETYPE_WBMP:
          $src_img = imagecreatefromwbmp ($pFILENAME); 
        break;

        case IMAGETYPE_JPEG:
          $src_img = imagecreatefromjpeg ($pFILENAME); 
        break;

        case IMAGETYPE_GIF:
          $src_img = imagecreatefromgif ($pFILENAME); 
        break;
      } // switch

      // Copy the source image.
      $this->Resource = imagecreatetruecolor ($width, $height);
      $result = imagecopy($this->Resource,$src_img,0,0,0,0,$width,$height);

      return ($result);

    } // Convert

    // Resize an image.
    function Resize ($pNEWWIDTH, $pNEWHEIGHT, $pPROPORTIONAL = TRUE, $pXONLY = FALSE, $pYONLY = FALSE) {

      // Calculate the proportional new height and width.
      if ($pPROPORTIONAL) {
        if ($pNEWWIDTH && ($this->Width < $this->Height) && 
                          (!$pXONLY) || ($pYONLY)      ) {
             $pNEWWIDTH = ($pNEWHEIGHT / $this->Height) * $this->Width;
             $pNEWWIDTH = floor ($pNEWWIDTH);
        } else {
             $pNEWHEIGHT = ($pNEWWIDTH / $this->Width) * $this->Height;
             $pNEWHEIGHT = floor ($pNEWHEIGHT);
        } // if
      } // if

      $src_img = imagecreatetruecolor ($this->Width, $this->Height);
      imagecopy($src_img, $this->Resource, 0, 0, 0, 0, $this->Width, $this->Height);

      $this->Destroy ();

      $this->Resource = imagecreatetruecolor ($pNEWWIDTH, $pNEWHEIGHT);

      $result = imagecopyresampled($this->Resource, $src_img, 0, 0, 0, 0, $pNEWWIDTH, $pNEWHEIGHT, $this->Width, $this->Height);
      // int imagecopyresized(int dst_im, int src_im, int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h) 

      $this->Width = $pNEWWIDTH;
      $this->Height = $pNEWHEIGHT;

      return ($result);

    } // Resize
    
    // ResizeAndCrop an image.
    function ResizeAndCrop ($pNEWWIDTH, $pNEWHEIGHT) {

      if ($this->Height == $this->Width) {
        // Proportion is the same
        $newwidth = $pNEWWIDTH; $newheight = $pNEWHEIGHT;
        $startx = 0; $starty = 0;
        $endx = $pNEWWIDTH; $endy = $pNEWHEIGHT;
      } elseif ($this->Height > $this->Width) {
        // Proportion is vertical
        $newwidth = $pNEWWIDTH;
        $newheight = ($pNEWWIDTH / $this->Width) * $this->Height;
        $newheight = floor ($newheight);
        $startx = 0; $starty = floor((($newheight - $pNEWHEIGHT) / 2));
        $endy = $pNEWWIDTH; $endy = $newheight - ceil((($newheight - $pNEWHEIGHT) / 2));
      } else {
        // Proportion is horizontal
        $newwidth = ($pNEWHEIGHT / $this->Height) * $this->Width;
        $newwidth = floor ($newwidth);
        $newheight = $pNEWHEIGHT;
        $startx = floor((($newwidth - $pNEWWIDTH) / 2));  $starty = 0;
        $endx = $newwidth - ceil((($newwidth - $pNEWWIDTH) / 2));  $endy = $pNEWHEIGHT;
      } // if
      
      /*
      echo $this->Width, "<br />";
      echo $this->Height, "<br /><br />";
      echo $pNEWWIDTH, "<br />";
      echo $pNEWHEIGHT, "<br /><br />";
      echo $newwidth, "<br />";
      echo $newheight, "<br /><br />";
      echo $startx, "<br />";
      echo $starty, "<br />";
      echo $endx, "<br />";
      echo $endy, "<br />";
      exit;
      */
        
      $src_img = imagecreatetruecolor ($this->Width, $this->Height);
      imagecopy($src_img, $this->Resource, 0, 0, 0, 0, $this->Width, $this->Height);

      $this->Destroy ();

      $intermediary = imagecreatetruecolor ($newwidth, $newheight);
      $this->Resource = imagecreatetruecolor ($pNEWWIDTH, $pNEWHEIGHT);

      // Resize image.
      $result = imagecopyresampled($intermediary, $src_img, 0, 0, 0, 0, $newwidth, $newheight, $this->Width, $this->Height);
      
      // Crop image.
      $result = imagecopy($this->Resource, $intermediary, 0, 0, $startx, $starty, $pNEWWIDTH, $pNEWHEIGHT);

      imagedestroy ($intermediary);
      
      $this->Width = $pNEWWIDTH;
      $this->Height = $pNEWHEIGHT;

      return ($result);
    } // ResizeAndCrop

    // Display an image to the browser. 
    function Show ($pFILENAME) {

      // Load image attributes.
      $this->Attributes ($pFILENAME);
      
      // Determine which type of file to convert from.
      switch ($this->Type) {
        case IMAGETYPE_PNG:
          $src_img = imagecreatefrompng ($pFILENAME);
          ob_end_clean();
          header('Content-type: ' . image_type_to_mime_type(IMAGETYPE_PNG));
          imagepng ($src_img);
        break;

        case IMAGETYPE_WBMP:
          $src_img = imagecreatefromwbmp ($pFILENAME); 
          ob_end_clean();
          header('Content-type: ' . image_type_to_mime_type(IMAGETYPE_WBMP));
          imagewbmp ($src_img);
        break;

        case IMAGETYPE_JPEG:
          $src_img = imagecreatefromjpeg ($pFILENAME); 
          ob_end_clean();
          header('Content-type: ' . image_type_to_mime_type(IMAGETYPE_JPEG));
          imagejpeg ($src_img);
        break;

        case IMAGETYPE_GIF:
          $src_img = imagecreatefromgif ($pFILENAME); 
          ob_end_clean();
          header('Content-type: ' . image_type_to_mime_type(IMAGETYPE_GIF));
          imagegif ($src_img);
        break;
      } // switch

      imagedestroy  ($src_img);
      return (0);
    } // Show

    // Save the image resource to a file.
    function Save ($pFILENAME, $pTYPE = IMAGETYPE_JPEG) {

      // Delete the old file if it exists.
      if (file_exists ($pFILENAME) ) {
        // NOTE: Permission Denied error.
        // unlink ($pFILENAME);
      } // if

      // Determine which type of file to save to.
      switch ($pTYPE) {
        case IMAGETYPE_PNG:
          $result = imagepng($this->Resource, $pFILENAME);
        break;

        case IMAGETYPE_WBMP:
          $result = imagewbmp($this->Resource, $pFILENAME);
        break;

        case IMAGETYPE_JPEG:
          $result = imagejpeg($this->Resource, $pFILENAME, 100);
        break;

        case IMAGETYPE_GIF:
          $result = imagegif($this->Resource, $pFILENAME);
        break;
      } // switch

      // NOTE: Probably not the best way to do this.
      chmod ($pFILENAME, 0777);

      // Save the image resource .
      if (!$result) {

        $this->Error = -1;
        $this->Errorlist['image'] = __("Can't Save Error");
        return (-1);
      } // if
     
      return (0);
    } // Save

    // Destroy the image resource.
    function Destroy () {

      imagedestroy ($this->Resource);

    } // Destroy

    // Wrapper for base class Broadcast function.
    function Broadcast ($pCLASS = "", $pFIELDERROR = "") {

      $zBASE = new cBASEDATACLASS;
      $zBASE->Error = $this->Error;
      $zBASE->Errorlist = $this->Errorlist;
      $zBASE->Message = $this->Message;
      $zBASE->Broadcast ($pCLASS, $pFIELDERROR);
      unset ($zBASE);

      return (0);
    } // Broadcast

  } // cIMAGE

?>
