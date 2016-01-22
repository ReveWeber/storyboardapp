<div class="header-bar">
    
    <!-- should set a variable in the account page and if set, show load, new, delete, and log out only -->
    
    <a href="/storyboardapp/"><img src="assets/logo.png" class="logo" height=100 width=100></a>
    <h1 class="inline-elt"><a href="/storyboardapp/">Aquilino Arts Storyboard &alpha; Version</a></h1>
        <div id="message-box" class="message-box inline-elt"><span></span></div>
        <?php if ($show_available) :?>
        <ul class="main-menu">
            <li><a href="/storyboardapp/?board=new" id="new-button"><i class="fa fa-plus"></i> New Board</a></li>
            <li><a href="account.php"><i class="fa fa-wrench"></i> Edit Account</a></li>
            <li><a href="/storyboardapp/?logout=true" id="logout-button"><i class="fa fa-sign-out"></i> Log Out</a></li>
        </ul>
        <?php endif; ?>
        <?php if ($is_logged_in && !$show_available) : ?>
<ul class="main-menu">
    <li id="save-button"><i class="fa fa-floppy-o"></i> Save</li>
    <li id="collapse-all"><i class="fa fa-chevron-up"></i> Collapse All</li>
    <li id="expand-all"><i class="fa fa-chevron-down"></i> Expand All</li>
    <li class="expanding-menu" id="expanding-menu"><span id="menu-toggle"><i class="fa fa-bars"></i> More Options </span>
        <ul class="submenu" id="submenu">
            <li><a href="print.php" id="printable-version" class="printable-version"><i class="fa fa-print"></i> Printable Version</a></li>
            <li id="save-as-new"><i class="fa fa-floppy-o"></i><i class="fa fa-plus shrink-and-backup"></i> Save as New</li>
            <li <?php if (!$_SESSION['has_boards']) {echo 'class="inactive"';} ?>><a href="/storyboardapp/?board=load" id="reload-button"><i class="fa fa-folder-open-o"></i> Load Board</a></li>
            <li><a href="/storyboardapp/?board=new" id="new-button"><i class="fa fa-plus"></i> New Board</a></li>
            <li class="inactive"><i class="fa fa-user-plus"></i> Share This Board</li>
            <li class="inactive"><i class="fa fa-trash-o"></i> Delete Boards</li>
            <li><a href="account.php"><i class="fa fa-wrench"></i> Edit Account</a></li>
            <li class="inactive"><i class="fa fa-question-circle"></i> Help/About</li>
            <?php if ($curr_user->level == "super" || $curr_user->level == "admin") { ?>
                <li><a href="admin.php"><i class="fa fa-cogs"></i> Admin Area</a></li>
            <?php } ?>
            <li><a href="/storyboardapp/?logout=true" id="logout-button"><i class="fa fa-sign-out"></i> Log Out</a></li>
        </ul>
    </li>
</ul>        <?php endif; ?>
        
        </div><!-- .header-bar-->