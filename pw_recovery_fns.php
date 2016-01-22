<?php
function user_exists($email, &$db) {
    //echo "user exists function called. <br><br>";
    try {
        $sql = 'SELECT userid 
                FROM sb_users 
                WHERE email = :email';
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
    if (isset($error)) {
        //echo "user exists db error: $error <br><br>";
        return [false, $error];
    }
    $stmt->fetch(PDO::FETCH_BOUND);
    if ($userid) {
        //echo "user id found by user exists function: $userid <br><br>"; 
        return [true];
    } else {
        //echo "no user id found by user exists function. <br><br>"; 
        return [false, 'Request failed: No such user.'];
    }
}

function generate_email($email, $key) {
    //echo "generate email function called. <br><br>";
    // generate email message
    $message = array();
    $message[] = "This email was requested on the Aquilino Arts Storyboard password reset page.";
    $message[] = "If you did not request it you are safe to ignore it.";
    $message[] = "If you did request it, please click or paste the link below to be taken to a password reset form.";
    $message[] = "http://localhost/storyboardapp/password.php?key=$key";
    $message[] = "This link will expire in one hour.";
    //echo "message constructed: ";
    //echo implode(' ', $message);
    //echo " <br><br>";
    
    // generate email headers
    $headers   = array();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/plain; charset=iso-8859-1";
    $headers[] = "From: Aquilino Arts Storyboard <storyboard@aquilinoarts.com>";
    $headers[] = "Bcc: Rebecca Weber <reveweber@gmail.com>";
    $headers[] = "Reply-To: Aquilino Arts Storyboard <storyboard@aquilinoarts.com>";
    $headers[] = "Subject: Storyboard account password recovery";
    $headers[] = "X-Mailer: PHP/".phpversion();
    //echo "headers constructed: ";
    //echo implode(' ', $headers);
    //echo " <br><br>";

    // send message
    return mail($email, "Storyboard account password recovery", implode("\r\n", $message), implode("\r\n", $headers));
}

function generate_request($email, &$db) {
    //echo "generate request function called. <br><br>";
    $user_confirmed = user_exists($email, $db);
    if ($user_confirmed[0]) {
        $requestkey = mt_rand(10000000, mt_getrandmax());
        $time = time();
        //echo "user confirmed to exist; request key generated: $requestkey <br><br>";
        try {
            $sql = 'INSERT INTO password_reset_requests 
                    (email, requestkey, time)
                    VALUES (:email, :requestkey, :time)';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':requestkey', $requestkey);
            $stmt->bindValue(':time', $time);
            $stmt->execute();
            $errorInfo = $stmt->errorInfo();
            if (isset($errorInfo[2])) {
                $error = $errorInfo[2];
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        if (isset($error) ) {
            //echo "database error in request insertion: $error <br><br>";
            return [false, $error];
        }
        $requestid = $db->lastInsertId();
        if (!isset($requestid)) {
            //echo "error: no request id returned by db. <br><br>";
            return [false, 'Error: Could not add request to database.'];
        } else {
            //echo "made it! have a request id: $requestid <br><br>";
            $key = $requestid . '-' . $requestkey;
            //echo "about to generate an email with address $email and key $key <br><br>";
            if (generate_email($email, $key)) {
                //echo "email sent. <br><br>";
                return [true];
            } else {
                //echo "email not sent. <br><br>";
                return [false, "Error: email could not be sent."];
            }
        }
    } else {
        //echo "user does not exist. <br><br>";
        return $user_confirmed; // already in the form [false, error] 
    }
}

function mark_request_used($requestid, &$db) {
    //echo "mark request used function called. <br><br>";
    try {
        $sql = 'UPDATE password_reset_requests
                SET used = 1
                WHERE requestid = :requestid';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':requestid', $requestid);
        $stmt->execute();
        $errorInfo = $stmt->errorInfo();
        if (isset($errorInfo[2])) {
            $error = $errorInfo[2];
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    if($error) {
        //echo "error in marking request used in db: $error <br><br>";
        return [false, $error];
    } else {
        //echo "request marked used in db. <br><br>";
        return [true];
    }
}

function check_request($key, &$db) {
    //echo "check request function called. <br><br>";
    $key_pieces = explode('-', $key);
    $requestid = $key_pieces[0];
    $givenrequestkey = $key_pieces[1];
    //echo "request key disassembled into id $requestid and key $givenrequestkey <br><br>";
    try {
        $sql = 'SELECT email, requestkey, time, used 
                FROM password_reset_requests 
                WHERE requestid = :requestid';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':requestid', $requestid);
        $stmt->execute();
        $stmt->bindColumn('email', $email);
        $stmt->bindColumn('requestkey', $requestkey);
        $stmt->bindColumn('time', $time);
        $stmt->bindColumn('used', $used);
        $errorInfo = $stmt->errorInfo();
        if (isset($errorInfo[2])) {
            $error = $errorInfo[2];
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    if (isset($error)) {
        //echo "db selection error: $error <br><br>";
        return [false, $error];
    }
    $stmt->fetch(PDO::FETCH_BOUND);
    if (isset($email)) {
        //echo "we got a response! email $email requestkey $requestkey timestamp $time used $used <br><br>";
        if ($used) {
            return [false, "Error: This request has already been used."];
        }
        if (time() > ($time + 360)) {
            return [false, "Error: This request has expired."];
        }
        if ($requestkey != $givenrequestkey) {
            return [false, "Error: Request failed identification test."];
        }
        $_SESSION['request_email'] = $email;
        $mark_used = mark_request_used($requestid, $db);
        return $mark_used;
    } else {
        
        return [false, 'Error: No password request matching given information or error pulling data from database.'];
    }

}

function reset_password($email, $password, &$db) {
    //echo "in the reset password function. <br><br>";
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    try {
        $sql = 'UPDATE sb_users
                SET password_hash = :password_hash
                WHERE email = :email';
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':password_hash', $password_hash);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $errorInfo = $stmt->errorInfo();
        if (isset($errorInfo[2])) {
            $error = $errorInfo[2];
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    if($error) {
        //echo "error in database update: $error <br><br>";
        return [false, $error];
    } else {
        //echo "password updated. <br><br>";
        return [true];
    }
}