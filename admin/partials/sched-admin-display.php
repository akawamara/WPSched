<div class="wrap">
    <h1><?php esc_html_e('Sched Conference Plugin Settings', 'sched-conference-plugin'); ?></h1>
    
    <div class="notice notice-info">
        <p>
            <strong><?php esc_html_e('Read-Only API Key Support:', 'sched-conference-plugin'); ?></strong>
            <?php esc_html_e('This plugin is fully optimized for read-only API keys and will automatically use the appropriate endpoints for your access level.', 'sched-conference-plugin'); ?>
        </p>
    </div>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('sched_options_group');
        do_settings_sections('sched-conference-plugin');
        $api_key = get_option('sched_api_key');
        $conference_url = get_option('sched_conference_url');
        $pagination_number = get_option('sched_pagination_number');
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php esc_html_e('API Key', 'sched-conference-plugin'); ?></th>
                <td>
                    <input type="text" name="sched_api_key" value="<?php echo esc_attr($api_key); ?>" />
                    <p class="description">
                        <?php esc_html_e('Enter your Sched.com API key. Read-only API keys are fully supported.', 'sched-conference-plugin'); ?>
                    </p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Conference URL', 'sched-conference-plugin'); ?></th>
                <td>
                    <input type="text" name="sched_conference_url" value="<?php echo esc_attr($conference_url); ?>" />
                    <p class="description">
                        <?php esc_html_e('Full URL to your Sched conference (e.g., https://yourconference.sched.com)', 'sched-conference-plugin'); ?>
                    </p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Pagination Number', 'sched-conference-plugin'); ?></th>
                <td>
                    <input type="number" name="sched_pagination_number" value="<?php echo esc_attr($pagination_number); ?>" min="1" />
                    <p class="description">
                        <?php esc_html_e('Number of items to display per page in session and speaker listings.', 'sched-conference-plugin'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>