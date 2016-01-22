# Storyboard Collaboration

A program to allow remote collaboration on storyboarding short videos. Not yet in beta, but usable. See Database Setup below to try it.

## Features

- each scene has space for title, short description, sketch, onscreen text, voiceover content, music, animation, and effects.
- HTML canvas drawing ability via wPaint by Websanova, wpaint.websanova.com (MIT license) [with one change to wPaint.js to fix path duplication on scenes 2 and later]
- add new scenes anywhere (except top), delete scenes, reorder scenes by clicking and dragging
- collapse and expand scenes
- load, save, and save as new
- printable version
- make new account, password reset by email, change account information

## In Progress

- board locking and read-only functionality

## Planned

- improve/replace drawing program (import images, snap to grid)
- help page
- account permission levels and admin functions
- storyboard sharing (currently possible only through DB editing)
- "generate script" option for voiceover text
- responsiveness and accessibility
- track whether changes have been made; prompt to save
- undo/versioning/manual change merging
- attachments
- refactor and improve error handling

# Database Setup

I haven't made an installer for this so the DB tables have to be created manually. You'll also need a file called PDO_connect.php with 4 lines:
<?php
$dsn = 'mysql:host=localhost;dbname=DATABASE_NAME';
$db = new PDO($dsn, 'USERNAME', 'PASSWORD');
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

The last line is optional for functionality but good for security since it disables the emulation of prepared statements, forcing them all to be genuine prepared statements.

## table: permissions

permissionid (primary key, auto_increment) - int(11) - Not NULL 	 	 	 
userid - int(11) - Not NULL - links to sb_users -> userid 	 
boardid - int(11) - Not NULL - links to storyboards -> boardid 	 

## table: sb_users

userid (primary key, auto_increment) - int(11) - Not NULL 	 	 	 
firstname - varchar(60) - Not NULL	 	 	 
lastname - varchar(60) - Not NULL 	 	 
company - varchar(60) - NULL allowed and default 	 	 
email (unique) - varchar(60) - Not NULL 	 	 	 
level - varchar(10) - Not NULL - default 'free' 	 	 
password_hash - varchar(255) - Not NULL

## table: storyboards

boardid (primary, auto_increment) - int(11) - Not NULL 	 	 	 
title - tinytext - NULL allowed 	 	 
client - tinytext - NULL allowed 	 	 
creationDate - date - Not NULL	 	 
dueDate - date - NULL allowed 	 
scenes - longtext - NULL allowed 	 	 
is_locked - int(11) - Not NULL - default 0 	 	 
locked_by - int(11) - NULL allowed	 
lock_expires - int(11) - NULL allowed	 
