#!/usr/bin/env php
<?php

if (!defined('PAKE_DIR'))
    define('PAKE_DIR', dirname(__FILE__));
function pake_autoloader($classname)
{
    static $classes = null;
    if (null === $classes) {
        $classes = array(
            'pakeException'     => PAKE_DIR.'/pakeException.class.php',
            'pakeYaml'          => PAKE_DIR.'/pakeYaml.class.php',
            'pakeGetopt'        => PAKE_DIR.'/pakeGetopt.class.php',
            'pakeGlobToRegex'   => PAKE_DIR.'/pakeGlobToRegex.class.php',
            'pakeFinder'        => PAKE_DIR.'/pakeFinder.class.php',
            'pakeNumberCompare' => PAKE_DIR.'/pakeNumberCompare.class.php',
            'pakeTask'          => PAKE_DIR.'/pakeTask.class.php',
            'pakeFileTask'      => PAKE_DIR.'/pakeFileTask.class.php',
            'pakeColor'         => PAKE_DIR.'/pakeColor.class.php',
            'pakeApp'           => PAKE_DIR.'/pakeApp.class.php',
            'pakeSubversion'    => PAKE_DIR.'/pakeSubversion.class.php',
            'pakeGit'           => PAKE_DIR.'/pakeGit.class.php',
            'pakeRSync'         => PAKE_DIR.'/pakeRSync.class.php',
            'pakeSSH'           => PAKE_DIR.'/pakeSSH.class.php',
            'pakeArchive'       => PAKE_DIR.'/pakeArchive.class.php',
            'pakeInput'         => PAKE_DIR.'/pakeInput.class.php',
            'pakeMercurial'     => PAKE_DIR.'/pakeMercurial.class.php',
            'pakeHttp'          => PAKE_DIR.'/pakeHttp.class.php',
            'pakeMySQL'         => PAKE_DIR.'/pakeMySQL.class.php',
            'pakePHPDoc'        => PAKE_DIR.'/pakePHPDoc.class.php',
            'sfYaml'            => PAKE_DIR.'/sfYaml/sfYaml.php',
            'sfYamlDumper'      => PAKE_DIR.'/sfYaml/sfYamlDumper.php',
            'sfYamlInline'      => PAKE_DIR.'/sfYaml/sfYamlInline.php',
            'sfYamlParser'      => PAKE_DIR.'/sfYaml/sfYamlParser.php',
        );
    }
    if (isset($classes[$classname]))
        require $classes[$classname];
}
spl_autoload_register('pake_autoloader');
function pake_exception_default_handler($exception)
{
  pakeException::render($exception);
  exit(1);
}
set_exception_handler('pake_exception_default_handler');
mb_internal_encoding('utf-8');
if (false !== strpos(PHP_SAPI, 'cgi'))
{
      @ob_end_flush();
   ob_implicit_flush(true);
      set_time_limit(0);
   ini_set('track_errors', true);
   ini_set('html_errors', false);
   ini_set('magic_quotes_runtime', false);
      define('STDIN', fopen('php://stdin', 'r'));
   define('STDOUT', fopen('php://stdout', 'w'));
   define('STDERR', fopen('php://stderr', 'w'));
      if (isset($_SERVER['PWD']))
   {
     chdir($_SERVER['PWD']);
   }
      register_shutdown_function(create_function('', 'fclose(STDIN); fclose(STDOUT); fclose(STDERR); return true;'));
}

function pake_require_version($version)
{
    if (version_compare(pakeApp::VERSION, $version, '<'))
        throw new pakeException('Pake '.$version.' or newer is required. Please upgrade');
}
function pake_import($name, $import_default_tasks = true)
{
    $class_name = 'pake'.ucfirst($name).'Task';
    if (!class_exists($class_name)) {
                $plugin_path = '';
        foreach (pakeApp::get_plugin_dirs() as $dir) {
            if (file_exists($dir.'/'.$class_name.'.class.php')) {
                $plugin_path = $dir.'/'.$class_name.'.class.php';
                break;
            }
        }
        if (!$plugin_path) {
            throw new pakeException('Plugin "'.$name.'" does not exist.');
        }
        require_once $plugin_path;
    }
    if ($import_default_tasks && is_callable($class_name, 'import_default_tasks')) {
        call_user_func(array($class_name, 'import_default_tasks'));
    }
}
function pake_task($name)
{
    $args = func_get_args();
    array_shift($args);
    pakeTask::define_task($name, $args);
    return $name;
}
function pake_alias($alias, $name)
{
  pakeTask::define_alias($alias, $name);
  return $alias;
}
function pake_desc($comment)
{
  pakeTask::define_comment($comment);
}
function pake_help($help)
{
    pakeTask::define_help($help);
}
function pake_properties($property_file)
{
    $app = pakeApp::get_instance();
    $file = $property_file;
    if (!pakeFinder::isPathAbsolute($file)) {
        $file = dirname($app->getPakefilePath()).'/'.$property_file;
    }
    if (!file_exists($file)) {
        throw new pakeException('Properties file does not exist.');
    }
    $app->set_properties(parse_ini_file($file, true));
}
function pake_file($name)
{
  $args = func_get_args();
  array_shift($args);
  pakeFileTask::define_task($name, $args);
  return $name;
}
function pake_mkdirs($path, $mode = 0777)
{
    if (is_dir($path)) {
        return true;
    }
    if (file_exists($path)) {
        throw new pakeException('Can not create directory at "'.$path.'" as place is already occupied by file');
    }
    if (!@mkdir($path, $mode, true)) {
        throw new pakeException('Failed to create dir: "'.$path.'"');
    }
    pake_echo_action('dir+', $path);
    return true;
}

function pake_copy($origin_file, $target_file, $options = array())
{
    if (!array_key_exists('override', $options)) {
        $options['override'] = false;
    }
        $override = (!stream_is_local($origin_file) or $options['override']);
        if (!is_dir(dirname($target_file))) {
        pake_mkdirs(dirname($target_file));
    }
    $most_recent = false;
    if (false === $override and file_exists($target_file)) {
        $stat_target = stat($target_file);
        $stat_origin = stat($origin_file);
        $most_recent = ($stat_origin['mtime'] > $stat_target['mtime']) ? true : false;
    }
    if ($override || !file_exists($target_file) || $most_recent) {
        pake_echo_action('file+', $target_file);
        copy($origin_file, $target_file);
    }
}
function pake_rename($origin, $target)
{
        if (file_exists($target)) {
        throw new pakeException('Cannot rename because the target "'.$target.'" already exist.');
    }
    if (!file_exists($origin) or !is_readable($origin)) {
        throw new pakeException('Cannot rename because origin "'.$origin.'" does not exist (or not readable)');
    }
    if (!is_writable($origin)) {
        throw new pakeException('Cannot rename because there are no enough rights to delete origin "'.$origin.'"');
    }
            if (is_dir($origin)) {
        rename($origin, $target);
        return;
    }
        if (copy($origin, $target)) {
        if (unlink($origin)) {
            pake_echo_action('rename', $origin.' -> '.$target);
        } else {
            unlink($target);
            throw new pakeException('Can not delete "'.$origin.'" file. Rename failed');
        }
    }
}
function pake_mirror($arg, $origin_dir, $target_dir, $options = array())
{
  $files = pakeFinder::get_files_from_argument($arg, $origin_dir, true);
  foreach ($files as $file)
  {
    if (is_dir($origin_dir.DIRECTORY_SEPARATOR.$file))
    {
      pake_mkdirs($target_dir.DIRECTORY_SEPARATOR.$file);
    }
    else if (is_file($origin_dir.DIRECTORY_SEPARATOR.$file))
    {
      pake_copy($origin_dir.DIRECTORY_SEPARATOR.$file, $target_dir.DIRECTORY_SEPARATOR.$file, $options);
    }
    else if (is_link($origin_dir.DIRECTORY_SEPARATOR.$file))
    {
      pake_symlink($origin_dir.DIRECTORY_SEPARATOR.$file, $target_dir.DIRECTORY_SEPARATOR.$file);
    }
    else
    {
      throw new pakeException('Unable to determine "'.$file.'" type');
    }
  }
}
function pake_remove($arg, $target_dir)
{
  $files = array_reverse(pakeFinder::get_files_from_argument($arg, $target_dir));
  foreach ($files as $file)
  {
    if (is_dir($file) && !is_link($file))
    {
      pake_echo_action('dir-', $file);
      rmdir($file);
    }
    else
    {
      pake_echo_action(is_link($file) ? 'link-' : 'file-', $file);
      unlink($file);
    }
  }
}
function pake_remove_dir($path)
{
        $finder = pakeFinder::type('any');
    pake_remove($finder, $path);
        $finder = pakeFinder::type('dir')->name(basename($path))->maxdepth(0);
    pake_remove($finder, dirname($path));
}
function pake_touch($arg, $target_dir)
{
  $files = pakeFinder::get_files_from_argument($arg, $target_dir);
  foreach ($files as $file)
  {
    pake_echo_action('file+', $file);
    touch($file);
  }
}
function pake_replace_tokens_to_dir($arg, $src_dir, $target_dir, $begin_token, $end_token, $tokens)
{
    $files = pakeFinder::get_files_from_argument($arg, $src_dir, true);
    foreach ($files as $file)
    {
      $replaced = false;
      $content = pake_read_file($src_dir.'/'.$file);
      foreach ($tokens as $key => $value)
      {
        $content = str_replace($begin_token.$key.$end_token, $value, $content, $count);
        if ($count) $replaced = true;
      }
      pake_echo_action('tokens', $target_dir.DIRECTORY_SEPARATOR.$file);
      file_put_contents($target_dir.DIRECTORY_SEPARATOR.$file, $content);
    }
}
function pake_replace_tokens($arg, $target_dir, $begin_token, $end_token, $tokens)
{
    pake_replace_tokens_to_dir($arg, $target_dir, $target_dir, $begin_token, $end_token, $tokens);
}
function pake_replace_regexp_to_dir($arg, $src_dir, $target_dir, $regexps)
{
    $files = pakeFinder::get_files_from_argument($arg, $src_dir, true);
    foreach ($files as $file)
    {
        $replaced = false;
        $content = pake_read_file($src_dir.'/'.$file);
        foreach ($regexps as $key => $value)
        {
            $content = preg_replace($key, $value, $content, -1, $count);
            if ($count) $replaced = true;
        }
        pake_echo_action('tokens', $target_dir.DIRECTORY_SEPARATOR.$file);
        file_put_contents($target_dir.DIRECTORY_SEPARATOR.$file, $content);
    }
}
function pake_replace_regexp($arg, $target_dir, $regexps)
{
    pake_replace_regexp_to_dir($arg, $target_dir, $target_dir, $regexps);
}
function pake_symlink($origin_dir, $target_dir, $copy_on_windows = false)
{
  if (!function_exists('symlink') && $copy_on_windows)
  {
    $finder = pakeFinder::type('any')->ignore_version_control();
    pake_mirror($finder, $origin_dir, $target_dir);
    return;
  }
  $ok = false;
  if (is_link($target_dir))
  {
    if (readlink($target_dir) != $origin_dir)
    {
      unlink($target_dir);
    }
    else
    {
      $ok = true;
    }
  }
  if (!$ok)
  {
    pake_echo_action('link+', $target_dir);
    symlink($origin_dir, $target_dir);
  }
}
function pake_chmod($arg, $target_dir, $mode, $umask = 0000)
{
  $current_umask = umask();
  umask($umask);
  $files = pakeFinder::get_files_from_argument($arg, $target_dir, true);
  foreach ($files as $file)
  {
    pake_echo_action(sprintf('chmod %o', $mode), $target_dir.DIRECTORY_SEPARATOR.$file);
    chmod($target_dir.DIRECTORY_SEPARATOR.$file, $mode);
  }
  umask($current_umask);
}
function pake_which($cmd)
{
    if (!isset($_SERVER['PATH']))
        throw new pakeException('PATH environment variable is not set');
    $paths = explode(PATH_SEPARATOR, $_SERVER['PATH']);
    foreach ($paths as $path) {
        if (strlen($path) === 0) {
            continue;
        }
        $test = $path.'/'.$cmd;
        if (file_exists($test) and !is_dir($test) and is_executable($test)) {
            return $test;
        }
    }
    throw new pakeException('Can not find "'.$cmd.'" executable');
}
function pake_sh($cmd, $interactive = false)
{
    $verbose = pakeApp::get_instance()->get_verbose();
    pake_echo_action('exec', $cmd);
    if (false === $interactive) {
        ob_start();
    }
    passthru($cmd.' 2>&1', $return);
    if (false === $interactive) {
        $content = ob_get_clean();
        if ($return > 0) {
            throw new pakeException('Problem executing command'.($verbose ? "\n".$content : ''));
        }
    } else {
        if ($return > 0) {
            throw new pakeException('Problem executing command');
        }
    }
    if (false === $interactive) {
        return $content;
    }
}
function pake_superuser_sh($cmd, $interactive = false)
{
    if (!isset($_SERVER['USER']))
        throw new pakeException("Don't know how to run commands as superuser");
        if ($_SERVER['USER'] === 'root')
        return pake_sh($cmd, $interactive);
    try {
        $sudo = pake_which('sudo');
        $cmd = escapeshellarg($sudo).' '.$cmd;
    } catch (pakeException $e) {
        try {
            $su = pake_which('su');
            $cmd = escapeshellarg($su).' root -c '.$cmd;
            $interactive = true;         } catch (pakeException $e) {
                        throw new pakeException("Don't know how to run commands as superuser");
        }
    }
    pake_echo_comment('Next command will be run using superuser privileges');
    pake_sh($cmd, $interactive);
}
function pake_strip_php_comments($arg, $target_dir = '')
{
  
  if (!defined('T_ML_COMMENT'))
  {
    define('T_ML_COMMENT', T_COMMENT);
  }
  else
  {
    if (!defined('T_DOC_COMMENT')) define('T_DOC_COMMENT', T_ML_COMMENT);
  }
  $files = pakeFinder::get_files_from_argument($arg, $target_dir);
  foreach ($files as $file)
  {
    if (!is_file($file)) continue;
    $source = pake_read_file($file);
    $output = '';
    $tokens = token_get_all($source);
    foreach ($tokens as $token)
    {
      if (is_string($token))
      {
                $output .= $token;
      }
      else
      {
                list($id, $text) = $token;
        switch ($id)
        {
          case T_COMMENT:
          case T_ML_COMMENT:           case T_DOC_COMMENT:                         break;
          default:
                    $output .= $text;
          break;
        }
      }
    }
    file_put_contents($file, $output);
  }
}
function pake_write_file($fname, $contents, $overwrite = false)
{
    if (false === $overwrite and file_exists($fname)) {
        throw new pakeException('File "'.$fname.'" already exists');
    }
    $res = file_put_contents($fname, $contents, LOCK_EX);
    if ($res === false) {
        throw new pakeException("Couldn't write {$fname} file");
    }
    pake_echo_action("file+", $fname);
}
function pake_read_file($file_or_url)
{
    $is_http_url = (substr($file_or_url, 0, 7) == 'http://' or substr($file_or_url, 0, 8) == 'https://');
    if ($is_http_url) {
        return pakeHttp::get($file_or_url);
    }
    $retval = @file_get_contents($file_or_url);
    if (false === $retval) {
        $err = error_get_last();
        throw new pakeException("Couldn't get file: ".$err['message']);
    }
    return $retval;
}
function pake_input($question, $default = null)
{
    pake_echo($question);
    while (true) {
        if (null === $default)
            $prompt = '[>] ';
        else
            $prompt = '[> default="'.$default.'"] ';
        $retval = pakeInput::getString($prompt);
        if ('' === $retval) {
            if (null !== $default) {
                $retval = $default;
                break;
            }
            continue;
        }
        break;
    }
    return $retval;
}
function pake_select_input($question, array $options, $default = null)
{
    if (null !== $default) {
        if (!is_numeric($default))
            throw new UnexpectedValueException("Default is specified, but is not numeric");
        if (!isset($options[$default]))
            throw new UnexpectedValueException("Default is specified, but it is not one of options");
    }
    pake_echo($question);
    $i = 1;
    $options_strs = array();
    foreach ($options as $option) {
        $options_strs[] = '('.$i++.') '.$option;
    }
    pake_echo('  '.implode("\n  ", $options_strs));
    while (true) {
        if (null === $default)
            $prompt = '[>] ';
        else
            $prompt = '[> default="'.($default + 1).'"] ';
        $retval = pakeInput::getString($prompt);
        if ('' === $retval) {
            if (null === $default) {
                continue;
            }
            $retval = $options[$default];
        } else {
            if (!is_numeric($retval)) {
                pake_echo_error("Just enter number");
                continue;
            }
            if (!isset($options[$retval - 1])) {
                pake_echo_error("There is no option ".$retval);
                continue;
            }
            $retval = $options[$retval - 1];
        }
        break;
    }
    return $retval;
}
function pake_format_action($section, $text, $size = null)
{
    $longest_action = 12;     if (pakeApp::get_instance()->shouldDoExcerpts()) {
        $offset = $longest_action + 4;         $text = pake_excerpt($text, $size, $offset);
    }
    $width = $longest_action + mb_strlen(pakeColor::colorize('', 'INFO'));
    return pake_sprintf('>> %-'.$width.'s %s', pakeColor::colorize($section, 'INFO'), $text);
}
function pake_echo_action($section, $text)
{
    if (pakeApp::get_instance()->get_verbose()) {
        pake_echo(pake_format_action($section, $text));
    }
}
function pake_excerpt($text, $size = null, $offset = 0)
{
    if (null === $size) {
        $size = pakeApp::screenWidth() - $offset;
    }
    if (mb_strlen($text) < $size) {
        return $text;
    }
    $subsize = floor(($size - 1) / 2);     return mb_substr($text, 0, $subsize).pakeColor::colorize('…', 'INFO').mb_substr($text, -$subsize);
}
function pake_echo($text)
{
    echo $text."\n";
}
function pake_echo_comment($text)
{
    if (pakeApp::get_instance()->get_verbose()) {
        pake_echo(pake_sprintf(pakeColor::colorize('   # %s', 'COMMENT'), $text));
    }
}
function pake_echo_error($text)
{
    pake_echo(pake_sprintf(pakeColor::colorize('   ! %s', 'ERROR'), $text));
}

function pake_sprintf($format)
{
    $argv = func_get_args();
    array_shift($argv);
    return pake_vsprintf($format, $argv);
}

function pake_vsprintf($format, $argv, $encoding=null)
{
    if (is_null($encoding))
        $encoding = mb_internal_encoding();
        $format = mb_convert_encoding($format, 'UTF-8', $encoding);
    $newformat = "";     $newargv = array();     while ($format !== "") {
                        $format_pieces = preg_split("!\%(\+?)('.|[0 ]|)(-?)([1-9][0-9]*|)(\.[1-9][0-9]*|)([%a-zA-Z])!u", $format, 2, PREG_SPLIT_DELIM_CAPTURE);
        if (count($format_pieces) == 1) {
            list($pre, $sign, $filler, $align, $size, $precision, $type, $post) = array($format_pieces[0], '', '', '', '', '', '', '');
        } else {
            list($pre, $sign, $filler, $align, $size, $precision, $type, $post) = $format_pieces;
        }
        $newformat .= mb_convert_encoding($pre, $encoding, 'UTF-8');
        if ($type == '') {
                    } elseif ($type == '%') {
                        $newformat .= '%%';
        } elseif ($type == 's') {
            $arg = array_shift($argv);
            $arg = mb_convert_encoding($arg, 'UTF-8', $encoding);
            $padding_pre = '';
            $padding_post = '';
                        if ($precision !== '') {
                $precision = intval(substr($precision,1));
                if ($precision > 0 && mb_strlen($arg,$encoding) > $precision)
                    $arg = mb_substr($precision,0,$precision,$encoding);
            }
                        if ($size > 0) {
                $arglen = mb_strlen($arg, $encoding);
                if ($arglen < $size) {
                    if ($filler === '')
                        $filler = ' ';
                    if ($align == '-')
                        $padding_post = str_repeat($filler, $size - $arglen);
                    else
                        $padding_pre = str_repeat($filler, $size - $arglen);
                }
            }
                        $newformat .= $padding_pre . str_replace('%', '%%', $arg) . $padding_post;
        } else {
                        $newformat .= "%$sign$filler$align$size$precision$type";
            $newargv[] = array_shift($argv);
        }
        $format = strval($post);
    }
        $newformat = mb_convert_encoding($newformat, $encoding, 'UTF-8');
    return vsprintf($newformat, $newargv);
}


class pakeColor
{
  static public $styles = array();
  static function style($name, $options = array())
  {
    self::$styles[$name] = $options;
  }
  static function colorize($text = '', $parameters = array(), $stream = STDOUT)
  {
        if (!pakeApp::isTTY()) {
      return $text;
    }
    static $options    = array('bold' => 1, 'underscore' => 4, 'blink' => 5, 'reverse' => 7, 'conceal' => 8);
    static $foreground = array('black' => 30, 'red' => 31, 'green' => 32, 'yellow' => 33, 'blue' => 34, 'magenta' => 35, 'cyan' => 36, 'white' => 37);
    static $background = array('black' => 40, 'red' => 41, 'green' => 42, 'yellow' => 43, 'blue' => 44, 'magenta' => 45, 'cyan' => 46, 'white' => 47);
    if (!is_array($parameters) && isset(self::$styles[$parameters]))
    {
      $parameters = self::$styles[$parameters];
    }
    $codes = array();
    if (isset($parameters['fg']))
    {
      $codes[] = $foreground[$parameters['fg']];
    }
    if (isset($parameters['bg']))
    {
      $codes[] = $background[$parameters['bg']];
    }
    foreach ($options as $option => $value)
    {
      if (isset($parameters[$option]) && $parameters[$option])
      {
        $codes[] = $value;
      }
    }
    return "\033[".implode(';', $codes).'m'.$text."\033[0m";
  }
}
pakeColor::style('ERROR',    array('bg' => 'red', 'fg' => 'white', 'bold' => true));
pakeColor::style('INFO',     array('fg' => 'green', 'bold' => true));
pakeColor::style('COMMENT',  array('fg' => 'yellow'));


class pakePHPDoc
{
    
    public static function getDescriptions($function_name)
    {
        if (is_string($function_name))
            $reflection = new ReflectionFunction($function_name);
        elseif (is_array($function_name))
            $reflection = new ReflectionMethod($function_name[0], $function_name[1]);
        else
            throw new LogicException();
        $comment = $reflection->getDocComment();
        $short_desc = '';
        $long_desc = '';
        $looking_for_short = true;
        $short_lines_counter = 0;
        $lines = explode("\n", $comment);
        $obj = new self($lines);
        return array(trim($obj->short_desc), trim($obj->long_desc));
    }
    private $lines;
    private $looking_for_short = true;
    private $short_lines_counter = 0;
    public $short_desc = '';
    public $long_desc = '';
    private function __construct($lines)
    {
        $this->lines = $lines;
        $this->filterDocblockLines();
        $this->parse();
    }
    private function filterDocblockLines()
    {
        $lines = array();
        foreach ($this->lines as $line) {
            if (false === $line = self::filterDocblockLine($line))
                continue;
            $lines[] = $line;
        }
        $this->lines = $lines;
    }
    private function parse()
    {
        foreach ($this->lines as $line) {
            if (substr($line, 0, 1) == '@')
                break;             if ($this->looking_for_short) {
                if (false === $this->parseShortString($line))
                    break;
            } else {
                $this->long_desc .= $this->parseLongDesc($line);
            }
        }
    }
    private static function filterDocblockLine($line)
    {
        $line = trim($line);
        if ($line == '/**' or $line == '*/')
            return false;         if (substr($line, 0, 1) != '*')
            return false;         return trim(substr($line, 1));
    }
    private function parseShortString($line)
    {
                if (strlen($line) == 0) {
            $this->looking_for_short = false;
            return true;
        }
        if (++$this->short_lines_counter > 3) {
                        $this->short_lines_counter = 0;
            $this->short_desc = '';
            $this->long_desc = '';
                        $this->parseShortString($this->lines[0]);
            $this->looking_for_short = false;
            array_shift($this->lines);
            $this->parse();
            return false;
        }
        $success = preg_match('/(.*\.)(\W.*)/', $line, $matches);
        if ($success !== false and $success > 0) {
                        $this->short_desc .= trim($matches[1]);
            $this->looking_for_short = false;
            $this->long_desc .= trim($matches[2]).' ';
            return true;
        }
        $this->short_desc .= $line.' ';
        return true;
    }
    private function parseLongDesc($line)
    {
        if (strlen($line) == 0)
            return "\n";
        return $line.' ';
    }
}


class pakeApp
{
    const VERSION = '1.6.3.99';
    const QUIT_INTERACTIVE = 0xDD;
    public static $MAX_LINE_SIZE = 78;
    protected static $EXEC_NAME = 'pake';
    private static $PROPERTIES = array();
    protected static $PAKEFILES = array('pakefile', 'Pakefile', 'pakefile.php', 'Pakefile.php');
    protected static $PLUGINDIRS = array();
    protected static $OPTIONS = array(
        array('--interactive', '-i', pakeGetopt::NO_ARGUMENT,       "Start pake in interactive (shell-like) mode."),
        array('--dry-run',     '-n', pakeGetopt::NO_ARGUMENT,       "Do a dry run without executing actions."),
        array('--libdir',      '-I', pakeGetopt::REQUIRED_ARGUMENT, "Include LIBDIR in the search path for required modules."),
        array('--nosearch',    '-N', pakeGetopt::NO_ARGUMENT,       "Do not search parent directories for the pakefile."),
        array('--prereqs',     '-P', pakeGetopt::NO_ARGUMENT,       "Display the tasks and dependencies, then exit."),
        array('--quiet',       '-q', pakeGetopt::NO_ARGUMENT,       "Do not log messages to standard output."),
        array('--pakefile',    '-f', pakeGetopt::REQUIRED_ARGUMENT, "Use FILE as the pakefile."),
        array('--require',     '-r', pakeGetopt::REQUIRED_ARGUMENT, "Require php-FILE before executing pakefile."),
        array('--import',      '',   pakeGetopt::REQUIRED_ARGUMENT, "Import pake-plugin before executing pakefile."),
        array('--tasks',       '-T', pakeGetopt::NO_ARGUMENT,       "Display the tasks and dependencies, then exit."),
        array('--trace',       '-t', pakeGetopt::NO_ARGUMENT,       "Turn on invoke/execute tracing, enable full backtrace."),
        array('--usage',       '-h', pakeGetopt::NO_ARGUMENT,       "Display usage."),
        array('--verbose',     '-v', pakeGetopt::NO_ARGUMENT,       "Log message to standard output (default)."),
        array('--force-tty',   '',   pakeGetopt::NO_ARGUMENT,       "Force coloured output"),
        array('--full-width',  '',   pakeGetopt::NO_ARGUMENT,       "Force full width of output"),
        array('--version',     '-V', pakeGetopt::NO_ARGUMENT,       "Display the program version."),
    );
    private $opt = null;
    private $nosearch = false;
    private $trace = false;
    private $verbose = true;
    private $dryrun = false;
    private $nowrite = false;
    private $show_tasks = false;
    private $show_prereqs = false;
    private $interactive = false;
    private $pakefile = '';
    private $full_width = false;
    protected static $instance = null;
    protected function __construct()
    {
        self::$PLUGINDIRS[] = dirname(__FILE__).'/tasks';
    }
    public static function get_plugin_dirs()
    {
        return self::$PLUGINDIRS;
    }
    public function get_properties()
    {
        return self::$PROPERTIES;
    }
    public function set_properties($properties)
    {
        self::$PROPERTIES = $properties;
    }
    public static function get_instance()
    {
        if (!self::$instance)
            self::$instance = new pakeApp();
        return self::$instance;
    }
    public function get_verbose()
    {
        return $this->verbose;
    }
    public function get_trace()
    {
        return $this->trace;
    }
    public function get_dryrun()
    {
        return $this->dryrun;
    }
    public function getPakefilePath()
    {
        return $this->pakefile;
    }
    public function run($pakefile = null, $options = null, $load_pakefile = true)
    {
        if ($pakefile) {
            self::$PAKEFILES = array($pakefile);
        }
        $this->handle_options($options);
                pake_task('pakeApp::help');
        if ($load_pakefile) {
            $this->load_pakefile();
        }
        if ($this->show_tasks) {
            $this->display_tasks_and_comments();
            return;
        }
        if ($this->show_prereqs) {
            $this->display_prerequisites();
            return;
        }
        if ($this->interactive) {
            $this->runInteractiveSession();
            return;
        }
                $argv = $this->opt->get_arguments();
        list($task_name, $args, $options) = self::parseTaskAndParameters($argv);
        if (!$task_name) {
            return $this->runDefaultTask();
        } else {
            $task_name = pakeTask::get_full_task_name($task_name);
            return $this->initAndRunTask($task_name, $args, $options);
        }
    }
    private function runInteractiveSession()
    {
        pake_import('interactive');
        pake_echo("=================================================================================");
        pake_echo("Welcome to pake's interactive console. To get list of commands type \"?\" or \"help\"");
        pake_echo("type \"quit\" or press ^D to exit");
        pake_echo("=================================================================================");
        $this->showVersion();
        echo "\n";
        while (true) {
            $command = pakeInput::getString('pake> ', false);
            if (false === $command) {
                                pakeInteractiveTask::run_quit_pake();
            }
            if ('' === $command) {
                continue;
            }
            $this->initAndRunTaskInSubprocess($command);
        }
    }
    protected function initAndRunTaskInSubprocess($command)
    {
        if (function_exists('pcntl_fork')) {
                        $argv = explode(' ', $command);
            list($task_name, $args, $options) = self::parseTaskAndParameters($argv);
            $task_name = pakeTask::get_full_task_name($task_name);
            $pid = pcntl_fork();
            if ($pid == -1) {
                die('could not fork');
            }
            if ($pid) {
                                pcntl_wait($status);
                $status = pcntl_wexitstatus($status);
                if ($status == self::QUIT_INTERACTIVE) {
                    exit(0);
                }
            } else {
                try {
                    $status = $this->initAndRunTask($task_name, $args, $options);
                    if (true === $status)
                        exit(0);
                    exit($status);
                } catch (pakeException $e) {
                    pakeException::render($e);
                    exit(1);
                }
            }
        } else {
                        $php_exec = escapeshellarg((isset($_SERVER['_']) and substr($_SERVER['_'], -4) != 'pake') ? $_SERVER['_'] : 'php');
            $force_tty = '';
            if (pakeApp::isTTY()) {
                $force_tty = ' --force-tty';
            }
            $pake_php = escapeshellarg($_SERVER['SCRIPT_NAME']);
            $import_flag = ' --import=interactive';
            system($php_exec.' '.$pake_php.$force_tty.$import_flag.' '.$command, $status);
            if ($status == self::QUIT_INTERACTIVE) {
                exit(0);
            }
        }
    }
    protected function initAndRunTask($task_name, $args, $options)
    {
                $abbreviated_tasks = pakeTask::get_abbreviated_tasknames();
                if (!array_key_exists($task_name, $abbreviated_tasks)) {
            throw new pakeException('Task "'.$task_name.'" is not defined.');
        }
        if (count($abbreviated_tasks[$task_name]) > 1) {
            throw new pakeException('Task "'.$task_name.'" is ambiguous ('.implode(', ', $abbreviated_tasks[$task_name]).').');
        }
                $task = pakeTask::get($abbreviated_tasks[$task_name][0]);
        return $task->invoke($args, $options);
    }
    protected function runDefaultTask()
    {
        return $this->initAndRunTask('default', array(), array());
    }
    protected static function parseTaskAndParameters(array $args)
    {
        $options = array();
        if (count($args) == 0) {
            $task_name = null;
        } else {
            $task_name = array_shift($args);
            for ($i = 0, $max = count($args); $i < $max; $i++) {
                if (0 === strpos($args[$i], '--')) {
                    if (false !== $pos = strpos($args[$i], '=')) {
                        $key = substr($args[$i], 2, $pos - 2);
                        $value = substr($args[$i], $pos + 1);
                    } else {
                        $key = substr($args[$i], 2);
                        $value = true;
                    }
                    if ('[]' == substr($key, -2)) {
                        if (!isset($options[$key])) {
                            $options[$key] = array();
                        }
                        $options[$key][] = $value;
                    } else {
                        $options[$key] = $value;
                    }
                    unset($args[$i]);
                }
            }
            $args = array_values($args);
        }
        return array($task_name, $args, $options);
    }
        public function handle_options($options = null)
    {
        $this->opt = new pakeGetopt(self::$OPTIONS);
        $this->opt->parse($options);
        foreach ($this->opt->get_options() as $opt => $value) {
            $this->do_option($opt, $value);
        }
    }
            public function have_pakefile()
    {
        $here = getcwd();
        foreach (self::$PAKEFILES as $file) {
            if (file_exists($here.'/'.$file)) {
                $this->pakefile = $here.'/'.$file;
                return true;
            }
        }
        return false;
    }
    public function load_pakefile()
    {
        $start = $here = getcwd();
        while (!$this->have_pakefile()) {
            chdir('..');
            if (getcwd() == $here || $this->nosearch) {
                chdir($start);
                throw new pakeException('No pakefile found (looking for: '.join(', ', self::$PAKEFILES).')');
            }
            $here = getcwd();
        }
        require($this->pakefile);
        chdir($start);
    }
        public function do_option($opt, $value)
    {
        switch ($opt) {
            case 'interactive':
                $this->interactive = true;
                break;
            case 'dry-run':
                $this->verbose = true;
                $this->nowrite = true;
                $this->dryrun = true;
                $this->trace = true;
                break;
            case 'help':
                $this->help();
                exit();
            case 'libdir':
                set_include_path($value.PATH_SEPARATOR.get_include_path());
                break;
            case 'nosearch':
                $this->nosearch = true;
                break;
            case 'prereqs':
                $this->show_prereqs = true;
                break;
            case 'quiet':
                $this->verbose = false;
                break;
            case 'pakefile':
                self::$PAKEFILES = array($value);
                break;
            case 'require':
                require $value;
                break;
            case 'import':
                pake_import($value);
                break;
            case 'tasks':
                $this->show_tasks = true;
                break;
            case 'trace':
                $this->trace = true;
                $this->verbose = true;
                break;
            case 'usage':
                $this->usage();
                exit();
            case 'verbose':
                $this->verbose = true;
                break;
            case 'force-tty':
                define('PAKE_FORCE_TTY', true);
                break;
            case 'full-width':
                $this->full_width = true;
                break;
            case 'version':
                $this->showVersion();
                exit();
            default:
                throw new pakeException('Unknown option: '.$opt);
        }
    }
    public function showVersion()
    {
        echo pake_sprintf('pake version %s', pakeColor::colorize(self::VERSION, 'INFO'))."\n";
    }
        public function usage($hint_about_help = true)
    {
        echo self::$EXEC_NAME." [-f pakefile] {options} targets…\n";
        if (true === $hint_about_help) {
            echo 'Try "';
            echo pakeColor::colorize(self::$EXEC_NAME.' help', 'INFO');
            echo '" for more information'."\n";
        }
    }
        public function help()
    {
        $this->usage(false);
        echo "\n";
        echo "available options:";
        echo "\n";
        foreach (self::$OPTIONS as $option) {
            list($long, $short, $mode, $comment) = $option;
            if ($mode == pakeGetopt::REQUIRED_ARGUMENT) {
                if (preg_match('/\b([A-Z]{2,})\b/', $comment, $match))
                    $long .= '='.$match[1];
            }
            printf("  %-20s", pakeColor::colorize($long, 'INFO'));
            if (!empty($short)) {
                printf(" (%s)", pakeColor::colorize($short, 'INFO'));
            }
            printf("\n      %s\n", $comment);
        }
    }
        public function display_tasks_and_comments()
    {
        $width = 0;
        $tasks = pakeTask::get_tasks();
        foreach ($tasks as $name => $task) {
            $w = strlen(pakeTask::get_mini_task_name($name));
            if ($w > $width)
                $width = $w;
        }
        $width += mb_strlen(pakeColor::colorize(' ', 'INFO'));
        echo "available ".self::$EXEC_NAME." tasks:\n";
                $has_alias = false;
        ksort($tasks);
        foreach ($tasks as $name => $task) {
            if ($task->get_alias()) {
                $has_alias = true;
            }
            if (!$task->get_alias() and $task->get_comment()) {
                $mini_name = pakeTask::get_mini_task_name($name);
                printf('  %-'.$width.'s > %s'."\n", pakeColor::colorize($mini_name, 'INFO'), $task->get_comment().($mini_name != $name ? ' ['.$name.']' : ''));
            }
        }
        if ($has_alias) {
            print("\ntask aliases:\n");
                        foreach ($tasks as $name => $task) {
                if ($task->get_alias()) {
                    $mini_name = pakeTask::get_mini_task_name($name);
                    printf('  %-'.$width.'s = '.self::$EXEC_NAME.' %s'."\n", pakeColor::colorize(pakeTask::get_mini_task_name($name), 'INFO'), $task->get_alias().($mini_name != $name ? ' ['.$name.']' : ''));
                }
            }
        }
        echo "\n".'Try "';
        echo pakeColor::colorize(self::$EXEC_NAME.' help taskname', 'INFO');
        echo '" to get detailed information about task'."\n\n";
    }
        public function display_prerequisites()
    {
        foreach (pakeTask::get_tasks() as $name => $task) {
            echo self::$EXEC_NAME." ".pakeTask::get_mini_task_name($name)."\n";
            foreach ($task->get_prerequisites() as $prerequisite) {
                echo "    $prerequisite\n";
            }
        }
    }
    public function shouldDoExcerpts()
    {
        return self::isTTY() and $this->full_width === false;
    }
    public static function screenWidth()
    {
        $cols = getenv('COLUMNS');
        return (false === $cols ? self::$MAX_LINE_SIZE : $cols);
    }
    public static function isTTY()
    {
        return defined('PAKE_FORCE_TTY') or (DIRECTORY_SEPARATOR != '\\' and function_exists('posix_isatty') and @posix_isatty(STDOUT));
    }
    
    public static function run_help($task, $args)
    {
        if (count($args) == 0) {
            self::get_instance()->help();
            return;
        }
        $victim_name = $args[0];
        foreach (pakeTask::get_tasks() as $name => $task) {
            if ($victim_name == $name or $victim_name == pakeTask::get_mini_task_name($name)) {
                $victim = $task;
                break;
            }
        }
        $title = 'Documentation for "'.$victim_name.'" task';
        pake_echo($title);
        pake_echo(str_repeat('=', mb_strlen($title)));
        pake_echo($victim->get_comment()."\n");
        pake_echo($victim->get_help());
    }
}


class pakeGetopt
{
  const NO_ARGUMENT = 0;
  const REQUIRED_ARGUMENT = 1;
  const OPTIONAL_ARGUMENT = 2;
  private $short_options = array();
  private $long_options = array();
  private $args = '';
  private $options = array();
  private $arguments = array();
  public function __construct($options)
  {
    $this->args = '';
    foreach ($options as $option)
    {
      if (!$option[0])
      {
        throw new pakeException('pakeGetopt: You must define a long option name! for option '.$option[1].' ('.$option[3].').');
      }
      $this->add_option($option[0], $option[1], $option[2], $option[3]);
    }
  }
  public function add_option($long_opt, $short_opt, $mode = self::NO_ARGUMENT, $comment = '')
  {
    if ($long_opt{0} == '-' && $long_opt{1} == '-')
    {
      $long_opt = substr($long_opt, 2);
    }
    if ($short_opt)
    {
      if ($short_opt{0} == '-')
      {
        $short_opt = substr($short_opt, 1);
      }
      $this->short_options[$short_opt] = array('mode' => $mode, 'comment' => $comment, 'name' => $long_opt);
    }
    $this->long_options[$long_opt] = array('mode' => $mode, 'comment' => $comment, 'name' => $long_opt);
  }
  public function parse($args = null)
  {
    if (is_string($args))
    {
            $args = preg_replace('/(\'|")(.+?)\\1/e', "str_replace(' ', '=PLACEHOLDER=', '\\2')", $args);
      $args = preg_split('/\s+/', $args);
      $args = str_replace('=PLACEHOLDER=', ' ', $args);
    }
    else if (!$args)
    {
      $args = $this->read_php_argv();
            if (isset($args[0]) && $args[0]{0} != '-')
      {
        array_shift($args);
      }
    }
    $this->args = $args;
    $this->options = array();
    $this->arguments = array();
    while ($arg = array_shift($this->args))
    {
      
      if ($arg == '--')
      {
        $this->arguments = array_merge($this->arguments, $this->args);
        break;
      }
      if ($arg{0} != '-' || (strlen($arg) > 1 && $arg{1} == '-' && !$this->long_options))
      {
        $this->arguments = array_merge($this->arguments, array($arg), $this->args);
        break;
      }
      elseif (strlen($arg) > 1 && $arg{1} == '-')
      {
        $this->parse_long_option(substr($arg, 2));
      }
      else
      {
        $this->parse_short_option(substr($arg, 1));
      }
    }
  }
  public function has_option($option)
  {
    return (array_key_exists($option, $this->options) ? true : false);
  }
  public function get_option($option)
  {
        if (array_key_exists($option, $this->long_options) && $this->long_options[$option]['mode'] != self::NO_ARGUMENT)
    {
      return (array_key_exists($option, $this->options) ? $this->options[$option] : '');
    }
    else
    {
      throw new pakeException('pakeGetopt: You cannot get a value for a NO_ARGUMENT option.');
    }
  }
  public function get_options()
  {
    return $this->options;
  }
  public function get_arguments()
  {
    return $this->arguments;
  }
  private function parse_short_option($arg)
  {
    for ($i = 0; $i < strlen($arg); $i++)
    {
      $opt = $arg{$i};
      $opt_arg = true;
      
      if (!array_key_exists($opt, $this->short_options))
      {
        throw new pakeException('pakeGetopt: unrecognized option -'.$opt.'.');
      }
      
      if ($this->short_options[$opt]['mode'] == self::REQUIRED_ARGUMENT)
      {
        if ($i + 1 < strlen($arg))
        {
          $this->options[$this->short_options[$opt]['name']] = substr($arg, $i + 1);
          break;
        }
        else
        {
                    if (count($this->args) && $this->args[0]{0} != '-')
          {
            $this->options[$this->short_options[$opt]['name']] = array_shift($this->args);
            break;
          }
          else
          {
            throw new pakeException('pakeGetopt: option -'.$opt.' requires an argument');
          }
        }
      }
      else if ($this->short_options[$opt]['mode'] == self::OPTIONAL_ARGUMENT)
      {
        if (substr($arg, $i + 1) != '')
        {
          $this->options[$this->short_options[$opt]['name']] = substr($arg, $i + 1);
        }
        else
        {
                    if (count($this->args) && $this->args[0]{0} != '-')
          {
            $this->options[$this->short_options[$opt]['name']] = array_shift($this->args);
          }
          else
          {
            $this->options[$this->short_options[$opt]['name']] = true;
          }
        }
        break;
      }
      $this->options[$this->short_options[$opt]['name']] = $opt_arg;
    }
  }
  private function parse_long_option($arg)
  {
    @list($opt, $opt_arg) = explode('=', $arg);
    if (!$opt_arg)
    {
      $opt_arg = true;
    }
    
    if (!array_key_exists($opt, $this->long_options))
    {
      throw new pakeException('pakeGetopt: unrecognized option --'.$opt.'.');
    }
    
    if ($this->long_options[$opt]['mode'] == self::REQUIRED_ARGUMENT)
    {
      if ($opt_arg)
      {
        $this->options[$this->long_options[$opt]['name']] = $opt_arg;
        return;
      }
      else
      {
        throw new pakeException('pakeGetopt: option --'.$opt.' requires an argument.');
      }
    }
    else if ($this->long_options[$opt]['mode'] == self::OPTIONAL_ARGUMENT)
    {
      $this->options[$this->long_options[$opt]['name']] = $opt_arg;
      return;
    }
    else
    {
      $this->options[$this->long_options[$opt]['name']] = true;
    }
  }
  
  private function read_php_argv()
  {
    global $argv;
    if (!is_array($argv))
    {
      if (!@is_array($_SERVER['argv']))
      {
        if (!@is_array($GLOBALS['HTTP_SERVER_VARS']['argv']))
        {
          throw new pakeException("pakeGetopt: Could not read cmd args (register_argc_argv=Off?).");
        }
        return $GLOBALS['HTTP_SERVER_VARS']['argv'];
      }
      return $_SERVER['argv'];
    }
    return $argv;
  }
}


class pakeFileTask extends pakeTask
{
  public function is_needed()
  {
    if (!file_exists($this->get_name())) return true;
    $latest_prereq = 0;
    foreach ($this->prerequisites as $prerequisite)
    {
      $t = pakeTask::get($prerequisite)->timestamp();
      if ($t > $latest_prereq)
      {
        $latest_prereq = $t;
      }
    }
    if ($latest_prereq == 0)
    {
      return false;
    }
    return ($this->timestamp() < $latest_prereq);
  }
  public function timestamp()
  {
    if (!file_exists($this->get_name()))
    {
      throw new pakeException('File "'.$this->get_name().'" does not exist!');
    }
    return filemtime($this->get_name());
  }
  public static function define_task($name, $deps = null)
  {
     $task = pakeTask::lookup($name, 'pakeFileTask');
     $task->add_comment();
     $task->enhance($deps);
  }
}

class pakeTask
{
  protected static $TASKS = array();
  protected static $ALIAS = array();
  protected static $last_comment = '';
  protected static $last_help = '';
  protected $prerequisites = array();
  protected $name = '';
  protected $comment = '';
  protected $help = '';
  protected $already_invoked = false;
  protected $trace = null;
  protected $verbose = null;
  protected $dryrun = null;
  protected $alias = '';
  public function __construct($task_name)
  {
    $this->name = $task_name;
    $this->comment = '';
    $this->help = '';
    $this->prerequisites = array();
    $this->already_invoked = false;
    $pake = pakeApp::get_instance();
    $this->trace = $pake->get_trace();
    $this->dryrun = $pake->get_dryrun();
    $this->verbose = $pake->get_verbose();
  }
  public function is_verbose()
  {
    return $this->verbose;
  }
  public function enhance($deps = null)
  {
    if (!$deps) return;
    if (is_array($deps))
    {
      $this->prerequisites = array_merge($this->prerequisites, $deps);
    }
    else
    {
      $this->prerequisites[] = $deps;
    }
  }
  public static function get_tasks()
  {
    $tasks = pakeTask::$TASKS;
        foreach (pakeTask::$ALIAS as $alias => $name)
    {
      if (!array_key_exists($name, $tasks))
      {
        throw new pakeException('Task "'.$name.'" cannot be cloned to "'.$alias.'" because it does not exist.');
      }
      $alias_task = clone $tasks[$name];
      $alias_task->alias = $name;
      $alias_task->name = $alias;
      $tasks[$alias] = $alias_task;
    }
    return $tasks;
  }
  public static function get_abbreviated_tasknames()
  {
      return self::abbrev(array_keys(self::get_tasks()));
  }
  public function get_property($name, $section = null)
  {
    $properties = pakeApp::get_instance()->get_properties();
    if ($section)
    {
      if (!array_key_exists($section, $properties) || !array_key_exists($name, $properties[$section]))
      {
        throw new pakeException('Property "'.$section.'/'.$name.'" does not exist.');
      }
      else
      {
        return $properties[$section][$name];
      }
    }
    else
    {
      if (!array_key_exists($name, $properties))
      {
        throw new pakeException('Property "'.$name.'" does not exist.');
      }
      else
      {
        return $properties[$name];
      }
    }
  }
  public function get_alias()
  {
    return $this->alias;
  }
  public function get_prerequisites()
  {
    return $this->prerequisites;
  }
  public function get_name()
  {
    return $this->name;
  }
  public function get_comment()
  {
    return $this->comment;
  }
    public function get_help()
    {
        return $this->help;
    }
    private function format_trace_flags()
  {
    $flags = array();
    if (!$this->already_invoked)
    {
      $flags[] = 'first_time';
    }
    if (!$this->is_needed())
    {
      $flags[] = 'not_needed';
    }
    return (count($flags)) ? '('.join(', ', $flags).')' : '';
  }
  public function invoke($args, $options)
  {
    if ($this->trace)
    {
      pake_echo_action('invoke', $this->name.' '.$this->format_trace_flags());
    }
        if ($this->already_invoked) return;
    $this->already_invoked = true;
        $tasks = self::get_tasks();
    foreach ($this->prerequisites as $prerequisite)
    {
      $real_prerequisite = self::get_full_task_name($prerequisite);
      if (array_key_exists($real_prerequisite, $tasks))
      {
        $tasks[$real_prerequisite]->invoke($args, $options);
      }
      else
      {
        throw new pakeException('Prerequisite "'.$prerequisite.'" does not exist.');
      }
    }
        if ($this->is_needed())
    {
      return $this->execute($args, $options);
    }
  }
  public function execute($args, $options)
  {
    if ($this->dryrun)
    {
      pake_echo_action('execute', '(dry run) '.$this->name);
      return;
    }
    if ($this->trace)
    {
      pake_echo_action('execute', $this->name);
    }
        $function = $this->getCallable();
        return call_user_func_array($function, array($this, $args, $options));
  }
    private function getCallable()
    {
        $function = ($this->get_alias() ? $this->get_alias() : $this->get_name());
        if ($pos = strpos($function, '::')) {
            $function = array(substr($function, 0, $pos), preg_replace('/\-/', '_', 'run_'.strtolower(substr($function, $pos + 2))));
            if (!is_callable($function)) {
                throw new pakeException('Task "'.$function[1].'" is defined but with no action defined.');
            }
        } else {
            $function = preg_replace('/\-/', '_', 'run_'.strtolower($function));
            if (!function_exists($function)) {
                throw new pakeException('Task "'.$this->name.'" is defined but with no action defined.');
            }
        }
        return $function;
    }
  public function is_needed()
  {
    return true;
  }
  public function timestamp()
  {
    $max = 0;
    foreach ($this->prerequisites as $prerequisite)
    {
      $t = pakeTask::get($prerequisite)->timestamp();
      if ($t > $max) $max = $t;
    }
    return ($max ? $max : time());
  }
  public static function define_task($name, $deps = null)
  {
     $task = pakeTask::lookup($name, 'pakeTask');
     $task->add_comment();
     $task->set_help();
     $task->enhance($deps);
  }
  public static function define_alias($alias, $name)
  {
    self::$ALIAS[$alias] = $name;
  }
  public static function lookup($task_name, $class = 'pakeTask')
  {
    $tasks = self::get_tasks();
    $task_name = self::get_full_task_name($task_name);
    if (!array_key_exists($task_name, $tasks))
    {
      pakeTask::$TASKS[$task_name] = new $class($task_name);
    }
    return pakeTask::$TASKS[$task_name];
  }
  public static function get($task_name)
  {
    $tasks = self::get_tasks();
    $task_name = self::get_full_task_name($task_name);
    if (!array_key_exists($task_name, $tasks))
    {
      throw new pakeException('Task "'.$task_name.'" is not defined.');
    }
    return $tasks[$task_name];
  }
  public static function get_full_task_name($task_name)
  {
    foreach (self::get_tasks() as $task)
    {
      $mini_task_name = self::get_mini_task_name($task->get_name());
      if ($mini_task_name == $task_name)
      {
        return $task->get_name();
      }
    }
    return $task_name;
  }
  
  public static function get_mini_task_name($task_name)
  {
    $is_method_task = strpos($task_name, '::');
    return ((false !== $is_method_task) ? substr($task_name, $is_method_task + 2) : $task_name);
  }
  public static function define_comment($comment)
  {
    pakeTask::$last_comment = $comment;
  }
    public static function define_help($help)
    {
        pakeTask::$last_help = $help;
    }
    public function add_comment()
    {
        if (self::$last_comment) {
            $comment_to_add = self::$last_comment;
            self::$last_comment = '';
        } else {
            $descriptions = pakePHPDoc::getDescriptions($this->getCallable());
            $comment_to_add = $descriptions[0];
        }
        if (empty($comment_to_add))
            return;
        if ($this->comment) {
            $this->comment .= ' / ';
        }
        $this->comment .= $comment_to_add;
    }
    public function set_help()
    {
        if (self::$last_help) {
            $help_to_add = self::$last_help;
            self::$last_help = '';
        } else {
            $descriptions = pakePHPDoc::getDescriptions($this->getCallable());
            $help_to_add = $descriptions[1];
        }
        if (empty($help_to_add))
            return;
        $this->help = $help_to_add;
    }
  
  public static function abbrev(array $options)
  {
      $abbrevs = array();
      $table = array();
      foreach ($options as $option) {
          $option = pakeTask::get_mini_task_name($option);
          for ($len = (strlen($option)) - 1; $len > 0; --$len) {
              $abbrev = substr($option, 0, $len);
              if (!array_key_exists($abbrev, $table))
                  $table[$abbrev] = 1;
              else
                  ++$table[$abbrev];
              $seen = $table[$abbrev];
              if ($seen == 1) {
                                    $abbrevs[$abbrev] = array($option);
              } elseif ($seen == 2) {
                                                      $abbrevs[$abbrev][] = $option;
              } else {
                                    continue;
              }
          }
      }
            foreach ($options as $option) {
          $abbrevs[$option] = array($option);
      }
      return $abbrevs;
  }
}
class pakeInput
{
    public static function getString($prompt = '> ', $retry_on_ctrld = true)
    {
        while (true) {
            echo $prompt;
            $fp = fopen('php://stdin', 'r');
            $retval = fgets($fp);
            fclose($fp);
                        if (false === $retval) {
                echo "\n";
                if ($retry_on_ctrld) {
                    continue;
                }
                return false;
            }
            return rtrim($retval, "\r\n");
        }
    }
    public static function getPassword($prompt = '>')
    {
    }
}

class pakeYaml
{
    public static function loadString($input)
    {
        if (extension_loaded('yaml')) {
            $retval = yaml_parse($input);
            if (false === $retval) {
                throw new pakeException("empty yaml document");
            }
        } else {
            sfYaml::setSpecVersion('1.1');             $parser = new sfYamlParser();
            $retval = $parser->parse($input);
            if (null === $retval) {
                throw new pakeException("empty yaml document");
            }
        }
        return $retval;
    }
    public static function loadFile($file)
    {
        return self::loadString(pake_read_file($file));
    }
    public static function emitString($data)
    {
        if (extension_loaded('yaml')) {
            return yaml_emit($data);
        }
        sfYaml::setSpecVersion('1.1');         $dumper = new sfYamlDumper();
        return $dumper->dump($data);
    }
    public static function emitFile($data, $file)
    {
        if (file_exists($file) and !is_writable($file))
            throw new pakeException('Not enough rights to overwrite "'.$file.'"');
        $dir = dirname($file);
        pake_mkdirs($dir);
        if (!is_writable($dir))
            throw new pakeException('Not enough rights to create file in "'.$dir.'"');
        if (extension_loaded('yaml')) {
                                                if (false === file_put_contents($file, yaml_emit($data)))
                throw new pakeException("Couldn't create file");
        } else {
            sfYaml::setSpecVersion('1.1');             $dumper = new sfYamlDumper();
            if (false === file_put_contents($file, $dumper->dump($data, 1)))
                throw new pakeException("Couldn't create file");
        }
        pake_echo_action('file+', $file);
    }
        public static function load($input)
    {
        return self::loadFile($input);
    }
    public static function dump($array)
    {
        return self::emitString($array);
    }
}

class pakeRSync
{
    public static function mirror_dir($src_path, $target_path)
    {
                if ($src_path[strlen($src_path)-1] != '/')
            $src_path .= '/';
        self::throwIfPathDoesNotExists($src_path);
        pake_mkdirs($target_path);
        pake_sh(escapeshellarg(pake_which('rsync')).' -az '.escapeshellarg($src_path).' '.escapeshellarg($target_path));
    }
    
    public static function sync_to_server($src, $server_host, $remote_path, $rsync_login = '', $transport = 'ssh')
    {
        if (strlen($rsync_login) > 0) {
            $rsync_login .= '@';
        }
        if (is_string($src)) {
                        if ($src[strlen($src)-1] != '/')
                $src .= '/';
            $src = array($src);
        } elseif (is_array($src)) {
                        $src = array_map(create_function('$path', 'return rtrim($path, "/");'), $src);
        }
        array_walk($src, array('self', 'throwIfPathDoesNotExists'));
        pake_sh(escapeshellarg(pake_which('rsync')).' -az -e '.escapeshellarg($transport).' '
                .implode(' ', array_map('escapeshellarg', $src)).' '
                .escapeshellarg("{$rsync_login}{$server_host}:{$remote_path}")
        );
    }
    public static function sync_from_server($local_path, $server_host, $remote_paths, $rsync_login = '', $transport = 'ssh')
    {
        if (strlen($rsync_login) > 0) {
            $rsync_login .= '@';
        }
        pake_mkdirs($local_path);
        if (is_string($remote_paths)) {
                        if ($remote_paths[strlen($remote_paths)-1] != '/')
                $remote_paths .= '/';
            $remote_paths = array($remote_paths);
        } elseif (is_array($remote_paths)) {
                        $remote_paths = array_map(create_function('$path', 'return rtrim($path, "/");'), $remote_paths);
        }
        foreach ($remote_paths as &$remote_path) {
            $remote_path = $rsync_login.$server_host.':'.$remote_path;
        }
        unset($remote_path);
        pake_sh(escapeshellarg(pake_which('rsync')).' -az -e '.escapeshellarg($transport).' '
                .implode(' ', array_map('escapeshellarg', $remote_paths)).' '
                .escapeshellarg($local_path)
        );
    }
        private static function throwIfPathDoesNotExists($path)
    {
        if (!file_exists($path)) {
            throw new pakeException('source path "'.$path.'" does not exist');
        }
    }
}
class pakeMySQL
{
    private $mode = null;
    private $db = null;
    private $more = null;
    public function __construct($login, $password, $host = 'localhost', $port = 3306)
    {
        if (extension_loaded('pdo_mysql')) {
            $this->mode = 'pdo';
            $this->db = new PDO('mysql:host='.$host.';port='.$port, $login, $password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } elseif (extension_loaded('mysqli')) {
            $this->mode = 'mysqli';
            $this->db = new mysqli($host, $login, $password, '', $port);
            if ($this->db->connect_error) {
                throw new pakeException('MySQLi Connect Error ('.$this->db->connect_errno.') '.$this->db->connect_error);
            }
        } elseif (extension_loaded('mysql')) {
            $this->mode = 'mysql';
            $this->db = mysqli_connect($host.':'.$port, $login, $password, true);
            if (false === $this->db) {
                throw new pakeException('MySQL Connect Error ('.mysqli::$errno().') '.mysqli::$error());
            }
        } else {
            $this->mode = 'cli';
            $this->db = pake_which('mysqladmin');             $this->more = array('login' => $login, 'password' => $password, 'host' => $host, 'port' => $port);
        }
    }
    public function __destruct()
    {
        if ($this->mode == 'mysql') {
            mysqli::close($this->db);
        }
    }
    public function createDatabase($name)
    {
        if ($this->mode == 'cli') {
            pake_sh($this->cliCommandPrefix().' create '.escapeshellarg($name));
        } else {
            $sql = 'CREATE DATABASE '.$name.' DEFAULT CHARACTER SET = utf8 DEFAULT COLLATE = utf8_general_ci';
            $this->sqlExec($sql);
        }
    }
    public function dropDatabase($name)
    {
        if ($this->mode == 'cli') {
            pake_sh($this->cliCommandPrefix().' drop '.escapeshellarg($name));
        } else {
            $sql = 'DROP DATABASE IF EXISTS '.$name;
            $this->sqlExec($sql);
        }
    }
    private function cliCommandPrefix()
    {
        return escapeshellarg($this->db)
                .' --force'
                .' '.escapeshellarg('--host='.$this->more['host'])
                .' '.escapeshellarg('--port='.$this->more['port'])
                .' '.escapeshellarg('--user='.$this->more['login'])
                .' '.escapeshellarg('--password='.$this->more['password']);
    }
    private function sqlExec($sql)
    {
        if ($this->mode == 'pdo') {
            $this->db->exec($sql);
            pake_echo_action('pdo_mysql', $sql);
        } elseif ($this->mode == 'mysqli') {
            $result = $this->db->real_query($sql);
            if (false === $result) {
                throw new pakeException('MySQLi Error ('.$this->db->errno.') '.$this->db->error);
            }
            pake_echo_action('mysqli', $sql);
        } elseif ($this->mode == 'mysql') {
            $result = mysqli::query($sql, $this->db);
            if (false === $result) {
                throw new pakeException('MySQL Error ('.mysqli::$errno().') '.mysqli::$error());
            }
            pake_echo_action('mysql', $sql);
        }
    }
}

class pakeMercurial
{
    private $repository_path;
    public function __construct($repository_path)
    {
        if (!self::isRepository($repository_path)) {
            throw new pakeException('"'.$repository_path.'" directory is not a Mercurial repository');
        }
        $this->repository_path = $repository_path;
    }
    public function getPath()
    {
        return $this->repository_path;
    }
    public function pull()
    {
        $this->hg_run('pull -u');
        return $this;
    }
    public function add($files = null)
    {
        if (null === $files) {
            $files = array('--all');
        } else {
            $files = pakeFinder::get_files_from_argument($files, $this->repository_path, true);
        }
        $this->hg_run('add '.implode(' ', array_map('escapeshellarg', $files)));
        return $this;
    }
    public function commit($message = '')
    {
        $this->hg_run('commit -m '.escapeshellarg($message));
        return $this;
    }
    public function switch_branch($branch_name)
    {
        $this->hg_run('update -C '.escapeshellarg($branch_name));
        return $this;
    }
        public static function isRepository($path)
    {
        return is_dir($path.'/.hg');
    }
    private function hg_run($command)
    {
        $cmd = escapeshellarg(pake_which('hg')).' -q';
        $cmd .= ' --cwd '.escapeshellarg($this->repository_path);
        $cmd .= ' '.$command;
        pake_sh($cmd);
    }
        public static function init($path)
    {
        pake_mkdirs($path);
        pake_sh(escapeshellarg(pake_which('hg')).' init -q '.escapeshellarg($path));
        return new pakeMercurial($path);
    }
    public static function clone_repository($src_url, $target_path = null)
    {
        if (null === $target_path) {
                        $target_path = basename($src_url);
                        if (substr($target_path, -3) === '.hg')
                $target_path = substr($target_path, 0, -3);
        }
        if (self::isRepository($target_path)) {
            throw new pakeException('"'.$target_path.'" directory is a Mercurial repository already');
        }
        if (file_exists($target_path)) {
            throw new pakeException('"'.$target_path.'" directory already exists. Can not clone Mercurial-repository there');
        }
        pake_mkdirs($target_path);
        pake_sh(escapeshellarg(pake_which('hg')).' clone -q '.escapeshellarg($src_url).' '.escapeshellarg($target_path));
        return new pakeMercurial($target_path);
    }
        public static function add_to_repo($repository_path, $files = null)
    {
        $repo = new pakeMercurial($repository_path);
        $repo->add($files);
        return $repo;
    }
    public static function commit_repo($repository_path, $message = '')
    {
        $repo = new pakeMercurial($repository_path);
        $repo->commit($message);
        return $repo;
    }
    public static function pull_repo($repository_path)
    {
        $repo = new pakeMercurial($repository_path);
        $repo->pull();
        return $repo;
    }
}

class pakeSubversion
{
    public static function isRepository($path)
    {
        return is_dir($path.'/.svn');
    }
    public static function checkout($src_url, $target_path)
    {
        pake_mkdirs($target_path);
        if (self::isRepository($target_path)) {
            throw new pakeException('"'.$target_path.'" directory is a Subversion repository already');
        }
        if (count(pakeFinder::type('any')->in($target_path)) > 0) {
            throw new pakeException('"'.$target_path.'" directory is not empty. Can not checkout there');
        }
        pake_echo_action('svn checkout', $target_path);
        if (extension_loaded('svn')) {
            $result = svn_checkout($src_url, $target_path);
            if (false === $result) {
                throw new pakeException('Couldn\'t checkout "'.$src_url.'" repository');
            }
        } else {
            pake_sh(escapeshellarg(pake_which('svn')).' checkout '.escapeshellarg($src_url).' '.escapeshellarg($target_path));
        }
    }
    public static function update($path)
    {
        if (!self::isRepository($path)) {
            throw new pakeException('"'.$path.'" directory is not a Subversion repository');
        }
        pake_echo_action('svn update', $path);
        if (extension_loaded('svn')) {
            $result = svn_update($path);
            if (false === $result) {
                throw new pakeException('Couldn\'t update "'.$path.'" repository');
            }
        } else {
            pake_sh(escapeshellarg(pake_which('svn')).' update '.escapeshellarg($path));
        }
    }
    public static function export($src_url, $target_path)
    {
        if (count(pakeFinder::type('any')->in($target_path)) > 0) {
            throw new pakeException('"'.$target_path.'" directory is not empty. Can not export there');
        }
        pake_echo_action('svn export', $target_path);
        if (extension_loaded('svn')) {
            $result = svn_export($src_url, $target_path, false);
            if (false === $result) {
                throw new pakeException('Couldn\'t export "'.$src_url.'" repository');
            }
        } else {
            pake_sh(escapeshellarg(pake_which('svn')).' export '.escapeshellarg($src_url).' '.escapeshellarg($target_path));
        }
    }
}


class pakeException extends Exception
{
    public static function strlen($string)
    {
        return function_exists('mb_strlen') ? mb_strlen($string) : strlen($string);
    }
    public static function render($e)
    {
        $isatty = pakeApp::isTTY();
        $title = '  ['.get_class($e).']  ';
        $len = self::strlen($title);
        $lines = array();
        foreach (explode("\n", $e->getMessage()) as $line) {
            if ($isatty) {
                $pieces = explode("\n", wordwrap($line, pakeApp::screenWidth() - 4, "\n", true));
            } else {
                $pieces = array($line);
            }
            foreach ($pieces as $piece) {
                $lines[] = '  '.$piece.'  ';
                $len = max(self::strlen($piece) + 4, $len);
            }
        }
        if ($isatty) {
            $messages = array(
                str_repeat(' ', $len),
                $title.str_repeat(' ', $len - self::strlen($title)),
            );
        } else {
            $messages = array('', $title);
        }
        foreach ($lines as $line) {
            if ($isatty) {
                $messages[] = $line.str_repeat(' ', $len - self::strlen($line));
            } else {
                $messages[] = $line;
            }
        }
        if ($isatty) {
            $messages[] = str_repeat(' ', $len);
        } else {
            $messages[] = '';
        }
        fwrite(STDERR, "\n");
        foreach ($messages as $message) {
            fwrite(STDERR, pakeColor::colorize($message, 'ERROR', STDERR)."\n");
        }
        fwrite(STDERR, "\n");
        $pake = pakeApp::get_instance();
        if ($pake->get_trace()) {
            fwrite(STDERR, "exception trace:\n");
            $trace = self::trace($e);
            for ($i = 0, $count = count($trace); $i < $count; $i++) {
                $class = (isset($trace[$i]['class']) ? $trace[$i]['class'] : '');
                $type = (isset($trace[$i]['type']) ? $trace[$i]['type'] : '');
                $function = $trace[$i]['function'];
                $file = isset($trace[$i]['file']) ? $trace[$i]['file'] : 'n/a';
                $line = isset($trace[$i]['line']) ? $trace[$i]['line'] : 'n/a';
                fwrite(STDERR, pake_sprintf(" %s%s%s at %s:%s\n", $class, $type, $function, pakeColor::colorize($file, 'INFO', STDERR), pakeColor::colorize($line, 'INFO', STDERR)));
            }
        }
        fwrite(STDERR, "\n");
    }
    public static function trace($exception)
    {
                $trace = $exception->getTrace();
        array_unshift($trace, array(
            'function' => '',
            'file'     => ($exception->getFile() != null) ? $exception->getFile() : 'n/a',
            'line'     => ($exception->getLine() != null) ? $exception->getLine() : 'n/a',
            'args'     => array(),
        ));
        return $trace;
    }
}

class pakeGit
{
    static $needs_work_tree_workaround = false;
    private $repository_path;
    public function __construct($repository_path)
    {
        if (!self::isRepository($repository_path)) {
            throw new pakeException('"'.$repository_path.'" directory is not a Git repository');
        }
        $this->repository_path = $repository_path;
    }
    public function getPath()
    {
        return $this->repository_path;
    }
    public function add($files = null)
    {
        if (null === $files) {
            $files = array('--all');
        } else {
            $files = pakeFinder::get_files_from_argument($files, $this->repository_path, true);
        }
        $this->git_run('add '.implode(' ', array_map('escapeshellarg', $files)));
        return $this;
    }
    public function commit($message = '', $all = false)
    {
        $this->git_run('commit -q -m '.escapeshellarg($message).($all ? ' -a' : ''));
        return $this;
    }
    public function checkout($branch)
    {
        $this->git_run('checkout -q -f '.escapeshellarg($branch));
        return $this;
    }
    public function pull($remote = null, $branch = null)
    {
        $cmd = 'pull -q';
        if (null !== $remote) {
            $cmd .= ' '.escapeshellarg($remote);
            if (null !== $branch) {
                $cmd .= ' '.escapeshellarg($branch);
            }
        }
        $this->git_run($cmd);
        return $this;
    }
        public static function isRepository($path)
    {
        return is_dir($path.'/.git');
    }
    private function git_run($command)
    {
        $git = escapeshellarg(pake_which('git'));
        if (self::$needs_work_tree_workaround === true) {
            $cmd = '(cd '.escapeshellarg($this->repository_path).' && '.$git.' '.$command.')';
        } else {
            $cmd = $git;
            $cmd .= ' '.escapeshellarg('--git-dir='.$this->repository_path.'/.git');
            $cmd .= ' '.escapeshellarg('--work-tree='.$this->repository_path);
            $cmd .= ' '.$command;
        }
        try {
            pake_sh($cmd);
        } catch (pakeException $e) {
            if (strpos($e->getMessage(), 'cannot be used without a working tree') !== false) {
                pake_echo_error('Your version of git is buggy. Using workaround');
                self::$needs_work_tree_workaround = true;
                $this->git_run($command);
            }
        }
    }
        public static function init($path, $template_path = null, $shared = false)
    {
        pake_mkdirs($path);
        if (false === $shared)
            $shared = 'false';
        elseif (true === $shared)
            $shared = 'true';
        elseif (is_int($shared))
            $shared = sprintf("%o", $shared);
        $cmd = escapeshellarg(pake_which('git')).' init -q';
        if (null !== $template_path) {
            $cmd .= ' '.escapeshellarg('--template='.$template_path);
        }
        $cmd .= ' '.escapeshellarg('--shared='.$shared);
        $cwd = getcwd();
        chdir($path);
        chdir('.');         pake_sh($cmd);
        chdir($cwd);
        return new pakeGit($path);
    }
    public static function clone_repository($src_url, $target_path = null)
    {
        if (null === $target_path) {
                        $target_path = basename($src_url);
                        if (substr($target_path, -4) === '.git')
                $target_path = substr($target_path, 0, -4);
        }
        if (self::isRepository($target_path)) {
            throw new pakeException('"'.$target_path.'" directory is a Git repository already');
        }
        if (file_exists($target_path)) {
            throw new pakeException('"'.$target_path.'" directory already exists. Can not clone git-repository there');
        }
        pake_sh(escapeshellarg(pake_which('git')).' clone -q '.escapeshellarg($src_url).' '.escapeshellarg($target_path));
        return new pakeGit($target_path);
    }
        public static function add_to_repo($repository_path, $files = null)
    {
        $repo = new pakeGit($repository_path);
        $repo->add($files);
        return $repo;
    }
    public static function commit_repo($repository_path, $message = '', $all = false)
    {
        $repo = new pakeGit($repository_path);
        $repo->commit($message, $all);
        return $repo;
    }
    public static function checkout_repo($repository_path, $branch)
    {
        $repo = new pakeGit($repository_path);
        $repo->checkout($branch);
        return $repo;
    }
    public static function pull_repo($repository_path)
    {
        $repo = new pakeGit($repository_path);
        $repo->pull();
        return $repo;
    }
}


class pakeFinder
{
    private $type = 'file';
    private $patterns = array();
    private $not_patterns = array();
    private $names = array();
    private $prunes = array();
    private $discards = array();
    private $execs = array();
    private $mindepth = 0;
    private $sizes = array();
    private $maxdepth = 1000000;
    private $relative = false;
    private $follow_link = false;
    private $search_dir = '';
    
    protected function __construct()
    {
    }
    
    public static function type($name)
    {
        $finder = new pakeFinder();
        if (strtolower(substr($name, 0, 3)) == 'dir') {
            $finder->type = 'directory';
        } else {
            if (strtolower($name) == 'any') {
                $finder->type = 'any';
            } else {
                $finder->type = 'file';
            }
        }
        return $finder;
    }
    
    public function maxdepth($level)
    {
        $this->maxdepth = $level;
        return $this;
    }
    
    public function mindepth($level)
    {
        $this->mindepth = $level;
        return $this;
    }
    public function get_type()
    {
        return $this->type;
    }
    
    private function to_regex($str)
    {
        if ($str[0] == '/' && $str[strlen($str) - 1] == '/') {
            return $str;
        } else {
            return pakeGlobToRegex::glob_to_regex($str);
        }
    }
    private function args_to_array($arg_list, $not = false)
    {
        $list = array();
        for ($i = 0; $i < count($arg_list); $i++) {
            if (is_array($arg_list[$i])) {
                foreach ($arg_list[$i] as $arg) {
                    $list[] = array($not, $this->to_regex($arg));
                }
            } else {
                $list[] = array($not, $this->to_regex($arg_list[$i]));
            }
        }
        return $list;
    }
    
    private function pattern_to_regex($pattern)
    {
        if (substr($pattern, -1) == '/') {
            $pattern .= '**';
        }
        $regex = '|^';
        foreach (explode('/', $pattern) as $i => $piece) {
            if ($i > 0) {
                $regex .= preg_quote('/', '|');
            }
            if ('**' == $piece) {
                $regex .= '.*';
            } else {
                $regex .= str_replace(array('?', '*'), array('[^/]', '[^/]*'), $piece);
            }
        }
        $regex .= '$|';
        return $regex;
    }
    
    public function pattern()
    {
        $patterns = func_get_args();
        foreach (func_get_args() as $pattern) {
            $this->patterns[] = $this->pattern_to_regex($pattern);
        }
        return $this;
    }
    
    public function not_pattern()
    {
        $patterns = func_get_args();
        foreach (func_get_args() as $pattern) {
            $this->not_patterns[] = $this->pattern_to_regex($pattern);
        }
        return $this;
    }
    
    public function name()
    {
        $args = func_get_args();
        $this->names = array_merge($this->names, $this->args_to_array($args));
        return $this;
    }
    
    public function not_name()
    {
        $args = func_get_args();
        $this->names = array_merge($this->names, $this->args_to_array($args, true));
        return $this;
    }
    
    public function size()
    {
        $args = func_get_args();
        for ($i = 0; $i < count($args); $i++) {
            $this->sizes[] = new pakeNumberCompare($args[$i]);
        }
        return $this;
    }
    
    public function prune()
    {
        $args = func_get_args();
        $this->prunes = array_merge($this->prunes, $this->args_to_array($args));
        return $this;
    }
    
    public function discard()
    {
        $args = func_get_args();
        $this->discards = array_merge($this->discards, $this->args_to_array($args));
        return $this;
    }
    
    public function ignore_version_control()
    {
        $ignores = array('.svn', 'CVS', '_darcs', '.arch-params', '.monotone', '.bzr', '.git', '.hg');
        return $this->discard($ignores)->prune($ignores);
    }
    
    public function exec()
    {
        $args = func_get_args();
        for ($i = 0; $i < count($args); $i++) {
            if (is_array($args[$i]) && !method_exists($args[$i][0], $args[$i][1])) {
                throw new pakeException('Method ' . $args[$i][1] . ' does not exist for object ' . $args[$i][0]);
            } else {
                if (!is_array($args[$i]) && !function_exists($args[$i])) {
                    throw new pakeException('Function ' . $args[$i] . ' does not exist.');
                }
            }
            $this->execs[] = $args[$i];
        }
        return $this;
    }
    
    public function relative()
    {
        $this->relative = true;
        return $this;
    }
    
    public function follow_link()
    {
        $this->follow_link = true;
        return $this;
    }
    
    public function in()
    {
        $files = array();
        $here_dir = getcwd();
        $numargs = func_num_args();
        $arg_list = func_get_args();
                if ($numargs == 1 && is_array($arg_list[0])) {
            $arg_list = $arg_list[0];
            $numargs = count($arg_list);
        }
        $dirs = array();
        for ($i = 0; $i < $numargs; $i++) {
            if ($argDirs = glob($arg_list[$i])) {
                $dirs = array_merge($dirs, $argDirs);
            }
        }
        foreach ($dirs as $dir)
        {
            $real_dir = realpath($dir);
                        if (!self::isPathAbsolute($real_dir)) {
                $dir = $here_dir . DIRECTORY_SEPARATOR . $real_dir;
            } else {
                $dir = $real_dir;
            }
            if (!is_dir($real_dir)) {
                continue;
            }
            $this->search_dir = $dir;
            if ($this->relative) {
                $files = array_merge($files, str_replace($dir . DIRECTORY_SEPARATOR, '', $this->search_in($dir)));
            } else {
                $files = array_merge($files, $this->search_in($dir));
            }
        }
        return array_unique($files);
    }
    private function search_in($dir, $depth = 0)
    {
        if ($depth > $this->maxdepth) {
                        return array();
        }
        if (is_link($dir) && !$this->follow_link) {
                        return array();
        }
        if (!is_dir($dir)) {
                        return array();
        }
        $files = array();
                $current_dir = opendir($dir);
        while (false !== $entryname = readdir($current_dir)) {
            if ($entryname == '.' || $entryname == '..') {
                continue;
            }
            $current_entry = $dir.DIRECTORY_SEPARATOR.$entryname;
            if (is_link($current_entry) && !$this->follow_link) {
                                continue;
            }
            if (is_dir($current_entry)) {
                if (($this->type == 'directory' || $this->type == 'any')
                        && ($depth >= $this->mindepth)
                        && !$this->is_discarded($dir, $entryname)
                        && $this->matches_patterns($dir, $entryname)
                        && $this->matches_names($dir, $entryname)
                        && $this->exec_ok($dir, $entryname)
                ) {
                    $files[] = realpath($current_entry);
                }
                                if (!$this->is_pruned($dir, $entryname)) {
                    $files = array_merge($files, $this->search_in($current_entry, $depth + 1));
                }
            } else {
                if (($this->type == 'file' || $this->type == 'any')
                        && ($depth >= $this->mindepth)
                        && !$this->is_discarded($dir, $entryname)
                        && $this->matches_patterns($dir, $entryname)
                        && $this->matches_names($dir, $entryname)
                        && $this->size_is_ok($dir, $entryname)
                        && $this->exec_ok($dir, $entryname)
                ) {
                    $files[] = realpath($current_entry);
                }
            }
        }
        closedir($current_dir);
        return $files;
    }
    private function matches_patterns($dir, $entry)
    {
                $full_name = str_replace(DIRECTORY_SEPARATOR, '/', $dir.'/'.$entry);
                $full_name = substr($full_name, strlen($this->search_dir) + 1);
                foreach ($this->not_patterns as $regex) {
            if (preg_match($regex, $full_name) == 1) {
                return false;
            }
        }
                foreach ($this->patterns as $regex) {
            if (preg_match($regex, $full_name) == 1) {
                return true;
            }
        }
        if (count($this->patterns) > 0) {
            return false;
        } else {
            return true;
        }
    }
    private function matches_names($dir, $entry)
    {
        if (!count($this->names)) {
            return true;
        }
        $one_not_name_rule = false;
        $one_name_rule = false;
                foreach ($this->names as $args) {
            list($not, $regex) = $args;
            if ($not) {
                $one_not_name_rule = true;
                if (preg_match($regex, $entry)) {
                    return false;
                }
            }
        }
                foreach ($this->names as $args) {
            list($not, $regex) = $args;
            if (!$not) {
                $one_name_rule = true;
                if (preg_match($regex, $entry)) {
                    return true;
                }
            }
        }
                if ($one_name_rule) {
                        return false;
        } else {
            return true;
        }
    }
    private function size_is_ok($dir, $entry)
    {
        if (!count($this->sizes)) {
            return true;
        }
        if (!is_file($dir . DIRECTORY_SEPARATOR . $entry)) {
            return true;
        }
        $filesize = filesize($dir . DIRECTORY_SEPARATOR . $entry);
        foreach ($this->sizes as $number_compare) {
            if (!$number_compare->test($filesize)) {
                return false;
            }
        }
        return true;
    }
    private function is_pruned($dir, $entry)
    {
        if (!count($this->prunes)) {
            return false;
        }
        foreach ($this->prunes as $args) {
            $regex = $args[1];
            if (preg_match($regex, $entry)) {
                return true;
            }
        }
        return false;
    }
    private function is_discarded($dir, $entry)
    {
        if (!count($this->discards)) {
            return false;
        }
        foreach ($this->discards as $args) {
            $regex = $args[1];
            if (preg_match($regex, $entry)) {
                return true;
            }
        }
        return false;
    }
    private function exec_ok($dir, $entry)
    {
        if (!count($this->execs)) {
            return true;
        }
        foreach ($this->execs as $exec) {
            if (!call_user_func_array($exec, array($dir, $entry))) {
                return false;
            }
        }
        return true;
    }
    public static function isPathAbsolute($path)
    {
        if ($path{0} == '/' || $path{0} == '\\' ||
            (strlen($path) > 3 && ctype_alpha($path{0}) &&
             $path{1} == ':' &&
             ($path{2} == '\\' || $path{2} == '/')
            )
        ) {
            return true;
        }
        return false;
    }
    public static function get_files_from_argument($arg, $target_dir = '', $relative = false)
    {
        $files = array();
        if (is_array($arg)) {
            $files = $arg;
        } elseif (is_string($arg)) {
            $files[] = $arg;
        } elseif ($arg instanceof pakeFinder) {
            $files = $arg->in($target_dir);
        } else {
            throw new pakeException('Wrong argument type (must be a list, a string or a pakeFinder object).');
        }
        if ($relative and $target_dir) {
            $files = preg_replace('/^' . preg_quote(realpath($target_dir), '/') . '/', '', $files);
                        $files = array_map(create_function('$f', 'return 0 === strpos($f, DIRECTORY_SEPARATOR) ? substr($f, 1) : $f;'), $files);
        }
        return $files;
    }
}

class pakeSSH
{
    private $host;
    private $login;
    public function __construct($host, $login)
    {
        $this->host = $host;
        $this->login = $login;
    }
    public function copy_to_server($src, $remote_path)
    {
        if (is_string($src)) {
            $src = array($src);
        }
        array_walk($src, array('self', 'throwIfPathDoesNotExists'));
        pake_sh(escapeshellarg(pake_which('scp')).' -rC '
                .implode(' ', array_map('escapeshellarg', $src)).' '
                .escapeshellarg($this->login.'@'.$this->host.':'.$remote_path)
        );
    }
    public function copy_from_server($src, $local_path)
    {
        if (is_string($src)) {
            $src = array($src);
        }
        pake_mkdirs($local_path);
        foreach ($src as &$remote_path) {
            $remote_path = $this->login.'@'.$this->host.':'.$remote_path;
        }
        unset($remote_path);
        pake_sh(escapeshellarg(pake_which('scp')).' -rC '
                .implode(' ', array_map('escapeshellarg', $src)).' '
                .escapeshellarg($local_path)
        );
    }
    public function execute($command)
    {
        return pake_sh(escapeshellarg(pake_which('ssh')).' -C '.escapeshellarg($this->login.'@'.$this->host).' '.escapeshellarg($command));
    }
        private static function throwIfPathDoesNotExists($path)
    {
        if (!file_exists($path)) {
            throw new pakeException('source path "'.$path.'" does not exist');
        }
    }
}


class pakeNumberCompare
{
  private static $magnitudes = null;
  private $test = '';
  public function __construct($test)
  {
    if (null === self::$magnitudes) {
            self::$magnitudes = array(
         'k' =>           1000,
         'ki'=>           1024,
         'm' =>      1000*1000,
         'mi'=>      1024*1024,
         'g' => 1000*1000*1000,
         'gi'=> 1024*1024*1024,
      );
    }
    $this->test = $test;
  }
  public function test($number)
  {
    if (!preg_match('{^([<>]=?)?(.*?)([kmg]i?)?$}i', $this->test, $matches))
    {
      throw new pakeException('Don\'t understand "'.$this->test.'" as a test.');
    }
    $target = array_key_exists(2, $matches) ? $matches[2] : '';
    $magnitude = array_key_exists(3, $matches) ? $matches[3] : '';
    if ('' !== $magnitude) {
      $target *= self::$magnitudes[strtolower($magnitude)];
    }
    $comparison = array_key_exists(1, $matches) ? $matches[1] : '==';
    switch ($comparison)
    {
      case '==':
      case '':
        return ($number == $target);
      case '>':
        return ($number > $target);
      case '>=':
        return ($number >= $target);
      case '<':
        return ($number < $target);
      case '<=':
        return ($number <= $target);
    }
    return false;
  }
}


class pakeArchive
{
    public static function createArchive($arg, $origin_dir, $archive_file, $overwrite = false)
    {
        if (!extension_loaded('phar'))
            throw new pakeException(__CLASS__.' module requires "phar" extension');
        if (false === $overwrite and file_exists($archive_file))
            return true;
        if (self::endsWith($archive_file, '.tar.gz')) {
            $archive_file = substr($archive_file, 0, -3);
            $compress = Phar::GZ;
            $extension = '.tar.gz';
            if (!extension_loaded('zlib')) {
                throw new pakeException('GZip compression method is not available on this system (install "zlib" extension)');
            }
        } elseif (self::endsWith($archive_file, '.tgz')) {
            $archive_file = substr($archive_file, 0, -3).'tar';
            $compress = Phar::GZ;
            $extension = '.tgz';
            if (!extension_loaded('zlib')) {
                throw new pakeException('GZip compression method is not available on this system (install "zlib" extension)');
            }
        } elseif (self::endsWith($archive_file, '.tar.bz2')) {
            $archive_file = substr($archive_file, 0, -4);
            $compress = Phar::BZ2;
            $extension = '.tar.bz2';
            if (!extension_loaded('bz2')) {
                throw new pakeException('BZip2 compression method is not available on this system (install "bzip2" extension)');
            }
        } elseif (self::endsWith($archive_file, '.tar') or self::endsWith($archive_file, '.zip')) {
            $compress = Phar::NONE;
        } else {
            throw new pakeException("Only .zip, .tar, .tar.gz and .tar.bz2 archives are supported");
        }
        $files = pakeFinder::get_files_from_argument($arg, $origin_dir, true);
        pake_echo_action('file+', $archive_file);
        try {
            $arc = new PharData($archive_file);
            foreach ($files as $file) {
                $full_path = $origin_dir.'/'.$file;
                pake_echo_action('archive', '-> '.$file);
                if (is_dir($full_path))
                    $arc->addEmptyDir($file);
                else
                    $arc->addFile($full_path, $file);
            }
            if (Phar::NONE !== $compress) {
                $new_name = substr($archive_file, 0, -4).$extension;
                pake_echo_action('file+', $new_name);
                $arc->compress($compress, $extension);
                unset($arc);
                pake_remove($archive_file, '/');
            }
        } catch (PharException $e) {
            unset($arc);
            pake_remove($archive_file);
            throw $e;
        }
    }
    public static function createPharArchive($arg, $origin_dir, $archive_file, $stub = null, $web_stub = null, $overwrite = false)
    {
        if (!extension_loaded('phar'))
            throw new pakeException(__CLASS__.' module requires "phar" extension');
        if (false === $overwrite and file_exists($archive_file))
            return true;
        if (!self::endsWith($archive_file, '.phar')) {
            throw new pakeException("Archive must have .phar extension");
        }
        $files = pakeFinder::get_files_from_argument($arg, $origin_dir, true);
        pake_echo_action('file+', $archive_file);
        try {
            $arc = new Phar($archive_file);
            foreach ($files as $file) {
                $full_path = $origin_dir.'/'.$file;
                pake_echo_action('phar', '-> '.$file);
                if (is_dir($full_path))
                    $arc->addEmptyDir($file);
                else
                    $arc->addFile($full_path, $file);
            }
            if (null !== $stub) {
                pake_echo_action('phar', '[stub] '.$stub.(null === $web_stub ? '' : ', '.$web_stub));
                $arc->setStub($arc->createDefaultStub($stub, $web_stub));
            }
        } catch (PharException $e) {
            unset($arc);
            pake_remove($archive_file);
            throw $e;
        }
    }
    public static function extractArchive($archive_file, $target_dir, $overwrite = false, $files = null)
    {
        if (!extension_loaded('phar'))
            throw new pakeException(__CLASS__.' module requires "phar" extension');
        pake_mkdirs($target_dir);
        pake_echo_action('extract', $archive_file);
        $arc = new PharData($archive_file);
        $arc->extractTo($target_dir, $files, $overwrite);
    }
    private static function endsWith($string, $suffix)
    {
        $pos = strlen($string) - strlen($suffix);
        return (strpos($string, $suffix, $pos) == $pos);
    }
}


class pakeGlobToRegex
{
  private static $strict_leading_dot = true;
  private static $strict_wildcard_slash = true;
  public static function setStrictLeadingDot($boolean)
  {
    self::$strict_leading_dot = $boolean;
  }
  public static function setStrictWildcardSlash($boolean)
  {
    self::$strict_wildcard_slash = $boolean;
  }
  
  public static function glob_to_regex($glob)
  {
    $first_byte = true;
    $escaping = false;
    $in_curlies = 0;
    $regex = '';
    for ($i = 0; $i < strlen($glob); $i++)
    {
      $car = $glob[$i];
      if ($first_byte)
      {
        if (self::$strict_leading_dot && $car != '.')
        {
          $regex .= '(?=[^\.])';
        }
        $first_byte = false;
      }
      if ($car == '/')
      {
        $first_byte = true;
      }
      if ($car == '.' || $car == '(' || $car == ')' || $car == '|' || $car == '+' || $car == '^' || $car == '$')
      {
        $regex .= "\\$car";
      }
      else if ($car == '*')
      {
        $regex .= ($escaping ? "\\*" : (self::$strict_wildcard_slash ? "[^/]*" : ".*"));
      }
      else if ($car == '?')
      {
        $regex .= ($escaping ? "\\?" : (self::$strict_wildcard_slash ? "[^/]" : "."));
      }
      else if ($car == '{')
      {
        $regex .= ($escaping ? "\\{" : "(");
        if (!$escaping) ++$in_curlies;
      }
      else if ($car == '}' && $in_curlies)
      {
        $regex .= ($escaping ? "}" : ")");
        if (!$escaping) --$in_curlies;
      }
      else if ($car == ',' && $in_curlies)
      {
        $regex .= ($escaping ? "," : "|");
      }
      else if ($car == "\\")
      {
        if ($escaping)
        {
          $regex .= "\\\\";
          $escaping = false;
        }
        else
        {
          $escaping = true;
        }
        continue;
      }
      else
      {
        $regex .= $car;
        $escaping = false;
      }
      $escaping = false;
    }
    return "#^$regex$#";
  }
}

class pakeHttp
{
    
    public static function request($method, $url, $query_data = null, $body = null, array $headers = array(), array $options = array())
    {
        $method = strtoupper($method);
        $_options = array(
            'method' => $method,
            'user_agent' => 'pake '.pakeApp::VERSION,
            'ignore_errors' => true,
        );
        $parsed_url = parse_url($url);
        $proxy_var_name = null;
        if ($parsed_url['scheme'] == 'http') {
            $proxy_var_name = 'http_proxy';
        } elseif ($parsed_url['scheme'] == 'https') {
            if (isset($_SERVER['https_proxy'])) {
                $proxy_var_name = 'https_proxy';
            } elseif (isset($_SERVER['HTTPS_PROXY'])) {
                $proxy_var_name = 'HTTPS_PROXY';
            }
        }
        if (null !== $proxy_var_name) {
            if (isset($_SERVER[$proxy_var_name])) {
                $parsed_proxy_str = parse_url($_SERVER[$proxy_var_name]);
                if (is_array($parsed_proxy_str) and
                    $parsed_proxy_str['scheme'] == 'http' and
                    isset($parsed_proxy_str['host']) and
                    isset($parsed_proxy_str['port'])
                ) {
                    $_options['proxy'] = 'tcp://'.$parsed_proxy_str['host'].':'.$parsed_proxy_str['port'];
                    $_options['request_fulluri'] = true;
                    pake_echo_comment('(using proxy: '.$parsed_proxy_str['host'].':'.$parsed_proxy_str['port'].')');
                } else {
                    pake_echo_error('"'.$proxy_var_name.'" environment variable is set to the wrong value. expecting http://host:port');
                }
            }
        }
        if (null !== $body) {
            if (is_array($body)) {
                $body = http_build_query($body);
            }
            $_options['content'] = $body;
        }
        if (count($headers) > 0) {
            $_options['header'] = implode("\r\n", $headers)."\r\n";
        }
        $options = array_merge($_options, $options);
        if (null !== $query_data) {
            if (is_array($query_data)) {
                $query_data = http_build_query($query_data);
            }
            $url .= '?'.$query_data;
        }
        $context = stream_context_create(array('http' => $options));
        pake_echo_action('HTTP '.$method, $url);
        $stream = @fopen($url, 'r', false, $context);
        if (false === $stream) {
            $err = error_get_last();
            throw new pakeException('HTTP request failed: '.$err['message']);
        }
        $meta = stream_get_meta_data($stream);
        $response = stream_get_contents($stream);
        fclose($stream);
        $status = $meta['wrapper_data'][0];
        $code = substr($status, 9, 3);
        if ($status > 400)
            throw new pakeException('http request returned: '.$status);
        pake_echo_action('…', 'got '.strlen($response).' bytes');
        return $response;
    }
    
    public static function matchRequest($regexp, $method, $url, $query_data = null, $body = null, array $headers = array(), array $options = array())
    {
        $response = self::request($method, $url, $query_data, $body, $headers, $options);
        $result = preg_match($regexp, $response);
        if (false === $result) {
            throw new pakeException("There's some error with this regular expression: ".$regexp);
        }
        if (0 === $result) {
            throw new pakeException("HTTP Response didn't match against regular expression: ".$regexp);
        }
        pake_echo_comment('HTTP response matched against '.$regexp);
    }
        public static function get($url, $query_data = null, array $headers = array(), array $options = array())
    {
        return self::request('GET', $url, $query_data, null, $headers, $options);
    }
    public static function matchGet($regexp, $url, $query_data = null, array $headers = array(), array $options = array())
    {
        return self::matchRequest($regexp, 'GET', $url, $query_data, null, $headers, $options);
    }
    public static function post($url, $query_data = null, $body = null, array $headers = array(), array $options = array())
    {
        return self::request('POST', $url, $query_data, $body, $headers, $options);
    }
    public static function matchPost($regexp, $url, $query_data = null, $body = null, array $headers = array(), array $options = array())
    {
        return self::matchRequest($regexp, 'POST', $url, $query_data, $body, $headers, $options);
    }
}


class sfYamlInline
{
  const REGEX_QUOTED_STRING = '(?:"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\']*(?:\'\'[^\']*)*)\')';
  
  static public function load($value)
  {
    $value = trim($value);
    if (0 == strlen($value))
    {
      return '';
    }
    if (function_exists('mb_internal_encoding') && ((int) ini_get('mbstring.func_overload')) & 2)
    {
      $mbEncoding = mb_internal_encoding();
      mb_internal_encoding('ASCII');
    }
    switch ($value[0])
    {
      case '[':
        $result = self::parseSequence($value);
        break;
      case '{':
        $result = self::parseMapping($value);
        break;
      default:
        $result = self::parseScalar($value);
    }
    if (isset($mbEncoding))
    {
      mb_internal_encoding($mbEncoding);
    }
    return $result;
  }
  
  static public function dump($value)
  {
    if ('1.1' === sfYaml::getSpecVersion())
    {
      $trueValues = array('true', 'on', '+', 'yes', 'y');
      $falseValues = array('false', 'off', '-', 'no', 'n');
    }
    else
    {
      $trueValues = array('true');
      $falseValues = array('false');
    }
    switch (true)
    {
      case is_resource($value):
        throw new InvalidArgumentException('Unable to dump PHP resources in a YAML file.');
      case is_object($value):
        return '!!php/object:'.serialize($value);
      case is_array($value):
        return self::dumpArray($value);
      case null === $value:
        return 'null';
      case true === $value:
        return 'true';
      case false === $value:
        return 'false';
      case ctype_digit($value):
        return is_string($value) ? "'$value'" : (int) $value;
      case is_numeric($value):
        return is_infinite($value) ? str_ireplace('INF', '.Inf', strval($value)) : (is_string($value) ? "'$value'" : $value);
      case false !== strpos($value, "\n") || false !== strpos($value, "\r"):
        return sprintf('"%s"', str_replace(array('"', "\n", "\r"), array('\\"', '\n', '\r'), $value));
      case preg_match('/[ \s \' " \: \{ \} \[ \] , & \* \# \?] | \A[ - ? | < > = ! % @ ` ]/x', $value):
        return sprintf("'%s'", str_replace('\'', '\'\'', $value));
      case '' == $value:
        return "''";
      case preg_match(self::getTimestampRegex(), $value):
        return "'$value'";
      case in_array(strtolower($value), $trueValues):
        return "'$value'";
      case in_array(strtolower($value), $falseValues):
        return "'$value'";
      case in_array(strtolower($value), array('null', '~')):
        return "'$value'";
      default:
        return $value;
    }
  }
  
  static protected function dumpArray($value)
  {
        $keys = array_keys($value);
    if (
      (1 == count($keys) && '0' == $keys[0])
      ||
      (count($keys) > 1 && array_reduce($keys, create_function('$v,$w', 'return (integer) $v + $w;'), 0) == count($keys) * (count($keys) - 1) / 2))
    {
      $output = array();
      foreach ($value as $val)
      {
        $output[] = self::dump($val);
      }
      return sprintf('[%s]', implode(', ', $output));
    }
        $output = array();
    foreach ($value as $key => $val)
    {
      $output[] = sprintf('%s: %s', self::dump($key), self::dump($val));
    }
    return sprintf('{ %s }', implode(', ', $output));
  }
  
  static public function parseScalar($scalar, $delimiters = null, $stringDelimiters = array('"', "'"), &$i = 0, $evaluate = true)
  {
    if (in_array($scalar[$i], $stringDelimiters))
    {
            $output = self::parseQuotedScalar($scalar, $i);
    }
    else
    {
            if (!$delimiters)
      {
        $output = substr($scalar, $i);
        $i += strlen($output);
                if (false !== $strpos = strpos($output, ' #'))
        {
          $output = rtrim(substr($output, 0, $strpos));
        }
      }
      else if (preg_match('/^(.+?)('.implode('|', $delimiters).')/', substr($scalar, $i), $match))
      {
        $output = $match[1];
        $i += strlen($output);
      }
      else
      {
        throw new InvalidArgumentException(sprintf('Malformed inline YAML string (%s).', $scalar));
      }
      $output = $evaluate ? self::evaluateScalar($output) : $output;
    }
    return $output;
  }
  
  static protected function parseQuotedScalar($scalar, &$i)
  {
    if (!preg_match('/'.self::REGEX_QUOTED_STRING.'/Au', substr($scalar, $i), $match))
    {
      throw new InvalidArgumentException(sprintf('Malformed inline YAML string (%s).', substr($scalar, $i)));
    }
    $output = substr($match[0], 1, strlen($match[0]) - 2);
    if ('"' == $scalar[$i])
    {
            $output = str_replace(array('\\"', '\\n', '\\r'), array('"', "\n", "\r"), $output);
    }
    else
    {
            $output = str_replace('\'\'', '\'', $output);
    }
    $i += strlen($match[0]);
    return $output;
  }
  
  static protected function parseSequence($sequence, &$i = 0)
  {
    $output = array();
    $len = strlen($sequence);
    $i += 1;
        while ($i < $len)
    {
      switch ($sequence[$i])
      {
        case '[':
                    $output[] = self::parseSequence($sequence, $i);
          break;
        case '{':
                    $output[] = self::parseMapping($sequence, $i);
          break;
        case ']':
          return $output;
        case ',':
        case ' ':
          break;
        default:
          $isQuoted = in_array($sequence[$i], array('"', "'"));
          $value = self::parseScalar($sequence, array(',', ']'), array('"', "'"), $i);
          if (!$isQuoted && false !== strpos($value, ': '))
          {
                        try
            {
              $value = self::parseMapping('{'.$value.'}');
            }
            catch (InvalidArgumentException $e)
            {
                          }
          }
          $output[] = $value;
          --$i;
      }
      ++$i;
    }
    throw new InvalidArgumentException(sprintf('Malformed inline YAML string %s', $sequence));
  }
  
  static protected function parseMapping($mapping, &$i = 0)
  {
    $output = array();
    $len = strlen($mapping);
    $i += 1;
        while ($i < $len)
    {
      switch ($mapping[$i])
      {
        case ' ':
        case ',':
          ++$i;
          continue 2;
        case '}':
          return $output;
      }
            $key = self::parseScalar($mapping, array(':', ' '), array('"', "'"), $i, false);
            $done = false;
      while ($i < $len)
      {
        switch ($mapping[$i])
        {
          case '[':
                        $output[$key] = self::parseSequence($mapping, $i);
            $done = true;
            break;
          case '{':
                        $output[$key] = self::parseMapping($mapping, $i);
            $done = true;
            break;
          case ':':
          case ' ':
            break;
          default:
            $output[$key] = self::parseScalar($mapping, array(',', '}'), array('"', "'"), $i);
            $done = true;
            --$i;
        }
        ++$i;
        if ($done)
        {
          continue 2;
        }
      }
    }
    throw new InvalidArgumentException(sprintf('Malformed inline YAML string %s', $mapping));
  }
  
  static protected function evaluateScalar($scalar)
  {
    $scalar = trim($scalar);
    if ('1.1' === sfYaml::getSpecVersion())
    {
      $trueValues = array('true', 'on', '+', 'yes', 'y');
      $falseValues = array('false', 'off', '-', 'no', 'n');
    }
    else
    {
      $trueValues = array('true');
      $falseValues = array('false');
    }
    switch (true)
    {
      case 'null' == strtolower($scalar):
      case '' == $scalar:
      case '~' == $scalar:
        return null;
      case 0 === strpos($scalar, '!str'):
        return (string) substr($scalar, 5);
      case 0 === strpos($scalar, '! '):
        return intval(self::parseScalar(substr($scalar, 2)));
      case 0 === strpos($scalar, '!!php/object:'):
        return unserialize(substr($scalar, 13));
      case ctype_digit($scalar):
        $raw = $scalar;
        $cast = intval($scalar);
        return '0' == $scalar[0] ? octdec($scalar) : (((string) $raw == (string) $cast) ? $cast : $raw);
      case in_array(strtolower($scalar), $trueValues):
        return true;
      case in_array(strtolower($scalar), $falseValues):
        return false;
      case is_numeric($scalar):
        return '0x' == $scalar[0].$scalar[1] ? hexdec($scalar) : floatval($scalar);
      case 0 == strcasecmp($scalar, '.inf'):
      case 0 == strcasecmp($scalar, '.NaN'):
        return -log(0);
      case 0 == strcasecmp($scalar, '-.inf'):
        return log(0);
      case preg_match('/^(-|\+)?[0-9,]+(\.[0-9]+)?$/', $scalar):
        return floatval(str_replace(',', '', $scalar));
      case preg_match(self::getTimestampRegex(), $scalar):
        return strtotime($scalar);
      default:
        return (string) $scalar;
    }
  }
  static protected function getTimestampRegex()
  {
    return <<<EOF
    ~^
    (?P<year>[0-9][0-9][0-9][0-9])
    -(?P<month>[0-9][0-9]?)
    -(?P<day>[0-9][0-9]?)
    (?:(?:[Tt]|[ \t]+)
    (?P<hour>[0-9][0-9]?)
    :(?P<minute>[0-9][0-9])
    :(?P<second>[0-9][0-9])
    (?:\.(?P<fraction>[0-9]*))?
    (?:[ \t]*(?P<tz>Z|(?P<tz_sign>[-+])(?P<tz_hour>[0-9][0-9]?)
    (?::(?P<tz_minute>[0-9][0-9]))?))?)?
    $~x
EOF;
  }
}


class sfYamlDumper
{
  
  public function dump($input, $inline = 0, $indent = 0)
  {
    $output = '';
    $prefix = $indent ? str_repeat(' ', $indent) : '';
    if ($inline <= 0 || !is_array($input) || empty($input))
    {
      $output .= $prefix.sfYamlInline::dump($input);
    }
    else
    {
      $isAHash = array_keys($input) !== range(0, count($input) - 1);
      foreach ($input as $key => $value)
      {
        $willBeInlined = $inline - 1 <= 0 || !is_array($value) || empty($value);
        $output .= sprintf('%s%s%s%s',
          $prefix,
          $isAHash ? sfYamlInline::dump($key).':' : '-',
          $willBeInlined ? ' ' : "\n",
          $this->dump($value, $inline - 1, $willBeInlined ? 0 : $indent + 2)
        ).($willBeInlined ? "\n" : '');
      }
    }
    return $output;
  }
}

if (!defined('PREG_BAD_UTF8_OFFSET_ERROR'))
{
  define('PREG_BAD_UTF8_OFFSET_ERROR', 5);
}

class sfYamlParser
{
  protected
    $offset        = 0,
    $lines         = array(),
    $currentLineNb = -1,
    $currentLine   = '',
    $refs          = array();
  
  public function __construct($offset = 0)
  {
    $this->offset = $offset;
  }
  
  public function parse($value)
  {
    $this->currentLineNb = -1;
    $this->currentLine = '';
    $this->lines = explode("\n", $this->cleanup($value));
    if (function_exists('mb_internal_encoding') && ((int) ini_get('mbstring.func_overload')) & 2)
    {
      $mbEncoding = mb_internal_encoding();
      mb_internal_encoding('UTF-8');
    }
    $data = array();
    while ($this->moveToNextLine())
    {
      if ($this->isCurrentLineEmpty())
      {
        continue;
      }
            if (preg_match('#^\t+#', $this->currentLine))
      {
        throw new InvalidArgumentException(sprintf('A YAML file cannot contain tabs as indentation at line %d (%s).', $this->getRealCurrentLineNb() + 1, $this->currentLine));
      }
      $isRef = $isInPlace = $isProcessed = false;
      if (preg_match('#^\-((?P<leadspaces>\s+)(?P<value>.+?))?\s*$#u', $this->currentLine, $values))
      {
        if (isset($values['value']) && preg_match('#^&(?P<ref>[^ ]+) *(?P<value>.*)#u', $values['value'], $matches))
        {
          $isRef = $matches['ref'];
          $values['value'] = $matches['value'];
        }
                if (!isset($values['value']) || '' == trim($values['value'], ' ') || 0 === strpos(ltrim($values['value'], ' '), '#'))
        {
          $c = $this->getRealCurrentLineNb() + 1;
          $parser = new sfYamlParser($c);
          $parser->refs =& $this->refs;
          $data[] = $parser->parse($this->getNextEmbedBlock());
        }
        else
        {
          if (isset($values['leadspaces'])
            && ' ' == $values['leadspaces']
            && preg_match('#^(?P<key>'.sfYamlInline::REGEX_QUOTED_STRING.'|[^ \'"\{].*?) *\:(\s+(?P<value>.+?))?\s*$#u', $values['value'], $matches))
          {
                        $c = $this->getRealCurrentLineNb();
            $parser = new sfYamlParser($c);
            $parser->refs =& $this->refs;
            $block = $values['value'];
            if (!$this->isNextLineIndented())
            {
              $block .= "\n".$this->getNextEmbedBlock($this->getCurrentLineIndentation() + 2);
            }
            $data[] = $parser->parse($block);
          }
          else
          {
            $data[] = $this->parseValue($values['value']);
          }
        }
      }
      else if (preg_match('#^(?P<key>'.sfYamlInline::REGEX_QUOTED_STRING.'|[^ \'"].*?) *\:(\s+(?P<value>.+?))?\s*$#u', $this->currentLine, $values))
      {
        $key = sfYamlInline::parseScalar($values['key']);
        if ('<<' === $key)
        {
          if (isset($values['value']) && '*' === substr($values['value'], 0, 1))
          {
            $isInPlace = substr($values['value'], 1);
            if (!array_key_exists($isInPlace, $this->refs))
            {
              throw new InvalidArgumentException(sprintf('Reference "%s" does not exist at line %s (%s).', $isInPlace, $this->getRealCurrentLineNb() + 1, $this->currentLine));
            }
          }
          else
          {
            if (isset($values['value']) && $values['value'] !== '')
            {
              $value = $values['value'];
            }
            else
            {
              $value = $this->getNextEmbedBlock();
            }
            $c = $this->getRealCurrentLineNb() + 1;
            $parser = new sfYamlParser($c);
            $parser->refs =& $this->refs;
            $parsed = $parser->parse($value);
            $merged = array();
            if (!is_array($parsed))
            {
              throw new InvalidArgumentException(sprintf("YAML merge keys used with a scalar value instead of an array at line %s (%s)", $this->getRealCurrentLineNb() + 1, $this->currentLine));
            }
            else if (isset($parsed[0]))
            {
                            foreach (array_reverse($parsed) as $parsedItem)
              {
                if (!is_array($parsedItem))
                {
                  throw new InvalidArgumentException(sprintf("Merge items must be arrays at line %s (%s).", $this->getRealCurrentLineNb() + 1, $parsedItem));
                }
                $merged = array_merge($parsedItem, $merged);
              }
            }
            else
            {
                            $merged = array_merge($merge, $parsed);
            }
            $isProcessed = $merged;
          }
        }
        else if (isset($values['value']) && preg_match('#^&(?P<ref>[^ ]+) *(?P<value>.*)#u', $values['value'], $matches))
        {
          $isRef = $matches['ref'];
          $values['value'] = $matches['value'];
        }
        if ($isProcessed)
        {
                    $data = $isProcessed;
        }
                else if (!isset($values['value']) || '' == trim($values['value'], ' ') || 0 === strpos(ltrim($values['value'], ' '), '#'))
        {
                    if ($this->isNextLineIndented())
          {
            $data[$key] = null;
          }
          else
          {
            $c = $this->getRealCurrentLineNb() + 1;
            $parser = new sfYamlParser($c);
            $parser->refs =& $this->refs;
            $data[$key] = $parser->parse($this->getNextEmbedBlock());
          }
        }
        else
        {
          if ($isInPlace)
          {
            $data = $this->refs[$isInPlace];
          }
          else
          {
            $data[$key] = $this->parseValue($values['value']);
          }
        }
      }
      else
      {
                if (2 == count($this->lines) && empty($this->lines[1]))
        {
          $value = sfYamlInline::load($this->lines[0]);
          if (is_array($value))
          {
            $first = reset($value);
            if ('*' === substr($first, 0, 1))
            {
              $data = array();
              foreach ($value as $alias)
              {
                $data[] = $this->refs[substr($alias, 1)];
              }
              $value = $data;
            }
          }
          if (isset($mbEncoding))
          {
            mb_internal_encoding($mbEncoding);
          }
          return $value;
        }
        switch (preg_last_error())
        {
          case PREG_INTERNAL_ERROR:
            $error = 'Internal PCRE error on line';
            break;
          case PREG_BACKTRACK_LIMIT_ERROR:
            $error = 'pcre.backtrack_limit reached on line';
            break;
          case PREG_RECURSION_LIMIT_ERROR:
            $error = 'pcre.recursion_limit reached on line';
            break;
          case PREG_BAD_UTF8_ERROR:
            $error = 'Malformed UTF-8 data on line';
            break;
          case PREG_BAD_UTF8_OFFSET_ERROR:
            $error = 'Offset doesn\'t correspond to the begin of a valid UTF-8 code point on line';
            break;
          default:
            $error = 'Unable to parse line';
        }
        throw new InvalidArgumentException(sprintf('%s %d (%s).', $error, $this->getRealCurrentLineNb() + 1, $this->currentLine));
      }
      if ($isRef)
      {
        $this->refs[$isRef] = end($data);
      }
    }
    if (isset($mbEncoding))
    {
      mb_internal_encoding($mbEncoding);
    }
    return empty($data) ? null : $data;
  }
  
  protected function getRealCurrentLineNb()
  {
    return $this->currentLineNb + $this->offset;
  }
  
  protected function getCurrentLineIndentation()
  {
    return strlen($this->currentLine) - strlen(ltrim($this->currentLine, ' '));
  }
  
  protected function getNextEmbedBlock($indentation = null)
  {
    $this->moveToNextLine();
    if (null === $indentation)
    {
      $newIndent = $this->getCurrentLineIndentation();
      if (!$this->isCurrentLineEmpty() && 0 == $newIndent)
      {
        throw new InvalidArgumentException(sprintf('Indentation problem at line %d (%s)', $this->getRealCurrentLineNb() + 1, $this->currentLine));
      }
    }
    else
    {
      $newIndent = $indentation;
    }
    $data = array(substr($this->currentLine, $newIndent));
    while ($this->moveToNextLine())
    {
      if ($this->isCurrentLineEmpty())
      {
        if ($this->isCurrentLineBlank())
        {
          $data[] = substr($this->currentLine, $newIndent);
        }
        continue;
      }
      $indent = $this->getCurrentLineIndentation();
      if (preg_match('#^(?P<text> *)$#', $this->currentLine, $match))
      {
                $data[] = $match['text'];
      }
      else if ($indent >= $newIndent)
      {
        $data[] = substr($this->currentLine, $newIndent);
      }
      else if (0 == $indent)
      {
        $this->moveToPreviousLine();
        break;
      }
      else
      {
        throw new InvalidArgumentException(sprintf('Indentation problem at line %d (%s)', $this->getRealCurrentLineNb() + 1, $this->currentLine));
      }
    }
    return implode("\n", $data);
  }
  
  protected function moveToNextLine()
  {
    if ($this->currentLineNb >= count($this->lines) - 1)
    {
      return false;
    }
    $this->currentLine = $this->lines[++$this->currentLineNb];
    return true;
  }
  
  protected function moveToPreviousLine()
  {
    $this->currentLine = $this->lines[--$this->currentLineNb];
  }
  
  protected function parseValue($value)
  {
    if ('*' === substr($value, 0, 1))
    {
      if (false !== $pos = strpos($value, '#'))
      {
        $value = substr($value, 1, $pos - 2);
      }
      else
      {
        $value = substr($value, 1);
      }
      if (!array_key_exists($value, $this->refs))
      {
        throw new InvalidArgumentException(sprintf('Reference "%s" does not exist (%s).', $value, $this->currentLine));
      }
      return $this->refs[$value];
    }
    if (preg_match('/^(?P<separator>\||>)(?P<modifiers>\+|\-|\d+|\+\d+|\-\d+|\d+\+|\d+\-)?(?P<comments> +#.*)?$/', $value, $matches))
    {
      $modifiers = isset($matches['modifiers']) ? $matches['modifiers'] : '';
      return $this->parseFoldedScalar($matches['separator'], preg_replace('#\d+#', '', $modifiers), intval(abs($modifiers)));
    }
    else
    {
      return sfYamlInline::load($value);
    }
  }
  
  protected function parseFoldedScalar($separator, $indicator = '', $indentation = 0)
  {
    $separator = '|' == $separator ? "\n" : ' ';
    $text = '';
    $notEOF = $this->moveToNextLine();
    while ($notEOF && $this->isCurrentLineBlank())
    {
      $text .= "\n";
      $notEOF = $this->moveToNextLine();
    }
    if (!$notEOF)
    {
      return '';
    }
    if (!preg_match('#^(?P<indent>'.($indentation ? str_repeat(' ', $indentation) : ' +').')(?P<text>.*)$#u', $this->currentLine, $matches))
    {
      $this->moveToPreviousLine();
      return '';
    }
    $textIndent = $matches['indent'];
    $previousIndent = 0;
    $text .= $matches['text'].$separator;
    while ($this->currentLineNb + 1 < count($this->lines))
    {
      $this->moveToNextLine();
      if (preg_match('#^(?P<indent> {'.strlen($textIndent).',})(?P<text>.+)$#u', $this->currentLine, $matches))
      {
        if (' ' == $separator && $previousIndent != $matches['indent'])
        {
          $text = substr($text, 0, -1)."\n";
        }
        $previousIndent = $matches['indent'];
        $text .= str_repeat(' ', $diff = strlen($matches['indent']) - strlen($textIndent)).$matches['text'].($diff ? "\n" : $separator);
      }
      else if (preg_match('#^(?P<text> *)$#', $this->currentLine, $matches))
      {
        $text .= preg_replace('#^ {1,'.strlen($textIndent).'}#', '', $matches['text'])."\n";
      }
      else
      {
        $this->moveToPreviousLine();
        break;
      }
    }
    if (' ' == $separator)
    {
            $text = preg_replace('/ (\n*)$/', "\n$1", $text);
    }
    switch ($indicator)
    {
      case '':
        $text = preg_replace('#\n+$#s', "\n", $text);
        break;
      case '+':
        break;
      case '-':
        $text = preg_replace('#\n+$#s', '', $text);
        break;
    }
    return $text;
  }
  
  protected function isNextLineIndented()
  {
    $currentIndentation = $this->getCurrentLineIndentation();
    $notEOF = $this->moveToNextLine();
    while ($notEOF && $this->isCurrentLineEmpty())
    {
      $notEOF = $this->moveToNextLine();
    }
    if (false === $notEOF)
    {
      return false;
    }
    $ret = false;
    if ($this->getCurrentLineIndentation() <= $currentIndentation)
    {
      $ret = true;
    }
    $this->moveToPreviousLine();
    return $ret;
  }
  
  protected function isCurrentLineEmpty()
  {
    return $this->isCurrentLineBlank() || $this->isCurrentLineComment();
  }
  
  protected function isCurrentLineBlank()
  {
    return '' == trim($this->currentLine, ' ');
  }
  
  protected function isCurrentLineComment()
  {
        $ltrimmedLine = ltrim($this->currentLine, ' ');
    return $ltrimmedLine[0] === '#';
  }
  
  protected function cleanup($value)
  {
    $value = str_replace(array("\r\n", "\r"), "\n", $value);
    if (!preg_match("#\n$#", $value))
    {
      $value .= "\n";
    }
        $count = 0;
    $value = preg_replace('#^\%YAML[: ][\d\.]+.*\n#su', '', $value, -1, $count);
    $this->offset += $count;
        $trimmedValue = preg_replace('#^(\#.*?\n)+#s', '', $value, -1, $count);
    if ($count == 1)
    {
            $this->offset += substr_count($value, "\n") - substr_count($trimmedValue, "\n");
      $value = $trimmedValue;
    }
        $trimmedValue = preg_replace('#^\-\-\-.*?\n#s', '', $value, -1, $count);
    if ($count == 1)
    {
            $this->offset += substr_count($value, "\n") - substr_count($trimmedValue, "\n");
      $value = $trimmedValue;
            $value = preg_replace('#\.\.\.\s*$#s', '', $value);
    }
    return $value;
  }
}


class sfYaml
{
  static protected
    $spec = '1.2';
  
  static public function setSpecVersion($version)
  {
    if (!in_array($version, array('1.1', '1.2')))
    {
      throw new InvalidArgumentException(sprintf('Version %s of the YAML specifications is not supported', $version));
    }
    self::$spec = $version;
  }
  
  static public function getSpecVersion()
  {
    return self::$spec;
  }
  
  public static function load($input)
  {
    $file = '';
        if (strpos($input, "\n") === false && is_file($input))
    {
      $file = $input;
      ob_start();
      $retval = include($input);
      $content = ob_get_clean();
            $input = is_array($retval) ? $retval : $content;
    }
        if (is_array($input))
    {
      return $input;
    }
    $yaml = new sfYamlParser();
    try
    {
      $ret = $yaml->parse($input);
    }
    catch (Exception $e)
    {
      throw new InvalidArgumentException(sprintf('Unable to parse %s: %s', $file ? sprintf('file "%s"', $file) : 'string', $e->getMessage()));
    }
    return $ret;
  }
  
  public static function dump($array, $inline = 2)
  {
    $yaml = new sfYamlDumper();
    return $yaml->dump($array, $inline);
  }
}

function echoln($string)
{
  echo $string."\n";
}
if (getenv('PHP_CLASSPATH')) {
    set_include_path(dirname(__FILE__).'/../lib'.PATH_SEPARATOR.getenv('PHP_CLASSPATH').PATH_SEPARATOR.get_include_path());
} else {
    set_include_path(dirname(__FILE__).'/../lib'.PATH_SEPARATOR.get_include_path());
}
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $retval = pakeApp::get_instance()->run();
    if (false === $retval) {
        exit(1);
    }
}
