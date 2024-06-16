<?php
/**
 * @license GPL v2 (http://www.gnu.org/licenses/gpl.html)
 * @author  Willi Schönborn (w.schoenborn@googlemail.com)
 */

if (!defined('DOKU_INC')) define('DOKU_INC', dirname(__FILE__) . '/../../../');
define('NOSESSION', true);
require_once(DOKU_INC . 'inc/init.php');

$data = $_REQUEST;
$plugin = plugin_load('syntax', 'plantumlsvg');
$cache  = $plugin->_imgfile($data, 'svg');

if ($cache) {
    echo io_readFile($cache, false);
}
else {
    echo "sth wrong.";
}
