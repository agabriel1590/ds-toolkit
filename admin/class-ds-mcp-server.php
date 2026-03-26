<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * DS Toolkit MCP Server
 *
 * Registers /wp-json/ds-toolkit/v1/mcp — a Model Context Protocol endpoint
 * (JSON-RPC 2.0, protocol 2024-11-05). Each tool group can be independently
 * enabled/disabled from the MCP tab in DS Toolkit settings.
 *
 * Auth: HTTP Basic Auth + WordPress Application Passwords (WP 5.6+)
 */
class DS_MCP_Server {

    public function init() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( 'ds-toolkit/v1', '/mcp', array(
            'methods'             => array( 'POST', 'GET' ),
            'callback'            => array( $this, 'handle_request' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );
    }

    public function check_permission( $request ) {
        if ( $request->get_method() === 'GET' ) {
            return true;
        }
        return is_user_logged_in() && current_user_can( 'edit_posts' );
    }

    public function handle_request( WP_REST_Request $request ) {
        if ( $request->get_method() === 'GET' ) {
            return new WP_REST_Response( array(
                'server'   => 'DS Toolkit MCP',
                'version'  => DS_TOOLKIT_VERSION,
                'protocol' => '2024-11-05',
                'endpoint' => rest_url( 'ds-toolkit/v1/mcp' ),
            ), 200 );
        }

        $body = $request->get_json_params();

        if ( ! is_array( $body ) || ! isset( $body['jsonrpc'] ) || $body['jsonrpc'] !== '2.0' ) {
            return $this->rpc_error( null, -32600, 'Invalid Request — expected JSON-RPC 2.0' );
        }

        $method = isset( $body['method'] ) ? $body['method'] : '';
        $id     = isset( $body['id'] ) ? $body['id'] : null;
        $params = isset( $body['params'] ) ? $body['params'] : array();

        switch ( $method ) {
            case 'initialize':
                return $this->handle_initialize( $id, $params );
            case 'notifications/initialized':
                return new WP_REST_Response( null, 204 );
            case 'tools/list':
                return $this->handle_tools_list( $id );
            case 'tools/call':
                return $this->handle_tools_call( $id, $params );
            case 'ping':
                return $this->rpc_result( $id, new stdClass() );
            default:
                return $this->rpc_error( $id, -32601, 'Method not found: ' . $method );
        }
    }

    // -------------------------------------------------------------------------
    // Group Access
    // -------------------------------------------------------------------------

    /**
     * Check if a tool group is enabled. Defaults to true if the key isn't set yet.
     */
    private function is_group_enabled( $key ) {
        static $settings = null;
        if ( $settings === null ) {
            $settings = get_option( 'ds_toolkit_settings', array() );
        }
        return ! isset( $settings[ $key ] ) || ! empty( $settings[ $key ] );
    }

    /**
     * Returns true only for users whose email matches DS_TOOLKIT_ADMIN_DOMAIN.
     * Required for all destructive / schema-level MCP tools.
     */
    private function is_leagueapps_user() {
        $user = wp_get_current_user();
        if ( ! $user || ! $user->exists() ) {
            return false;
        }
        $domain  = preg_quote( DS_TOOLKIT_ADMIN_DOMAIN, '/' );
        return (bool) preg_match( '/' . $domain . '$/i', $user->user_email );
    }

    /**
     * Gate helper for tools that require manage_options + DS_TOOLKIT_ADMIN_DOMAIN email.
     * Also enforces the group toggle. Returns an error response or null on pass.
     */
    private function leagueapps_gate( $id, $group_key ) {
        if ( ! $this->is_group_enabled( $group_key ) ) {
            return $this->group_disabled_error( $id, $group_key );
        }
        if ( ! current_user_can( 'manage_options' ) || ! $this->is_leagueapps_user() ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions — manage_options + ' . DS_TOOLKIT_ADMIN_DOMAIN . ' account required' );
        }
        return null;
    }

    /**
     * Returns the group key for a given post type slug.
     */
    private function group_for_post_type( $post_type ) {
        return in_array( $post_type, array( 'post', 'page' ), true )
            ? 'mcp_posts_pages_enabled'
            : 'mcp_cpt_enabled';
    }

    // -------------------------------------------------------------------------
    // MCP Methods
    // -------------------------------------------------------------------------

    private function handle_initialize( $id, $params ) {
        return $this->rpc_result( $id, array(
            'protocolVersion' => '2024-11-05',
            'capabilities'    => array( 'tools' => new stdClass() ),
            'serverInfo'      => array(
                'name'    => 'DS Toolkit',
                'version' => DS_TOOLKIT_VERSION,
            ),
            'instructions' => 'DS Toolkit MCP server for WordPress. Tools are grouped by type — Posts & Pages, Custom Post Types, Taxonomies, ACF Fields, and Toolkit Settings. Each group can be enabled/disabled in DS Toolkit > MCP tab.',
        ) );
    }

    private function handle_tools_list( $id ) {
        $all_tools = $this->get_tools_schema();
        $available = array_values( array_filter( $all_tools, array( $this, 'is_tool_available' ) ) );
        return $this->rpc_result( $id, array( 'tools' => $available ) );
    }

    /**
     * Check if a tool should appear in tools/list based on group settings.
     */
    private function is_tool_available( $tool ) {
        $name = $tool['name'];
        // Post & Pages + CPT tools — show if either group is enabled
        $post_tools = array( 'list_posts', 'get_post', 'create_post', 'update_post', 'delete_post' );
        if ( in_array( $name, $post_tools, true ) ) {
            return $this->is_group_enabled( 'mcp_posts_pages_enabled' )
                || $this->is_group_enabled( 'mcp_cpt_enabled' );
        }
        $map = array(
            'list_post_types'         => 'mcp_cpt_enabled',
            'list_taxonomies'         => 'mcp_taxonomies_enabled',
            'list_terms'              => 'mcp_taxonomies_enabled',
            'get_term'                => 'mcp_taxonomies_enabled',
            'create_term'             => 'mcp_taxonomies_enabled',
            'update_term'             => 'mcp_taxonomies_enabled',
            'delete_term'             => 'mcp_taxonomies_enabled',
            'set_post_terms'          => 'mcp_taxonomies_enabled',
            'get_post_fields'          => 'mcp_acf_enabled',
            'update_post_fields'       => 'mcp_acf_enabled',
            'get_partner_settings'     => 'mcp_acf_enabled',
            'update_partner_settings'  => 'mcp_acf_enabled',
            'get_toolkit_settings'    => 'mcp_toolkit_settings_enabled',
            'update_toolkit_settings' => 'mcp_toolkit_settings_enabled',
            'get_bb_global_colors'      => 'mcp_bb_enabled',
            'update_bb_global_colors'   => 'mcp_bb_enabled',
            'bb_list_layout_templates'  => 'mcp_bb_enabled',
            'bb_apply_layout_template'  => 'mcp_bb_enabled',
            'acf_list_post_types'     => 'mcp_acf_schema_enabled',
            'acf_create_post_type'    => 'mcp_acf_schema_enabled',
            'acf_update_post_type'    => 'mcp_acf_schema_enabled',
            'acf_delete_post_type'    => 'mcp_acf_schema_enabled',
            'acf_list_taxonomies'     => 'mcp_acf_schema_enabled',
            'acf_create_taxonomy'     => 'mcp_acf_schema_enabled',
            'acf_update_taxonomy'     => 'mcp_acf_schema_enabled',
            'acf_delete_taxonomy'     => 'mcp_acf_schema_enabled',
            'acf_list_field_groups'   => 'mcp_acf_schema_enabled',
            'acf_get_field_group'     => 'mcp_acf_schema_enabled',
            'acf_create_field_group'  => 'mcp_acf_schema_enabled',
            'acf_update_field_group'  => 'mcp_acf_schema_enabled',
            'acf_delete_field_group'  => 'mcp_acf_schema_enabled',
            'acf_list_options_pages'  => 'mcp_acf_schema_enabled',
            'acf_create_options_page' => 'mcp_acf_schema_enabled',
            'acf_delete_options_page' => 'mcp_acf_schema_enabled',
        );
        return isset( $map[ $name ] ) ? $this->is_group_enabled( $map[ $name ] ) : true;
    }

    private function handle_tools_call( $id, $params ) {
        $name      = isset( $params['name'] ) ? $params['name'] : '';
        $arguments = isset( $params['arguments'] ) ? $params['arguments'] : array();

        switch ( $name ) {
            // Posts & Pages / CPT
            case 'list_posts':          return $this->tool_list_posts( $id, $arguments );
            case 'get_post':            return $this->tool_get_post( $id, $arguments );
            case 'create_post':         return $this->tool_create_post( $id, $arguments );
            case 'update_post':         return $this->tool_update_post( $id, $arguments );
            case 'delete_post':         return $this->tool_delete_post( $id, $arguments );
            // CPT discovery
            case 'list_post_types':     return $this->tool_list_post_types( $id );
            // Taxonomies
            case 'list_taxonomies':     return $this->tool_list_taxonomies( $id );
            case 'list_terms':          return $this->tool_list_terms( $id, $arguments );
            case 'get_term':            return $this->tool_get_term( $id, $arguments );
            case 'create_term':         return $this->tool_create_term( $id, $arguments );
            case 'update_term':         return $this->tool_update_term( $id, $arguments );
            case 'delete_term':         return $this->tool_delete_term( $id, $arguments );
            case 'set_post_terms':      return $this->tool_set_post_terms( $id, $arguments );
            // ACF / Custom Fields
            case 'get_post_fields':          return $this->tool_get_post_fields( $id, $arguments );
            case 'update_post_fields':       return $this->tool_update_post_fields( $id, $arguments );
            // Partner Settings
            case 'get_partner_settings':     return $this->tool_get_partner_settings( $id );
            case 'update_partner_settings':  return $this->tool_update_partner_settings( $id, $arguments );
            // Toolkit Settings
            case 'get_toolkit_settings':    return $this->tool_get_toolkit_settings( $id );
            case 'update_toolkit_settings': return $this->tool_update_toolkit_settings( $id, $arguments );
            // Beaver Builder
            case 'get_bb_global_colors':        return $this->tool_get_bb_global_colors( $id );
            case 'update_bb_global_colors':     return $this->tool_update_bb_global_colors( $id, $arguments );
            case 'bb_list_layout_templates':    return $this->tool_bb_list_layout_templates( $id, $arguments );
            case 'bb_apply_layout_template':    return $this->tool_bb_apply_layout_template( $id, $arguments );
            // ACF Schema — Post Types
            case 'acf_list_post_types':   return $this->tool_acf_list_post_types( $id );
            case 'acf_create_post_type':  return $this->tool_acf_create_post_type( $id, $arguments );
            case 'acf_update_post_type':  return $this->tool_acf_update_post_type( $id, $arguments );
            case 'acf_delete_post_type':  return $this->tool_acf_delete_post_type( $id, $arguments );
            // ACF Schema — Taxonomies
            case 'acf_list_taxonomies':   return $this->tool_acf_list_taxonomies( $id );
            case 'acf_create_taxonomy':   return $this->tool_acf_create_taxonomy( $id, $arguments );
            case 'acf_update_taxonomy':   return $this->tool_acf_update_taxonomy( $id, $arguments );
            case 'acf_delete_taxonomy':   return $this->tool_acf_delete_taxonomy( $id, $arguments );
            // ACF Schema — Field Groups
            case 'acf_list_field_groups':   return $this->tool_acf_list_field_groups( $id );
            case 'acf_get_field_group':     return $this->tool_acf_get_field_group( $id, $arguments );
            case 'acf_create_field_group':  return $this->tool_acf_create_field_group( $id, $arguments );
            case 'acf_update_field_group':  return $this->tool_acf_update_field_group( $id, $arguments );
            case 'acf_delete_field_group':  return $this->tool_acf_delete_field_group( $id, $arguments );
            // ACF Schema — Options Pages
            case 'acf_list_options_pages':   return $this->tool_acf_list_options_pages( $id );
            case 'acf_create_options_page':  return $this->tool_acf_create_options_page( $id, $arguments );
            case 'acf_delete_options_page':  return $this->tool_acf_delete_options_page( $id, $arguments );
            default:
                return $this->rpc_error( $id, -32602, 'Unknown tool: ' . $name );
        }
    }

    // -------------------------------------------------------------------------
    // Posts & Pages / CPT Tools
    // -------------------------------------------------------------------------

    private function tool_list_posts( $id, $args ) {
        $post_type = ! empty( $args['post_type'] ) ? sanitize_key( $args['post_type'] ) : 'post';
        if ( ! $this->is_group_enabled( $this->group_for_post_type( $post_type ) ) ) {
            return $this->group_disabled_error( $id, $this->group_for_post_type( $post_type ) );
        }
        $query_args = array(
            'post_type'      => $post_type,
            'posts_per_page' => ! empty( $args['per_page'] ) ? min( (int) $args['per_page'], 100 ) : 20,
            'post_status'    => ! empty( $args['status'] ) ? sanitize_key( $args['status'] ) : 'any',
            'orderby'        => 'date',
            'order'          => 'DESC',
        );
        if ( ! empty( $args['search'] ) ) {
            $query_args['s'] = sanitize_text_field( $args['search'] );
        }
        $query = new WP_Query( $query_args );
        $posts = array();
        foreach ( $query->posts as $post ) {
            $posts[] = array(
                'id'       => $post->ID,
                'title'    => $post->post_title,
                'status'   => $post->post_status,
                'type'     => $post->post_type,
                'url'      => get_permalink( $post->ID ),
                'date'     => $post->post_date,
                'modified' => $post->post_modified,
            );
        }
        return $this->tool_result( $id, array( 'total' => $query->found_posts, 'posts' => $posts ) );
    }

    private function tool_get_post( $id, $args ) {
        if ( empty( $args['id'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: id' );
        }
        $post = get_post( (int) $args['id'] );
        if ( ! $post ) {
            return $this->rpc_error( $id, -32602, 'Post not found: ID ' . (int) $args['id'] );
        }
        if ( ! $this->is_group_enabled( $this->group_for_post_type( $post->post_type ) ) ) {
            return $this->group_disabled_error( $id, $this->group_for_post_type( $post->post_type ) );
        }
        $taxonomies  = get_object_taxonomies( $post->post_type );
        $terms_data  = array();
        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'all' ) );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                $terms_data[ $taxonomy ] = array_map( function( $t ) {
                    return array( 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug );
                }, $terms );
            }
        }
        return $this->tool_result( $id, array(
            'id'       => $post->ID,
            'title'    => $post->post_title,
            'content'  => $post->post_content,
            'excerpt'  => $post->post_excerpt,
            'status'   => $post->post_status,
            'type'     => $post->post_type,
            'url'      => get_permalink( $post->ID ),
            'date'     => $post->post_date,
            'modified' => $post->post_modified,
            'author'   => get_the_author_meta( 'display_name', $post->post_author ),
            'terms'    => $terms_data,
        ) );
    }

    private function tool_create_post( $id, $args ) {
        $post_type = ! empty( $args['post_type'] ) ? sanitize_key( $args['post_type'] ) : 'post';
        if ( ! $this->is_group_enabled( $this->group_for_post_type( $post_type ) ) ) {
            return $this->group_disabled_error( $id, $this->group_for_post_type( $post_type ) );
        }
        if ( ! current_user_can( 'publish_posts' ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions — publish_posts required' );
        }
        $post_data = array(
            'post_title'   => sanitize_text_field( isset( $args['title'] ) ? $args['title'] : 'Untitled' ),
            'post_content' => wp_kses_post( isset( $args['content'] ) ? $args['content'] : '' ),
            'post_status'  => sanitize_key( isset( $args['status'] ) ? $args['status'] : 'draft' ),
            'post_type'    => $post_type,
        );
        if ( ! empty( $args['excerpt'] ) ) {
            $post_data['post_excerpt'] = sanitize_textarea_field( $args['excerpt'] );
        }
        $post_id = wp_insert_post( $post_data, true );
        if ( is_wp_error( $post_id ) ) {
            return $this->rpc_error( $id, -32603, $post_id->get_error_message() );
        }
        $terms_assigned = array();
        if ( ! empty( $args['terms'] ) && is_array( $args['terms'] ) ) {
            foreach ( $args['terms'] as $taxonomy => $terms ) {
                $result = wp_set_object_terms( $post_id, $terms, sanitize_key( $taxonomy ) );
                if ( ! is_wp_error( $result ) ) {
                    $terms_assigned[] = $taxonomy;
                }
            }
        }
        return $this->tool_result( $id, array( 'id' => $post_id, 'url' => get_permalink( $post_id ), 'created' => true, 'terms_assigned' => $terms_assigned ) );
    }

    private function tool_update_post( $id, $args ) {
        if ( empty( $args['id'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: id' );
        }
        $post = get_post( (int) $args['id'] );
        if ( ! $post ) {
            return $this->rpc_error( $id, -32602, 'Post not found: ID ' . (int) $args['id'] );
        }
        if ( ! $this->is_group_enabled( $this->group_for_post_type( $post->post_type ) ) ) {
            return $this->group_disabled_error( $id, $this->group_for_post_type( $post->post_type ) );
        }
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions to edit post ' . $post->ID );
        }
        $post_data = array( 'ID' => $post->ID );
        if ( isset( $args['title'] ) )   $post_data['post_title']   = sanitize_text_field( $args['title'] );
        if ( isset( $args['content'] ) ) $post_data['post_content'] = wp_kses_post( $args['content'] );
        if ( isset( $args['excerpt'] ) ) $post_data['post_excerpt'] = sanitize_textarea_field( $args['excerpt'] );
        if ( isset( $args['status'] ) )  $post_data['post_status']  = sanitize_key( $args['status'] );
        $result = wp_update_post( $post_data, true );
        if ( is_wp_error( $result ) ) {
            return $this->rpc_error( $id, -32603, $result->get_error_message() );
        }
        $terms_assigned = array();
        if ( ! empty( $args['terms'] ) && is_array( $args['terms'] ) ) {
            foreach ( $args['terms'] as $taxonomy => $terms ) {
                $r = wp_set_object_terms( $post->ID, $terms, sanitize_key( $taxonomy ) );
                if ( ! is_wp_error( $r ) ) {
                    $terms_assigned[] = $taxonomy;
                }
            }
        }
        return $this->tool_result( $id, array( 'id' => $post->ID, 'updated' => true, 'url' => get_permalink( $post->ID ), 'terms_assigned' => $terms_assigned ) );
    }

    private function tool_delete_post( $id, $args ) {
        if ( empty( $args['id'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: id' );
        }
        $post = get_post( (int) $args['id'] );
        if ( ! $post ) {
            return $this->rpc_error( $id, -32602, 'Post not found: ID ' . (int) $args['id'] );
        }
        if ( ! $this->is_group_enabled( $this->group_for_post_type( $post->post_type ) ) ) {
            return $this->group_disabled_error( $id, $this->group_for_post_type( $post->post_type ) );
        }
        if ( ! current_user_can( 'delete_post', $post->ID ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions to delete post ' . $post->ID );
        }
        $force  = ! empty( $args['force'] );
        $result = wp_delete_post( $post->ID, $force );
        if ( ! $result ) {
            return $this->rpc_error( $id, -32603, 'Failed to delete post ' . $post->ID );
        }
        return $this->tool_result( $id, array( 'id' => $post->ID, 'deleted' => true, 'trashed' => ! $force ) );
    }

    // -------------------------------------------------------------------------
    // CPT Discovery Tools
    // -------------------------------------------------------------------------

    private function tool_list_post_types( $id ) {
        if ( ! $this->is_group_enabled( 'mcp_cpt_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_cpt_enabled' );
        }
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        $result = array();
        foreach ( $post_types as $pt ) {
            $result[] = array(
                'slug'         => $pt->name,
                'label'        => $pt->label,
                'singular'     => $pt->labels->singular_name,
                'hierarchical' => $pt->hierarchical,
                'supports'     => get_all_post_type_supports( $pt->name ),
                'taxonomies'   => get_object_taxonomies( $pt->name ),
            );
        }
        return $this->tool_result( $id, $result );
    }

    // -------------------------------------------------------------------------
    // Taxonomy Tools
    // -------------------------------------------------------------------------

    private function tool_list_taxonomies( $id ) {
        if ( ! $this->is_group_enabled( 'mcp_taxonomies_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_taxonomies_enabled' );
        }
        $taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
        $result = array();
        foreach ( $taxonomies as $tax ) {
            $result[] = array(
                'slug'        => $tax->name,
                'label'       => $tax->label,
                'singular'    => $tax->labels->singular_name,
                'hierarchical'=> $tax->hierarchical,
                'post_types'  => $tax->object_type,
            );
        }
        return $this->tool_result( $id, $result );
    }

    private function tool_list_terms( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_taxonomies_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_taxonomies_enabled' );
        }
        if ( empty( $args['taxonomy'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: taxonomy' );
        }
        $terms = get_terms( array(
            'taxonomy'   => sanitize_key( $args['taxonomy'] ),
            'hide_empty' => isset( $args['hide_empty'] ) ? (bool) $args['hide_empty'] : false,
            'number'     => ! empty( $args['per_page'] ) ? min( (int) $args['per_page'], 200 ) : 100,
            'search'     => ! empty( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '',
        ) );
        if ( is_wp_error( $terms ) ) {
            return $this->rpc_error( $id, -32602, $terms->get_error_message() );
        }
        $result = array();
        foreach ( $terms as $term ) {
            $result[] = array(
                'id'          => $term->term_id,
                'name'        => $term->name,
                'slug'        => $term->slug,
                'description' => $term->description,
                'count'       => $term->count,
                'parent'      => $term->parent,
            );
        }
        return $this->tool_result( $id, array( 'taxonomy' => $args['taxonomy'], 'terms' => $result ) );
    }

    private function tool_get_term( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_taxonomies_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_taxonomies_enabled' );
        }
        if ( empty( $args['taxonomy'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: taxonomy' );
        }
        $by   = ! empty( $args['slug'] ) ? 'slug' : 'id';
        $val  = ! empty( $args['slug'] ) ? sanitize_text_field( $args['slug'] ) : (int) $args['id'];
        $term = get_term_by( $by, $val, sanitize_key( $args['taxonomy'] ) );
        if ( ! $term ) {
            return $this->rpc_error( $id, -32602, 'Term not found' );
        }
        return $this->tool_result( $id, array(
            'id'          => $term->term_id,
            'name'        => $term->name,
            'slug'        => $term->slug,
            'description' => $term->description,
            'count'       => $term->count,
            'parent'      => $term->parent,
            'taxonomy'    => $term->taxonomy,
        ) );
    }

    private function tool_create_term( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_taxonomies_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_taxonomies_enabled' );
        }
        if ( empty( $args['taxonomy'] ) || empty( $args['name'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required arguments: taxonomy, name' );
        }
        if ( ! current_user_can( 'manage_categories' ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions — manage_categories required' );
        }
        $term_args = array();
        if ( ! empty( $args['slug'] ) )        $term_args['slug']        = sanitize_title( $args['slug'] );
        if ( ! empty( $args['description'] ) ) $term_args['description'] = sanitize_textarea_field( $args['description'] );
        if ( ! empty( $args['parent'] ) )      $term_args['parent']      = (int) $args['parent'];
        $result = wp_insert_term( sanitize_text_field( $args['name'] ), sanitize_key( $args['taxonomy'] ), $term_args );
        if ( is_wp_error( $result ) ) {
            return $this->rpc_error( $id, -32603, $result->get_error_message() );
        }
        return $this->tool_result( $id, array( 'id' => $result['term_id'], 'created' => true ) );
    }

    private function tool_update_term( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_taxonomies_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_taxonomies_enabled' );
        }
        if ( empty( $args['id'] ) || empty( $args['taxonomy'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required arguments: id, taxonomy' );
        }
        if ( ! current_user_can( 'manage_categories' ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions — manage_categories required' );
        }
        $term_args = array();
        if ( isset( $args['name'] ) )        $term_args['name']        = sanitize_text_field( $args['name'] );
        if ( isset( $args['slug'] ) )        $term_args['slug']        = sanitize_title( $args['slug'] );
        if ( isset( $args['description'] ) ) $term_args['description'] = sanitize_textarea_field( $args['description'] );
        $result = wp_update_term( (int) $args['id'], sanitize_key( $args['taxonomy'] ), $term_args );
        if ( is_wp_error( $result ) ) {
            return $this->rpc_error( $id, -32603, $result->get_error_message() );
        }
        return $this->tool_result( $id, array( 'id' => (int) $args['id'], 'updated' => true ) );
    }

    private function tool_delete_term( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_taxonomies_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_taxonomies_enabled' );
        }
        if ( empty( $args['id'] ) || empty( $args['taxonomy'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required arguments: id, taxonomy' );
        }
        if ( ! current_user_can( 'manage_categories' ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions — manage_categories required' );
        }
        $result = wp_delete_term( (int) $args['id'], sanitize_key( $args['taxonomy'] ) );
        if ( is_wp_error( $result ) || $result === false ) {
            return $this->rpc_error( $id, -32603, 'Failed to delete term' );
        }
        return $this->tool_result( $id, array( 'id' => (int) $args['id'], 'deleted' => true ) );
    }

    private function tool_set_post_terms( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_taxonomies_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_taxonomies_enabled' );
        }
        if ( empty( $args['id'] ) || empty( $args['taxonomy'] ) || ! isset( $args['terms'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required arguments: id, taxonomy, terms' );
        }
        $post = get_post( (int) $args['id'] );
        if ( ! $post ) {
            return $this->rpc_error( $id, -32602, 'Post not found: ID ' . (int) $args['id'] );
        }
        if ( ! current_user_can( 'edit_post', $post->ID ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions to edit post ' . $post->ID );
        }
        $taxonomy = sanitize_key( $args['taxonomy'] );
        $terms    = is_array( $args['terms'] ) ? $args['terms'] : array( $args['terms'] );
        $append   = ! empty( $args['append'] );
        $result   = wp_set_object_terms( $post->ID, $terms, $taxonomy, $append );
        if ( is_wp_error( $result ) ) {
            return $this->rpc_error( $id, -32603, $result->get_error_message() );
        }
        return $this->tool_result( $id, array(
            'post_id'  => $post->ID,
            'taxonomy' => $taxonomy,
            'term_ids' => $result,
            'append'   => $append,
            'updated'  => true,
        ) );
    }

    // -------------------------------------------------------------------------
    // ACF / Custom Fields Tools
    // -------------------------------------------------------------------------

    private function tool_get_post_fields( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_acf_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_acf_enabled' );
        }
        if ( empty( $args['id'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: id' );
        }
        $post_id = (int) $args['id'];
        $post    = get_post( $post_id );
        if ( ! $post ) {
            return $this->rpc_error( $id, -32602, 'Post not found: ID ' . $post_id );
        }
        if ( function_exists( 'get_fields' ) ) {
            $fields = get_fields( $post_id );
            $fields = $fields ?: array();
        } else {
            $meta   = get_post_meta( $post_id );
            $fields = array();
            foreach ( $meta as $key => $values ) {
                if ( substr( $key, 0, 1 ) === '_' ) continue; // skip internal WP meta
                $fields[ $key ] = count( $values ) === 1 ? $values[0] : $values;
            }
        }
        return $this->tool_result( $id, array(
            'post_id' => $post_id,
            'source'  => function_exists( 'get_fields' ) ? 'acf' : 'post_meta',
            'fields'  => $fields,
        ) );
    }

    private function tool_update_post_fields( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_acf_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_acf_enabled' );
        }
        if ( empty( $args['id'] ) || empty( $args['fields'] ) || ! is_array( $args['fields'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required arguments: id, fields (object)' );
        }
        $post_id = (int) $args['id'];
        $post    = get_post( $post_id );
        if ( ! $post ) {
            return $this->rpc_error( $id, -32602, 'Post not found: ID ' . $post_id );
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return $this->rpc_error( $id, -32603, 'Insufficient permissions to edit post ' . $post_id );
        }
        $updated = array();
        if ( function_exists( 'update_field' ) ) {
            foreach ( $args['fields'] as $key => $value ) {
                update_field( sanitize_key( $key ), $value, $post_id );
                $updated[] = $key;
            }
        } else {
            foreach ( $args['fields'] as $key => $value ) {
                update_post_meta( $post_id, sanitize_key( $key ), $value );
                $updated[] = $key;
            }
        }
        return $this->tool_result( $id, array(
            'post_id'      => $post_id,
            'updated_keys' => $updated,
            'source'       => function_exists( 'update_field' ) ? 'acf' : 'post_meta',
        ) );
    }

    // -------------------------------------------------------------------------
    // Partner Settings Tools
    // -------------------------------------------------------------------------

    /** All known partner settings fields and their sanitization type. */
    private function partner_fields_map() {
        return array(
            'partner_logo'        => 'image',
            'partner_email'       => 'email',
            'partner_phone'       => 'text',
            'partner_address'     => 'textarea',
            'partner_fb'          => 'url',
            'partner_instagram'   => 'url',
            'partner_x'           => 'url',
            'partner_youtube'     => 'url',
            'partner_linkedin'    => 'url',
            'partner_tiktok'      => 'url',
            'partner_leagueapps'  => 'url',
        );
    }

    private function tool_get_partner_settings( $id ) {
        if ( ! $this->is_group_enabled( 'mcp_acf_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_acf_enabled' );
        }
        if ( ! function_exists( 'get_field' ) ) {
            return $this->rpc_error( $id, -32603, 'ACF is required for partner settings.' );
        }

        $result = array();
        foreach ( $this->partner_fields_map() as $field => $type ) {
            $value = get_field( $field, 'option' );
            if ( $type === 'image' ) {
                // ACF image field returns an array or attachment ID depending on field settings
                if ( is_array( $value ) && isset( $value['url'] ) ) {
                    $result[ $field ] = array( 'id' => $value['ID'], 'url' => $value['url'] );
                } elseif ( is_numeric( $value ) && $value ) {
                    $result[ $field ] = array( 'id' => (int) $value, 'url' => wp_get_attachment_url( $value ) );
                } else {
                    $result[ $field ] = null;
                }
            } else {
                $result[ $field ] = $value;
            }
        }

        return $this->tool_result( $id, array( 'partner_settings' => $result ) );
    }

    private function tool_update_partner_settings( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_acf_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_acf_enabled' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return $this->rpc_error( $id, -32603, 'manage_options capability required.' );
        }
        if ( ! function_exists( 'update_field' ) ) {
            return $this->rpc_error( $id, -32603, 'ACF is required for partner settings.' );
        }
        if ( empty( $args['fields'] ) || ! is_array( $args['fields'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: fields (object of field_name => value)' );
        }

        $allowed  = $this->partner_fields_map();
        $updated  = array();
        $rejected = array();

        foreach ( $args['fields'] as $field => $value ) {
            if ( ! isset( $allowed[ $field ] ) ) {
                $rejected[] = $field;
                continue;
            }
            switch ( $allowed[ $field ] ) {
                case 'image':
                    $value = absint( $value ); // expect Media Library attachment ID
                    break;
                case 'email':
                    $value = sanitize_email( $value );
                    break;
                case 'url':
                    $value = esc_url_raw( $value );
                    break;
                case 'textarea':
                    $value = sanitize_textarea_field( $value );
                    break;
                default:
                    $value = sanitize_text_field( $value );
            }
            update_field( $field, $value, 'option' );
            $updated[] = $field;
        }

        return $this->tool_result( $id, array(
            'updated'  => $updated,
            'rejected' => $rejected,
            'note'     => empty( $rejected ) ? '' : 'Unrecognised fields were skipped. Use get_partner_settings to see valid field names.',
        ) );
    }

    // -------------------------------------------------------------------------
    // Toolkit Settings Tools
    // -------------------------------------------------------------------------

    private function tool_get_toolkit_settings( $id ) {
        $err = $this->leagueapps_gate( $id, 'mcp_toolkit_settings_enabled' );
        if ( $err ) return $err;
        $settings = get_option( 'ds_toolkit_settings', array() );
        $summary  = $settings;
        unset( $summary['global_css_content'], $summary['global_js_content'] );
        return $this->tool_result( $id, array(
            'settings' => $summary,
            'note'     => 'global_css_content and global_js_content omitted for brevity.',
        ) );
    }

    private function tool_update_toolkit_settings( $id, $args ) {
        $err = $this->leagueapps_gate( $id, 'mcp_toolkit_settings_enabled' );
        if ( $err ) return $err;
        if ( empty( $args['settings'] ) || ! is_array( $args['settings'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing or invalid argument: settings (must be an object)' );
        }
        $allowed = array_flip( array(
            'enable_login_branding', 'hide_fl_assistant', 'acf_css_vars_enabled',
            'acf_css_vars_mappings', 'getsubmenu_enabled', 'current_year_enabled',
            'forminator_email_partner_enabled', 'forminator_email_partner_fallback',
            'global_css_enabled', 'global_css_content', 'global_js_enabled', 'global_js_content',
            'child_pages_enabled', 'child_pages_template_id', 'child_pages_columns',
            'child_pages_columns_tablet', 'child_pages_columns_mobile',
        ) );
        $current  = get_option( 'ds_toolkit_settings', array() );
        $updated  = array();
        $rejected = array();
        foreach ( $args['settings'] as $key => $value ) {
            if ( isset( $allowed[ $key ] ) ) {
                $current[ $key ] = $value;
                $updated[]       = $key;
            } else {
                $rejected[] = $key;
            }
        }
        update_option( 'ds_toolkit_settings', $current );
        return $this->tool_result( $id, array( 'updated_keys' => $updated, 'rejected_keys' => $rejected ) );
    }

    // -------------------------------------------------------------------------
    // Beaver Builder Tools
    // -------------------------------------------------------------------------

    private function tool_get_bb_global_colors( $id ) {
        $err = $this->leagueapps_gate( $id, 'mcp_bb_enabled' );
        if ( $err ) return $err;
        $settings = get_option( '_fl_builder_styles' );
        if ( empty( $settings ) || empty( $settings->colors ) ) {
            return $this->tool_result( $id, array( 'colors' => new stdClass() ) );
        }
        $colors = array();
        foreach ( $settings->colors as $color ) {
            if ( ! empty( $color['label'] ) && isset( $color['color'] ) ) {
                $colors[ $color['label'] ] = $color['color'];
            }
        }
        return $this->tool_result( $id, array( 'colors' => $colors ) );
    }

    private function tool_update_bb_global_colors( $id, $args ) {
        $err = $this->leagueapps_gate( $id, 'mcp_bb_enabled' );
        if ( $err ) return $err;
        if ( empty( $args['colors'] ) || ! is_array( $args['colors'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: colors (object of label => hex)' );
        }
        if ( ! class_exists( 'FLBuilderModel' ) ) {
            return $this->rpc_error( $id, -32603, 'Beaver Builder is not active on this site' );
        }
        $settings = get_option( '_fl_builder_styles' );
        if ( empty( $settings ) || ! isset( $settings->colors ) ) {
            return $this->rpc_error( $id, -32603, 'No Beaver Builder global colors found. Set them up in BB > Global Styles first.' );
        }
        $updated  = array();
        $rejected = array();
        foreach ( $args['colors'] as $label => $hex ) {
            $matched = false;
            foreach ( $settings->colors as &$color ) {
                if ( isset( $color['label'] ) && $color['label'] === $label ) {
                    $color['color'] = sanitize_hex_color_no_hash( ltrim( $hex, '#' ) );
                    $updated[]      = $label;
                    $matched        = true;
                    break;
                }
            }
            unset( $color );
            if ( ! $matched ) {
                $rejected[] = $label;
            }
        }
        if ( ! empty( $updated ) ) {
            update_option( '_fl_builder_styles', $settings );
            FLBuilderModel::delete_asset_cache_for_all_posts();
        }
        return $this->tool_result( $id, array(
            'updated_colors' => $updated,
            'rejected_labels' => $rejected,
            'note' => empty( $rejected ) ? '' : 'Rejected labels did not match any existing color name. Use get_bb_global_colors to see available names.',
        ) );
    }

    // -------------------------------------------------------------------------
    // Beaver Builder — Layout Template Tools
    // -------------------------------------------------------------------------

    private function tool_bb_list_layout_templates( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_bb_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_bb_enabled' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return $this->rpc_error( $id, -32603, 'manage_options capability required.' );
        }

        $filter_type = ! empty( $args['type'] ) ? sanitize_key( $args['type'] ) : '';

        $templates = get_posts( array(
            'post_type'      => 'fl-builder-template',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ) );

        $patterns = array(
            'header' => '/^Header Style (\d+)$/i',
            'footer' => '/^Footer Style (\d+)$/i',
            'home'   => '/^Home Page Layout (\d+)$/i',
        );

        $result = array( 'header' => array(), 'footer' => array(), 'home' => array() );

        foreach ( $templates as $post ) {
            foreach ( $patterns as $type => $pattern ) {
                if ( preg_match( $pattern, $post->post_title, $m ) ) {
                    $result[ $type ][] = array(
                        'id'           => $post->ID,
                        'title'        => $post->post_title,
                        'style_number' => (int) $m[1],
                    );
                }
            }
        }

        if ( $filter_type && isset( $result[ $filter_type ] ) ) {
            return $this->tool_result( $id, array( 'type' => $filter_type, 'templates' => $result[ $filter_type ] ) );
        }

        return $this->tool_result( $id, array( 'templates' => $result ) );
    }

    private function tool_bb_apply_layout_template( $id, $args ) {
        if ( ! $this->is_group_enabled( 'mcp_bb_enabled' ) ) {
            return $this->group_disabled_error( $id, 'mcp_bb_enabled' );
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            return $this->rpc_error( $id, -32603, 'manage_options capability required.' );
        }
        if ( empty( $args['type'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: type (header, footer, or home)' );
        }
        if ( empty( $args['style_number'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: style_number' );
        }
        if ( empty( $args['confirm'] ) ) {
            return $this->rpc_error( $id, -32602, 'WARNING: This will permanently replace the current layout content. Pass confirm: true to proceed.' );
        }

        $type         = sanitize_key( $args['type'] );
        $style_number = absint( $args['style_number'] );

        $title_map = array(
            'header' => 'Header Style ' . $style_number,
            'footer' => 'Footer Style ' . $style_number,
            'home'   => 'Home Page Layout ' . $style_number,
        );

        if ( ! isset( $title_map[ $type ] ) ) {
            return $this->rpc_error( $id, -32602, 'Invalid type. Must be: header, footer, or home.' );
        }

        $template_title = $title_map[ $type ];

        // Find source template by exact title
        global $wpdb;
        $template_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'fl-builder-template' AND post_status = 'publish' LIMIT 1",
            $template_title
        ) );

        if ( ! $template_id ) {
            return $this->rpc_error( $id, -32602, 'Template not found: "' . $template_title . '". Use bb_list_layout_templates to see available templates.' );
        }

        // Find target layout/page
        $target_id          = 0;
        $target_description = '';

        if ( $type === 'header' || $type === 'footer' ) {
            $target_title = ( $type === 'header' ) ? 'Header Main' : 'Footer Main';
            $target_id    = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'fl-theme-layout' AND post_status != 'trash' LIMIT 1",
                $target_title
            ) );
            $target_description = '"' . $target_title . '" (BB Themer Layout)';
        } else {
            $target_id = (int) get_option( 'page_on_front' );
            if ( ! $target_id ) {
                return $this->rpc_error( $id, -32603, 'No static front page is set. Go to Settings > Reading and assign a static front page first.' );
            }
            $front_page         = get_post( $target_id );
            $target_description = '"' . ( $front_page ? $front_page->post_title : 'Front Page' ) . '" (Front Page, ID ' . $target_id . ')';
        }

        if ( ! $target_id ) {
            $missing = ( $type === 'header' ) ? 'Header Main' : 'Footer Main';
            return $this->rpc_error( $id, -32603, '"' . $missing . '" Themer layout not found on this site.' );
        }

        // Get BB layout data from template
        $builder_data = get_post_meta( $template_id, '_fl_builder_data', true );
        if ( empty( $builder_data ) ) {
            return $this->rpc_error( $id, -32603, '"' . $template_title . '" has no Beaver Builder content to apply.' );
        }

        // Write to target — both the published and draft copies
        update_post_meta( $target_id, '_fl_builder_data',  $builder_data );
        update_post_meta( $target_id, '_fl_builder_draft', $builder_data );

        $data_settings = get_post_meta( $template_id, '_fl_builder_data_settings', true );
        if ( $data_settings ) {
            update_post_meta( $target_id, '_fl_builder_data_settings',  $data_settings );
            update_post_meta( $target_id, '_fl_builder_draft_settings', $data_settings );
        }

        // Touch post modified date so BB picks up the change
        wp_update_post( array( 'ID' => $target_id ) );

        // Flush BB CSS cache
        if ( class_exists( 'FLBuilderModel' ) ) {
            FLBuilderModel::delete_asset_cache_for_all_posts();
        }

        return $this->tool_result( $id, array(
            'applied'   => true,
            'template'  => $template_title,
            'target'    => $target_description,
            'message'   => 'Layout replaced successfully. BB CSS cache flushed — changes are live.',
        ) );
    }

    // -------------------------------------------------------------------------
    // ACF Schema Tools — Post Types
    // -------------------------------------------------------------------------

    private function acf_schema_gate( $id, $group_key ) {
        $err = $this->leagueapps_gate( $id, $group_key );
        if ( $err ) return $err;
        if ( ! function_exists( 'acf_get_acf_post_types' ) ) {
            return $this->rpc_error( $id, -32603, 'ACF Pro 6.1+ is required for this tool' );
        }
        return null;
    }

    private function tool_acf_list_post_types( $id ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        $post_types = acf_get_acf_post_types();
        $result     = array();
        foreach ( $post_types as $pt ) {
            $result[] = array(
                'key'            => $pt['key'],
                'post_type'      => $pt['post_type'],
                'label'          => $pt['label'],
                'singular_label' => $pt['singular_label'],
                'description'    => $pt['description'],
                'public'         => $pt['public'],
                'hierarchical'   => $pt['hierarchical'],
                'supports'       => $pt['supports'],
                'taxonomies'     => $pt['taxonomies'],
            );
        }
        return $this->tool_result( $id, array( 'post_types' => $result ) );
    }

    private function tool_acf_create_post_type( $id, $args ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        if ( empty( $args['post_type'] ) || empty( $args['label'] ) || empty( $args['singular_label'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required arguments: post_type, label, singular_label' );
        }
        $data = array(
            'post_type'      => sanitize_key( $args['post_type'] ),
            'label'          => sanitize_text_field( $args['label'] ),
            'singular_label' => sanitize_text_field( $args['singular_label'] ),
            'description'    => isset( $args['description'] ) ? sanitize_textarea_field( $args['description'] ) : '',
            'public'         => isset( $args['public'] ) ? (bool) $args['public'] : true,
            'hierarchical'   => isset( $args['hierarchical'] ) ? (bool) $args['hierarchical'] : false,
            'supports'       => isset( $args['supports'] ) && is_array( $args['supports'] ) ? $args['supports'] : array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
            'taxonomies'     => isset( $args['taxonomies'] ) && is_array( $args['taxonomies'] ) ? $args['taxonomies'] : array(),
        );
        $result = acf_update_post_type( $data );
        if ( empty( $result['key'] ) ) {
            return $this->rpc_error( $id, -32603, 'Failed to create post type. The post_type slug may already be in use.' );
        }
        return $this->tool_result( $id, array( 'key' => $result['key'], 'post_type' => $result['post_type'], 'created' => true ) );
    }

    private function tool_acf_update_post_type( $id, $args ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        if ( empty( $args['key'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: key (use acf_list_post_types to get it)' );
        }
        $existing = acf_get_post_type( sanitize_text_field( $args['key'] ) );
        if ( empty( $existing ) ) {
            return $this->rpc_error( $id, -32602, 'Post type not found for key: ' . $args['key'] );
        }
        $allowed = array( 'label', 'singular_label', 'description', 'public', 'hierarchical', 'supports', 'taxonomies' );
        foreach ( $allowed as $field ) {
            if ( isset( $args[ $field ] ) ) {
                $existing[ $field ] = $args[ $field ];
            }
        }
        $result = acf_update_post_type( $existing );
        return $this->tool_result( $id, array( 'key' => $result['key'], 'post_type' => $result['post_type'], 'updated' => true ) );
    }

    private function tool_acf_delete_post_type( $id, $args ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        if ( empty( $args['key'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: key (use acf_list_post_types to get it)' );
        }
        $existing = acf_get_post_type( sanitize_text_field( $args['key'] ) );
        if ( empty( $existing ) ) {
            return $this->rpc_error( $id, -32602, 'Post type not found for key: ' . $args['key'] );
        }
        $post = acf_get_post_type_post( sanitize_text_field( $args['key'] ) );
        if ( empty( $post ) ) {
            return $this->rpc_error( $id, -32603, 'Could not locate the internal post for this ACF post type.' );
        }
        $deleted = acf_delete_post_type( $post->ID );
        return $this->tool_result( $id, array( 'key' => $args['key'], 'deleted' => (bool) $deleted ) );
    }

    // -------------------------------------------------------------------------
    // ACF Schema Tools — Taxonomies
    // -------------------------------------------------------------------------

    private function tool_acf_list_taxonomies( $id ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        $taxonomies = acf_get_acf_taxonomies();
        $result     = array();
        foreach ( $taxonomies as $tax ) {
            $result[] = array(
                'key'            => $tax['key'],
                'taxonomy'       => $tax['taxonomy'],
                'label'          => $tax['label'],
                'singular_label' => $tax['singular_label'],
                'description'    => $tax['description'],
                'public'         => $tax['public'],
                'hierarchical'   => $tax['hierarchical'],
                'object_type'    => $tax['object_type'],
            );
        }
        return $this->tool_result( $id, array( 'taxonomies' => $result ) );
    }

    private function tool_acf_create_taxonomy( $id, $args ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        if ( empty( $args['taxonomy'] ) || empty( $args['label'] ) || empty( $args['singular_label'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required arguments: taxonomy, label, singular_label' );
        }
        $data = array(
            'taxonomy'       => sanitize_key( $args['taxonomy'] ),
            'label'          => sanitize_text_field( $args['label'] ),
            'singular_label' => sanitize_text_field( $args['singular_label'] ),
            'description'    => isset( $args['description'] ) ? sanitize_textarea_field( $args['description'] ) : '',
            'public'         => isset( $args['public'] ) ? (bool) $args['public'] : true,
            'hierarchical'   => isset( $args['hierarchical'] ) ? (bool) $args['hierarchical'] : false,
            'object_type'    => isset( $args['object_type'] ) && is_array( $args['object_type'] ) ? $args['object_type'] : array(),
        );
        $result = acf_update_taxonomy( $data );
        if ( empty( $result['key'] ) ) {
            return $this->rpc_error( $id, -32603, 'Failed to create taxonomy. The taxonomy slug may already be in use.' );
        }
        return $this->tool_result( $id, array( 'key' => $result['key'], 'taxonomy' => $result['taxonomy'], 'created' => true ) );
    }

    private function tool_acf_update_taxonomy( $id, $args ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        if ( empty( $args['key'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: key (use acf_list_taxonomies to get it)' );
        }
        $existing = acf_get_taxonomy( sanitize_text_field( $args['key'] ) );
        if ( empty( $existing ) ) {
            return $this->rpc_error( $id, -32602, 'Taxonomy not found for key: ' . $args['key'] );
        }
        $allowed = array( 'label', 'singular_label', 'description', 'public', 'hierarchical', 'object_type' );
        foreach ( $allowed as $field ) {
            if ( isset( $args[ $field ] ) ) {
                $existing[ $field ] = $args[ $field ];
            }
        }
        $result = acf_update_taxonomy( $existing );
        return $this->tool_result( $id, array( 'key' => $result['key'], 'taxonomy' => $result['taxonomy'], 'updated' => true ) );
    }

    private function tool_acf_delete_taxonomy( $id, $args ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        if ( empty( $args['key'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: key (use acf_list_taxonomies to get it)' );
        }
        $existing = acf_get_taxonomy( sanitize_text_field( $args['key'] ) );
        if ( empty( $existing ) ) {
            return $this->rpc_error( $id, -32602, 'Taxonomy not found for key: ' . $args['key'] );
        }
        $post = acf_get_taxonomy_post( sanitize_text_field( $args['key'] ) );
        if ( empty( $post ) ) {
            return $this->rpc_error( $id, -32603, 'Could not locate the internal post for this ACF taxonomy.' );
        }
        $deleted = acf_delete_taxonomy( $post->ID );
        return $this->tool_result( $id, array( 'key' => $args['key'], 'deleted' => (bool) $deleted ) );
    }

    // -------------------------------------------------------------------------
    // ACF Schema Tools — Field Groups
    // -------------------------------------------------------------------------

    private function tool_acf_list_field_groups( $id ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        $groups = acf_get_field_groups();
        $result = array();
        foreach ( $groups as $group ) {
            $result[] = array(
                'key'      => $group['key'],
                'title'    => $group['title'],
                'active'   => $group['active'],
                'location' => $group['location'],
                'position' => $group['position'],
            );
        }
        return $this->tool_result( $id, array( 'field_groups' => $result ) );
    }

    private function tool_acf_get_field_group( $id, $args ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        if ( empty( $args['key'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: key' );
        }
        $group = acf_get_field_group( sanitize_text_field( $args['key'] ) );
        if ( empty( $group ) ) {
            return $this->rpc_error( $id, -32602, 'Field group not found for key: ' . $args['key'] );
        }
        $fields = acf_get_fields( $group );
        $fields = $fields ?: array();
        $simplified_fields = array();
        foreach ( $fields as $field ) {
            $simplified_fields[] = array(
                'key'      => $field['key'],
                'label'    => $field['label'],
                'name'     => $field['name'],
                'type'     => $field['type'],
                'required' => $field['required'],
            );
        }
        return $this->tool_result( $id, array(
            'key'      => $group['key'],
            'title'    => $group['title'],
            'active'   => $group['active'],
            'location' => $group['location'],
            'position' => $group['position'],
            'fields'   => $simplified_fields,
        ) );
    }

    private function tool_acf_create_field_group( $id, $args ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        if ( empty( $args['title'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: title' );
        }
        // Default location: show on all posts if not provided
        $location = isset( $args['location'] ) && is_array( $args['location'] )
            ? $args['location']
            : array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'post' ) ) );

        $group = array(
            'title'           => sanitize_text_field( $args['title'] ),
            'location'        => $location,
            'position'        => isset( $args['position'] ) ? sanitize_key( $args['position'] ) : 'normal',
            'label_placement' => isset( $args['label_placement'] ) ? sanitize_key( $args['label_placement'] ) : 'top',
            'active'          => isset( $args['active'] ) ? (bool) $args['active'] : true,
        );
        $result = acf_update_field_group( $group );
        if ( empty( $result['key'] ) ) {
            return $this->rpc_error( $id, -32603, 'Failed to create field group.' );
        }
        // Add fields if provided
        $fields_created = array();
        if ( ! empty( $args['fields'] ) && is_array( $args['fields'] ) ) {
            foreach ( $args['fields'] as $field_def ) {
                if ( empty( $field_def['label'] ) || empty( $field_def['name'] ) || empty( $field_def['type'] ) ) {
                    continue;
                }
                $field = array(
                    'label'    => sanitize_text_field( $field_def['label'] ),
                    'name'     => sanitize_key( $field_def['name'] ),
                    'type'     => sanitize_key( $field_def['type'] ),
                    'required' => ! empty( $field_def['required'] ) ? 1 : 0,
                    'parent'   => $result['key'],
                );
                if ( isset( $field_def['instructions'] ) ) {
                    $field['instructions'] = sanitize_text_field( $field_def['instructions'] );
                }
                if ( isset( $field_def['default_value'] ) ) {
                    $field['default_value'] = $field_def['default_value'];
                }
                $saved = acf_update_field( $field );
                if ( ! empty( $saved['key'] ) ) {
                    $fields_created[] = array( 'key' => $saved['key'], 'name' => $saved['name'], 'type' => $saved['type'] );
                }
            }
        }
        return $this->tool_result( $id, array(
            'key'            => $result['key'],
            'title'          => $result['title'],
            'created'        => true,
            'fields_created' => $fields_created,
        ) );
    }

    private function tool_acf_update_field_group( $id, $args ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        if ( empty( $args['key'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: key (use acf_list_field_groups to get it)' );
        }
        $existing = acf_get_field_group( sanitize_text_field( $args['key'] ) );
        if ( empty( $existing ) ) {
            return $this->rpc_error( $id, -32602, 'Field group not found for key: ' . $args['key'] );
        }
        $allowed = array( 'title', 'location', 'position', 'label_placement', 'active' );
        foreach ( $allowed as $field ) {
            if ( isset( $args[ $field ] ) ) {
                $existing[ $field ] = $args[ $field ];
            }
        }
        $result = acf_update_field_group( $existing );
        return $this->tool_result( $id, array( 'key' => $result['key'], 'title' => $result['title'], 'updated' => true ) );
    }

    private function tool_acf_delete_field_group( $id, $args ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        if ( empty( $args['key'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: key (use acf_list_field_groups to get it)' );
        }
        $group = acf_get_field_group( sanitize_text_field( $args['key'] ) );
        if ( empty( $group ) ) {
            return $this->rpc_error( $id, -32602, 'Field group not found for key: ' . $args['key'] );
        }
        $post = acf_get_field_group_post( sanitize_text_field( $args['key'] ) );
        if ( empty( $post ) ) {
            return $this->rpc_error( $id, -32603, 'Could not locate internal post for this field group.' );
        }
        $deleted = acf_delete_field_group( $post->ID );
        return $this->tool_result( $id, array( 'key' => $args['key'], 'deleted' => (bool) $deleted ) );
    }

    // -------------------------------------------------------------------------
    // ACF Schema Tools — Options Pages
    // -------------------------------------------------------------------------

    private function tool_acf_list_options_pages( $id ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        if ( ! function_exists( 'acf_get_ui_options_pages' ) ) {
            return $this->rpc_error( $id, -32603, 'ACF Pro 6.2+ is required for Options Pages' );
        }
        $pages  = acf_get_ui_options_pages();
        $result = array();
        foreach ( $pages as $page ) {
            $result[] = array(
                'key'         => $page['key'],
                'title'       => $page['title'],
                'menu_slug'   => $page['menu_slug'],
                'menu_title'  => $page['menu_title'],
                'parent_slug' => $page['parent_slug'],
                'capability'  => $page['capability'],
            );
        }
        return $this->tool_result( $id, array( 'options_pages' => $result ) );
    }

    private function tool_acf_create_options_page( $id, $args ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        if ( ! function_exists( 'acf_update_ui_options_page' ) ) {
            return $this->rpc_error( $id, -32603, 'ACF Pro 6.2+ is required for Options Pages' );
        }
        if ( empty( $args['title'] ) || empty( $args['menu_slug'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required arguments: title, menu_slug' );
        }
        $data = array(
            'title'       => sanitize_text_field( $args['title'] ),
            'menu_slug'   => sanitize_key( $args['menu_slug'] ),
            'menu_title'  => isset( $args['menu_title'] ) ? sanitize_text_field( $args['menu_title'] ) : '',
            'parent_slug' => isset( $args['parent_slug'] ) ? sanitize_text_field( $args['parent_slug'] ) : '',
            'capability'  => isset( $args['capability'] ) ? sanitize_key( $args['capability'] ) : 'edit_posts',
            'redirect'    => isset( $args['redirect'] ) ? (bool) $args['redirect'] : false,
            'description' => isset( $args['description'] ) ? sanitize_textarea_field( $args['description'] ) : '',
        );
        $result = acf_update_ui_options_page( $data );
        if ( empty( $result['key'] ) ) {
            return $this->rpc_error( $id, -32603, 'Failed to create options page. The menu_slug may already be in use.' );
        }
        return $this->tool_result( $id, array( 'key' => $result['key'], 'menu_slug' => $result['menu_slug'], 'created' => true ) );
    }

    private function tool_acf_delete_options_page( $id, $args ) {
        $err = $this->acf_schema_gate( $id, 'mcp_acf_schema_enabled' );
        if ( $err ) return $err;

        if ( ! function_exists( 'acf_delete_ui_options_page' ) ) {
            return $this->rpc_error( $id, -32603, 'ACF Pro 6.2+ is required for Options Pages' );
        }
        if ( empty( $args['key'] ) ) {
            return $this->rpc_error( $id, -32602, 'Missing required argument: key (use acf_list_options_pages to get it)' );
        }
        $page = acf_get_ui_options_page( sanitize_text_field( $args['key'] ) );
        if ( empty( $page ) ) {
            return $this->rpc_error( $id, -32602, 'Options page not found for key: ' . $args['key'] );
        }
        $post = acf_get_ui_options_page_post( sanitize_text_field( $args['key'] ) );
        if ( empty( $post ) ) {
            return $this->rpc_error( $id, -32603, 'Could not locate internal post for this options page.' );
        }
        $deleted = acf_delete_ui_options_page( $post->ID );
        return $this->tool_result( $id, array( 'key' => $args['key'], 'deleted' => (bool) $deleted ) );
    }

    // -------------------------------------------------------------------------
    // Tool Schema
    // -------------------------------------------------------------------------

    private function get_tools_schema() {
        return array(
            // Posts & Pages
            array(
                'name'        => 'list_posts',
                'description' => 'List WordPress posts, pages, or custom post types. Returns ID, title, status, type, URL, and dates.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'post_type' => array( 'type' => 'string',  'description' => 'Post type slug: post, page, events, athletes, staff, teams, or any CPT. Default: post' ),
                        'per_page'  => array( 'type' => 'integer', 'description' => 'Number of results (max 100). Default: 20' ),
                        'search'    => array( 'type' => 'string',  'description' => 'Keyword search' ),
                        'status'    => array( 'type' => 'string',  'description' => 'Filter by status: publish, draft, private, any. Default: any' ),
                    ),
                ),
            ),
            array(
                'name'        => 'get_post',
                'description' => 'Get the full content of a post, page, or CPT entry by ID.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id' ),
                    'properties' => array(
                        'id' => array( 'type' => 'integer', 'description' => 'WordPress post ID' ),
                    ),
                ),
            ),
            array(
                'name'        => 'create_post',
                'description' => 'Create a new post, page, or CPT entry. Optionally assign taxonomy terms in the same call.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'title' ),
                    'properties' => array(
                        'title'     => array( 'type' => 'string', 'description' => 'Title' ),
                        'content'   => array( 'type' => 'string', 'description' => 'Content (HTML allowed)' ),
                        'excerpt'   => array( 'type' => 'string', 'description' => 'Excerpt' ),
                        'status'    => array( 'type' => 'string', 'description' => 'draft, publish, private. Default: draft' ),
                        'post_type' => array( 'type' => 'string', 'description' => 'Post type slug. Default: post' ),
                        'terms'     => array( 'type' => 'object', 'description' => 'Taxonomy terms to assign. Keys are taxonomy slugs, values are arrays of term IDs or slugs. Example: {"category":[1,3],"event_category":["basketball"]}' ),
                    ),
                ),
            ),
            array(
                'name'        => 'update_post',
                'description' => 'Update an existing post, page, or CPT entry. Only provided fields are changed. Optionally assign taxonomy terms.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id' ),
                    'properties' => array(
                        'id'      => array( 'type' => 'integer', 'description' => 'Post ID' ),
                        'title'   => array( 'type' => 'string',  'description' => 'New title' ),
                        'content' => array( 'type' => 'string',  'description' => 'New content (HTML allowed)' ),
                        'excerpt' => array( 'type' => 'string',  'description' => 'New excerpt' ),
                        'status'  => array( 'type' => 'string',  'description' => 'New status: draft, publish, private' ),
                        'terms'   => array( 'type' => 'object',  'description' => 'Taxonomy terms to assign. Keys are taxonomy slugs, values are arrays of term IDs or slugs. Example: {"category":[1,3],"event_category":["basketball"]}' ),
                    ),
                ),
            ),
            array(
                'name'        => 'delete_post',
                'description' => 'Delete (or trash) a post, page, or CPT entry.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id' ),
                    'properties' => array(
                        'id'    => array( 'type' => 'integer', 'description' => 'Post ID' ),
                        'force' => array( 'type' => 'boolean', 'description' => 'true = permanent delete, false = trash (default)' ),
                    ),
                ),
            ),
            // CPT Discovery
            array(
                'name'        => 'list_post_types',
                'description' => 'List all registered public post types on this WordPress site — useful to discover available CPTs like events, athletes, staff, teams.',
                'inputSchema' => array( 'type' => 'object', 'properties' => new stdClass() ),
            ),
            // Taxonomies
            array(
                'name'        => 'list_taxonomies',
                'description' => 'List all registered public taxonomies and which post types they belong to.',
                'inputSchema' => array( 'type' => 'object', 'properties' => new stdClass() ),
            ),
            array(
                'name'        => 'list_terms',
                'description' => 'List terms in a taxonomy (e.g. athlete-category, staff-category, team-category).',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'taxonomy' ),
                    'properties' => array(
                        'taxonomy'   => array( 'type' => 'string',  'description' => 'Taxonomy slug' ),
                        'search'     => array( 'type' => 'string',  'description' => 'Search keyword' ),
                        'per_page'   => array( 'type' => 'integer', 'description' => 'Max results (default 100, max 200)' ),
                        'hide_empty' => array( 'type' => 'boolean', 'description' => 'Exclude terms with no posts. Default: false' ),
                    ),
                ),
            ),
            array(
                'name'        => 'get_term',
                'description' => 'Get a single taxonomy term by ID or slug.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'taxonomy' ),
                    'properties' => array(
                        'taxonomy' => array( 'type' => 'string',  'description' => 'Taxonomy slug' ),
                        'id'       => array( 'type' => 'integer', 'description' => 'Term ID (use id or slug)' ),
                        'slug'     => array( 'type' => 'string',  'description' => 'Term slug (use id or slug)' ),
                    ),
                ),
            ),
            array(
                'name'        => 'create_term',
                'description' => 'Create a new term in a taxonomy.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'taxonomy', 'name' ),
                    'properties' => array(
                        'taxonomy'    => array( 'type' => 'string', 'description' => 'Taxonomy slug' ),
                        'name'        => array( 'type' => 'string', 'description' => 'Term name' ),
                        'slug'        => array( 'type' => 'string', 'description' => 'Term slug (optional, auto-generated from name)' ),
                        'description' => array( 'type' => 'string', 'description' => 'Term description' ),
                        'parent'      => array( 'type' => 'integer','description' => 'Parent term ID (for hierarchical taxonomies)' ),
                    ),
                ),
            ),
            array(
                'name'        => 'update_term',
                'description' => 'Update an existing taxonomy term.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id', 'taxonomy' ),
                    'properties' => array(
                        'id'          => array( 'type' => 'integer', 'description' => 'Term ID' ),
                        'taxonomy'    => array( 'type' => 'string',  'description' => 'Taxonomy slug' ),
                        'name'        => array( 'type' => 'string',  'description' => 'New name' ),
                        'slug'        => array( 'type' => 'string',  'description' => 'New slug' ),
                        'description' => array( 'type' => 'string',  'description' => 'New description' ),
                    ),
                ),
            ),
            array(
                'name'        => 'set_post_terms',
                'description' => 'Assign or replace taxonomy terms on a post. Use append=true to add without removing existing terms.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id', 'taxonomy', 'terms' ),
                    'properties' => array(
                        'id'       => array( 'type' => 'integer', 'description' => 'Post ID' ),
                        'taxonomy' => array( 'type' => 'string',  'description' => 'Taxonomy slug (e.g. category, event_category, athlete-category)' ),
                        'terms'    => array( 'type' => 'array',   'description' => 'Array of term IDs (integers) or slugs (strings)' ),
                        'append'   => array( 'type' => 'boolean', 'description' => 'true = add to existing terms, false = replace all (default: false)' ),
                    ),
                ),
            ),
            array(
                'name'        => 'delete_term',
                'description' => 'Delete a taxonomy term.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id', 'taxonomy' ),
                    'properties' => array(
                        'id'       => array( 'type' => 'integer', 'description' => 'Term ID' ),
                        'taxonomy' => array( 'type' => 'string',  'description' => 'Taxonomy slug' ),
                    ),
                ),
            ),
            // ACF / Custom Fields
            array(
                'name'        => 'get_post_fields',
                'description' => 'Get all ACF (or custom meta) fields for a post, page, or CPT entry. Uses get_fields() if ACF is active, otherwise returns post meta.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id' ),
                    'properties' => array(
                        'id' => array( 'type' => 'integer', 'description' => 'Post ID' ),
                    ),
                ),
            ),
            array(
                'name'        => 'update_post_fields',
                'description' => 'Update ACF (or custom meta) fields on a post. Pass a fields object with field_name → value pairs.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'id', 'fields' ),
                    'properties' => array(
                        'id'     => array( 'type' => 'integer', 'description' => 'Post ID' ),
                        'fields' => array( 'type' => 'object',  'description' => 'Object of field_name → value pairs to update' ),
                    ),
                ),
            ),
            // Partner Settings
            array(
                'name'        => 'get_partner_settings',
                'description' => 'Read all ACF Partner Settings (logo, email, phone, address, and social links). Requires ACF.',
                'inputSchema' => array( 'type' => 'object', 'properties' => new stdClass() ),
            ),
            array(
                'name'        => 'update_partner_settings',
                'description' => 'Update one or more ACF Partner Settings fields. Accepts any combination of: partner_logo (attachment ID), partner_email, partner_phone, partner_address, partner_fb, partner_instagram, partner_x, partner_youtube, partner_linkedin, partner_tiktok, partner_leagueapps. Requires manage_options.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'fields' ),
                    'properties' => array(
                        'fields' => array(
                            'type'        => 'object',
                            'description' => 'Object of field_name → value pairs. Social/URL fields expect full URLs. partner_logo expects a Media Library attachment ID.',
                            'properties'  => array(
                                'partner_logo'       => array( 'type' => 'integer', 'description' => 'Media Library attachment ID for the partner logo' ),
                                'partner_email'      => array( 'type' => 'string',  'description' => 'Partner contact email address' ),
                                'partner_phone'      => array( 'type' => 'string',  'description' => 'Partner phone number' ),
                                'partner_address'    => array( 'type' => 'string',  'description' => 'Partner mailing address' ),
                                'partner_fb'         => array( 'type' => 'string',  'description' => 'Facebook page URL' ),
                                'partner_instagram'  => array( 'type' => 'string',  'description' => 'Instagram profile URL' ),
                                'partner_x'          => array( 'type' => 'string',  'description' => 'X (Twitter) profile URL' ),
                                'partner_youtube'    => array( 'type' => 'string',  'description' => 'YouTube channel URL' ),
                                'partner_linkedin'   => array( 'type' => 'string',  'description' => 'LinkedIn page URL' ),
                                'partner_tiktok'     => array( 'type' => 'string',  'description' => 'TikTok profile URL' ),
                                'partner_leagueapps' => array( 'type' => 'string',  'description' => 'LeagueApps site URL' ),
                            ),
                        ),
                    ),
                ),
            ),
            // Toolkit Settings
            array(
                'name'        => 'get_toolkit_settings',
                'description' => 'Read current DS Toolkit settings — feature toggles, column counts, template IDs, etc. Requires manage_options.',
                'inputSchema' => array( 'type' => 'object', 'properties' => new stdClass() ),
            ),
            array(
                'name'        => 'update_toolkit_settings',
                'description' => 'Update DS Toolkit settings by passing a key-value settings object. Requires manage_options.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'settings' ),
                    'properties' => array(
                        'settings' => array(
                            'type'        => 'object',
                            'description' => 'Key-value map of settings to update.',
                        ),
                    ),
                ),
            ),
            // ACF Schema — Post Types
            array(
                'name'        => 'acf_list_post_types',
                'description' => 'List all custom post types registered via ACF Pro. Returns key, slug, labels, and settings. Requires manage_options + @leagueapps.com.',
                'inputSchema' => array( 'type' => 'object', 'properties' => new stdClass() ),
            ),
            array(
                'name'        => 'acf_create_post_type',
                'description' => 'Create a new custom post type via ACF Pro. Requires manage_options + @leagueapps.com.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'post_type', 'label', 'singular_label' ),
                    'properties' => array(
                        'post_type'      => array( 'type' => 'string',  'description' => 'Post type slug — lowercase, max 20 chars, alphanumeric/underscores/dashes only (e.g. "athletes")' ),
                        'label'          => array( 'type' => 'string',  'description' => 'Plural label (e.g. "Athletes")' ),
                        'singular_label' => array( 'type' => 'string',  'description' => 'Singular label (e.g. "Athlete")' ),
                        'description'    => array( 'type' => 'string',  'description' => 'Optional description' ),
                        'public'         => array( 'type' => 'boolean', 'description' => 'Whether the post type is publicly accessible. Default: true' ),
                        'hierarchical'   => array( 'type' => 'boolean', 'description' => 'Whether the post type is hierarchical like pages. Default: false' ),
                        'supports'       => array( 'type' => 'array',   'description' => 'Features supported: title, editor, thumbnail, excerpt, custom-fields, revisions, etc. Default: [title, editor, thumbnail, custom-fields]' ),
                        'taxonomies'     => array( 'type' => 'array',   'description' => 'Array of taxonomy slugs to associate with this post type' ),
                    ),
                ),
            ),
            array(
                'name'        => 'acf_update_post_type',
                'description' => 'Update an existing ACF Pro post type by its ACF key. Only provided fields are changed. Requires manage_options + @leagueapps.com.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'key' ),
                    'properties' => array(
                        'key'            => array( 'type' => 'string',  'description' => 'ACF post type key — get from acf_list_post_types' ),
                        'label'          => array( 'type' => 'string',  'description' => 'New plural label' ),
                        'singular_label' => array( 'type' => 'string',  'description' => 'New singular label' ),
                        'description'    => array( 'type' => 'string',  'description' => 'New description' ),
                        'public'         => array( 'type' => 'boolean', 'description' => 'Public visibility' ),
                        'hierarchical'   => array( 'type' => 'boolean', 'description' => 'Hierarchical structure' ),
                        'supports'       => array( 'type' => 'array',   'description' => 'Supported features array' ),
                        'taxonomies'     => array( 'type' => 'array',   'description' => 'Associated taxonomy slugs' ),
                    ),
                ),
            ),
            array(
                'name'        => 'acf_delete_post_type',
                'description' => 'Permanently delete an ACF Pro post type by its ACF key. WARNING: This is irreversible. Requires manage_options + @leagueapps.com.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'key' ),
                    'properties' => array(
                        'key' => array( 'type' => 'string', 'description' => 'ACF post type key — get from acf_list_post_types' ),
                    ),
                ),
            ),
            // ACF Schema — Taxonomies
            array(
                'name'        => 'acf_list_taxonomies',
                'description' => 'List all taxonomies registered via ACF Pro. Returns key, slug, labels, and associated post types. Requires manage_options + @leagueapps.com.',
                'inputSchema' => array( 'type' => 'object', 'properties' => new stdClass() ),
            ),
            array(
                'name'        => 'acf_create_taxonomy',
                'description' => 'Create a new taxonomy via ACF Pro. Requires manage_options + @leagueapps.com.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'taxonomy', 'label', 'singular_label' ),
                    'properties' => array(
                        'taxonomy'       => array( 'type' => 'string',  'description' => 'Taxonomy slug — lowercase, max 32 chars, alphanumeric/underscores/dashes (e.g. "athlete-category")' ),
                        'label'          => array( 'type' => 'string',  'description' => 'Plural label (e.g. "Athlete Categories")' ),
                        'singular_label' => array( 'type' => 'string',  'description' => 'Singular label (e.g. "Athlete Category")' ),
                        'description'    => array( 'type' => 'string',  'description' => 'Optional description' ),
                        'public'         => array( 'type' => 'boolean', 'description' => 'Whether publicly accessible. Default: true' ),
                        'hierarchical'   => array( 'type' => 'boolean', 'description' => 'Whether hierarchical like categories (true) or flat like tags (false). Default: false' ),
                        'object_type'    => array( 'type' => 'array',   'description' => 'Post type slugs to associate this taxonomy with (e.g. ["athletes", "teams"])' ),
                    ),
                ),
            ),
            array(
                'name'        => 'acf_update_taxonomy',
                'description' => 'Update an existing ACF Pro taxonomy by its ACF key. Only provided fields are changed. Requires manage_options + @leagueapps.com.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'key' ),
                    'properties' => array(
                        'key'            => array( 'type' => 'string',  'description' => 'ACF taxonomy key — get from acf_list_taxonomies' ),
                        'label'          => array( 'type' => 'string',  'description' => 'New plural label' ),
                        'singular_label' => array( 'type' => 'string',  'description' => 'New singular label' ),
                        'description'    => array( 'type' => 'string',  'description' => 'New description' ),
                        'public'         => array( 'type' => 'boolean', 'description' => 'Public visibility' ),
                        'hierarchical'   => array( 'type' => 'boolean', 'description' => 'Hierarchical structure' ),
                        'object_type'    => array( 'type' => 'array',   'description' => 'Associated post type slugs' ),
                    ),
                ),
            ),
            array(
                'name'        => 'acf_delete_taxonomy',
                'description' => 'Permanently delete an ACF Pro taxonomy by its ACF key. WARNING: This is irreversible. Requires manage_options + @leagueapps.com.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'key' ),
                    'properties' => array(
                        'key' => array( 'type' => 'string', 'description' => 'ACF taxonomy key — get from acf_list_taxonomies' ),
                    ),
                ),
            ),
            // ACF Schema — Field Groups
            array(
                'name'        => 'acf_list_field_groups',
                'description' => 'List all ACF field groups. Returns key, title, active status, and location rules. Requires manage_options + @leagueapps.com.',
                'inputSchema' => array( 'type' => 'object', 'properties' => new stdClass() ),
            ),
            array(
                'name'        => 'acf_get_field_group',
                'description' => 'Get a single ACF field group by key, including its fields (key, label, name, type, required). Requires manage_options + @leagueapps.com.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'key' ),
                    'properties' => array(
                        'key' => array( 'type' => 'string', 'description' => 'ACF field group key — get from acf_list_field_groups' ),
                    ),
                ),
            ),
            array(
                'name'        => 'acf_create_field_group',
                'description' => 'Create a new ACF field group, optionally with fields. Requires manage_options + @leagueapps.com.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'title' ),
                    'properties' => array(
                        'title'           => array( 'type' => 'string', 'description' => 'Field group title' ),
                        'location'        => array( 'type' => 'array',  'description' => 'ACF location rules array. Defaults to show on all posts if omitted.' ),
                        'position'        => array( 'type' => 'string', 'description' => 'Meta box position: normal, side, acf_after_title. Default: normal' ),
                        'label_placement' => array( 'type' => 'string', 'description' => 'Label placement: top or left. Default: top' ),
                        'active'          => array( 'type' => 'boolean', 'description' => 'Whether the group is active. Default: true' ),
                        'fields'          => array(
                            'type'        => 'array',
                            'description' => 'Optional fields to create. Each field: {label, name, type, required, instructions, default_value}',
                            'items'       => array(
                                'type'       => 'object',
                                'properties' => array(
                                    'label'         => array( 'type' => 'string' ),
                                    'name'          => array( 'type' => 'string' ),
                                    'type'          => array( 'type' => 'string', 'description' => 'ACF field type: text, textarea, number, email, url, image, file, select, checkbox, radio, true_false, relationship, etc.' ),
                                    'required'      => array( 'type' => 'boolean' ),
                                    'instructions'  => array( 'type' => 'string' ),
                                    'default_value' => array( 'type' => 'string' ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            array(
                'name'        => 'acf_update_field_group',
                'description' => 'Update an existing ACF field group. Merges only provided fields. Requires manage_options + @leagueapps.com.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'key' ),
                    'properties' => array(
                        'key'             => array( 'type' => 'string',  'description' => 'ACF field group key — get from acf_list_field_groups' ),
                        'title'           => array( 'type' => 'string',  'description' => 'New title' ),
                        'location'        => array( 'type' => 'array',   'description' => 'New location rules' ),
                        'position'        => array( 'type' => 'string',  'description' => 'New position: normal, side, acf_after_title' ),
                        'label_placement' => array( 'type' => 'string',  'description' => 'New label placement: top or left' ),
                        'active'          => array( 'type' => 'boolean', 'description' => 'Enable or disable the group' ),
                    ),
                ),
            ),
            array(
                'name'        => 'acf_delete_field_group',
                'description' => 'Permanently delete an ACF field group and all its fields. Irreversible. Requires manage_options + @leagueapps.com.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'key' ),
                    'properties' => array(
                        'key' => array( 'type' => 'string', 'description' => 'ACF field group key — get from acf_list_field_groups' ),
                    ),
                ),
            ),
            // ACF Schema — Options Pages
            array(
                'name'        => 'acf_list_options_pages',
                'description' => 'List all ACF Pro options pages. Returns key, title, menu_slug, and parent_slug. Requires ACF Pro 6.2+, manage_options + @leagueapps.com.',
                'inputSchema' => array( 'type' => 'object', 'properties' => new stdClass() ),
            ),
            array(
                'name'        => 'acf_create_options_page',
                'description' => 'Create a new ACF Pro options page. Requires ACF Pro 6.2+, manage_options + @leagueapps.com.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'title', 'menu_slug' ),
                    'properties' => array(
                        'title'       => array( 'type' => 'string',  'description' => 'Options page title' ),
                        'menu_slug'   => array( 'type' => 'string',  'description' => 'Unique menu slug (URL-safe)' ),
                        'menu_title'  => array( 'type' => 'string',  'description' => 'Admin menu label. Defaults to title if omitted.' ),
                        'parent_slug' => array( 'type' => 'string',  'description' => 'Parent admin menu slug (e.g. "edit.php" to nest under Posts)' ),
                        'capability'  => array( 'type' => 'string',  'description' => 'Required capability. Default: edit_posts' ),
                        'redirect'    => array( 'type' => 'boolean', 'description' => 'Redirect to first child page. Default: false' ),
                        'description' => array( 'type' => 'string',  'description' => 'Optional description' ),
                    ),
                ),
            ),
            array(
                'name'        => 'acf_delete_options_page',
                'description' => 'Permanently delete an ACF Pro options page. Irreversible. Requires ACF Pro 6.2+, manage_options + @leagueapps.com.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'key' ),
                    'properties' => array(
                        'key' => array( 'type' => 'string', 'description' => 'Options page key — get from acf_list_options_pages' ),
                    ),
                ),
            ),
            // Beaver Builder
            array(
                'name'        => 'get_bb_global_colors',
                'description' => 'Get all Beaver Builder Global Style colors. Returns a label → hex map (e.g. "Primary": "e63946"). Requires manage_options.',
                'inputSchema' => array( 'type' => 'object', 'properties' => new stdClass() ),
            ),
            array(
                'name'        => 'update_bb_global_colors',
                'description' => 'Update one or more Beaver Builder Global Style colors by label. Flushes BB CSS cache automatically. Requires manage_options.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'colors' ),
                    'properties' => array(
                        'colors' => array(
                            'type'        => 'object',
                            'description' => 'Object of label → hex pairs. Label must match exactly (e.g. "Primary", "Accent", "Headings"). Hex with or without #. Example: {"Primary":"e63946","Accent":"457b9d"}',
                        ),
                    ),
                ),
            ),
            array(
                'name'        => 'bb_list_layout_templates',
                'description' => 'List available DS Launchpad layout templates by type (header/footer/home). Returns template titles and style numbers.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'properties' => array(
                        'type' => array( 'type' => 'string', 'description' => 'Filter by type: header, footer, or home. Omit to return all types.' ),
                    ),
                ),
            ),
            array(
                'name'        => 'bb_apply_layout_template',
                'description' => 'Replace the Beaver Builder content of "Header Main", "Footer Main" (Themer layouts), or the site front page with a DS Launchpad layout template. WARNING: replaces current content — requires confirm: true.',
                'inputSchema' => array(
                    'type'       => 'object',
                    'required'   => array( 'type', 'style_number', 'confirm' ),
                    'properties' => array(
                        'type'         => array( 'type' => 'string',  'description' => 'Target to replace: header (→ Header Main themer layout), footer (→ Footer Main themer layout), or home (→ site front page).' ),
                        'style_number' => array( 'type' => 'integer', 'description' => 'Template number to apply. Header: 1–5, Footer: 1–3, Home: 1–6. Use bb_list_layout_templates to see what is available.' ),
                        'confirm'      => array( 'type' => 'boolean', 'description' => 'Must be true to proceed. Confirms the user understands the current layout content will be replaced.' ),
                    ),
                ),
            ),
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function group_disabled_error( $id, $group_key ) {
        $labels = array(
            'mcp_posts_pages_enabled'      => 'Posts & Pages',
            'mcp_cpt_enabled'              => 'Custom Post Types',
            'mcp_taxonomies_enabled'       => 'Taxonomies',
            'mcp_acf_enabled'              => 'ACF / Custom Fields',
            'mcp_toolkit_settings_enabled' => 'Toolkit Settings',
            'mcp_bb_enabled'               => 'Beaver Builder',
            'mcp_acf_schema_enabled'       => 'ACF Schema (Post Types & Taxonomies)',
        );
        $label = isset( $labels[ $group_key ] ) ? $labels[ $group_key ] : $group_key;
        return $this->rpc_error( $id, -32603, '"' . $label . '" tools are disabled. Enable them in DS Toolkit → Settings → MCP tab.' );
    }

    private function rpc_result( $id, $result ) {
        return new WP_REST_Response( array(
            'jsonrpc' => '2.0',
            'id'      => $id,
            'result'  => $result,
        ), 200 );
    }

    private function tool_result( $id, $data ) {
        return $this->rpc_result( $id, array(
            'content' => array(
                array(
                    'type' => 'text',
                    'text' => wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ),
                ),
            ),
        ) );
    }

    private function rpc_error( $id, $code, $message ) {
        return new WP_REST_Response( array(
            'jsonrpc' => '2.0',
            'id'      => $id,
            'error'   => array( 'code' => $code, 'message' => $message ),
        ), 200 );
    }
}
