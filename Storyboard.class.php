<?php

class Storyboard {
    public $boardid;
    
    protected $title;
    protected $client;
    protected $creationDate;
    protected $dueDate;
    protected $scenes;
    protected $readonly = false;
    protected $lock_expires = 0;
    protected $is_shared = false;
    
    public function __construct() {
        $this->creationDate = strftime('%F'); //today
        $this->boardid = 'new';
    }
    
    public function modify($boardid, $content) {
        $this->boardid = $boardid;
        foreach($content as $key=>$value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function print_board() {
        if ($this->readonly) {
            echo '<div class="read-only">';
            $this->printable_view();
            echo '</div>';
        } else {
            echo '<div class="board-header-wrapper">';
            echo '<div class="board-header">';
            echo '    <label for="project-name">Project Name:</label>';
            echo "    <input type=\"text\" id=\"project-name\" value=\"$this->title\">";
            echo '    <br>';
            echo '    <label for="client">Client:</label>';
            echo "    <input type=\"text\" id=\"client\" value=\"$this->client\">";
            echo '    <br>';
            echo '    <label for="start-date">Date Begun:</label>';
            echo "    <input type=\"text\" id=\"start-date\" value=\"$this->creationDate\" readonly>";
            echo '    <br>';
            echo '    <label for="due-date">Date Due:</label>';
            echo "    <input type=\"text\" id=\"due-date\" value=\"$this->dueDate\">";
            echo '    <br>';
            echo '</div></div>';

            // Scene Printing Loop
            // camel case instead of hyphens for input names to avoid annoyance
            $counter = 1;
            if ($this->scenes != '') {
                $counter = count($this->scenes);
            }
            echo '<div class="sort-message">Reorder scenes by clicking their number and dragging.</div>';
            echo '<div id="sorting-wrapper" class="sorting-wrapper">';
            for ($i = 0 ; $i < $counter ; $i++) {
                $sceneInfo = $this->scenes[$i];
                $currScene = new Scene($sceneInfo);
                $currScene->print_scene($i+1);
            }
            echo '</div><!-- #sorting-wrapper -->';
        }
    }
    
    public function printable_view() {
        echo "<h1>".htmlentities($this->title, ENT_QUOTES)."</h1>";
        echo "<div class=\"printing-sb-header\"><strong>Client:</strong> ". htmlentities($this->client, ENT_QUOTES) ."<br> <strong>Date Begun:</strong> ".htmlentities($this->creationDate, ENT_QUOTES)." <br> <strong>Date Due:</strong> ".htmlentities($this->dueDate, ENT_QUOTES)."</div>";
        // Scene Printing Loop
        $counter = 1;
        if ($this->scenes != '') {
            $counter = count($this->scenes);
        }
        for ($i = 0 ; $i < $counter ; $i++) {
            $sceneInfo = $this->scenes[$i];
            $currScene = new Scene($sceneInfo);
            $currScene->printable_version($i+1);
        }
    }
    
    public function unlock_board(&$db) {
        if (!$this->readonly) {
            try {
                $sql = 'UPDATE storyboards
                        SET is_locked = 0
                        WHERE boardid = :boardid';
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':boardid', $this->boardid);
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
            }
        }
        return [true];
    }
    
    public function load_board(&$db, $boardid, $userid) {
        try {
            $sql = 'SELECT title, client, creationDate, dueDate, scenes, is_locked, locked_by, lock_expires
                    FROM storyboards
                    WHERE boardid = :boardid';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':boardid', $boardid);
            $stmt->execute();
            $stmt->bindColumn('title', $title);
            $stmt->bindColumn('client', $client);
            $stmt->bindColumn('creationDate', $creationDate);
            $stmt->bindColumn('dueDate', $dueDate);
            $stmt->bindColumn('scenes', $scenes);
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
        if (isset($error)) {
            return [false, $error];
        }
        $stmt->fetch(PDO::FETCH_BOUND);
        if ($creationDate) {
            $content['title'] = JSON_decode($title);
            $content['client'] = JSON_decode($client);
            $content['creationDate'] = strftime("%F", strtotime($creationDate));
            if ($dueDate !== NULL) {
                $content['dueDate'] = strftime("%F", strtotime($dueDate));
            } else {
                $content['dueDate'] = '';
            }
            $content['scenes'] = JSON_decode($scenes);
            if ($is_locked && $locked_by != $userid && time() < $lock_expires) {
                $content['readonly'] = true;
            } else {
                $content['readonly'] = false;
                try {
                    $sql = 'SELECT userid
                            FROM permissions
                            WHERE boardid = :boardid';
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(':boardid', $boardid);
                    $stmt->execute();
                    $stmt->bindColumn('userid', $userlist);
                    $errorInfo = $stmt->errorInfo();
                    if (isset($errorInfo[2])) {
                        $error = $errorInfo[2];
                    }
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
                if (isset($error)) {
                    return [false, $error];
                }
                $stmt->fetch(PDO::FETCH_BOUND);
                if ($userlist) {
                    $userarray = array();
                    do {
                        $userarray[] = $userlist;
                    } while($stmt->fetch(PDO::FETCH_BOUND));
                    $usercount = count(array_count_values ($userarray));
                    if ($usercount > 1) {
                        $this->is_shared = true;
                    } else {
                        $this->is_shared = false;
                    }
                }
                $this->lock_expires = time() + 5400;
                try {
                    $sql = 'UPDATE storyboards
                            SET is_locked = 1, locked_by = :locked_by, lock_expires = :lock_expires
                            WHERE boardid = :boardid';
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(':locked_by', $userid);
                    $stmt->bindValue(':lock_expires', $this->lock_expires);
                    $stmt->bindValue(':boardid', $boardid);
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
                }
            }
            $this->modify($boardid, $content);
        }
        return [true];
    }
    
    public function save_board(&$db, $boardid, $content, $userid) {
        $locked = false;
        if ($this->is_shared && (time() > $this->lock_expires) && is_numeric($boardid) ) {
            try {
                $sql = 'SELECT is_locked, locked_by, lock_expires
                        FROM storyboards
                        WHERE boardid = :boardid';
                $stmt = $db->prepare($sql);
                $stmt->bindValue(':boardid', $boardid);
                $stmt->execute();
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
            if (isset($error)) {
                return [false, $error];
            }
            $stmt->fetch(PDO::FETCH_BOUND);
            if ($lock_expires) {
                if ($is_locked && $locked_by != $userid && time() < $lock_expires) {
                    $locked = true;
                    $this->readonly = true;
                    return [false, "Error: your lock expired and another user has locked this board."];
                } 
            } 
        }
        if (!$locked) {
            $title = JSON_encode($content['title']);
            $client = JSON_encode($content['client']);
            $creationDate = strftime("%F", strtotime($content['creationDate']));
            if ($content['dueDate'] == '') {
                $dueDate = NULL;
            } else {
                $dueDate = strftime("%F", strtotime($content['dueDate']));
            }
            $this->lock_expires = time() + 5400;
            $scenes = JSON_encode($content['scenes']);
            if ( is_numeric($boardid) ) {
                try {
                    $sql = 'UPDATE storyboards
                            SET title = :title, client = :client, creationDate = :creationDate, dueDate = :dueDate, scenes = :scenes, is_locked = 1, locked_by = :locked_by, lock_expires = :lock_expires
                            WHERE boardid = :boardid';
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(':boardid', $boardid);
                    $stmt->bindValue(':title', $title);
                    $stmt->bindValue(':client', $client);
                    $stmt->bindValue(':creationDate', $creationDate);
                    $stmt->bindValue(':dueDate', $dueDate);
                    $stmt->bindValue(':scenes', $scenes);
                    $stmt->bindValue(':locked_by', $userid);
                    $stmt->bindValue(':lock_expires', $this->lock_expires);
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
                }
            } else {
                // 'new' or any other nonnumeric boardid
                try {
                    $sql = 'INSERT INTO storyboards
                            (title, client, creationDate, dueDate, scenes, is_locked, locked_by, lock_expires) 
                            VALUES (:title, :client, :creationDate, :dueDate, :scenes, 1, :locked_by, :lock_expires)';
                    $stmt = $db->prepare($sql);
                    $stmt->bindValue(':title', $title);
                    $stmt->bindValue(':client', $client);
                    $stmt->bindValue(':creationDate', $creationDate);
                    $stmt->bindValue(':dueDate', $dueDate);
                    $stmt->bindValue(':scenes', $scenes);
                    $stmt->bindValue(':locked_by', $userid);
                    $stmt->bindValue(':lock_expires', $this->lock_expires);
                    $stmt->execute();
                    $errorInfo = $stmt->errorInfo();
                    if (isset($errorInfo[2])) {
                        $error = $errorInfo[2];
                    }
                } catch (Exception $e) {
                    $error = $e->getMessage();
                }
                if (!isset($error)) {
                    $boardid = $db->lastInsertId();
                    $this->boardid = $boardid;
                    $this->add_board_permission($db, $userid);
                }
            }
            $this->modify($boardid, $content);
        }
        return [true];
    }
    
    public function add_board_permission(&$db, $userid) {
        try {
            $sql = 'INSERT INTO permissions
                    (userid, boardid) 
                    VALUES (:userid, :boardid)';
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':userid', $userid);
            $stmt->bindValue(':boardid', $this->boardid);
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
        }
        return [true];
    }
    
    public function time_to_lock_expiry() {
        if ($this->lock_expires != 0) {
            return $this->lock_expires - time();
        }
        return 'none';
    }
    
}