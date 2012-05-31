<?php
class SEO_Auto_Linker_Front extends SEO_Auto_Linker_Base
{
    /*
     * Container for our post autolinker posts
     */
    protected static $links;

    /*
     * Container for our options
     */
    protected static $opts;

    /*
     * Container for the current post's permalink
     */
    protected static $permalink;

    /*
     * Adds actions and filters and such
     *
     * @since 0.7
     */
    public static function init()
    {
        add_filter(
            'the_content',
            array(get_class(), 'content'),
            1
        );
    }

    /*
     * Main event.  Filters the conntent to add links
     *
     * @since 0.7
     */
    public static function content($content)
    {
        global $post;
        if(!self::allowed($post)) return $content;

        $header_replacements = array();
        $link_replacements = array();
        $other_replacements = array();
        $shortcode_replacements = array();
        $filtered = $content;

        preg_match_all('/<h[1-6][^>]*>.+?<\/h[1-6]>/iu', $filtered, $headers);
        if(!empty($headers[0]))
        {
            $header_replacements = self::gen_replacements($headers[0], 'header');
            $filtered = self::replace($header_replacements, $filtered);
        }

        preg_match_all('/' . get_shortcode_regex() . '/', $filtered, $scodes);
        if(!empty($scodes[0]))
        {
            $shortcode_replacements = self::gen_replacements($scodes[0], 'shortcode');
            $filtered = self::replace($shortcode_replacements, $filtered);
        }

        preg_match_all('/<(img|input)(.*?) \/?>/iu', $filtered, $others);
        if(!empty($others[0]))
        {
            $other_replacements = self::gen_replacements($others[0], 'others');
            $filtered = self::replace($other_replacements, $filtered);
        }

        foreach(self::$links as $l)
        {
            preg_match_all(
                '/<a(.*?)href="(.*?)"(.*?)>(.*?)<\/a>/iu',
                $filtered,
                $links
            );
            if(!empty($links[0]))
            {
                $start = count($link_replacements);
                $tmp = self::gen_replacements($links[0], 'links', $start);
                $filtered = self::replace($tmp, $filtered);
                $link_replacements = array_merge(
                    $link_replacements,
                    $tmp
                );
            }

            $regex = self::get_kw_regex($l);
            $url = self::get_link_url($l);
            $max = self::get_link_max($l);
            if(!$regex || !$url || !$max)
                continue;

            $filtered = preg_replace(
                $regex,
                '$1<a href="' . esc_url( $url ) . '" title="$2">$2</a>$3',
                $filtered,
                absint($max)
            );
        }

        $filtered = apply_filters('seoal_pre_replace', $filtered, $post);

        $filtered = self::replace_bak($link_replacements, $filtered);
        $filtered = self::replace_bak($header_replacements, $filtered);
        $filtered = self::replace_bak($shortcode_replacements, $filtered);
        $filtered = self::replace_bak($other_replacements, $filtered);
        
        return apply_filters('seoal_post_replace', $filtered, $post);
    }

    /*
     * Determins whether or not a post can be editted
     */
    protected static function allowed($post)
    {
        $rv = true;
        if(!is_singular() || !in_the_loop()) $rv = false;

        self::setup_links($post);
        if(!self::$links) $rv = false;

        if(in_array(self::$permalink, self::$opts['blacklist'])) $rv = false;

        return apply_filters('seoal_allowed', $rv, $post);
    }

    /*
     * Fetch all of the links posts
     *
     * @since 0.7
     */
    protected static function setup_links($post)
    {
        self::$opts = get_option(self::SETTING, array());
        if(!isset(self::$opts['blacklist'])) self::$opts['blacklist'] = array();
        self::$permalink = get_permalink($post);
        $links = get_posts(array(
            'post_type'   => self::POST_TYPE,
            'numberposts' => -1,
            'meta_query'  => array(
                'relation' => 'AND',
                array(
                    'key'     => self::get_key("type_{$post->post_type}"),
                    'value'   => 'on',
                    'compare' => '='
                ),
                array(
                    'key'     => self::get_key('url'),
                    'compare' => 'EXISTS' // doesn't do anything, just a reminder
                ),
                array(
                    'key'     => self::get_key('keywords'),
                    'compare' => 'EXISTS' // doesn't do anything, just a reminder
                )
            )
        ));
        $rv = array();
        if($links)
        {
            foreach($links as $l)
            {
                $blacklist = self::get_meta($l, 'blacklist');
                if(!$blacklist || !in_array(self::$permalink, (array)$blacklist))
                    $rv[] = $l;
            }
        }
        self::$links = apply_filters('seoal_links', $rv);
    }

    /*
     * Get the regex for a link
     *
     * @since 0.7
     */
    protected static function get_kw_regex($link)
    {
        $keywords = self::get_keywords($link);
        if(!$keywords) return false;
        return sprintf('/(\b)(%s)(\b)/ui', implode('|', $keywords));
    }

    /*
     * fetch the clean and sanitied keywords
     *
     * @since 0.7
     */
    protected static function get_keywords($link)
    {
        $keywords = self::get_meta($link, 'keywords');
        $kw_arr = explode(',', $keywords);
        $kw_arr = apply_filters('seoal_link_keywords', $kw_arr, $link);
        $kw_arr = array_map('trim', (array)$kw_arr);
        $kw_arr = array_map('preg_quote', $kw_arr);
        return $kw_arr;
    }

    /*
     * Get the link URL for a keyword
     *
     * @since 0.7
     */
    protected static function get_link_url($link)
    {
        $meta = self::get_meta($link, 'url');
        return apply_filters('seoal_link_url', $meta, $link);
    }

    /*
     * Get the maximum number of time a link can be replaced
     *
     * @since 0.7
     */
    protected static function get_link_max($link)
    {
        $meta = self::get_meta($link, 'times');
        $meta = absint($meta) ? absint($meta) : 1;
        return apply_filters('seoal_link_max', $meta, $link);
    }

    /*
     * Replace get meta
     *
     * @since 0.7
     */
    protected static function get_meta($post, $key)
    {
        $res = apply_filters('seoal_pre_get_meta', false, $key, $post);
        if($res !== false)
        {
            return $res;
        }
        if(isset($post->ID))
        {
            $res = get_post_meta($post->ID, self::get_key($key), true);
        }
        else
        {
            $res = '';
        }
        return $res;
    }

    /*
     * Loop through a an array of matches and create an associative array of 
     * key value pairs to use for str replacements
     *
     * @since 0.7
     */
    protected function gen_replacements($arr, $key, $start=0)
    {
        $rv = array();
        foreach($arr as $a)
        {
            $rv["<!--seo-auto-linker-{$key}-{$start}-->"] = $a;
            $start++;
        }
        return $rv;
    }

    /*
     * Wrapper around str_replace
     *
     * @since 0.7
     */
    protected static function replace($arr, $content)
    {
        return str_replace(
            array_values($arr),
            array_keys($arr),
            $content
        );
    }

    protected static function replace_bak($arr, $content)
    {
        return str_replace(
            array_keys($arr),
            array_values($arr),
            $content
        );
    }
}

SEO_Auto_Linker_Front::init();
