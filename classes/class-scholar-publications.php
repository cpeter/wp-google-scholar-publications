<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

/**
 * Scholar Publications
 *
 * All functionality pertaining to the Publications feature.
 *
 * @package WordPress
 * @subpackage Scholar_Publications
 * @category Plugin
 * @author Csaba Peter
 * @since 1.0.0
 */
class Scholar_Publications {
    private $dir;
    private $assets_dir;
    private $assets_url;
    private $token;
    public $version;
    private $file;

    /**
     * Constructor function.
     *
     * @access public
     * @since 1.0.0
     * @return void
     */
    public function __construct( $file ) {
        $this->dir = dirname( $file );
        $this->file = $file;
        $this->assets_dir = trailingslashit( $this->dir ) . 'assets';
        $this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $file ) ) );
        $this->token = 'publication';

        $this->load_plugin_textdomain();
        add_action( 'init', array( $this, 'load_localisation' ), 0 );

        // Run this on activation.
        register_activation_hook( $this->file, array( $this, 'activation' ) );

        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'init', array( $this, 'register_taxonomy' ) );
        add_action( 'wp_head', array( $this, 'write_metadata' ) );
        add_action( 'init', array( $this, 'support_jetpack_omnisearch' ) );
        add_filter( 'jetpack_relatedposts_filter_headline', array( $this, 'support_jetpack_relatedposts' )  );

        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'meta_box_setup' ), 20 );
            add_action( 'save_post', array( $this, 'meta_box_save' ) );
            add_filter( 'enter_title_here', array( $this, 'enter_title_here' ) );
            add_action( 'admin_print_styles', array( $this, 'enqueue_admin_styles' ), 10 );
            add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );
            //display contextual help for Publications
            add_action('contextual_help', array( $this, 'add_contextual_help' ), 10, 3);
        }

        add_action( 'after_setup_theme', array( $this, 'ensure_post_thumbnails_support' ) );
    } // End __construct()

    /**
     * Register the post type.
     *
     * @access public
     * @param string $token
     * @param string 'Publication'
     * @param string 'Publications'
     * @param array $supports
     * @return void
     */
    public function register_post_type () {
        $labels = array(
            'name' => _x( 'Publications', 'post type general name', 'scholar-publications' ),
            'singular_name' => _x( 'Publication', 'post type singular name', 'scholar-publications' ),
            'add_new' => _x( 'Add New', 'Publication', 'scholar-publications' ),
            'add_new_item' => sprintf( __( 'Add New %s', 'scholar-publications' ), __( 'Publication', 'scholar-publications' ) ),
            'edit_item' => sprintf( __( 'Edit %s', 'scholar-publications' ), __( 'Publication', 'scholar-publications' ) ),
            'new_item' => sprintf( __( 'New %s', 'scholar-publications' ), __( 'Publication', 'scholar-publications' ) ),
            'all_items' => sprintf( __( 'All %s', 'scholar-publications' ), __( 'Publications', 'scholar-publications' ) ),
            'view_item' => sprintf( __( 'View %s', 'scholar-publications' ), __( 'Publication', 'scholar-publications' ) ),
            'search_items' => sprintf( __( 'Search %a', 'scholar-publications' ), __( 'Publications', 'scholar-publications' ) ),
            'not_found' =>  sprintf( __( 'No %s Found', 'scholar-publications' ), __( 'Publications', 'scholar-publications' ) ),
            'not_found_in_trash' => sprintf( __( 'No %s Found In Trash', 'scholar-publications' ), __( 'Publications', 'scholar-publications' ) ),
            'parent_item_colon' => '',
            'menu_name' => __( 'Publications', 'scholar-publications' )

        );

        // @todo check these lines
        $single_slug = apply_filters( 'scholar_publications_single_slug', _x( 'publication', 'single post url slug', 'scholar-publications' ) );
        $archive_slug = apply_filters( 'scholar_publications_archive_slug', _x( 'publications', 'post archive url slug', 'scholar-publications' ) );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array( 'slug' => $single_slug, 'with_front' => false ),
            'capability_type' => 'post',
            'has_archive' => $archive_slug,
            'hierarchical' => false,
            'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
            'menu_position' => 5
        );
        register_post_type( $this->token, apply_filters( 'scholar_publications_post_type_args', $args ) );
    } // End register_post_type()

    /**
     * Register the "publication-category" taxonomy.
     * @access public
     * @since  1.3.0
     * @return void
     */
    public function register_taxonomy () {
        $this->taxonomy_category = new Scholar_Publications_Taxonomy(); // Leave arguments empty, to use the default arguments.
        $this->taxonomy_category->register();
    } // End register_taxonomy()

    /**
     * Update messages for the post type admin.
     * @since  1.0.0
     * @param  array $messages Array of messages for all post types.
     * @return array           Modified array.
     */
    public function updated_messages ( $messages ) {
        global $post, $post_ID;

        $messages[$this->token] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => sprintf( __( 'Publication updated. %sView Publication%s', 'scholar-publications' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
            2 => __( 'Custom field updated.', 'scholar-publications' ),
            3 => __( 'Custom field deleted.', 'scholar-publications' ),
            4 => __( 'Publication updated.', 'scholar-publications' ),
            /* translators: %s: date and time of the revision */
            5 => isset($_GET['revision']) ? sprintf( __( 'Publication restored to revision from %s', 'scholar-publications' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => sprintf( __( 'Publication published. %sView Publication%s', 'scholar-publications' ), '<a href="' . esc_url( get_permalink( $post_ID ) ) . '">', '</a>' ),
            7 => __('Publication saved.'),
            8 => sprintf( __( 'Publication submitted. %sPreview Publication%s', 'scholar-publications' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
            9 => sprintf( __( 'Publication scheduled for: %1$s. %2$sPreview Publication%3$s', 'scholar-publications' ),
                // translators: Publish box date format, see http://php.net/date
                '<strong>' . date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) . '</strong>', '<a target="_blank" href="' . esc_url( get_permalink($post_ID) ) . '">', '</a>' ),
            10 => sprintf( __( 'Publication draft updated. %sPreview Publication%s', 'scholar-publications' ), '<a target="_blank" href="' . esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) . '">', '</a>' ),
        );

        return $messages;
    } // End updated_messages()

    /**
     * Setup the meta box.
     *
     * @access public
     * @since  1.0.0
     * @return void
     */
    public function meta_box_setup () {
        add_meta_box( 'publication-data', __( 'Publication Details', 'scholar-publications' ), array( $this, 'meta_box_content' ), $this->token, 'normal', 'high' );
    } // End meta_box_setup()

    /**
     * The contents of our meta box.
     *
     * @access public
     * @since  1.0.0
     * @return void
     */
    public function meta_box_content () {
        global $post_id;
        $fields = get_post_custom( $post_id );
        $field_data = $this->get_custom_fields_settings();

        $html = '<input type="hidden" name="2pmc_' . $this->token . '_noonce" id="2pmc_' . $this->token . '_noonce" value="' . wp_create_nonce( plugin_basename( $this->dir ) ) . '" />';

        if ( 0 < count( $field_data ) ) {
            $html .= '<table class="form-table">' . "\n";
            $html .= '<tbody>' . "\n";

            foreach ( $field_data as $k => $v ) {
                $data = $v['default'];
                if ( isset( $fields['_' . $k] ) && isset( $fields['_' . $k][0] ) ) {
                    $data = $fields['_' . $k][0];
                }

                $html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />' . "\n";
                $html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
                $html .= '</td><tr/>' . "\n";
            }

            $html .= '</tbody>' . "\n";
            $html .= '</table>' . "\n";
        }

        echo $html;
    } // End meta_box_content()

    /**
     * Save meta box fields.
     *
     * @access public
     * @since  1.0.0
     * @param int $post_id
     * @return void
     */
    public function meta_box_save ( $post_id ) {
        global $post, $messages;

        // Verify
        if ( ( get_post_type() != $this->token ) || ! wp_verify_nonce( $_POST['2pmc_' . $this->token . '_noonce'], plugin_basename( $this->dir ) ) ) {
            return $post_id;
        }

        if ( 'page' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $post_id ) ) {
                return $post_id;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return $post_id;
            }
        }

        $field_data = $this->get_custom_fields_settings();
        $fields = array_keys( $field_data );

        foreach ( $fields as $f ) {

            ${$f} = strip_tags(trim($_POST[$f]));

            // Escape the URLs.
            if ( 'url' == $field_data[$f]['type'] ) {
                ${$f} = esc_url( ${$f} );
            }

            if ( get_post_meta( $post_id, '_' . $f ) == '' ) {
                add_post_meta( $post_id, '_' . $f, ${$f}, true );
            } elseif( ${$f} != get_post_meta( $post_id, '_' . $f, true ) ) {
                update_post_meta( $post_id, '_' . $f, ${$f} );
            } elseif ( ${$f} == '' ) {
                delete_post_meta( $post_id, '_' . $f, get_post_meta( $post_id, '_' . $f, true ) );
            }
        }
    } // End meta_box_save()

    /**
     * Customise the "Enter title here" text.
     *
     * @access public
     * @since  1.0.0
     * @param string $title
     * @return void
     */
    public function enter_title_here ( $title ) {
        if ( get_post_type() == $this->token ) {
            $title = __( 'Enter the publication\'s name here', 'scholar-publications' );
        }
        return $title;
    } // End enter_title_here()

    /**
     * Enqueue post type admin CSS.
     *
     * @access public
     * @since   1.0.0
     * @return   void
     */
    public function enqueue_admin_styles () {
        wp_register_style( 'scholar-publications-admin', $this->assets_url . '/css/admin.css', array(), '1.0.1' );
        wp_enqueue_style( 'scholar-publications-admin' );
    } // End enqueue_admin_styles()

    /**
     * Get the settings for the custom fields.
     * @since  1.0.0
     * @return array
     */
    public function get_custom_fields_settings () {
        $fields = array();

        // @todo add the category taxonomy (we can add publications to journals

        $fields['publisher'] = array(
            'name' => __( 'Publisher', 'scholar-publications' )
        );

        $fields['authors'] = array(
            'name' => __( 'Authors', 'scholar-publications' ),
            'description' => __( 'Semi colon separated authors list (for example: Liu, Li ; Rannels, Stephen R.).', 'scholar-publications' )
        );

        $fields['journal_title'] = array(
            'name' => __( 'Journal title', 'scholar-publications' ),
            'description' => __( 'Enter the journal title the publication appears in.', 'scholar-publications' )
        );

        $fields['date'] = array(
            'name' => __( 'Date', 'scholar-publications' ),
            'description' => __( 'The date of the publication.', 'scholar-publications' )
        );

        $fields['year'] = array(
            'name' => __( 'Year', 'scholar-publications' ),
            'description' => __( 'The year of the publication.', 'scholar-publications' )
        );

        $fields['volume'] = array(
            'name' => __( 'Volume', 'scholar-publications' ),
            'description' => __( 'Volume nubmber.', 'scholar-publications' )
        );

        $fields['issue'] = array(
            'name' => __( 'Issue', 'scholar-publications' ),
            'description' => __( 'Issue nubmber.', 'scholar-publications' )
        );

        $fields['firstpage'] = array(
            'name' => __( 'First page', 'scholar-publications' ),
            'description' => __( 'First page number the publication is located at.', 'scholar-publications' )
        );

        $fields['lastpage'] = array(
            'name' => __( 'Last page', 'scholar-publications' ),
            'description' => __( 'Last page number the publication is located at.', 'scholar-publications' )
        );

        $fields['issn'] = array(
            'name' => __( 'ISSN', 'scholar-publications' ),
            'description' => __( 'The ISSN of the publication/journal.', 'scholar-publications' )
        );

        $fields['isbn'] = array(
            'name' => __( 'ISBN', 'scholar-publications' ),
            'description' => __( 'The ISBN of the publication/journal.', 'scholar-publications' )
        );

        $fields['keywords'] = array(
            'name' => __( 'Keywords', 'scholar-publications' ),
            'description' => __( 'Comma separated list of keywords (for example: keyword1,keyword2).', 'scholar-publications' )
        );

        $fields['pdf_url'] = array(
            'name' => __( 'PDF URL', 'scholar-publications' ),
            'description' => __( 'Enter a URL to the PDF file that has the publication details (for example: http://example.com/publication.pdf).', 'scholar-publications' ),
            'type' => 'url'
        );

        $fields['pmid'] = array(
            'name' => __( 'PMID', 'scholar-publications' ),
        );

        foreach( $fields as $k => $v ) {
            if (empty($v['type'])) {
                $fields[$k]['type'] = 'text';
            }
            if (empty($v['section']) && !empty($v['description'])) {
                $fields[$k]['section'] = 'info';
            }
        }

        return $fields;
    } // End get_custom_fields_settings()

    /**
     * Return the contextual help
     * @param $contextual_help
     * @param string $screen_id
     * @param string $screen
     * @return string
     */
    public function add_contextual_help($contextual_help, $screen_id, $screen)
    {
        if ($this->token == $screen->id) {
            $contextual_help =
                '<p>' . __('Things to remember when adding or editing a publication:') . '</p>' .
                '<ul>' .
                '<li>' . __('Specify the category for the publication. This can be the Journal this article was published in.') . '</li>' .
                '<li>' . __('Specify the correct author of the publication. You can add multiple authors in a semi-colon separated list.') . '</li>' .
                '</ul>' .
                '<p>' . __('If you want to schedule the publication to be published in the future:') . '</p>' .
                '<ul>' .
                '<li>' . __('Under the Publish module, click on the Edit link next to Publish.') . '</li>' .
                '<li>' . __('Change the date to the date to actual publish this article, then click on Ok.') . '</li>' .
                '</ul>' .
                '<p><strong>' . __('For more information:') . '</strong></p>' .
                '<p>' . __('<a href="http://codex.wordpress.org/Posts_Edit_SubPanel" target="_blank">Edit Posts Documentation</a>') . '</p>' .
                '<p>' . __('<a href="http://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>';
        }
        return $contextual_help;
    }

    /**
     * Generate HTML metadata
     */
    public function write_metadata()
    {
        global $post;
        if (is_single() && get_post_type() == 'publication') {

            echo '<meta name="citation_title" content="' . esc_html( get_the_title() ) . '">' . "\n";
            echo '<meta name="citation_abstract" content="' . esc_html( get_the_excerpt() ) . '">' . "\n";
            echo '<meta name="citation_abstract_html_url" content="' . esc_url(  get_permalink() ). '">' . "\n";

            $field_data = $this->get_custom_fields_settings();
            $fields = array_keys( $field_data );

            foreach ( $fields as $f ) {
                $meta_data = get_post_meta($post->ID, '_' . $f, true);
                if (!empty($meta_data)) {
                    echo '<meta name="citation_' . $f . '" content="' . esc_html($meta_data) . '">' . "\n";

                    # create separate meta entry for each author. Authors are separated by semi-colon
                    # ie: Liu, Li ; Rannels, Stephen R.
                    if ( $f == 'authors' ) {
                        $authors = explode(';', $meta_data);
                        foreach($authors as $author) {
                            echo '<meta name="citation_'  . $f .  '" content="' . esc_html( trim($author) ) . '">' . "\n";
                        }
                    }
                }
            }
        }
    }

    /**
     * Get Publications.
     * @param  string/array $args Arguments to be passed to the query.
     * @since  1.0.0
     * @return array/boolean      Array if true, boolean if false.
     */
    public function get_publications ( $args = '' ) {
        $defaults = array(
            'limit' => 5,
            'orderby' => 'menu_order',
            'order' => 'DESC',
            'id' => 0,
            'category' => 0
        );

        $args = wp_parse_args( $args, $defaults );

        // Allow child themes/plugins to filter here.
        $args = apply_filters( '2pmc_get_publications_args', $args );

        // The Query Arguments.
        $query_args = array();
        $query_args['post_type'] = $this->token;
        $query_args['numberposts'] = $args['limit'];
        $query_args['orderby'] = $args['orderby'];
        $query_args['order'] = $args['order'];
        $query_args['suppress_filters'] = false;

        $ids = explode( ',', $args['id'] );

        if ( 0 < intval( $args['id'] ) && 0 < count( $ids ) ) {
            $ids = array_map( 'intval', $ids );
            if ( 1 == count( $ids ) && is_numeric( $ids[0] ) && ( 0 < intval( $ids[0] ) ) ) {
                $query_args['p'] = intval( $args['id'] );
            } else {
                $query_args['ignore_sticky_posts'] = 1;
                $query_args['post__in'] = $ids;
            }
        }

        // Whitelist checks.
        if ( ! in_array( $query_args['orderby'], array( 'none', 'ID', 'author', 'title', 'date', 'modified', 'parent', 'rand', 'comment_count', 'menu_order', 'meta_value', 'meta_value_num' ) ) ) {
            $query_args['orderby'] = 'date';
        }

        if ( ! in_array( $query_args['order'], array( 'ASC', 'DESC' ) ) ) {
            $query_args['order'] = 'DESC';
        }

        if ( ! in_array( $query_args['post_type'], get_post_types() ) ) {
            $query_args['post_type'] = $this->token;
        }

        $tax_field_type = '';

        // If the category ID is specified.
        if ( is_numeric( $args['category'] ) && 0 < intval( $args['category'] ) ) {
            $tax_field_type = 'id';
        }

        // If the category slug is specified.
        if ( ! is_numeric( $args['category'] ) && is_string( $args['category'] ) ) {
            $tax_field_type = 'slug';
        }

        // Setup the taxonomy query.
        if ( '' != $tax_field_type ) {
            $term = $args['category'];
            if ( is_string( $term ) ) { $term = esc_html( $term ); } else { $term = intval( $term ); }
            $query_args['tax_query'] = array( array( 'taxonomy' => 'publication-category', 'field' => $tax_field_type, 'terms' => array( $term ) ) );
        }

        // The Query.
        $query = get_posts( $query_args );

        // The Display.
        if ( ! is_wp_error( $query ) && is_array( $query ) && count( $query ) > 0 ) {
            foreach ( $query as $k => $v ) {
                $meta = get_post_custom( $v->ID );

                foreach ( (array)$this->get_custom_fields_settings() as $i => $j ) {
                    if ( isset( $meta['_' . $i] ) && ( '' != $meta['_' . $i][0] ) ) {
                        $query[$k]->$i = $meta['_' . $i][0];
                    } else {
                        $query[$k]->$i = $j['default'];
                    }
                }
            }
        } else {
            $query = false;
        }

        return $query;
    } // End get_publications()

    /**
     * Load the plugin's localisation file.
     * @access public
     * @since 1.0.0
     * @return void
     */
    public function load_localisation () {
        load_plugin_textdomain( 'scholar-publications', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
    } // End load_localisation()

    /**
     * Load the plugin textdomain from the main WordPress "languages" folder.
     * @since  1.0.0
     * @return  void
     */
    public function load_plugin_textdomain () {
        $domain = 'scholar-publications';
        // The "plugin_locale" filter is also used in load_plugin_textdomain()
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( $this->file ) ) . '/lang/' );
    } // End load_plugin_textdomain()

    /**
     * Run on activation.
     * @access public
     * @since 1.0.0
     * @return void
     */
    public function activation () {
        $this->register_plugin_version();
        $this->flush_rewrite_rules();
    } // End activation()

    /**
     * Register the plugin's version.
     * @access public
     * @since 1.0.0
     * @return void
     */
    private function register_plugin_version () {
        if ( $this->version != '' ) {
            update_option( 'scholar-publications' . '-version', $this->version );
        }
    } // End register_plugin_version()

    /**
     * Flush the rewrite rules
     * @access public
     * @since 1.4.0
     * @return void
     */
    private function flush_rewrite_rules () {
        $this->register_post_type();
        flush_rewrite_rules();
    } // End flush_rewrite_rules()

    /**
     * Ensure that "post-thumbnails" support is available for those themes that don't register it.
     * @since  1.0.1
     * @return  void
     */
    public function ensure_post_thumbnails_support () {
        if ( ! current_theme_supports( 'post-thumbnails' ) ) { add_theme_support( 'post-thumbnails' ); }
    } // End ensure_post_thumbnails_support()

    /**
     * Add Publication Support to Jetpack Omnisearch
     */
    public function support_jetpack_omnisearch() {
        if ( class_exists( 'Jetpack_Omnisearch_Posts' ) ) {
            new Jetpack_Omnisearch_Posts( 'publication' );
        }
    }

    /**
     * Better Jetpack Related Posts Support for Publications
     */
    function support_jetpack_relatedposts( $headline ) {
        if ( is_singular( 'publication' ) ) {
            $headline = sprintf(
                '<h3 class="jp-relatedposts-headline"><em>%s</em></h3>',
                esc_html( 'Similar Publications' )
            );
            return $headline;
        }
    }

} // End Class

