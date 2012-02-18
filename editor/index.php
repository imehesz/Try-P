<?php

$options = array(
    // which string should represent a tab for indentation
    'tabsize' => 4,
);

/**
 * PHP Console
 *
 * A web-based php debug console
 *
 * Copyright (C) 2010, Jordi Boggiano
 * http://seld.be/ - j.boggiano@seld.be
 *
 * Licensed under the new BSD License
 * See the LICENSE file for details
 *
 * Source on Github http://github.com/Seldaek/php-console
 */

/*
// removed security, we should be good to go now ...
if (!in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'), true)) {
    header('HTTP/1.1 401 Access unauthorized');
    die('ERR/401 Go Away');
}
*/

function contains_weird_code( $code )
{
	$black_list = array( 'system', 'exec', 'basename', 'chgrp', 'chmod', 'chown', 'clearstatcache', 'copy', 'delete', 'dirname', 'disk_free_space', 'disk_total_space', 'diskfreespace', 'fclose', 'feof', 'fflush', 'fgetc', 'fgetcsv', 'fgets', 'fgetss', 'file_exists', 'file_get_contents', 'file_put_contents', 'file', 'fileatime', 'filectime', 'filegroup', 'fileinode', 'filemtime', 'fileowner', 'fileperms', 'filesize', 'filetype', 'flock', 'fnmatch', 'fopen', 'fpassthru', 'fputcsv', 'fputs', 'fread', 'fscanf', 'fseek', 'fstat', 'ftell', 'ftruncate', 'fwrite', 'glob', 'is_dir', 'is_executable', 'is_file', 'is_link', 'is_readable', 'is_uploaded_file', 'is_writable', 'is_writeable', 'lchgrp', 'lchown', 'link', 'linkinfo', 'lstat', 'mkdir', 'move_uploaded_file', 'parse_ini_file', 'parse_ini_string', 'phpinfo', 'pathinfo', 'pclose', 'popen', 'readfile', 'readlink', 'realpath_cache_get', 'realpath_cache_size', 'realpath', 'rename', 'rewind', 'rmdir', 'set_file_buffer', 'stat', 'symlink', 'tempnam', 'tmpfile', 'touch', 'unmask', 'unlink', 'eval' );

	// first off, let's remove ALL spaces
	$code = str_replace( ' ', '', $code );
	foreach( $black_list as $word )
	{
		if( stristr( $code, $word . '(' ) )
		{
			return true;
		}
	}

	return false;
}

define('PHP_CONSOLE_VERSION', '1.3.0-dev');
require 'krumo/class.krumo.php';

ini_set('log_errors', 0);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);

$debugOutput = '';

if (isset($_POST['code'])) {
    $code = $_POST['code'];

	if( contains_weird_code( $code ) )
	{
		$code='echo "Oops, something bad was about to happen ... Please try again.";';
	}

    if (get_magic_quotes_gpc()) {
        $code = stripslashes($code);
    }

    // if there's only one line wrap it into a krumo() call
    if (preg_match('#^(?!var_dump|echo|print|< )([^\r\n]+?);?\s*$#is', $code, $m) && trim($m[1])) {
        $code = 'krumo('.$m[1].');';
    }

    // replace '< foo' by krumo(foo)
    $code = preg_replace('#^<\s+(.+?);?[\r\n]?$#m', 'krumo($1);', $code);

    // replace newlines in the entire code block by the new specified one
    // i.e. put #\r\n on the first line to emulate a file with windows line
    // endings if you're on a unix box
    if (preg_match('{#((?:\\\\[rn]){1,2})}', $code, $m)) {
        $newLineBreak = str_replace(array('\\n', '\\r'), array("\n", "\r"), $m[1]);
        $code = preg_replace('#(\r?\n|\r\n?)#', $newLineBreak, $code);
    }

    ob_start();
    eval($code);
    $debugOutput = ob_get_clean();

    if (isset($_GET['js'])) {
        header('Content-Type: text/plain');
		$eval_pos = strpos( $debugOutput, 'eval' );

		if( $eval_pos )
		{
			$debugOutput = substr( $debugOutput, 0, $eval_pos );
			$debugOutput = str_replace( dirname( __FILE__ ) . '/index.php', '', $debugOutput ) ;
		}

        echo $debugOutput;
        die('#end-php-console-output#');
    }
}

?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title>Debug Console</title>
        <link rel="stylesheet" type="text/css" href="styles.css" />
        <link rel="stylesheet" type="text/css" href="../css/bootstrap.css" />
        <script src="jquery-1.7.1.min.js"></script>
        <script src="ace/ace.js"></script>
        <script src="ace/mode-php.js"></script>
        <script src="php-console.js"></script>
        <script>
            $.console({
                tabsize: <?php echo json_encode($options['tabsize']) ?>
            });
        </script>
    </head>
    <body>
		<hr />
        <form method="POST" action="">
            <div class="input">
                <textarea style="overflow:hidden;" class="editor" id="editor" name="code"><?php echo (isset($_POST['code']) ? htmlentities($_POST['code'], ENT_QUOTES, 'UTF-8') : null) ?></textarea>
				<div class="output"><?php echo $debugOutput ?></div>
				<div style="clear:both;"></div>
                <div class="statusbar" style="display:none;">
                    <span style="display:none;" class="position">Line: 1, Column: 1</span>
                    <span class="copy">
                        Copy selection: <object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="110" height="14" id="clippy">
                            <param name="movie" value="clippy/clippy.swf"/>
                            <param name="allowScriptAccess" value="always" />
                            <param name="quality" value="high" />
                            <param name="scale" value="noscale" />
                            <param NAME="FlashVars" value="text=">
                            <param name="bgcolor" value="#E8E8E8">
                            <embed src="clippy/clippy.swf"
                                   width="110"
                                   height="14"
                                   name="clippy"
                                   quality="high"
                                   allowScriptAccess="always"
                                   type="application/x-shockwave-flash"
                                   pluginspage="http://www.macromedia.com/go/getflashplayer"
                                   FlashVars="text="
                                   bgcolor="#E8E8E8"
                            />
                        </object>
                    </span>
                </div>
            </div>
			<div style="text-align:center;margin-top:10px;">
				<input type="submit" name="subm" value="SHOW ME THE MAGIC!" class="btn btn-primary" />
			</div>
        </form>

		<hr />

		<?php /*
        <div class="help">
            press ctrl-enter to submit
            put '#\n' on the first line to enforce
                \n line breaks (\r\n etc work too)
        </div>
        <div class="footer">
            php-console v<?php echo PHP_CONSOLE_VERSION ?> - by <a href="http://seld.be/">Jordi Boggiano</a> - <a href="http://github.com/Seldaek/php-console">sources on github</a>
        </div>
		*/ ?>
    </body>
</html>
