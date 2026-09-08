<?php

class UBUtil
{

    public static function array_select_by_key($input, $keep)
    {
        return array_intersect_key($input, array_flip($keep));
    }

    public static function array_fetch($array, $index, $default = null)
    {
        return isset($array[$index]) ? $array[$index] : $default;
    }

    public static function time_ago($timestamp)
    {
        $now = new DateTime('now');
        $from = new DateTime();
        $from->setTimestamp($timestamp);
        $diff = date_diff($now, $from);

        if ($diff->y > 0) {
            $message = $diff->y . ' year'. ($diff->y > 1 ? 's' : '');
        } elseif ($diff->m > 0) {
            $message = $diff->m . ' month'. ($diff->m > 1 ? 's' : '');
        } elseif ($diff->d > 0) {
            $message = $diff->d . ' day' . ($diff->d > 1 ? 's' : '');
        } elseif ($diff->h > 0) {
            $message = $diff->h . ' hour' . ($diff->h > 1 ? 's' : '');
        } elseif ($diff->i > 0) {
            $message = $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        } elseif ($diff->s > 0) {
            $message = $diff->s . ' second' . ($diff->s > 1? 's' : '');
        } else {
            $message = 'a moment';
        }

        return $message . ' ago';
    }

    public static function clear_flash()
    {
        foreach ($_COOKIE as $cookie_name => $value) {
            if (strpos($cookie_name, 'ub-flash-') === 0) {
                setcookie($cookie_name, '', time() - 60);
            }
        }
    }

    public static function get_flash($cookie_name, $default = null)
    {
        return UBUtil::array_fetch($_COOKIE, "ub-flash-{$cookie_name}", $default);
    }

    public static function set_flash($cookie_name, $value)
    {
        setcookie("ub-flash-{$cookie_name}", $value, time() + 60);
    }

    public static function get_lock()
    {
        global $wpdb;

        try {
            $lock = $wpdb->get_var('select coalesce(get_lock("' . UBConfig::UB_LOCK_NAME . '",0), 0);');

            return (bool) $lock;
        } catch (Exception $e) {
            // ensure backward compatibility on failure
            return true;
        }
    }

    public static function release_lock()
    {
        global $wpdb;

        try {
            $release = $wpdb->get_var('select coalesce(release_lock("' . UBConfig::UB_LOCK_NAME . '"), 0);');

            return (bool) $release;
        } catch (Exception $e) {
            // ensure backward compatibility on failure
            return true;
        }
    }

  /**
   * Checks if the current page is a preview page (from on GET parameters).
   *
   * This is needed because Wordpress's is_preview() is only true for pages that
   * are already published.
   *
   * This should return true when:
   *   - previewing posts
   *   - previewing pages
   *   - previewing drafts (of posts & pages)
   */
    public static function is_wordpress_preview($get_params)
    {
        return isset($get_params['preview'])
        && (isset($get_params['p']) || isset($get_params['page_id']) || isset($get_params['preview_id']));
    }

  /**
   * Guards an admin_post_* handler.
   *
   * wp-admin/admin-post.php dispatches admin_post_{action} for any logged in
   * user and enforces no capability of its own, so each handler has to check
   * for itself. Every one of ours changes plugin settings, which the admin
   * pages already gate behind 'manage_options'.
   *
   * Does not return when the request is rejected: both branches end in
   * wp_die(), matching how WordPress core handles a failed referer check.
   */
    public static function verify_admin_request($nonce_action)
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have sufficient permissions to manage Unbounce Landing Pages.',
                'Unbounce Landing Pages',
                array('response' => 403)
            );
        }

        check_admin_referer($nonce_action);
    }

    /**
    * Renders a list of values as an escaped, comma separated sentence of <code>
    * elements, e.g. "<code>a</code>, <code>b</code> and <code>c</code>".
    */
    public static function html_code_sentence_list($items)
    {
        $items = array_values(array_filter(array_filter((array) $items, 'is_string'), 'strlen'));

        $tagged = array_map(function ($item) {
            return '<code>' . esc_html($item) . '</code>';
        }, $items);

        if (count($tagged) === 0) {
            return 'none';
        }

        if (count($tagged) === 1) {
            return $tagged[0];
        }

        return implode(', ', array_slice($tagged, 0, -1)) . ' and ' . end($tagged);
    }
}
