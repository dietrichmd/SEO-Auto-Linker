<?php
class SEO_Auto_Linker_Admin extends SEO_Auto_Linker_Base
{
    /*
     * Adds actions and such around the site
     */
    public static function init()
    {
        add_action('admin_init', [self::class, 'settings']);
        add_action('admin_menu', [self::class, 'menu_page']);
    }

    /*
     * Registers the setting and adds fields
     *
     * 0.7
     */
    public static function settings()
    {
        register_setting(
            self::SETTING,
            self::SETTING,
            [self::class, 'cleaner']
        );

        add_settings_section(
            'blacklist',
            __('Sitewide Blacklist', 'seoal'),
            [self::class, 'blacklist_section'],
            self::SETTING
        );

        add_settings_field(
            'seoal-blacklist-field',
            __('Blacklist', 'seoal'),
            [self::class, 'blacklist_field'],
            self::SETTING,
            'blacklist'
        );

        add_settings_section(
            'word_boundary',
            __('Word Boundaries', 'seoal'),
            [self::class, 'boundary_section'],
            self::SETTING
        );

        add_settings_field(
            'seoal-boundary-field',
            __('Use Alternative Word Boundaries?', 'seoal'),
            [self::class, 'boundary_field'],
            self::SETTING,
            'word_boundary'
        );
    }

    /*
     * Adds the menu page
     *
     * @uses add_submenu_page
     * @since 0.7
     */
    public static function menu_page()
    {
        add_submenu_page(
            'edit.php?post_type=' . self::POST_TYPE,
            __('SEO Auto Linker Options', 'seoal'),
            __('Options', 'seoal'),
            'manage_options',
            'seo-auto-linker',
            [self::class, 'menu_page_cb']
        );
    }

    /*
     * Settings sanitation callback
     *
     * @since 0.7
     */
    public static function cleaner($in)
    {
        $out = [];

        $blacklist = isset($in['blacklist']) && $in['blacklist'] ? $in['blacklist'] : '';
        $lines = preg_split('/\r\n|\r|\n/', $blacklist);
        $out['blacklist'] = array_map('esc_url', $lines);

        if ($blacklist_max = apply_filters('seoal_blacklist_max', false)) {
            $out['blacklist'] = array_slice($out['blacklist'], 0, (int) $blacklist_max);
        }

        $out['word_boundary'] = !empty($in['word_boundary']) ? 'on' : 'off';

        add_settings_error(
            self::SETTING,
            'seoal-success',
            __('Settings Saved', 'seoal'),
            'updated'
        );

        return $out;
    }

    /*
     * Menu page callback.  Handles outputing our options page
     */
    public static function menu_page_cb()
    {
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2><?php _e('SEO Auto Linker Options', 'seoal'); ?></h2>
            <?php settings_errors(self::SETTING); ?>
            <form method="post" action="<?php echo esc_url(admin_url('options.php')); ?>" style="width:80%">
                <?php
                settings_fields(self::SETTING);
                do_settings_sections(self::SETTING);
                submit_button(__('Save Settings'));
                ?>
            </form>
        </div>
        <?php
    }

    /********** Settings Section Callbacks **********/

    /*
     * Callback for the blacklist section
     *
     * @since 0.7
     */
    public static function blacklist_section()
    {
        echo '<p class="description">';
        _e('The URLs on your site where you do not want any automatic links to' .
            ' appear.  One URL per line.', 'seoal');
        echo '</p>';
    
