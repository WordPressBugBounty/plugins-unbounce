<?php

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class UBPageTable extends WP_List_Table
{

    private $item_scroll_threshold = 10;

    public function __construct($page_urls)
    {
        parent::__construct();

        $this->items = array_map(function ($url) {
            return array('url' => $url);
        }, $page_urls);

        $this->_column_headers = array(array('url' => 'Url'), array(), array());
    }

    /**
     * Core's WP_List_Table::get_columns() is a die() stub, and since 4.3
     * get_column_info() reaches it through get_primary_column_name() even when
     * _column_headers has been set by hand -- so a subclass has to define this.
     * Public because core registers it as a manage_{screen}_columns callback.
     */
    public function get_columns()
    {
        return array('url' => 'Url');
    }

    protected function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'url':
                return UBPageTable::url_link($item[$column_name]);
            default:
                return $item[$column_name];
        }
    }

    /**
    * Page URLs are built from the remote sitemap, so they are untrusted input
    * and must be escaped before being interpolated into the admin page. Neither
    * parse_url() nor the sitemap parser strips HTML metacharacters.
    */
    public static function url_link($url)
    {
        return sprintf(
            '<a href="%s" target="_blank">%s</a>',
            esc_url('//' . $url),
            esc_html($url)
        );
    }

    protected function display_tablenav($which)
    {
    }

    protected function get_table_classes()
    {
        $super = parent::get_table_classes();

        if (count($this->items) > $this->item_scroll_threshold) {
            $super[] = 'ub-table-scroll';
        }

        return $super;
    }

    public function no_items()
    {
        _e('None of your Unbounce pages have been published to WordPress.');
    }
}
