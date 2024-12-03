<?php
/**
 * PlantUML-SVG-Plugin: Parses plantuml blocks to render images and html
 *
 * @license GPL v2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Charles Chan
 */

if (!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) . '/../../') . '/');
require_once(DOKU_INC . 'inc/init.php');
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once(DOKU_PLUGIN . 'syntax.php');

class syntax_plugin_plantumlsvg extends DokuWiki_Syntax_Plugin {
    protected static $cssAlign=array(
        '' => 'media', 'left' => 'medialeft',
        'right' => 'mediaright', 'center' => 'mediacenter'
    );

    /**
     * What kind of syntax are we?
     */
    function getType() {
        return 'substition';
    }

    /**
     * Where to sort in?
     */
    function getSort() {
        return 200;
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<uml.*?>\n.*?\n</uml>', $mode, 'plugin_plantumlsvg');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {
        // echo "handle: state=$state<br>";
        // echo "handle: match=$match<br>";
        // echo "handle: pos=$pos<br>";

        $info = $this->getInfo();

        // prepare default data
        $return = array(
            'title' => 'PlantUML Graph',
            'align' => '',
        );

        // prepare input
        $lines = explode("\n", $match);
        $conf = array_shift($lines);
        array_pop($lines);

        // alignment
        if (preg_match('/\b(left|center|right)\b/i', $conf, $matches)) {
            $return['align'] = $matches[1];
        }

        // title
        if (preg_match('/\b(?:title|t)=(\w+)\b/i', $conf, $matches)) {
            // single word titles
            $return['title'] = $matches[1];
        } else if (preg_match('/(?:title|t)="([\w+\s+]+)"/i', $conf, $matches)) {
            // multi word titles
            $return['title'] = $matches[1];
        }

        // type
        if (preg_match('/\b(?:type|y)=(\w+)\b/i', $conf, $matches)) {
            // single word titles
            $return['type'] = $matches[1];
        } else if (preg_match('/(?:type|y)="([\w+\s+]+)"/i', $conf, $matches)) {
            // multi word titles
            $return['type'] = $matches[1];
        }

        $input = join("\n", $lines);
        $return['md5'] = md5($input);

        if(isset($return['type']) && $return['type'] == "mindmap") {
            io_saveFile($this->_cachename($return, 'txt'), "@startmindmap\n$input\n@endmindmap");
        }
        else if(isset($return['type']) && $return['type'] == "gantt") {
            io_saveFile($this->_cachename($return, 'txt'), "@startgantt\n$input\n@endgantt");
        }
        else {
            io_saveFile($this->_cachename($return, 'txt'), "@startuml\n$input\n@enduml");
        }
        return $return;
    }

    /**
     * Cache file is based on parameters that influence the result image
     */
    function _cachename($data, $ext){
        unset($data['align']);
        unset($data['title']);
        return getcachename(join('x', array_values($data)), ".plantumlsvg.$ext");
    }

    /**
     * Create Output
     */
    function render($mode, Doku_Renderer $renderer, $data) {
        // $this->_log($mode);        
        if($this->exec_disabled()) {
            $renderer->doc .= "[PHP disbaled exec function.]";
        } else if ($mode == 'xhtml') {
            $this->_imgfile($data, 'svg');
            $cache = $this->_cachename($data, 'svg');
            $svg = io_readFile($cache, false);
            $svg = strstr($svg, '<svg');
            $align = self::$cssAlign[$data['align']];
            $svg = str_replace('<svg', '<svg class="plantumlsvg '.$align.'"', $svg);
            // $svg = $this->_prepare($svg);

            $renderer->doc .= '<div style="margin-bottom: 1.4em;">';
            $renderer->doc .= $svg;

            if(!is_a($renderer,'renderer_plugin_dw2pdf')) {
                $img = DOKU_BASE . 'lib/plugins/plantumlsvg/svg.php?' . buildURLParams($data);
                $big = '<a title="' . $data['title'] . '" class="media" href="' . $img . '"  target="_blank">查看原图</a>';
                $big = '<div style="text-align: right;">' . $big . '</div>';
                $renderer->doc .= $big;
            }

            $renderer->doc .= '</div>';

        } else {
            return false;
        }

        return true;
    }

    function _prepare( $text )
    {
        $r = preg_replace_callback(
            '/\\[\\[
                    ([^]]*)\\ ([^]]*)
            ]]
            /x',
            function( $match ) {
                // $this->_log("==>".$match[0]);    
                // $this->_log("==>".$match[1]);
                if(strlen($match[1])>4 && substr($match[1], 1, 4) == 'http') {
                    $p1 = $match[1];
                }
                else {
                    $p1 =  preg_replace_callback(
                        '/
                             ([^][|#]*)    # The page_id
                             (\\#[^]|]*)?  # #Anchor
                             (\\|[^]]*)?   # |description optional
                        /x',
                        function( $match ) {
                            // $this->_log("-->\"".$match[0]."\"");
                            if(strlen($match[2]) > 0) {
                                $anchor = "#" . cleanID($match[2]);
                            }
                            $url = wl( cleanID($match[1]), '', true ) . $anchor;
                            // $this->_log($url);
                            return $url;
                        },
                    $match[1], 1);
                }
                return '[[' . $p1 . ' ' . $match[2]. ']]';
            },
            $text);
        // $this->_log("+++>".$r);   
        return $r;
    }

    /**
     * Return path to the rendered image on our local system
     * Note this is also called by img.php
     */
    function _imgfile($data, $ext) {
        $cache = $this->_cachename($data, $ext);

        // create the file if needed
        if (!file_exists($cache)) {
            // $this->_log("creating ".$cache);    
            $in = $this->_cachename($data, 'txt');
            $uml = io_readFile($in, false);
            $uml = $this->_prepare($uml);
            io_saveFile($in, $uml);

            $ok = $this->_local($data, $in, $cache, $ext);
            if (!$ok) {
                // $this->_log("failed to create ".$cache);
                return false;
            }
            clearstatcache();
        }

        return file_exists($cache) ? $cache : false; 
    }

    /**
     * Render the output locally using the plantuml.jar
     */
    function _local($data, $in, $out, $ext) {
        if (!file_exists($in)) {
            $this->_log(' No such plantuml input file: '.$in);
            return false;
        }
        
        $java = $this->getConf('java');
        $jar = $this->getConf('jar');
        $tmpl = DOKU_PLUGIN .'/plantumlsvg/' . $this->getConf('theme');
        if (!file_exists($tmpl)) {
            $tmpl = wikiFN($this->getConf('theme'));
        }
        $jar = realpath($jar);
        $jar = escapeshellarg($jar);

        // we are not specifying the output here, because plantuml will generate a file with the same
        // name as the input but with .svg extension, which is exactly what we want
        $command = $java;
        $command .= ' -Djava.awt.headless=true';
        $command .= ' -Dfile.encoding=UTF-8';
        $command .= " -jar $jar";
        $command .= ' -charset UTF-8';
        $command .= ' -t'.$ext;
        if (file_exists($tmpl)) {
            $command .= ' -I'. $tmpl;
        }
        $command .= ' ' . escapeshellarg($in);
        $command .= ' 2>&1';

    // $this->_log("+++>".$command);
        exec($command, $output, $return_value);

        if ($return_value == 0) {
            return true;
        } else {
            $this->_log("PlantUML execution failed: $command");
            return false;
        }
    }

    function exec_disabled() {
        $disabled = explode(',', ini_get('disable_functions'));
        return in_array('exec', $disabled);
    }    

    /**
     * Dumps a message in a log file (named dokuwiki_plantuml.log and located in the Dokuwidi's cache directory)
     */
    function _log($text) {
        global $conf;
        $hFile = fopen($conf['cachedir'].'/dokuwiki_plantumlsvg.log', "a");
        if($hFile) {
            fwrite($hFile, $text . "\r\n");
            fclose($hFile);
        }
    }
}
