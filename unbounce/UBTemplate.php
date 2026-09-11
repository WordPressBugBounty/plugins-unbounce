<?php

class UBTemplate
{

 /*
  * Renders a PHP template with local variables.
  *
  * `$template` Path to a PHP template
  * `$vars` An array of local variables to render in the template
  *
  * For example:
  *
  * templates/hello.php:
  * <h1>Hello, <?php $name; ?>!</h1>
  *
  * echo UBTemplate::render('hello', array('name' => 'World'));
  *
  * will output:
  *
  * <h1>Hello, World!</h1>
  *
  */

    public static function render($template, $vars = array())
    {
        ob_start();
        try {
            extract($vars);
            include(UBTemplate::template_path($template));
        } catch (Throwable $e) {
            // Throwable, not Exception: an Error raised inside the template --
            // the most likely failure -- is not an Exception, so it used to
            // escape this handler entirely.
            //
            // Catching it also stops WordPress's fatal handler running, which
            // is what previously made a template failure visible: a critical
            // error page, an entry in the PHP error log, and a recovery mode
            // email. So the failure has to be surfaced deliberately here, in
            // both directions.
            ob_end_clean();

            // error_log() directly rather than UBLogger, which is a no-op
            // unless UB_ENABLE_LOCAL_LOGGING is defined in wp-config.php --
            // it is not in a customer install.
            error_log(UBLogger::format_log_entry('ERROR', "Error rendering template '$template': " . $e));

            // Visible to whoever is on the page, but without the throwable:
            // echoing it put a stack trace, including filesystem paths, into
            // the admin page. Partial markup is dropped rather than returned
            // half-rendered.
            return '<div class="notice notice-error"><p>'
                . 'The Unbounce Pages plugin could not render this section.'
                . ' Details have been written to the PHP error log.'
                . '</p></div>';
        }

        return ob_get_clean();
    }

    public static function template_path($template)
    {
        return UBTemplate::join_paths(dirname(__FILE__), 'templates', $template . '.php');
    }

    private static function join_paths()
    {
        return preg_replace('~[/\\\]+~', DIRECTORY_SEPARATOR, implode(DIRECTORY_SEPARATOR, func_get_args()));
    }
}
