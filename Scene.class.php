<?php

class Scene {
    protected $sceneTitle = '';
    protected $shortDescription = '';
    protected $voiceoverContent = '';
    protected $onscreenText = '';
    protected $animation = '';
    protected $music = '';
    protected $videoEffects = '';
    protected $drawingCode = '';
    
    public function __construct(&$props) {
        foreach($props as $key=>$value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }
    
    public function print_scene($scene_number) {
        echo '<div class="scene-wrapper">';
        echo "<div class=\"scene-number\">$scene_number.</div>";
        echo '<div class="scene-collapser" title="Collapse or expand this scene">';
        echo '    <i class="fa fa-chevron-up"><span class="screen-reader-text">collapse/expand scene</span></i>';
        echo '</div>';
        echo '<div class="scene-delete" title="Delete this scene">';
        echo '    <i class="fa fa-trash-o"><span class="screen-reader-text">delete scene</span></i>';
        echo '</div>';
        echo '<form class="scene">';
        echo "    <input type=\"text\" name=\"sceneTitle\" placeholder=\"Scene Title\" value=\"{$this->sceneTitle}\">";
        echo "    <textarea name=\"shortDescription\" placeholder=\"Short Description\">$this->shortDescription</textarea>";
        echo '<div class="collapsibles">';
        echo '    <div class="drawing-area"><div class="drawingCode">' . $this->drawingCode . '</div></div>';
        echo '    <label>Onscreen Text:</label>';
        echo "    <textarea name=\"onscreenText\" placeholder=\"Onscreen Text\">$this->onscreenText</textarea>";
        echo '    <label>Voiceover Content:</label>';
        echo "    <textarea name=\"voiceoverContent\" placeholder=\"Voiceover Content\">$this->voiceoverContent</textarea>";
        echo '    <label>Music:</label>';
        echo "    <textarea name=\"music\" placeholder=\"Music\">$this->music</textarea>";
        echo '    <label>Animation:</label>';
        echo "    <textarea name=\"animation\" placeholder=\"Animation\">$this->animation</textarea>";
        echo '    <label>Video Effects:</label>';
        echo "    <textarea name=\"videoEffects\" placeholder=\"Video Effects\">$this->videoEffects</textarea>";
        echo '</div><!-- .collapsibles -->';
        echo '</form>';
        echo '    <div class="new-scene-button">';
        echo '        <button>Insert New Scene Here</button>';
        echo '    </div>';
        echo '</div>';
    }
    
    public function printable_version($scene_number) {
        echo '<div class="scene-to-print scene">';
        echo "<div class=\"printing-scene-header\"><strong>$scene_number. ". htmlentities($this->sceneTitle, ENT_QUOTES)."</strong> <br>". htmlentities($this->shortDescription, ENT_QUOTES)."</div>";
        echo '<div class="drawing-area"><div class="drawingCode">' . $this->drawingCode . '</div></div>';
        echo "<div class=\"printing-scene-info\"><strong>Onscreen Text:</strong> ". htmlentities($this->onscreenText, ENT_QUOTES)."</div>";
        echo "<div class=\"printing-scene-info\"><strong>Voiceover Content:</strong> ". htmlentities($this->voiceoverContent, ENT_QUOTES)."</div>";
        echo "<div class=\"printing-scene-info\"><strong>Music:</strong> ". htmlentities($this->music, ENT_QUOTES)."</div>";
        echo "<div class=\"printing-scene-info\"><strong>Animation:</strong> ". htmlentities($this->animation, ENT_QUOTES)."</div>";
        echo "<div class=\"printing-scene-info\"><strong>Video Effects:</strong> ". htmlentities($this->videoEffects, ENT_QUOTES)."</div>";
        echo '</div><!-- .scene-to-print -->';
    }
}