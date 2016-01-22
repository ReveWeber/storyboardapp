$(document).ready(function () {
    $('#no-js-warning').hide();
    // eliminate query strings so reloading doesn't do unexpected things
    var uri = window.location.toString();
    if (uri.indexOf("?") > 0) {
        var clean_uri = uri.substring(0, uri.indexOf("?"));
        window.history.replaceState({}, document.title, clean_uri);
    }

    $('#submenu').hide();
    $('#menu-toggle').click(function (e) {
        e.preventDefault;
        $('#submenu').toggle();
    });
    $(document).on('click', function (event) {
        if (!$(event.target).closest("#expanding-menu").length) {
            $("#submenu").hide();
        }
    });

    $('.scene .drawing-area').wPaint({
        path: '/storyboardapp/js/'
    });

    $('.scene .drawing-area').each(function () {
        var $drawingCode = $(this).find('.drawingCode').text();
        $(this).wPaint('image', $drawingCode);
    });

    $("#sorting-wrapper").sortable({
        items: ".scene-wrapper",
        handle: ".scene-number",
        placeholder: "sortable-placeholder",
        stop: function (event, ui) {
            update_scene_numbers();
        },
    });

    // autosave (back up in session every 3 minutes in case of page reload)
    setInterval(function () {
        var postingArray = encode_scenes();
        console.log(postingArray);
        $.post('/storyboardapp/autosave_storyboard.php', postingArray);
    }, 180000);

    // also autosave before any links are followed.
    //    $(document).on('click', 'a', function (e) {
    //        e.preventDefault;
    //        var address = $(this).attr('href');
    //        var postingArray = encode_scenes();
    //        $.post('/storyboardapp/autosave_storyboard.php', postingArray, function() {
    //            window.location = address;
    //        });
    //    });

    // warning of impending lock expiration; check every 5 minutes
    // give 10 minute-ish (<600) and 5 minute-ish (<300) warnings
    // lock expiration is in seconds, JS intervals in milliseconds
    // if condition is to keep this from alerting if you leave the
    // login screen up for 5 minutes
    if ($('#sorting-wrapper').length > 0) { 
        setInterval(function () {
            $.get("/storyboardapp/lock_expiration.php", function (data) {
                var lockCountdown = 1000;
                if (data != 'none') {
                    lockCountdown = parseInt(data);
                }
                if (lockCountdown < 600) {
                    if (lockCountdown < 300) {
                        alert("Your lock on this board expires in less than five minutes. Please save to continue working without interruption.");
                    } else {
                        alert("Your lock on this board expires in less than ten minutes. Please save to continue working without interruption.");
                    }
                }
            });        
        }, 300000);
    }

    $('#save-button').click(function (e) {
        e.preventDefault;
        var postingArray = encode_scenes();
        $.post('/storyboardapp/save_storyboard.php', postingArray, function (data) {
            $('#message-box span').show().text(data).delay(3000).fadeOut();
        });
    });

    $('#save-as-new').click(function (e) {
        e.preventDefault;
        var postingArray = encode_scenes();
        $.post('/storyboardapp/save_new_storyboard.php', postingArray, function (data) {
            $('#message-box span').show().text(data).delay(3000).fadeOut();
        });
    });

    function encode_scenes() {
        var sceneArray = [];
        $('.scene').each(function (index, value) {
            if (!$(this).hasClass('deleted')) {
                var currScene = {};
                currScene.sceneTitle = $(this).find('input[name="sceneTitle"]').val();
                currScene.shortDescription = $(this).find('textarea[name="shortDescription"]').val();
                currScene.drawingCode = $(this).find('.drawing-area').wPaint('image');
                currScene.onscreenText = $(this).find('textarea[name="onscreenText"]').val();
                currScene.voiceoverContent = $(this).find('textarea[name="voiceoverContent"]').val();
                currScene.music = $(this).find('textarea[name="music"]').val();
                currScene.animation = $(this).find('textarea[name="animation"]').val();
                currScene.videoEffects = $(this).find('textarea[name="videoEffects"]').val();
                sceneArray = sceneArray.concat((currScene));
            }
        });
        var postingArray = {
            title: $('#project-name').val(),
            client: $('#client').val(),
            creationDate: $('#start-date').val(),
            dueDate: $('#due-date').val(),
            scenes: sceneArray
        };
        return postingArray;
    }

    function update_scene_numbers() {
        $('.scene-number').each(function (index, value) {
            var sceneNo = index + 1;
            $(this).text(sceneNo + '.');
        });
    }

    $('#collapse-all').click(function (e) {
        e.preventDefault;
        // collapse all scenes and change buttons accordingly
        $('.collapsibles').hide().addClass('collapsed');
        $('.scene-collapser i').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    });

    $('#expand-all').click(function (e) {
        e.preventDefault;
        // expand all scenes and change buttons accordingly
        $('.collapsibles').show().removeClass('collapsed');
        $('.scene-collapser i').removeClass('fa-chevron-down').addClass('fa-chevron-up');
    });

    // can't use .click() for new scenes, deleting scenes, or individual
    // scene collapse buttons because it won't apply to buttons (i.e. scenes)
    // added after page load.
    $(document).on('click', '.new-scene-button button', function (e) {
        e.preventDefault;
        // insert new scene
        var emptyScene = '<div class="scene-wrapper"><div class="scene-number"></div><div class="scene-collapser" title="Collapse or expand this scene"><i class="fa fa-chevron-up"><span class="screen-reader-text">collapse/expand scene</span></i></div><div class="scene-delete" title="Delete this scene"><i class="fa fa-trash-o"><span class="screen-reader-text">delete scene</span></i></div><form class="scene"><input type="text" name="sceneTitle" placeholder="Scene Title"><textarea name="shortDescription" placeholder="Short Description"></textarea><div class="collapsibles"><div class="drawing-area"><div class="drawingCode"></div></div><label>Onscreen Text:</label><textarea name="onscreenText" placeholder="Onscreen Text"></textarea><label>Voiceover Content:</label><textarea name="voiceoverContent" placeholder="Voiceover Content"></textarea><label>Music:</label><textarea name="music" placeholder="Music"></textarea><label>Animation:</label><textarea name="animation" placeholder="Animation"></textarea><label>Video Effects:</label><textarea name="videoEffects" placeholder="Video Effects"></textarea></div><!-- .collapsibles --></form><div class="new-scene-button"><button>Insert New Scene Here</button></div></div><!-- .scene-wrapper -->';
        $(this).closest('.scene-wrapper').after(emptyScene)
            .next().find('.drawing-area').wPaint({
                path: '/storyboardapp/js/'
            });
        update_scene_numbers();
    });

    $(document).on('click', '.scene-delete button', function (e) {
        e.preventDefault;
        // delete scene containing button
        var wrapper = $(this).closest('.scene-wrapper');
        wrapper.find('.scene').addClass('deleted');
        wrapper.children().hide();
        wrapper.append('<button class="undo-button">Undo</button>');
        var timerId = setTimeout(function () {
            remove_scene(wrapper);
        }, 3000);
        wrapper.find('.undo-button').click(function (event) {
            wrapper.find('.scene').removeClass('deleted');
            wrapper.children().show();
            wrapper.find('.undo-button').remove();
            clearTimeout(timerId);
        });

        function remove_scene(wrapper) {
            wrapper.remove();
            update_scene_numbers();
        }
    });

    $(document).on('click', '.scene-collapser', function (e) {
        e.preventDefault;
        // toggle collapsed-ness of "collapsibles" class in same scene
        var collapsibles = $(this).closest('.scene-wrapper').find('.collapsibles');
        collapsibles.toggle().toggleClass('collapsed');
        $(this).find('i').toggleClass('fa-chevron-up fa-chevron-down');
    });

});