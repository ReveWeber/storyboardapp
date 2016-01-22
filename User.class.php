<?php

class User {
    protected $firstname;
    protected $lastname;
    public $userid;
    public $level; // account type for permissions
    protected $email;
    protected $company;
        
    public function modify($arr) {
        foreach($arr as $key=>$value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function save_new_info(&$db, $acct_data) {
        $updated_info = [];
        if ($acct_data['curr_password'] != '' && $acct_data['new_password'] != '') {
            //check and reset password. 
            //if incorrect current pw or nonmatching new pws, abort everything.
            if ($acct_data['new_password'] != $acct_data['new_password_verify']) {
                return [false, 'Error: New password entries do not match.'];
            }
            try {
                $sql = 'SELECT password_hash 
                FROM sb_users 
                WHERE userid = :userid';
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':userid', $this->userid);
                $stmt->execute();
                $stmt->bindColumn('password_hash', $password_hash);
                $errorInfo = $stmt->errorInfo();
                if (isset($errorInfo[2])) {
                    $error = $errorInfo[2];
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            if($error) {
                return [false, $error];
            }
            $stmt->fetch(PDO::FETCH_BOUND);
            if ($password_hash) {
                if (!password_verify($acct_data['curr_password'], $password_hash)) {
                    return [false, "Error: Current password is incorrect."];
                } else {
                    $new_hash = password_hash($acct_data['new_password'], PASSWORD_DEFAULT);
                    try {
                        $sql = 'UPDATE sb_users
                                SET password_hash = :password_hash
                                WHERE userid = :userid';
                        $stmt = $db->prepare($sql);
                        $stmt->bindValue(':password_hash', $new_hash);
                        $stmt->bindValue(':userid', $this->userid);
                        $stmt->execute();
                        $errorInfo = $stmt->errorInfo();
                        if (isset($errorInfo[2])) {
                            $error = $errorInfo[2];
                        }
                    } catch (Exception $e) {
                        $error = $e->getMessage();
                    }
                    if($error) {
                        return [false, $error];
                    } else {
                        $updated_info[] = 'password';
                    }
                }
            }
        }
        // no "else" needed because of return statements.
        // however they may have only reset their password so we need an if
        if ( ($acct_data['firstname'] != '') || ($acct_data['lastname'] != '') || ($acct_data['company'] != '') ) {
            foreach($acct_data as $key=>$value) {
                if (property_exists($this, $key) && $value != '') {
                    $this->$key = $value;
                }
            }
            try {
                $sql = 'UPDATE sb_users
                        SET firstname = :firstname, lastname = :lastname, company = :company
                        WHERE userid = :userid';
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':userid', $this->userid);
                $stmt->bindValue(':firstname', json_encode($this->firstname));
                $stmt->bindValue(':lastname', json_encode($this->lastname));
                $stmt->bindValue(':company', json_encode($this->company));
                $stmt->execute();
                $errorInfo = $stmt->errorInfo();
                if ( isset($errorInfo[2])) {
                    $error = $errorInfo[2];
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            if($error) {
                return [false, $error];
            } else {
                if ($acct_data['firstname'] != '') {$updated_info[] = 'first name';}
                if ($acct_data['lastname'] != '') {$updated_info[] = 'last name';}
                if ($acct_data['company'] != '') {$updated_info[] = 'company';}
            }
        }
        return [true, $updated_info];
    }
    
    public function make_new_account(&$db, $acct_info) {
        $password = $acct_info['password'];
        $pass_ver = $acct_info['password_verify'];
        $firstname = $acct_info['firstname'];
        $lastname = $acct_info['lastname'];
        $company = $acct_info['company'];
        $email = $acct_info['email']; // page validates that this is a valid email
        if ($password != $pass_ver) {
            return [false, 'Error: Password entries do not match.'];
        }
        if ($password == '' || $pass_ver == '' || $firstname == '' || $lastname == '' || $email == '') {
            return [false, 'Error: All fields except &quot;company&quot; are required.'];
        }
        try {
            $sql = 'SELECT userid FROM sb_users WHERE email = :email';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            $stmt->bindColumn('userid', $userid);
            $errorInfo = $stmt->errorInfo();
            if (isset($errorInfo[2])) {
                $error = $errorInfo[2];
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        if (isset($error) ) {
            return [false, $error];
        }
        $stmt->fetch(PDO::FETCH_BOUND);
        if (isset($userid)) {
            return [false, 'Error: This email is already associated with an account.'];
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $sql = 'INSERT INTO sb_users
                        (email, firstname, lastname, company, password_hash)
                        VALUES (:email, :firstname, :lastname, :company, :password_hash)';
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':email', $email);
                $stmt->bindValue(':firstname', json_encode($firstname));
                $stmt->bindValue(':lastname', json_encode($lastname));
                $stmt->bindValue(':company', json_encode($company));
                $stmt->bindValue(':password_hash', $password_hash);
                $stmt->execute();
                $errorInfo = $stmt->errorInfo();
                if (isset($errorInfo[2])) {
                    $error = $errorInfo[2];
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            if (isset($error)) {
                return [false, $error];
            } else {
                $userid = $db->lastInsertId();
                if (!isset($userid)) {
                    return [false, 'Error: Could not add user to database.'];
                } else {
                    $this->userid = $userid;
                    $this->modify($acct_info);
                }
            }
        }
        return [true, $userid];
    }

    public function login_user($password, &$db) {
        try {
            $sql = 'SELECT userid, firstname, lastname, company, password_hash, level 
                    FROM sb_users 
                    WHERE email = :email';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $this->email);
            $stmt->execute();
            $stmt->bindColumn('userid', $attr['userid']);
            $stmt->bindColumn('firstname', $attr['firstname']);
            $stmt->bindColumn('lastname', $attr['lastname']);
            $stmt->bindColumn('company', $attr['company']);
            $stmt->bindColumn('password_hash', $password_hash);
            $stmt->bindColumn('level', $attr['level']);
            $errorInfo = $stmt->errorInfo();
            if (isset($errorInfo[2])) {
                $error = $errorInfo[2];
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $stmt->fetch(PDO::FETCH_BOUND);
        if ($attr['userid']) {
            if( password_verify($password, $password_hash) ) {
                $attr['firstname'] = json_decode($attr['firstname']);
                $attr['lastname'] = json_decode($attr['lastname']);
                $attr['company'] = json_decode($attr['company']);
                $this->modify($attr);
                return [true];
            } else {
                return[false, 'Login failed: Email and password do not match.'];
            }
        } else {
            return [false, 'Login failed: No such user or error pulling information from database.'];
        }
    }
    
    // returns identifying info about the boards the current user
    // has permission to view or edit.
    public function available_boards(&$db) {
        try {
            $sql = 'SELECT boardid, title, client, creationDate, dueDate, is_locked, locked_by, lock_expires 
                    FROM storyboards
                    WHERE boardid 
                    IN (SELECT boardid FROM permissions WHERE userid = :userid) 
                    ORDER BY creationDate DESC';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':userid', $this->userid);
            $stmt->execute();
            $stmt->bindColumn('boardid', $boardid);
            $stmt->bindColumn('title', $title);
            $stmt->bindColumn('client', $client);
            $stmt->bindColumn('creationDate', $creationDate);
            $stmt->bindColumn('dueDate', $dueDate);
            $stmt->bindColumn('is_locked', $is_locked);
            $stmt->bindColumn('locked_by', $locked_by);
            $stmt->bindColumn('lock_expires', $lock_expires);
            $errorInfo = $stmt->errorInfo();
            if (isset($errorInfo[2])) {
                $error = $errorInfo[2];
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        $stmt->fetch(PDO::FETCH_BOUND);
        if ($boardid) {
            echo '<div class="board-selection">';
            echo '<h2>Select a Storyboard:</h2>';
            echo '<form method="post" action="/storyboardapp/"><fieldset>';
            $i = 0;
            do {
                echo "<label for=\"board-$i\"><input type=\"radio\" name=\"boardid\" id=\"board-$i\" value=\"$boardid\"> <p>" . JSON_decode($title) . ' by ' . JSON_decode($client) . " (created $creationDate";
                if ($dueDate != "") { 
                    echo '; due ' . $dueDate;
                }
                echo ')';
                if ($is_locked && $locked_by != $this->userid && time() < $lock_expires) {
                    echo ' <span class="submessage">&ast; read-only</span>';
                }
                echo '</p></label>';
                $i++;
            } while($stmt->fetch(PDO::FETCH_BOUND));
            echo '<label for="board-new"><input type="radio" name="boardid" id="board-new" value="new"> <p>New Storyboard</p></label>';
            echo '</fieldset>';
            echo '<input type="submit" value="Load Board" class="load-board-button">';
            echo '</form>';
            echo '<h4><span class="green">&ast; Boards may be read-only because they are currently being edited by someone else or because you do not have permission to edit them.</span></h4>';
            echo '</div>';
            return true;
        } else {
            return false;
        }
    }
    
    // prints out info in the form of a form, for changing purposes.
    public function account_info_changeable() {
        $fn = '';
        $ln = '';
        $co = '';
        if ( isset( $_POST['firstname'] ) ) {
            $fn = $_POST['firstname'];
        }
        if ( isset( $_POST['lastname'] ) ) {
            $ln = $_POST['lastname'];
        }
        if ( isset( $_POST['company'] ) ) {
            $co = $_POST['company'];
        }
        echo '<form method="post">';
        echo '<table class="user-info-table">';
        echo "<tr><td>Email: </td><td>".htmlspecialchars($this->email, ENT_QUOTES)."</td><td><span class=\"greyed\">Cannot be changed.</span></td></tr>";
        echo "<tr><label><td>First Name: </td><td>".htmlspecialchars($this->firstname, ENT_QUOTES)."</td><td>";
        echo "<input type=\"text\" id=\"firstname\" name=\"firstname\" value=\"$fn\"></td></label></tr>";
        echo "<tr><label><td>Last Name: </td><td>".htmlspecialchars($this->lastname, ENT_QUOTES)."</td><td>";
        echo "<input type=\"text\" id=\"lastname\" name=\"lastname\" value=\"$ln\"></td></label></tr>";
        echo "<tr><label><td>Company: </td><td>".htmlspecialchars($this->company, ENT_QUOTES)."</td><td>";
        echo "<input type=\"text\" id=\"company\" name=\"company\" value=\"$co\"></td></label></tr>";
        echo "<tr class=\"top-border\"><label><td>Current Password: </td><td><input type=\"password\" id=\"curr_password\" name=\"curr_password\"></td><td></td></label></tr>";
        echo "<tr><label><td>New Password: </td><td><input type=\"password\" id=\"new_password\" name=\"new_password\"></td><td></td></label></tr>";
        echo "<tr><label><td>Repeat New Password: </td><td><input type=\"password\" id=\"new_password_verify\" name=\"new_password_verify\"></td><td></td></label></tr>";
        echo '<tr class="top-border"><td></td><td></td><td><button id="user-info-button" class="user-info-button">Change Account Information</button></td></tr>'; 
        echo '</table></form>';
    }
    
    public function print_admin_functions() {
        // should probably confirm status and identity via password entry for most all of these
        echo '<ul>';
        if ($this->level == "super") {
            echo '<li><form method="post"><button>Lock</button> <label>Lock all boards for <input type="number" value="60" name="duration"> minutes.</label></form></li>';
            echo '<li><a href="?unlock-all=true">Unlock all boards</a></li>';
        }
        echo '</ul>';
    }

    // assumes duration is submitted in minutes
    public function lock_all_boards(&$db, $duration) {
        if ($this->level != 'super') {
            return [false, 'Error: insufficient privileges to lock all boards.'];
        }
        $lock_expires = time() + (60 * $duration); // count in seconds
        try {
            $sql = 'UPDATE storyboards
                    SET is_locked = 1, locked_by = :locked_by, lock_expires = :lock_expires';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':locked_by', $this->userid);
            $stmt->bindValue(':lock_expires', $lock_expires);
            $stmt->execute();
            $errorInfo = $stmt->errorInfo();
            if ( isset($errorInfo[2])) {
                $error = $errorInfo[2];
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        if($error) {
            return [false, $error];
        } else {
            return [true, "All storyboards locked for the next $duration minutes."];
        }
    }
    
    public function unlock_all_boards(&$db) {
        if ($level != 'super') {
            return [false, 'Error: insufficient privileges to unlock all boards.'];
        }
        try {
            $sql = 'UPDATE storyboards
                    SET is_locked = 0';
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $errorInfo = $stmt->errorInfo();
            if ( isset($errorInfo[2])) {
                $error = $errorInfo[2];
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        if($error) {
            return [false, $error];
        } else {
            return [true, "All storyboards have been unlocked."];
        }
    }
}